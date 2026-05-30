<?php
// ============================================================
// FreeHub.Live — Dynamic Page Router (CMS)
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

// Query the page by slug
$page = db_fetch("SELECT * FROM pages WHERE slug = ?", [$slug]);

if ($page) {
    $currency = fh_user_currency();
    
    if ($slug === 'viewer-page' || $slug === 'viewer-info') {
        $cpm = fh_format_money((float)setting('viewer_cpm', '0.50'), $currency);
        $cpc = fh_format_money((float)setting('viewer_cpc', '2.00'), $currency);
        $min = fh_format_money((float)setting('min_withdrawal_viewer', setting('min_withdrawal', '25.00')), $currency);
        $days = (int)setting('withdrawal_days', '7');
        
        $placements = array_filter(array_map('trim', explode(',', setting('viewer_eligible_placements', ''))), 'strlen');
        $placements_html = '';
        if ($placements) {
            $placements_html = '<ul style="list-style-type: disc; padding-left: 20px; margin: 0; line-height: 1.6;">';
            foreach ($placements as $p) {
                $name = ucwords(str_replace(['_', '-'], ' ', $p));
                $placements_html .= "<li style=\"margin-bottom:6px;\"><strong>{$name}</strong></li>";
            }
            $placements_html .= '</ul>';
        } else {
            $placements_html = '<p class="text-muted" style="margin:0;">No eligible viewer placements configured.</p>';
        }

        $page['content'] = <<<HTML
        <div class="wysiwyg-content" style="font-family: var(--font);">
            <p>Welcome to the FreeHub Viewer Rewards system! We value your time and attention, and we believe that viewers should share in the success of the platform. Here is exactly how you can earn rewards while watching your favorite content.</p>
            
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin:24px 0;">
                <div class="stat-card" style="padding:16px; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--bg3);">
                    <div style="font-size:0.75rem; color:var(--text2); text-transform:uppercase; font-weight:600; margin-bottom:4px;">Viewer CPM Rate</div>
                    <div style="font-size:1.4rem; font-weight:800; color:var(--accent);">{$cpm}</div>
                    <div style="font-size:0.7rem; color:var(--text2); margin-top:4px;">Per 1,000 ad impressions served</div>
                </div>
                <div class="stat-card" style="padding:16px; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--bg3);">
                    <div style="font-size:0.75rem; color:var(--text2); text-transform:uppercase; font-weight:600; margin-bottom:4px;">Viewer CPC Rate</div>
                    <div style="font-size:1.4rem; font-weight:800; color:var(--accent);">{$cpc}</div>
                    <div style="font-size:0.7rem; color:var(--text2); margin-top:4px;">Per 1,000 active ad clicks</div>
                </div>
                <div class="stat-card" style="padding:16px; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--bg3);">
                    <div style="font-size:0.75rem; color:var(--text2); text-transform:uppercase; font-weight:600; margin-bottom:4px;">Minimum Withdrawal</div>
                    <div style="font-size:1.4rem; font-weight:800; color:var(--text);">{$min}</div>
                    <div style="font-size:0.7rem; color:var(--text2); margin-top:4px;">Required to request a payout</div>
                </div>
            </div>

            <h3 style="margin-top: 24px; margin-bottom: 12px; font-weight:700;">Eligible Ad Placements</h3>
            <p>You can generate active monetization rewards by interacting with ads placed across these verified sections of the platform:</p>
            <div style="background:var(--bg3); padding:16px 20px; border-radius:var(--radius-lg); border:1px solid var(--border); margin-bottom:24px;">
                {$placements_html}
            </div>

            <h3 style="margin-top: 24px; margin-bottom: 12px; font-weight:700;">Earnings Eligibility Rules</h3>
            <p>To ensure a fair ecosystem for creators and advertisers alike, viewer earnings are subject to the following rules:</p>
            <ul style="list-style-type: decimal; padding-left: 20px; margin-bottom:24px;">
                <li style="margin-bottom:8px;"><strong>Active Engagement:</strong> You must actively view the content. Background tabs, minimized windows, or automated bot scripts are strictly prohibited.</li>
                <li style="margin-bottom:8px;"><strong>Valid Traffic:</strong> Only human-generated views are eligible. System checks are run periodically to detect invalid activity.</li>
                <li style="margin-bottom:8px;"><strong>One Stream at a Time:</strong> Earnings are credited for one concurrent stream. Opening multiple tabs or browsers to play videos simultaneously will not increase earnings and may trigger restrictions.</li>
            </ul>

            <div style="background:rgba(239, 68, 68, 0.08); border: 1px solid var(--red); color: var(--text); padding:16px 20px; border-radius:var(--radius-lg); margin-top:28px;">
                <h4 style="margin: 0 0 8px 0; color: var(--red); font-weight: 700; font-size: 0.95rem; display:flex; align-items:center; gap:6px;">
                    ⚠️ AdBlock &amp; VPN Notices
                </h4>
                <ul style="margin:0; padding-left: 20px; font-size: 0.88rem; color: var(--text2); line-height: 1.6;">
                    <li><strong>AdBlock Policy:</strong> If AdBlock is enabled, eligible earnings may not be recorded. Please disable AdBlock to support the platform and earn rewards.</li>
                    <li style="margin-top:6px;"><strong>VPN Policy:</strong> If VPN usage is detected, eligible earnings may not be recorded.</li>
                </ul>
            </div>

            <h3 style="margin-top: 32px; margin-bottom: 12px; font-weight:700;">Timelines &amp; Withdrawal</h3>
            <p>Once you reach the minimum balance of <strong>{$min}</strong>, you can file a withdrawal request from your dashboard. All payouts are audited and processed within <strong>{$days} business days</strong>.</p>
        </div>
HTML;
    } elseif ($slug === 'creator-page' || $slug === 'creator-info') {
        $cpm = fh_format_money((float)setting('creator_cpm', '1.00'), $currency);
        $cpc = fh_format_money((float)setting('creator_cpc', '5.00'), $currency);
        $min = fh_format_money((float)setting('min_withdrawal_creator', setting('min_withdrawal', '25.00')), $currency);
        $days = (int)setting('withdrawal_days', '7');
        
        $placements = array_filter(array_map('trim', explode(',', setting('creator_eligible_placements', ''))), 'strlen');
        $placements_html = '';
        if ($placements) {
            $placements_html = '<ul style="list-style-type: disc; padding-left: 20px; margin: 0; line-height: 1.6;">';
            foreach ($placements as $p) {
                $name = ucwords(str_replace(['_', '-'], ' ', $p));
                $placements_html .= "<li style=\"margin-bottom:6px;\"><strong>{$name}</strong></li>";
            }
            $placements_html .= '</ul>';
        } else {
            $placements_html = '<p class="text-muted" style="margin:0;">No eligible creator placements configured.</p>';
        }

        $page['content'] = <<<HTML
        <div class="wysiwyg-content" style="font-family: var(--font);">
            <p>Welcome to the FreeHub Creator Program! Upload your high-definition content, grow a loyal subscriber base, and monetize your passion. Here are the active rates, rules, and settings for creators.</p>
            
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin:24px 0;">
                <div class="stat-card" style="padding:16px; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--bg3);">
                    <div style="font-size:0.75rem; color:var(--text2); text-transform:uppercase; font-weight:600; margin-bottom:4px;">Creator CPM Rate</div>
                    <div style="font-size:1.4rem; font-weight:800; color:var(--accent);">{$cpm}</div>
                    <div style="font-size:0.7rem; color:var(--text2); margin-top:4px;">Per 1,000 ad impressions on your videos</div>
                </div>
                <div class="stat-card" style="padding:16px; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--bg3);">
                    <div style="font-size:0.75rem; color:var(--text2); text-transform:uppercase; font-weight:600; margin-bottom:4px;">Creator CPC Rate</div>
                    <div style="font-size:1.4rem; font-weight:800; color:var(--accent);">{$cpc}</div>
                    <div style="font-size:0.7rem; color:var(--text2); margin-top:4px;">Per 1,000 valid ad clicks by viewers</div>
                </div>
                <div class="stat-card" style="padding:16px; border:1px solid var(--border); border-radius:var(--radius-lg); background:var(--bg3);">
                    <div style="font-size:0.75rem; color:var(--text2); text-transform:uppercase; font-weight:600; margin-bottom:4px;">Creator Minimum Withdrawal</div>
                    <div style="font-size:1.4rem; font-weight:800; color:var(--text);">{$min}</div>
                    <div style="font-size:0.7rem; color:var(--text2); margin-top:4px;">Required to request a creator payout</div>
                </div>
            </div>

            <h3 style="margin-top: 24px; margin-bottom: 12px; font-weight:700;">Eligible Creator Ad Placements</h3>
            <p>Your uploaded content generates revenue whenever advertising campaigns are served on these eligible slots of your video and channel viewports:</p>
            <div style="background:var(--bg3); padding:16px 20px; border-radius:var(--radius-lg); border:1px solid var(--border); margin-bottom:24px;">
                {$placements_html}
            </div>

            <h3 style="margin-top: 24px; margin-bottom: 12px; font-weight:700;">Creator Revenue Rules</h3>
            <p>Creators must operate in full compliance with the following quality and integrity standards to secure earnings payouts:</p>
            <ul style="list-style-type: decimal; padding-left: 20px; margin-bottom:24px;">
                <li style="margin-bottom:8px;"><strong>Original Content:</strong> You must own or hold explicit distribution rights for all uploaded content. Plagiarized or re-uploaded content from external portals will result in monetization disqualification.</li>
                <li style="margin-bottom:8px;"><strong>Engagement Integrity:</strong> Artificially inflating views, subscribers, or ad clicks via bots, click rings, or scripts is strictly prohibited.</li>
                <li style="margin-bottom:8px;"><strong>Community Adherence:</strong> Your channel must not contain illegal, explicit, harmful, or copyright-infringing content.</li>
            </ul>

            <div style="background:rgba(239, 68, 68, 0.08); border: 1px solid var(--red); color: var(--text); padding:16px 20px; border-radius:var(--radius-lg); margin-top:28px;">
                <h4 style="margin: 0 0 8px 0; color: var(--red); font-weight: 700; font-size: 0.95rem; display:flex; align-items:center; gap:6px;">
                    ⚠️ AdBlock &amp; VPN Restrictions
                </h4>
                <ul style="margin:0; padding-left: 20px; font-size: 0.88rem; color: var(--text2); line-height: 1.6;">
                    <li><strong>AdBlock Policy:</strong> If AdBlock is enabled, eligible earnings may not be recorded.</li>
                    <li style="margin-top:6px;"><strong>VPN Policy:</strong> If VPN usage is detected, eligible earnings may not be recorded.</li>
                </ul>
            </div>

            <h3 style="margin-top: 32px; margin-bottom: 12px; font-weight:700;">Withdrawal Processing &amp; Payouts</h3>
            <p>All creator earnings are accumulated in your unified balance in real-time. Payout requests meeting the minimum threshold of <strong>{$min}</strong> are carefully reviewed and securely dispatched within <strong>{$days} business days</strong>.</p>
        </div>
HTML;
    } elseif ($slug === 'privacy-policy') {
        $viewer_cpm = fh_format_money((float)setting('viewer_cpm', '0.50'), $currency);
        $creator_cpm = fh_format_money((float)setting('creator_cpm', '1.00'), $currency);

        $page['content'] = <<<HTML
        <div class="wysiwyg-content" style="font-family: var(--font);">
            <p>Your privacy is of paramount importance to us. This Privacy Policy details the types of personal data we collect, how we use it, and the strict security measures we implement to protect your information, especially in connection with our Monetization Program.</p>
            
            <h2>1. Information We Collect</h2>
            <p>We collect information to provide better services to all our users, including account details, viewing histories, interaction records, preferred payment preferences, IP hashes, and device contexts. These details are essential to maintain system integrity and prevent unauthorized activity.</p>
            
            <h2>2. How We Use Information</h2>
            <p>We use the collected information to manage user authentication, accurately calculate viewer rewards and creator earnings, process secure withdrawals, and prevent fraudulent activity on the platform.</p>

            <h2>3. Monetization Integrity &amp; Data Auditing</h2>
            <p>To ensure a safe environment for our users, creators, and advertising partners, our systems dynamically audit all platform traffic. Earnings are subject to the following monetization data policies:</p>
            <ul style="list-style-type: disc; padding-left: 20px; margin-bottom:20px;">
                <li style="margin-bottom:8px;"><strong>Valid Traffic Requirements:</strong> Earnings are generated strictly from verified, human ad interactions. We record impressions and clicks, and our auditing system rejects invalid or automated traffic.</li>
                <li style="margin-bottom:8px;"><strong>AdBlock Impact:</strong> System ad impressions and clicks require direct display on eligible screen formats. If AdBlock is enabled on viewer screens, ads will not load, and eligible earnings may not be recorded.</li>
                <li style="margin-bottom:8px;"><strong>VPN &amp; Proxy Limitations:</strong> VPN and proxy network routing prevent accurate geographic targeting and security validation. If VPN usage is detected, eligible earnings may not be recorded.</li>
                <li style="margin-bottom:8px;"><strong>Fraud Detection:</strong> Any fraudulent activity, account duplication, script automation, or click-inflation schemes may result in immediate account restrictions, forfeiture of outstanding balance, and permanent ban.</li>
            </ul>

            <h2>4. Current System Monetization Rates</h2>
            <p>Our advertising rates are periodically updated based on campaign demand. The current standard base rates are:</p>
            <table border="1" style="border-collapse: collapse; width: 100%; border-color: var(--border); margin:16px 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.03);">
                        <th style="padding: 10px 16px;">Program Role</th>
                        <th style="padding: 10px 16px;">Current CPM (Per 1,000 Imps)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 10px 16px;"><strong>Viewer</strong></td>
                        <td style="padding: 10px 16px; font-weight:700; color:var(--accent);">{$viewer_cpm}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px;"><strong>Creator</strong></td>
                        <td style="padding: 10px 16px; font-weight:700; color:var(--accent);">{$creator_cpm}</td>
                    </tr>
                </tbody>
            </table>
        </div>
HTML;
    } elseif ($slug === 'disclaimer') {
        $viewer_min = fh_format_money((float)setting('min_withdrawal_viewer', setting('min_withdrawal', '25.00')), $currency);
        $creator_min = fh_format_money((float)setting('min_withdrawal_creator', setting('min_withdrawal', '25.00')), $currency);
        $days = (int)setting('withdrawal_days', '7');

        $page['content'] = <<<HTML
        <div class="wysiwyg-content" style="font-family: var(--font);">
            <p>Please read this disclaimer carefully before utilizing any features of the FreeHub platform.</p>
            
            <h2>No Earnings Guarantees</h2>
            <p>Any earning statistics, rate tables, or success stories displayed on the platform are illustrative examples of potential outcomes. Actual creator and viewer earnings are not guaranteed and will vary based on user engagement, geographic location, adherence to community rules, and overall platform ad revenue.</p>

            <h2>Monetization and Traffic Validation</h2>
            <p>FreeHub maintains a strict fraud prevention policy to protect our advertising partners and community. Please review these eligibility notices:</p>
            <ul style="list-style-type: disc; padding-left: 20px; margin-bottom:20px;">
                <li style="margin-bottom:8px;"><strong>Valid Interactions:</strong> All viewer and creator earnings are calculated based on valid, human ad impressions and clicks. Invalid traffic generated via automation, bots, background streams, or multi-tabbing is actively rejected during audit.</li>
                <li style="margin-bottom:8px;"><strong>AdBlock Policy:</strong> If AdBlock is enabled on viewer screens, ads will not load, and eligible earnings may not be recorded.</li>
                <li style="margin-bottom:8px;"><strong>VPN Policy:</strong> If VPN or proxy usage is detected, eligible earnings may not be recorded due to tracking and security restrictions.</li>
                <li style="margin-bottom:8px;"><strong>Fraud &amp; Account Bans:</strong> Fraudulent activity of any kind, including duplicate accounts or scripted engagement, will result in the immediate forfeiture of balances and permanent restriction of your account.</li>
            </ul>

            <h2>System Withdrawal Thresholds</h2>
            <p>Eligible payouts are strictly governed by the following live settings, loaded dynamically from system configuration:</p>
            <table border="1" style="border-collapse: collapse; width: 100%; border-color: var(--border); margin:16px 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.03);">
                        <th style="padding: 10px 16px;">Monetization Metric</th>
                        <th style="padding: 10px 16px;">Value / Limit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 10px 16px;"><strong>Viewer Min Withdrawal</strong></td>
                        <td style="padding: 10px 16px; font-weight:700;">{$viewer_min}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px;"><strong>Creator Min Withdrawal</strong></td>
                        <td style="padding: 10px 16px; font-weight:700;">{$creator_min}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px;"><strong>Processing Timeline</strong></td>
                        <td style="padding: 10px 16px; font-weight:600; color:var(--accent);">{$days} business days</td>
                    </tr>
                </tbody>
            </table>
        </div>
HTML;
    } elseif ($slug === 'terms-conditions') {
        $viewer_min = fh_format_money((float)setting('min_withdrawal_viewer', setting('min_withdrawal', '25.00')), $currency);
        $creator_min = fh_format_money((float)setting('min_withdrawal_creator', setting('min_withdrawal', '25.00')), $currency);

        $page['content'] = <<<HTML
        <div class="wysiwyg-content" style="font-family: var(--font);">
            <p>These Terms and Conditions govern your access to and use of FreeHub. By creating an account or browsing the platform, you fully accept these terms.</p>
            
            <h2>1. Account Registration</h2>
            <p>You must provide accurate, complete, and up-to-date information during signup. You are solely responsible for maintaining account confidentiality. Creating duplicate accounts to exploit rewards is strictly prohibited.</p>
            
            <h2>2. Intellectual Property</h2>
            <p>All trademarks, logos, and system layouts remain the exclusive property of FreeHub. Uploaded content remains the property of the creator, who grants FreeHub a worldwide license to host, transcode, and stream it.</p>

            <h2>3. Monetization Terms &amp; Integrity Policies</h2>
            <p>Our advertising monetization program is subject to strict eligibility and security checks. By participating, you agree to the following conditions:</p>
            <ul style="list-style-type: disc; padding-left: 20px; margin-bottom:20px;">
                <li style="margin-bottom:8px;"><strong>Earning Crediting:</strong> Earnings depend strictly on valid ad impressions and ad clicks by actual human viewers. Invalid traffic, click injection, or simulated traffic is actively rejected.</li>
                <li style="margin-bottom:8px;"><strong>Network and AdBlock Policies:</strong> If AdBlock is enabled on viewer screens, ads will not load, and eligible earnings may not be recorded. If VPN or proxy usage is detected, eligible earnings may not be recorded.</li>
                <li style="margin-bottom:8px;"><strong>Fraudulent Abuse:</strong> Any fraudulent activity, bot automation, scripting, background loading, or multi-accounting results in immediate account restrictions, permanent ban, and forfeiture of all accumulated funds.</li>
            </ul>

            <h2>4. Active Withdrawal Limits</h2>
            <p>Withdrawals are dynamically governed by the following platform balance thresholds:</p>
            <ul style="list-style-type: square; padding-left: 20px; margin-bottom:20px;">
                <li>Viewer Payout Minimum Threshold: <strong>{$viewer_min}</strong></li>
                <li>Creator Payout Minimum Threshold: <strong>{$creator_min}</strong></li>
            </ul>
        </div>
HTML;
    } elseif ($slug === 'about-us') {
        $page['content'] = <<<HTML
        <div class="wysiwyg-content" style="font-family: var(--font);">
            <p>Welcome to FreeHub! We are a next-generation video-sharing platform, a robust content discovery platform, and a comprehensive creator monetization platform designed to bring creators, viewers, and advertisers together in a collaborative ecosystem.</p>
            
            <h2>Our Core Offerings</h2>
            <p>FreeHub is built on three core pillars designed to empower every participant on the platform:</p>
            <ul style="list-style-type: disc; padding-left: 20px; margin-bottom:20px;">
                <li style="margin-bottom:8px;"><strong>Video-Sharing Environment:</strong> A highly responsive, premium-grade infrastructure enabling creators to upload and manage content while providing viewers with a state-of-the-art streaming player interface.</li>
                <li style="margin-bottom:8px;"><strong>Content Discovery:</strong> Seamless navigation, categorizations, and customizable feeds designed to recommend trending, engaging, and relevant videos directly to your screen.</li>
                <li style="margin-bottom:8px;"><strong>Creator Monetization:</strong> Highly competitive, transparent monetization models driven by dynamic CPM and CPC ad campaigns integrated directly into the video streams.</li>
            </ul>

            <h2>Our Advertising Revenue Model</h2>
            <p>FreeHub utilizes a transparent and sustainable monetization model that respects the attention of viewers and the dedication of content creators:</p>
            <ul style="list-style-type: square; padding-left: 20px; margin-bottom:20px;">
                <li style="margin-bottom:8px;"><strong>Advertising Revenue:</strong> Advertising campaigns are placed dynamically across premium platform formats and viewports.</li>
                <li style="margin-bottom:8px;"><strong>Platform Retention:</strong> A fair portion of advertising revenue is retained by the platform to support content delivery networks (CDNs), server maintenance, security upgrades, and development costs.</li>
                <li style="margin-bottom:8px;"><strong>Community Distribution:</strong> The remaining portion of advertising revenue is distributed directly to eligible viewers and content creators based on verified impressions, clicks, and watch durations, in full accordance with platform rules.</li>
            </ul>

            <p style="margin-top: 24px;">Our mission is to establish a premium-tier digital economy where attention is rewarded, original content is nurtured, and monetization is fair and balanced for all parties.</p>
        </div>
HTML;
    }
}

$is_admin = is_admin();
$error_type = null;

if (!$page) {
    $error_type = '404';
} else {
    // Advanced visibility status gates
    if ($page['status'] === 'draft' && !$is_admin) {
        $error_type = '404';
    } elseif ($page['status'] === 'private' && !is_logged_in()) {
        $error_type = 'private';
    } elseif ($page['status'] === 'scheduled' && !$is_admin) {
        // If scheduled and time has not arrived yet, restrict access
        if (empty($page['publish_at']) || strtotime($page['publish_at']) > time()) {
            $error_type = 'scheduled';
        }
    }
}

// Render access restriction screen if gate is triggered
if ($error_type) {
    http_response_code($error_type === 'private' ? 403 : 404);
    $meta_title = ($error_type === 'private') ? 'Access Restricted' : 'Page Not Found';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="container" style="max-width: 800px; padding: 80px 20px; text-align: center;">
        <?php if ($error_type === 'private'): ?>
            <h1 style="font-size: 3rem; font-weight: 800; color: var(--text); margin-bottom: 16px;">🔒 Private Page</h1>
            <p style="font-size: 1.15rem; color: var(--text2); margin-bottom: 32px;">This page content is private. Please log in to your account to view this page.</p>
            <a href="<?= BASE_URL ?>/auth/login.php?next=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary" style="height: 38px; border-radius: 19px; display: inline-flex; align-items: center; padding: 0 24px; font-weight: 600; text-decoration: none;">
                Log In
            </a>
        <?php elseif ($error_type === 'scheduled'): ?>
            <h1 style="font-size: 3rem; font-weight: 800; color: var(--text); margin-bottom: 16px;">⏳ Scheduled Page</h1>
            <p style="font-size: 1.15rem; color: var(--text2); margin-bottom: 32px;">This page has been scheduled to be published soon. Please check back later.</p>
            <a href="<?= BASE_URL ?>/" class="btn btn-primary" style="height: 38px; border-radius: 19px; display: inline-flex; align-items: center; padding: 0 24px; font-weight: 600; text-decoration: none;">
                Return Home
            </a>
        <?php else: ?>
            <h1 style="font-size: 4rem; font-weight: 800; color: var(--text); margin-bottom: 16px; line-height: 1;">404</h1>
            <p style="font-size: 1.15rem; color: var(--text2); margin-bottom: 32px;">The page you are looking for does not exist or has been moved.</p>
            <a href="<?= BASE_URL ?>/" class="btn btn-primary" style="height: 38px; border-radius: 19px; display: inline-flex; align-items: center; padding: 0 24px; font-weight: 600; text-decoration: none;">
                Return Home
            </a>
        <?php endif; ?>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Set up dynamic SEO headers
$meta_title = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'];

if (!empty($page['meta_desc'])) {
    $meta_desc = $page['meta_desc'];
} else {
    $plain_text = strip_tags($page['content']);
    $meta_desc = mb_substr($plain_text, 0, 155);
    if (mb_strlen($plain_text) > 155) {
        $meta_desc .= '...';
    }
}

if (!empty($page['meta_keywords'])) {
    $meta_keywords = $page['meta_keywords'];
}

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* Responsive Article Content Styles matching Global Inter Font */
.wysiwyg-content {
    font-size: 0.96rem;
    line-height: 1.8;
    color: var(--text);
}
.wysiwyg-content p {
    margin-bottom: 20px;
}
.wysiwyg-content h1, 
.wysiwyg-content h2, 
.wysiwyg-content h3, 
.wysiwyg-content h4 {
    color: var(--text);
    font-weight: 700;
    margin-top: 32px;
    margin-bottom: 16px;
    line-height: 1.4;
}
.wysiwyg-content h1 { font-size: 1.8rem; border-bottom: 1px solid var(--border); padding-bottom: 8px; }
.wysiwyg-content h2 { font-size: 1.45rem; }
.wysiwyg-content h3 { font-size: 1.25rem; }

.wysiwyg-content ul, 
.wysiwyg-content ol {
    margin-top: 8px;
    margin-bottom: 20px;
    padding-left: 24px;
}
.wysiwyg-content li {
    margin-bottom: 8px;
}
.wysiwyg-content ul {
    list-style-type: disc;
}
.wysiwyg-content ol {
    list-style-type: decimal;
}

.wysiwyg-content table {
    width: 100% !important;
    border-collapse: collapse;
    margin: 24px 0;
    font-size: 0.88rem;
    background: var(--bg3);
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--border);
}
.wysiwyg-content th, 
.wysiwyg-content td {
    padding: 12px 16px;
    border: 1px solid var(--border);
    text-align: left;
}
.wysiwyg-content th {
    background: rgba(255, 255, 255, 0.03);
    font-weight: 700;
    color: var(--text);
}
.wysiwyg-content tr:last-child td {
    border-bottom: 0;
}

.wysiwyg-content img {
    max-width: 100%;
    height: auto !important;
    border-radius: 8px;
    margin: 16px 0;
    display: block;
}
.wysiwyg-content iframe, 
.wysiwyg-content video {
    max-width: 100%;
    width: 100%;
    aspect-ratio: 16/9;
    border-radius: 8px;
    border: 0;
    margin: 20px 0;
}
.wysiwyg-content a {
    color: var(--accent);
    text-decoration: underline;
    transition: color 0.15s;
}
.wysiwyg-content a:hover {
    color: var(--text);
}
.wysiwyg-content blockquote {
    border-left: 4px solid var(--accent);
    background: var(--bg3);
    padding: 16px 20px;
    margin: 20px 0;
    border-radius: 0 8px 8px 0;
    font-style: italic;
    color: var(--text2);
}
</style>

<div class="container" style="max-width: 860px; padding: 40px 16px; margin: 0 auto;">
    <article class="card" style="padding: 40px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
        
        <!-- Administrator CMS Info notice -->
        <?php if ($page['status'] !== 'published'): ?>
            <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid var(--yellow); color: var(--yellow); padding: 12px 16px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <span>⚠️</span>
                <span>
                    <strong><?= ucfirst($page['status']) ?> Mode:</strong> 
                    This page is visibility-restricted (Status: <?= $page['status'] ?>). 
                    <?php if ($page['status'] === 'scheduled'): ?>
                        Scheduled to go live on <?= date('M d, Y H:i', strtotime($page['publish_at'])) ?>.
                    <?php endif; ?>
                    Only administrators can view it in this state.
                </span>
            </div>
        <?php endif; ?>

        <!-- Article Header -->
        <header style="margin-bottom: 36px; border-bottom: 1px solid var(--border); padding-bottom: 20px;">
            <h1 style="font-size: 2.2rem; font-weight: 800; line-height: 1.25; margin-bottom: 8px; color: var(--text);"><?= e($page['title']) ?></h1>
            <div style="font-size: 0.8rem; color: var(--text2); display: flex; align-items: center; gap: 6px;">
                <span>Published by FreeHub</span>
                <span>•</span>
                <span>Updated <?= date('F d, Y', strtotime($page['updated_at'])) ?></span>
            </div>
        </header>

        <!-- Article Content -->
        <div class="wysiwyg-content">
            <?= $page['content'] ?>
        </div>
    </article>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
