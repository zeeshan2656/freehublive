<?php
// ============================================================
// FreeHub.Live — Premium Conversion-Focused Welcome Page
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If user is already logged in, redirect to Main Homepage
if (is_logged_in()) {
    redirect(BASE_URL . '/');
}

// ── Performance: HTTP Cache Headers ──
header('Cache-Control: public, max-age=300, s-maxage=600'); // 5 min browser, 10 min CDN
header('Vary: Accept-Encoding');
header('X-Content-Type-Options: nosniff');

// ── Performance: Enable output buffering with gzip ──
if (!ob_get_level()) {
    if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
        ob_start('ob_gzhandler');
    } else {
        ob_start();
    }
}

$site_name = setting('site_name', 'FreeHub');
$site_theme = setting('active_theme', 'dark-minimal');
$primary    = setting('primary_color', '#6366f1');

// ── Performance: Cache stats counts (file-based, 5 min TTL) ──
$cache_file = __DIR__ . '/cache/welcome_stats.cache';
$cache_ttl = 300; // 5 minutes
$stats_cached = false;

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
    $cached = @unserialize(file_get_contents($cache_file));
    if ($cached && isset($cached['users'], $cached['videos'])) {
        $total_users = $cached['users'];
        $total_videos = $cached['videos'];
        $stats_cached = true;
    }
}

if (!$stats_cached) {
    $total_users = (int)db_fetch("SELECT COUNT(id) as c FROM users")['c'];
    $total_videos = (int)db_fetch("SELECT COUNT(id) as c FROM videos WHERE status='published'")['c'];
    @file_put_contents($cache_file, serialize(['users' => $total_users, 'videos' => $total_videos]));
}

// Format stats beautifully
$users_formatted = $total_users > 1000 ? number_format($total_users / 1000, 1) . 'K+' : number_format($total_users) . '+';
$videos_formatted = $total_videos > 1000 ? number_format($total_videos / 1000, 1) . 'K+' : number_format($total_videos) . '+';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($site_theme) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to <?= e($site_name) ?> — Members-Only Video Platform</title>
  <meta name="description" content="Watch premium videos, discover amazing creators, and earn rewards on <?= e($site_name) ?>. Join free today!">

  <!-- Resource Hints -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="dns-prefetch" href="https://fonts.googleapis.com">

  <!-- Preload LCP image -->
  <link rel="preload" as="image" href="<?= BASE_URL ?>/assets/img/welcome_hero.webp" fetchpriority="high" type="image/webp">

  <!-- Non-render-blocking CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css" media="print" onload="this.media='all'">
  <noscript><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css"></noscript>

  <!-- Async Google Fonts -->
  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@400;600;700;800;900&display=swap" onload="this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@400;600;700;800;900&display=swap"></noscript>

  <style>
    /* Styling overrides/extensions for a jaw-dropping landing experience */
    :root {
      --primary-hsl: 238, 83%, 66%;
      --accent-hsl: 25, 95%, 53%;
      --glow-color: rgba(99, 102, 241, 0.15);
    }
    
    body.welcome-body {
      background: #060813;
      background-image: 
        radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.08) 0%, transparent 40%),
        radial-gradient(circle at 90% 80%, rgba(236, 72, 153, 0.05) 0%, transparent 40%),
        radial-gradient(circle at 50% 50%, rgba(6, 8, 19, 1) 0%, rgba(2, 3, 8, 1) 100%);
      color: #f3f4f6;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
      text-rendering: optimizeSpeed;
    }
    
    /* Sleek Navbar */
    .welcome-nav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 72px;
      backdrop-filter: blur(12px);
      background: rgba(6, 8, 19, 0.75);
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      z-index: 1000;
      display: flex;
      align-items: center;
      transition: all 0.3s ease;
    }
    
    .welcome-nav-container {
      width: 100%;
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .welcome-nav-links {
      display: flex;
      align-items: center;
      gap: 24px;
    }
    
    .welcome-nav-link {
      font-size: 0.9rem;
      font-weight: 500;
      color: #9ca3af;
      transition: color 0.2s ease;
    }
    
    .welcome-nav-link:hover {
      color: #fff;
    }
    
    .welcome-nav-ctas {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    /* Premium Buttons */
    .btn-premium {
      font-family: 'Outfit', sans-serif;
      font-weight: 600;
      font-size: 0.95rem;
      padding: 10px 24px;
      border-radius: 50px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
    }
    
    .btn-premium-primary {
      background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
      color: #fff;
      box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .btn-premium-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 24px rgba(99, 102, 241, 0.45);
      background: linear-gradient(135deg, #4f46e5 0%, #9333ea 100%);
    }
    
    .btn-premium-secondary {
      background: rgba(255, 255, 255, 0.04);
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(8px);
    }
    
    .btn-premium-secondary:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(255, 255, 255, 0.15);
      transform: translateY(-2px);
    }
    
    /* Hero Section */
    .welcome-hero {
      position: relative;
      padding: 140px 0 80px;
      min-height: 95vh;
      display: flex;
      align-items: center;
    }
    
    .welcome-hero-container {
      width: 100%;
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 24px;
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 48px;
      align-items: center;
    }
    
    .welcome-hero-content {
      z-index: 10;
    }
    
    .welcome-badge-glow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(99, 102, 241, 0.1);
      border: 1px solid rgba(99, 102, 241, 0.25);
      padding: 6px 16px;
      border-radius: 50px;
      font-size: 0.85rem;
      font-weight: 600;
      color: #a5b4fc;
      margin-bottom: 24px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      box-shadow: 0 0 15px rgba(99, 102, 241, 0.08);
    }
    
    .welcome-hero-title {
      font-family: 'Outfit', sans-serif;
      font-size: 3.4rem;
      font-weight: 900;
      line-height: 1.15;
      margin-bottom: 20px;
      letter-spacing: -0.02em;
      color: #fff;
    }
    
    .welcome-hero-title span.grad {
      background: linear-gradient(135deg, #a5b4fc 0%, #818cf8 40%, #ec4899 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .welcome-hero-subtitle {
      font-size: 1.1rem;
      color: #9ca3af;
      line-height: 1.6;
      margin-bottom: 28px;
      max-width: 580px;
    }
    
    .welcome-hero-ctas {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
      margin-bottom: 32px;
    }
    
    .welcome-hero-ctas .btn-premium {
      padding: 14px 32px;
      font-size: 1.05rem;
    }
    
    .welcome-hero-visual {
      position: relative;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    
    .welcome-hero-visual::before {
      content: '';
      position: absolute;
      width: 320px;
      height: 320px;
      background: radial-gradient(circle, rgba(99, 102, 241, 0.18) 0%, transparent 70%);
      filter: blur(20px);
      z-index: 1;
    }
    
    .welcome-hero-img {
      width: 100%;
      max-width: 480px;
      height: auto;
      aspect-ratio: 1/1;
      object-fit: contain;
      z-index: 2;
      filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.5));
      border-radius: 24px;
      contain: layout style;
    }
    
    /* Warnings Banner (VPN & AdBlock) */
    .warning-banner {
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(245, 158, 11, 0.02) 100%);
      border: 1px solid rgba(245, 158, 11, 0.2);
      border-radius: 12px;
      padding: 14px 18px;
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 24px;
      max-width: 580px;
    }
    
    .warning-banner svg {
      color: #fbbf24;
      flex-shrink: 0;
      margin-top: 2px;
    }
    
    .warning-title {
      font-size: 0.85rem;
      font-weight: 700;
      color: #fbbf24;
      margin-bottom: 3px;
    }
    
    .warning-desc {
      font-size: 0.8rem;
      color: #d1d5db;
      line-height: 1.4;
    }
    
    /* Stats Bar */
    .welcome-stats-row {
      display: flex;
      align-items: center;
      gap: 40px;
      border-top: 1px solid rgba(255, 255, 255, 0.06);
      padding-top: 24px;
    }
    
    .welcome-stat-item {
      display: flex;
      flex-direction: column;
    }
    
    .welcome-stat-num {
      font-family: 'Outfit', sans-serif;
      font-size: 1.85rem;
      font-weight: 800;
      color: #fff;
    }
    
    .welcome-stat-lbl {
      font-size: 0.8rem;
      color: #6b7280;
      margin-top: 2px;
    }
    
    /* Sections Shell */
    .welcome-section {
      padding: 80px 0;
      position: relative;
    }
    
    .welcome-section-header {
      text-align: center;
      max-width: 680px;
      margin: 0 auto 50px;
    }
    
    .welcome-section-tag {
      font-size: 0.85rem;
      font-weight: 700;
      color: #6366f1;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 10px;
      display: block;
    }
    
    .welcome-section-title {
      font-family: 'Outfit', sans-serif;
      font-size: 2.2rem;
      font-weight: 800;
      color: #fff;
      line-height: 1.3;
      margin-bottom: 16px;
    }
    
    .welcome-section-desc {
      font-size: 1.05rem;
      color: #9ca3af;
      line-height: 1.6;
    }
    
    /* Features Cards Grid */
    .features-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 24px;
    }
    
    /* Glass Feature Card */
    .feature-card {
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 20px;
      padding: 32px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    
    .feature-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #6366f1, #a855f7);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .feature-card:hover {
      transform: translateY(-6px);
      background: rgba(255, 255, 255, 0.04);
      border-color: rgba(99, 102, 241, 0.2);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4), 0 0 20px rgba(99, 102, 241, 0.05);
    }
    
    .feature-card:hover::before {
      opacity: 1;
    }
    
    .feature-icon-wrapper {
      width: 52px;
      height: 52px;
      border-radius: 12px;
      background: rgba(99, 102, 241, 0.1);
      color: #818cf8;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      font-size: 1.4rem;
      border: 1px solid rgba(99, 102, 241, 0.15);
      flex-shrink: 0;
    }
    
    .feature-card:nth-child(2) .feature-icon-wrapper {
      background: rgba(236, 72, 153, 0.1);
      color: #f472b6;
      border-color: rgba(236, 72, 153, 0.15);
    }
    .feature-card:nth-child(3) .feature-icon-wrapper {
      background: rgba(16, 185, 129, 0.1);
      color: #34d399;
      border-color: rgba(16, 185, 129, 0.15);
    }
    .feature-card:nth-child(4) .feature-icon-wrapper {
      background: rgba(245, 158, 11, 0.1);
      color: #fbbf24;
      border-color: rgba(245, 158, 11, 0.15);
    }
    .feature-card:nth-child(5) .feature-icon-wrapper {
      background: rgba(99, 102, 241, 0.15);
      color: #818cf8;
      border: 1.5px dashed #818cf8;
    }
    
    .feature-title {
      font-family: 'Outfit', sans-serif;
      font-size: 1.25rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 10px;
    }
    
    .feature-desc {
      font-size: 0.9rem;
      color: #9ca3af;
      line-height: 1.5;
      margin-bottom: 16px;
      flex-grow: 1;
    }
    
    .feature-highlight-badge {
      display: inline-flex;
      align-items: center;
      padding: 4px 10px;
      border-radius: 4px;
      font-size: 0.72rem;
      font-weight: 800;
      background: rgba(16, 185, 129, 0.15);
      color: #34d399;
      text-transform: uppercase;
      align-self: flex-start;
      margin-top: auto;
    }
    
    /* How it works */
    .how-it-works-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 32px;
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 24px;
      position: relative;
    }
    
    .step-item {
      position: relative;
      text-align: center;
    }
    
    .step-num {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #0f172a;
      border: 2px solid rgba(99, 102, 241, 0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Outfit', sans-serif;
      font-size: 1.4rem;
      font-weight: 800;
      color: #a5b4fc;
      margin: 0 auto 20px;
      box-shadow: 0 0 20px rgba(99, 102, 241, 0.2);
    }
    
    .step-title {
      font-family: 'Outfit', sans-serif;
      font-size: 1.2rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 8px;
    }
    
    .step-desc {
      font-size: 0.88rem;
      color: #9ca3af;
      line-height: 1.5;
      max-width: 280px;
      margin: 0 auto;
    }
    
    /* Trust & Info Grid */
    .trust-section {
      background: rgba(255, 255, 255, 0.01);
      border-top: 1px solid rgba(255, 255, 255, 0.03);
      border-bottom: 1px solid rgba(255, 255, 255, 0.03);
    }
    
    .trust-grid {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 24px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 64px;
      align-items: center;
    }
    
    .trust-info-block {
      margin-bottom: 32px;
    }
    
    .trust-info-block:last-child {
      margin-bottom: 0;
    }
    
    .trust-info-title {
      font-family: 'Outfit', sans-serif;
      font-size: 1.25rem;
      font-weight: 700;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 8px;
    }
    
    .trust-info-title svg {
      color: #6366f1;
      flex-shrink: 0;
    }
    
    .trust-info-desc {
      font-size: 0.92rem;
      color: #9ca3af;
      line-height: 1.6;
      padding-left: 32px;
    }
    
    /* Call to Action Banner */
    .cta-banner-wrapper {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 24px;
    }
    
    .cta-banner-box {
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(168, 85, 247, 0.05) 100%);
      border: 1px solid rgba(99, 102, 241, 0.2);
      border-radius: 24px;
      padding: 60px 40px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    
    .cta-banner-box::before {
      content: '';
      position: absolute;
      width: 400px;
      height: 400px;
      background: radial-gradient(circle, rgba(168, 85, 247, 0.1) 0%, transparent 60%);
      top: -200px;
      right: -200px;
      z-index: 1;
    }
    
    .cta-banner-title {
      font-family: 'Outfit', sans-serif;
      font-size: 2.4rem;
      font-weight: 900;
      color: #fff;
      margin-bottom: 16px;
    }
    
    .cta-banner-subtitle {
      font-size: 1.05rem;
      color: #d1d5db;
      max-width: 600px;
      margin: 0 auto 32px;
      line-height: 1.5;
    }
    
    .cta-banner-box .btn-premium {
      padding: 16px 40px;
      font-size: 1.1rem;
    }
    
    /* Minimal Modern Footer */
    .welcome-footer {
      background: #03040b;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
      padding: 60px 0 30px;
    }
    
    .welcome-footer-container {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 24px;
    }
    
    .welcome-footer-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 40px;
      flex-wrap: wrap;
      gap: 32px;
    }
    
    .footer-brand {
      max-width: 320px;
    }
    
    .footer-brand-logo {
      margin-bottom: 12px;
    }
    
    .footer-brand-desc {
      font-size: 0.85rem;
      color: #6b7280;
      line-height: 1.5;
    }
    
    .footer-links-col {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    .footer-col-title {
      font-family: 'Outfit', sans-serif;
      font-size: 0.9rem;
      font-weight: 700;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 6px;
    }
    
    .footer-link {
      font-size: 0.85rem;
      color: #9ca3af;
      transition: color 0.2s ease;
    }
    
    .footer-link:hover {
      color: #fff;
    }
    
    .welcome-footer-bottom {
      border-top: 1px solid rgba(255, 255, 255, 0.03);
      padding-top: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }
    
    .footer-copy {
      font-size: 0.82rem;
      color: #4b5563;
    }
    
    .footer-bottom-links {
      display: flex;
      gap: 20px;
    }
    
    .footer-bottom-link {
      font-size: 0.82rem;
      color: #6b7280;
      transition: color 0.2s ease;
    }
    
    .footer-bottom-link:hover {
      color: #9ca3af;
    }
    
    /* Mobile Menu Button */
    .mobile-menu-toggle {
      display: none;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      padding: 8px;
      cursor: pointer;
      color: #fff;
      transition: all 0.2s ease;
      align-items: center;
      justify-content: center;
    }
    .mobile-menu-toggle:hover {
      background: rgba(255, 255, 255, 0.1);
    }
    
    /* Responsive Queries */
    @media (max-width: 1024px) {
      .welcome-hero-container {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 32px;
      }
      .welcome-hero-content {
        display: flex;
        flex-direction: column;
        align-items: center;
      }
      .welcome-hero-subtitle {
        margin-left: auto;
        margin-right: auto;
      }
      .warning-banner {
        margin-left: auto;
        margin-right: auto;
        text-align: left;
      }
      .welcome-stats-row {
        justify-content: center;
      }
      .features-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      .trust-grid {
        grid-template-columns: 1fr;
        gap: 40px;
      }
    }
    
    @media (max-width: 768px) {
      .welcome-hero-title {
        font-size: 2.5rem;
      }
      .features-grid {
        grid-template-columns: 1fr;
      }
      .how-it-works-grid {
        grid-template-columns: 1fr;
        gap: 40px;
      }
      .welcome-nav-container {
        padding: 0 16px;
      }
      
      .mobile-menu-toggle {
        display: flex;
        order: 2;
      }
      
      .welcome-nav-links {
        position: fixed;
        top: 72px;
        left: 0;
        right: 0;
        background: rgba(6, 8, 19, 0.95);
        backdrop-filter: blur(16px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        flex-direction: column;
        align-items: center;
        gap: 0;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 999;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
      }
      
      .welcome-nav-links.open {
        max-height: 320px; /* Enough for 4 links */
        padding: 16px 0;
      }
      
      .welcome-nav-link {
        display: block;
        width: 100%;
        text-align: center;
        padding: 12px 24px;
        font-size: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      }
      
      .welcome-nav-link:last-child {
        border-bottom: none;
      }
      
      .welcome-nav-ctas {
        order: 3;
        gap: 8px;
      }
      
      .welcome-nav-ctas .btn-premium {
        padding: 6px 12px;
        font-size: 0.85rem;
      }
      
      /* Adjust hero top padding for fixed header spacing */
      .welcome-hero {
        padding-top: 110px;
      }
    }
    
    @media (max-width: 480px) {
      .welcome-nav-ctas .btn-premium {
        padding: 6px 10px;
        font-size: 0.75rem;
      }
      .welcome-nav-link {
        font-size: 0.9rem;
        padding: 10px 16px;
      }
    }
  </style>
</head>
<body class="welcome-body">

  <!-- ── Dynamic Navbar with Mobile Toggle ── -->
  <header class="welcome-nav">
    <div class="welcome-nav-container">
      <div class="welcome-nav-brand">
        <?= render_site_logo('nav') ?>
      </div>
      
      <!-- Mobile menu toggle button -->
      <button class="mobile-menu-toggle" aria-label="Menu" aria-expanded="false">
        <svg class="menu-icon" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
        <svg class="close-icon" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: none;">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
      
      <nav class="welcome-nav-links">
        <a href="<?= BASE_URL ?>/page.php?slug=about-us" class="welcome-nav-link">About</a>
        <a href="<?= BASE_URL ?>/page.php?slug=viewer-page" class="welcome-nav-link">Watch &amp; Earn Info</a>
        <a href="<?= BASE_URL ?>/page.php?slug=creator-page" class="welcome-nav-link">Creator Program</a>
        <a href="<?= BASE_URL ?>/page.php?slug=terms-conditions" class="welcome-nav-link">Terms</a>
      </nav>
      
      <div class="welcome-nav-ctas">
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn-premium btn-premium-secondary">Sign In</a>
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn-premium btn-premium-primary">Join Free</a>
      </div>
    </div>
  </header>

  <!-- ── Hero Section ── -->
  <section class="welcome-hero">
    <div class="welcome-hero-container">
      <div class="welcome-hero-content">
        <div class="welcome-badge-glow">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Members-Only Access
        </div>
        
        <h1 class="welcome-hero-title">
          Watch Videos, <br>
          Discover Content <br>
          &amp; <span class="grad">Earn Rewards</span>
        </h1>
        
        <p class="welcome-hero-subtitle">
          Join the next-generation digital media network where audience engagement is directly rewarded. Create, upload, watch, and thrive in an inclusive economic ecosystem.
        </p>

        <!-- VPN & AdBlock Notice -->
        <div class="warning-banner">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
          </svg>
          <div>
            <div class="warning-title">Important: AdBlocker &amp; VPN Notice</div>
            <div class="warning-desc">To preserve platform integrity and keep registration 100% free, active use of VPNs, proxies, or AdBlockers is prohibited and will suspend automated watch rewards.</div>
          </div>
        </div>
        
        <div class="welcome-hero-ctas">
          <a href="<?= BASE_URL ?>/auth/register.php" class="btn-premium btn-premium-primary">
            Sign Up Free
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </a>
          <a href="<?= BASE_URL ?>/auth/login.php" class="btn-premium btn-premium-secondary">Login to Account</a>
        </div>
        
        <div class="welcome-stats-row">
          <div class="welcome-stat-item">
            <div class="welcome-stat-num"><?= $users_formatted ?></div>
            <div class="welcome-stat-lbl">Active Members</div>
          </div>
          <div class="welcome-stat-item">
            <div class="welcome-stat-num"><?= $videos_formatted ?></div>
            <div class="welcome-stat-lbl">Published Media</div>
          </div>
          <div class="welcome-stat-item">
            <div class="welcome-stat-num">100%</div>
            <div class="welcome-stat-lbl">Free Registration</div>
          </div>
        </div>
      </div>
      
      <div class="welcome-hero-visual">
        <picture>
          <source srcset="<?= BASE_URL ?>/assets/img/welcome_hero.webp" type="image/webp">
          <img class="welcome-hero-img" src="<?= BASE_URL ?>/assets/img/welcome_hero.png" alt="FreeHub Platform Illustration" width="480" height="480" fetchpriority="high" decoding="async">
        </picture>
      </div>
    </div>
  </section>

  <!-- ── Feature Grid Section ── -->
  <section class="welcome-section">
    <div class="welcome-section-header">
      <span class="welcome-section-tag">Features &amp; Benefits</span>
      <h2 class="welcome-section-title">Designed for Audiences &amp; Creators</h2>
      <p class="welcome-section-desc">We represent a fresh era of digital media distribution. Get paid for your attention, build direct relationships, and unlock private features.</p>
    </div>
    
    <div class="features-grid">
      <!-- 1. Watch & Earn -->
      <div class="feature-card">
        <div class="feature-icon-wrapper">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>
        </div>
        <h3 class="feature-title">Watch &amp; Earn</h3>
        <p class="feature-desc">Discover outstanding videos and shorts. Earn platform reward coins automatically for every minute of authentic viewing, easily convertible to USD.</p>
        <span class="feature-highlight-badge">Viewer Rewards Active</span>
      </div>
      
      <!-- 2. Upload & Earn -->
      <div class="feature-card">
        <div class="feature-icon-wrapper">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
        </div>
        <h3 class="feature-title">Upload &amp; Earn</h3>
        <p class="feature-desc">Create your own branded creator channel. Upload videos and high-impact Reels to generate passive income from view counts and viewer tips.</p>
        <span class="feature-highlight-badge">Creator Program Active</span>
      </div>
      
      <!-- 3. Private Member-Only Content -->
      <div class="feature-card">
        <div class="feature-icon-wrapper">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h3 class="feature-title">Private Content</h3>
        <p class="feature-desc">Keep your feeds exclusive. Non-members cannot search, inspect channels, or view videos, guaranteeing full privacy and a secure premium experience.</p>
        <span class="feature-highlight-badge">Secure Members Gating</span>
      </div>
      
      <!-- 4. Enjoy & Earn -->
      <div class="feature-card">
        <div class="feature-icon-wrapper">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
        </div>
        <h3 class="feature-title">Enjoy &amp; Earn</h3>
        <p class="feature-desc">Enjoy full-length premium shows, movies, and video clips while generating network referrals to gain continuous lifetime commissions.</p>
        <span class="feature-highlight-badge">Multi-Tier Bonuses</span>
      </div>
      
      <!-- 5. Free to Join -->
      <div class="feature-card">
        <div class="feature-icon-wrapper">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <h3 class="feature-title">Free to Join</h3>
        <p class="feature-desc">Enjoy 100% Free Registration! Access all premium layouts immediately with no setup costs, monthly subscriptions, or credit card requirements.</p>
        <span class="feature-highlight-badge" style="background:rgba(99,102,241,0.15);color:#818cf8">100% Free Registration</span>
      </div>

      <!-- 6. Ultra Payouts -->
      <div class="feature-card">
        <div class="feature-icon-wrapper">
          <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <h3 class="feature-title">Guaranteed Withdrawals</h3>
        <p class="feature-desc">Withdrawal payouts are processed securely. Receive viewer earnings and creator splits reliably in your preferred currency within dynamic schedules.</p>
        <span class="feature-highlight-badge">Reliable Transfers</span>
      </div>
    </div>
  </section>

  <!-- ── How It Works Section ── -->
  <section class="welcome-section trust-section" style="padding: 100px 0;">
    <div class="welcome-section-header">
      <span class="welcome-section-tag">Platform Mechanics</span>
      <h2 class="welcome-section-title">How the Platform Works</h2>
      <p class="welcome-section-desc">It takes less than a minute to set up your profile and start participating in the digital creator economy.</p>
    </div>
    
    <div class="how-it-works-grid">
      <div class="step-item">
        <div class="step-num">1</div>
        <h3 class="step-title">Create Account</h3>
        <p class="step-desc">Register for free in 30 seconds. Choose whether you'd like to Watch &amp; Earn or activate a Creator Profile.</p>
      </div>
      <div class="step-item">
        <div class="step-num">2</div>
        <h3 class="step-title">Watch or Upload</h3>
        <p class="step-desc">Explore video/reels feeds or upload your own creative footage. Engagement triggers reward credits seamlessly.</p>
      </div>
      <div class="step-item">
        <div class="step-num">3</div>
        <h3 class="step-title">Redeem Earnings</h3>
        <p class="step-desc">Redeem accrued viewer balances and creator splits via lightning-fast, secure local transfer options.</p>
      </div>
    </div>
  </section>

  <!-- ── Trust & Information Sections ── -->
  <section class="welcome-section">
    <div class="trust-grid">
      <div>
        <span class="welcome-section-tag">Why Join Us</span>
        <h2 class="welcome-section-title" style="text-align: left; margin-bottom: 24px;">Restructuring Modern Media Rights &amp; Payouts</h2>
        <p class="welcome-section-desc" style="text-align: left; margin-bottom: 32px;">
          Traditional networks capture 90% of user data revenue. We challenge this model by returning the primary share of advertising margins directly to viewers and creators.
        </p>
        
        <div class="welcome-hero-ctas" style="margin-bottom: 0;">
          <a href="<?= BASE_URL ?>/auth/register.php" class="btn-premium btn-premium-primary">Join the Network Now</a>
        </div>
      </div>
      
      <div>
        <!-- Info Blocks -->
        <div class="trust-info-block">
          <h3 class="trust-info-title">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polygon points="12 2 2 7 12 12 22 7 12 2"/></svg>
            Advanced Content Discovery
          </h3>
          <p class="trust-info-desc">Our intelligent members-only feeds dynamically filter out spam, presenting high-resolution video streams matched to your individual interests.</p>
        </div>
        
        <div class="trust-info-block">
          <h3 class="trust-info-title">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Creator Opportunities
          </h3>
          <p class="trust-info-desc">Gain competitive CPM splits and monetize your audience. Leverage custom settings to unlock high revenue limits on all vertical Reels and standard video uploads.</p>
        </div>
        
        <div class="trust-info-block">
          <h3 class="trust-info-title">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Direct Viewer Benefits
          </h3>
          <p class="trust-info-desc">We respect your time. Earn from likes, comments, and authentic watch sessions. Get continuous payout credits while browsing through beautiful digital media.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Bottom CTA Banner ── -->
  <section class="welcome-section" style="padding-bottom: 120px;">
    <div class="cta-banner-wrapper">
      <div class="cta-banner-box">
        <h2 class="cta-banner-title">Start Your Free Membership Today</h2>
        <p class="cta-banner-subtitle">Create your account in under a minute to unlock premium video streaming, interactive creator hubs, and passive watch rewards.</p>
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn-premium btn-premium-primary">
          Join Free and Start Exploring Today
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      </div>
    </div>
  </section>

  <!-- ── Minimalist Modern Footer ── -->
  <footer class="welcome-footer">
    <div class="welcome-footer-container">
      <div class="welcome-footer-top">
        <div class="footer-brand">
          <div class="footer-brand-logo">
            <?= render_site_logo('footer') ?>
          </div>
          <p class="footer-brand-desc">
            The next-generation video sharing and discovery network where audiences and content creators thrive together in a unified community.
          </p>
        </div>
        
        <div class="footer-links-col">
          <div class="footer-col-title">Platform</div>
          <a href="<?= BASE_URL ?>/auth/login.php" class="footer-link">Sign In</a>
          <a href="<?= BASE_URL ?>/auth/register.php" class="footer-link">Join Free</a>
          <a href="<?= BASE_URL ?>/page.php?slug=about-us" class="footer-link">About Us</a>
        </div>
        
        <div class="footer-links-col">
          <div class="footer-col-title">Monetization</div>
          <a href="<?= BASE_URL ?>/page.php?slug=viewer-page" class="footer-link">Watch &amp; Earn Info</a>
          <a href="<?= BASE_URL ?>/page.php?slug=creator-page" class="footer-link">Creator Program</a>
        </div>
        
        <div class="footer-links-col">
          <div class="footer-col-title">Legals</div>
          <a href="<?= BASE_URL ?>/page.php?slug=terms-conditions" class="footer-link">Terms of Service</a>
          <a href="<?= BASE_URL ?>/page.php?slug=privacy-policy" class="footer-link">Privacy Policy</a>
          <a href="<?= BASE_URL ?>/page.php?slug=disclaimer" class="footer-link">Disclaimer</a>
        </div>
      </div>
      
      <div class="welcome-footer-bottom">
        <div class="footer-copy">
          &copy; <?= date('Y') ?> <?= e($site_name) ?>. All rights reserved. Built with professional excellence.
        </div>
        
        <div class="footer-bottom-links">
          <a href="<?= BASE_URL ?>/page.php?slug=terms-conditions" class="footer-bottom-link">Terms</a>
          <a href="<?= BASE_URL ?>/page.php?slug=privacy-policy" class="footer-bottom-link">Privacy</a>
          <a href="<?= BASE_URL ?>/page.php?slug=disclaimer" class="footer-bottom-link">Disclaimer</a>
        </div>
      </div>
    </div>
  </footer>

<!-- Deferred non-critical: reveal below-fold sections with fade-in & mobile menu -->
<script>
(function(){
  // Intersection Observer for lazy fade-in of below-fold sections
  if ('IntersectionObserver' in window) {
    var sections = document.querySelectorAll('.welcome-section, .trust-section, .welcome-footer');
    var io = new IntersectionObserver(function(entries) {
      entries.forEach(function(e) {
        if (e.isIntersecting) {
          e.target.style.opacity = '1';
          e.target.style.transform = 'translateY(0)';
          io.unobserve(e.target);
        }
      });
    }, { rootMargin: '100px' });
    sections.forEach(function(s) {
      s.style.opacity = '0';
      s.style.transform = 'translateY(20px)';
      s.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      io.observe(s);
    });
  }

  // Mobile menu toggle
  var toggleBtn = document.querySelector('.mobile-menu-toggle');
  var navLinks = document.querySelector('.welcome-nav-links');
  if (toggleBtn && navLinks) {
    var menuIcon = toggleBtn.querySelector('.menu-icon');
    var closeIcon = toggleBtn.querySelector('.close-icon');
    var toggleMenu = function() {
      var isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
      toggleBtn.setAttribute('aria-expanded', !isExpanded);
      navLinks.classList.toggle('open');
      if (menuIcon && closeIcon) {
        menuIcon.style.display = isExpanded ? 'block' : 'none';
        closeIcon.style.display = isExpanded ? 'none' : 'block';
      }
    };
    toggleBtn.addEventListener('click', toggleMenu);
    // Close menu when a link is clicked
    navLinks.querySelectorAll('.welcome-nav-link').forEach(function(link) {
      link.addEventListener('click', function() {
        if (navLinks.classList.contains('open')) {
          toggleMenu();
        }
      });
    });
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
      if (navLinks.classList.contains('open') && 
          !toggleBtn.contains(e.target) && 
          !navLinks.contains(e.target)) {
        toggleMenu();
      }
    });
  }
})();
</script>
</body>
</html>