<?php
// ============================================================
// FreeHub.Live — Background Transcoding Engine (HLS & Thumbnail)
// ============================================================

// CLI execution check
if (php_sapi_name() !== 'cli') {
    die('This script can only be run via CLI.');
}

// Runtime limits
ini_set('max_execution_time', 0);
ini_set('memory_limit', '1024M');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Parse CLI arguments
$id = null;
$type = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, 'id=')) {
        $id = (int)substr($arg, 3);
    }
    if (str_starts_with($arg, 'type=')) {
        $type = substr($arg, 5);
    }
}

if (!$id || !$type || !in_array($type, ['video', 'reel'], true)) {
    die("Usage: php -f transcode.php -- id=<int> type=<video|reel>\n");
}

// Fetch record
$table = ($type === 'reel') ? 'reels' : 'videos';
$record = db_fetch("SELECT * FROM `$table` WHERE id = ?", [$id]);
if (!$record) {
    die("Record ID $id not found in $table table.\n");
}

$video_url = $record['video_url'];
$is_external = str_starts_with($video_url, 'http://') || str_starts_with($video_url, 'https://');
if ($is_external) {
    // Cannot transcode external video, mark published immediately
    db_update($table, ['status' => 'published'], 'id = ?', [$id]);
    die("Skipping external video url.\n");
}

// Determine path
$base_path = ($type === 'reel') ? REEL_PATH : VIDEO_PATH;
$input_path = $base_path . $video_url;

if (!is_file($input_path)) {
    // Missing input file, set status as failed
    db_update($table, ['status' => 'failed'], 'id = ?', [$id]);
    die("Input file not found: $input_path\n");
}

// ── 1. Probe video metadata using ffprobe ──
$escaped_input = escapeshellarg($input_path);
$probe_cmd = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height,duration -of json $escaped_input 2>&1";
$probe_out = @shell_exec($probe_cmd);

$width = 1280;
$height = 720;
$duration = 0;

if ($probe_out) {
    $probe_data = json_decode($probe_out, true);
    if (isset($probe_data['streams'][0])) {
        $width = (int)($probe_data['streams'][0]['width'] ?? 1280);
        $height = (int)($probe_data['streams'][0]['height'] ?? 720);
        $duration = (int)round((float)($probe_data['streams'][0]['duration'] ?? 0));
    }
}

// Probe if video has audio stream
$probe_audio_cmd = "ffprobe -v error -select_streams a -show_entries stream=index -of json $escaped_input 2>&1";
$probe_audio_out = @shell_exec($probe_audio_cmd);
$has_audio = false;
if ($probe_audio_out) {
    $probe_audio_data = json_decode($probe_audio_out, true);
    if (!empty($probe_audio_data['streams'])) {
        $has_audio = true;
    }
}

// Ensure duration is non-zero
if ($duration <= 0) {
    $duration = ($type === 'reel') ? 15 : 60; // fallback default
}

// ── 2. Extract poster thumbnail at 10% duration ──
$thumb_sec = $duration > 10 ? (int)($duration * 0.1) : 1;
$thumb_filename = bin2hex(random_bytes(16)) . '.jpg';
$thumb_path = THUMB_PATH . $thumb_filename;

if (!is_dir(THUMB_PATH)) {
    @mkdir(THUMB_PATH, 0755, true);
}

$escaped_thumb = escapeshellarg($thumb_path);
$thumb_cmd = "ffmpeg -y -ss $thumb_sec -i $escaped_input -vframes 1 -f image2 $escaped_thumb 2>&1";
@shell_exec($thumb_cmd);

// ── 3. HLS Transcoding ──
$hls_dir = UPLOAD_PATH . 'hls/' . $type . '/' . $id . '/';
if (!is_dir($hls_dir)) {
    @mkdir($hls_dir, 0755, true);
}

$is_vertical = ($height > $width);
$resolutions = [];

if ($is_vertical) {
    // Portrait/Vertical quality steps (Reels)
    $resolutions['360p'] = ['w' => 360, 'h' => 640, 'bv' => '400k', 'maxv' => '450k', 'buf' => '800k', 'ba' => '64k'];
    if ($height >= 960) {
        $resolutions['540p'] = ['w' => 540, 'h' => 960, 'bv' => '800k', 'maxv' => '900k', 'buf' => '1600k', 'ba' => '96k'];
    }
    if ($height >= 1280) {
        $resolutions['720p'] = ['w' => 720, 'h' => 1280, 'bv' => '1500k', 'maxv' => '1700k', 'buf' => '3000k', 'ba' => '128k'];
    }
} else {
    // Landscape/Horizontal quality steps (Videos)
    $resolutions['360p'] = ['w' => 640, 'h' => 360, 'bv' => '400k', 'maxv' => '450k', 'buf' => '800k', 'ba' => '64k'];
    if ($width >= 960) {
        $resolutions['540p'] = ['w' => 960, 'h' => 540, 'bv' => '800k', 'maxv' => '900k', 'buf' => '1600k', 'ba' => '96k'];
    }
    if ($width >= 1280) {
        $resolutions['720p'] = ['w' => 1280, 'h' => 720, 'bv' => '1500k', 'maxv' => '1700k', 'buf' => '3000k', 'ba' => '128k'];
    }
}

// Fallback to always transcode at least one stream
if (empty($resolutions)) {
    if ($is_vertical) {
        $resolutions['360p'] = ['w' => 360, 'h' => 640, 'bv' => '400k', 'maxv' => '450k', 'buf' => '800k', 'ba' => '64k'];
    } else {
        $resolutions['360p'] = ['w' => 640, 'h' => 360, 'bv' => '400k', 'maxv' => '450k', 'buf' => '800k', 'ba' => '64k'];
    }
}

// Build FFmpeg complex scale filters
$filter = 'split=' . count($resolutions);
$v_inputs = [];
$idx = 0;
foreach ($resolutions as $key => $res) {
    $filter .= "[v$idx]";
    $v_inputs[] = "[v$idx]";
    $idx++;
}
$filter .= ';';
$idx = 0;
$scale_filters = [];
foreach ($resolutions as $key => $res) {
    $scale_filters[] = $v_inputs[$idx] . "scale=w={$res['w']}:h={$res['h']}[v{$idx}out]";
    $idx++;
}
$filter .= implode(';', $scale_filters);

// Build multi-output FFmpeg command
$args = [];
$args[] = 'ffmpeg -y -i ' . $escaped_input;
$args[] = '-filter_complex "' . $filter . '"';

$idx = 0;
$var_stream_map = [];
foreach ($resolutions as $key => $res) {
    $args[] = "-map \"[v{$idx}out]\" -c:v:$idx libx264 -b:v:$idx {$res['bv']} -maxrate:v:$idx {$res['maxv']} -bufsize:v:$idx {$res['buf']}";
    if ($has_audio) {
        $args[] = "-map a:0 -c:a:$idx aac -b:a:$idx {$res['ba']}";
        $var_stream_map[] = "v:$idx,a:$idx";
    } else {
        $var_stream_map[] = "v:$idx";
    }
    $idx++;
}

$args[] = '-f hls';
$args[] = '-hls_time 2';
$args[] = '-hls_playlist_type vod';
$args[] = '-hls_segment_filename "segment_%v_%03d.ts"';
$args[] = '-master_pl_name master.m3u8';
$args[] = '-var_stream_map "' . implode(' ', $var_stream_map) . '"';
$args[] = 'output_%v.m3u8';

$ffmpeg_cmd = implode(' ', $args);

// Execute FFmpeg from output HLS folder
$old_dir = getcwd();
chdir($hls_dir);
$transcode_out = @shell_exec($ffmpeg_cmd . ' 2>&1');
chdir($old_dir);

$master_path = $hls_dir . 'master.m3u8';

if (is_file($master_path)) {
    // Success! Update record with HLS URL & thumbnail
    $db_hls_url = 'uploads/hls/' . $type . '/' . $id . '/master.m3u8';
    
    $update_fields = [
        'hls_url'    => $db_hls_url,
        'status'     => 'published',
    ];
    if (is_file($thumb_path)) {
        $update_fields['thumbnail'] = $thumb_filename;
    }
    // Only update duration if video record has duration column
    if ($type === 'video') {
        $update_fields['duration'] = $duration;
    }
    db_update($table, $update_fields, 'id = ?', [$id]);
    echo "Transcoding ID $id ($type) completed successfully.\n";
} else {
    // Failure, fallback to direct MP4 streaming
    $update_fields = [
        'status' => 'published',
    ];
    if (is_file($thumb_path)) {
        $update_fields['thumbnail'] = $thumb_filename;
    }
    db_update($table, $update_fields, 'id = ?', [$id]);
    echo "Transcoding ID $id ($type) failed. Fell back to MP4. Details:\n" . $transcode_out . "\n";
}
