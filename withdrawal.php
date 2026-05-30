<?php
// ============================================================
// FreeHub.Live — Multi-Step Withdrawal Wizard
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$preview_uid = (int)($_GET['uid'] ?? 0);
$auth_uid    = (int)auth_user()['id'];
$display_uid = $auth_uid;
$display_role = auth_user()['role'] ?? 'viewer';

if ($preview_uid > 0 && is_admin()) {
    $preview_user = db_fetch("SELECT id, role, preferred_currency FROM users WHERE id=?", [$preview_uid]);
    if ($preview_user) {
        $display_uid  = (int)$preview_user['id'];
        $display_role = $preview_user['role'] ?? 'viewer';
    }
}

$sidebar_role = $display_role;
$uid  = $display_uid;
$user = db_fetch("SELECT * FROM users WHERE id=?", [$uid]);
$currency = $user['preferred_currency'] ?? fh_user_currency();
$minUsd   = fh_min_withdrawal_usd($uid);
$canWithdraw = (float)$user['balance'] >= $minUsd && !fh_pending_withdrawal($uid);

$error   = '';
$success = '';

// ── POST Handler ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'withdraw') {
        $method       = trim($_POST['payment_method'] ?? '');
        $country      = trim($_POST['country'] ?? '');
        $country_code = trim($_POST['country_code'] ?? '');
        $details = trim($_POST['payment_details'] ?? '');
        if (empty($details)) {
            // Build details from all extra fields
            $details_parts = [];
            $extra_fields  = $_POST['extra'] ?? [];
            if (is_array($extra_fields)) {
                foreach ($extra_fields as $k => $v) {
                    $k = trim(strip_tags($k));
                    $v = trim(strip_tags($v));
                    if ($k !== '' && $v !== '') {
                        $details_parts[] = "$k: $v";
                    }
                }
            }
            $details = implode("\n", $details_parts);
        }

        if (!$method) {
            $error = 'Please select a payment method.';
        } elseif (!$country) {
            $error = 'Please select your country.';
        } elseif (strlen($details) < 5) {
            $error = 'Please complete all account details fields.';
        } elseif ((float)$user['balance'] < $minUsd) {
            $error = 'Minimum withdrawal is ' . fh_format_money($minUsd, $currency) . '.';
        } elseif (fh_pending_withdrawal($uid)) {
            $error = 'You already have a pending withdrawal request.';
        } else {
            $amount       = (float)$user['balance'];
            $withdrawalDays = (int)setting('withdrawal_days', '7');
            $dueBy        = date('Y-m-d', strtotime("+$withdrawalDays days"));
            $approvalMode = setting('withdrawal_approval_mode', 'manual');
            $status       = ($approvalMode === 'auto') ? 'approved' : 'pending';

            db_insert('withdrawal_requests', [
                'user_id'        => $uid,
                'amount'         => $amount,
                'currency'       => $currency,
                'payment_method' => $method,
                'payment_details'=> $details,
                'country'        => $country ?: null,
                'status'         => $status,
                'due_by'         => $dueBy,
            ]);

            db_insert('earnings', [
                'user_id'     => $uid,
                'type'        => 'payout',
                'amount'      => $amount,
                'description' => 'Withdrawal request — due by ' . $dueBy,
                'status'      => $status,
            ]);

            db_update('users', ['balance' => 0], 'id=?', [$uid]);
            $_SESSION['user']['balance'] = 0;

            if ($status === 'approved') {
                flash('success', 'Withdrawal request approved and processed automatically.');
            } else {
                flash('success', "✅ Withdrawal request submitted! Payout will be processed within $withdrawalDays business days.");
            }
            redirect(BASE_URL . '/withdrawal.php');
        }
    }
}

// User history
$withdrawals = db_fetchAll(
    "SELECT * FROM withdrawal_requests WHERE user_id=? ORDER BY created_at DESC LIMIT 50",
    [$uid]
);
$pendingWd = fh_pending_withdrawal($uid);

$meta_title = 'Withdrawal Setup & Payout History';
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ── Wizard Container ────────────────────────────────────── */
.wd-wizard-wrap {
  max-width: 680px;
  margin: 0 auto;
}

/* Progress Bar */
.wd-steps-bar {
  display: flex;
  align-items: center;
  margin-bottom: 36px;
  gap: 0;
}
.wd-step-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  position: relative;
}
.wd-step-item:not(:last-child)::after {
  content: '';
  position: absolute;
  top: 18px;
  left: 50%;
  width: 100%;
  height: 2px;
  background: var(--border);
  z-index: 0;
  transition: background 0.4s;
}
.wd-step-item.done:not(:last-child)::after,
.wd-step-item.active:not(:last-child)::after {
  background: linear-gradient(90deg, var(--accent), #7c3aed);
}
.wd-step-bubble {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--bg3);
  border: 2px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 800;
  color: var(--text2);
  position: relative;
  z-index: 1;
  transition: all 0.3s ease;
}
.wd-step-item.active .wd-step-bubble {
  background: linear-gradient(135deg, var(--accent), #7c3aed);
  border-color: var(--accent);
  color: #fff;
  box-shadow: 0 0 20px rgba(99,102,241,0.4);
}
.wd-step-item.done .wd-step-bubble {
  background: var(--green);
  border-color: var(--green);
  color: #fff;
}
.wd-step-label {
  font-size: 0.7rem;
  color: var(--text3);
  margin-top: 6px;
  text-align: center;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}
.wd-step-item.active .wd-step-label { color: var(--accent); }
.wd-step-item.done .wd-step-label   { color: var(--green); }

/* Step Panels */
.wd-step-panel { display: none; }
.wd-step-panel.active { display: block; animation: fadeInUp 0.35s ease; }

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Method Cards */
.wd-method-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 8px;
}
.wd-method-card {
  position: relative;
  border: 2px solid var(--border);
  border-radius: 12px;
  padding: 16px 10px 14px;
  text-align: center;
  cursor: pointer;
  transition: all 0.22s ease;
  background: var(--bg2);
  user-select: none;
}
.wd-method-card:hover {
  border-color: var(--accent);
  background: rgba(99,102,241,0.04);
  transform: translateY(-2px);
}
.wd-method-card.selected {
  border-color: var(--accent);
  background: rgba(99,102,241,0.08);
  box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
}
.wd-method-icon { font-size: 1.8rem; margin-bottom: 6px; display: block; }
.wd-method-name {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--text);
  line-height: 1.2;
}
.wd-method-badge {
  font-size: 0.62rem;
  color: var(--text3);
  margin-top: 3px;
}
.wd-method-card input[type=radio] {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}
.wd-method-check {
  position: absolute;
  top: 8px; right: 8px;
  width: 18px; height: 18px;
  border-radius: 50%;
  background: var(--accent);
  color: #fff;
  font-size: 0.65rem;
  display: none;
  align-items: center;
  justify-content: center;
}
.wd-method-card.selected .wd-method-check { display: flex; }

/* Country Select */
.wd-country-search-wrap { position: relative; margin-bottom: 12px; }
.wd-country-search-wrap .form-input { padding-left: 40px; }
.wd-country-search-icon {
  position: absolute;
  left: 12px; top: 50%;
  transform: translateY(-50%);
  color: var(--text3);
  font-size: 1rem;
  pointer-events: none;
}
.wd-country-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 8px;
  max-height: 280px;
  overflow-y: auto;
  padding: 4px 2px;
  scrollbar-width: thin;
}
.wd-country-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 12px;
  border: 1.5px solid var(--border);
  border-radius: 9px;
  background: var(--bg2);
  cursor: pointer;
  transition: all 0.18s;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text);
  text-align: left;
}
.wd-country-btn:hover {
  border-color: var(--accent);
  background: rgba(99,102,241,0.05);
}
.wd-country-btn.selected {
  border-color: var(--accent);
  background: rgba(99,102,241,0.1);
  color: var(--accent);
}
.wd-flag { font-size: 1.1rem; flex-shrink: 0; }

/* Account Details */
.wd-field-group { margin-bottom: 18px; }
.wd-field-group label { display: block; font-size: 0.83rem; font-weight: 700; margin-bottom: 6px; color: var(--text); }
.wd-field-group .wd-field-hint { font-size: 0.74rem; color: var(--text3); margin-top: 4px; }

/* Review Step */
.wd-review-card {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 14px;
  overflow: hidden;
  margin-bottom: 20px;
}
.wd-review-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 13px 20px;
  border-bottom: 1px solid rgba(255,255,255,0.03);
  gap: 12px;
}
.wd-review-row:last-child { border-bottom: none; }
.wd-review-lbl {
  font-size: 0.8rem;
  color: var(--text2);
  font-weight: 600;
  flex-shrink: 0;
  min-width: 130px;
}
.wd-review-val {
  font-size: 0.85rem;
  color: #fff;
  font-weight: 700;
  text-align: right;
  word-break: break-word;
  flex: 1;
}
.wd-amount-highlight {
  font-size: 1.5rem;
  font-weight: 900;
  background: linear-gradient(135deg, #34d399, #10b981);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* Navigation Buttons */
.wd-nav-btns { display: flex; gap: 12px; margin-top: 24px; }
.wd-back-btn {
  flex: 0 0 auto;
  padding: 12px 22px;
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  color: var(--text);
  transition: all 0.2s;
}
.wd-back-btn:hover { background: var(--bg2); border-color: var(--accent); }
.wd-next-btn {
  flex: 1;
  padding: 13px;
  background: linear-gradient(135deg, var(--accent), #7c3aed);
  border: none;
  border-radius: 10px;
  font-weight: 800;
  font-size: 0.95rem;
  cursor: pointer;
  color: #fff;
  transition: all 0.22s;
  box-shadow: 0 4px 16px rgba(99,102,241,0.25);
}
.wd-next-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(99,102,241,0.35);
}
.wd-next-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
.wd-step-heading {
  font-family: var(--font2);
  font-weight: 800;
  font-size: 1.1rem;
  margin-bottom: 6px;
  color: #fff;
}
.wd-step-sub {
  font-size: 0.83rem;
  color: var(--text2);
  margin-bottom: 22px;
  line-height: 1.5;
}

/* Modal Popup */
.custom-modal-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(6px);
  z-index: 1100;
  align-items: center;
  justify-content: center;
  padding: 16px;
}
.custom-modal-backdrop.open { display: flex; }
.custom-modal-content {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 16px;
  width: 100%;
  max-width: 560px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.5);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.custom-modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.custom-modal-header h3 {
  font-family: var(--font2);
  font-size: 1.1rem;
  font-weight: 800;
  margin: 0;
  color: #fff;
}
.custom-modal-close {
  font-size: 1.5rem;
  color: var(--text2);
  cursor: pointer;
  border: none;
  background: none;
  transition: color 0.15s;
}
.custom-modal-close:hover { color: #fff; }
.custom-modal-body {
  padding: 24px;
  overflow-y: auto;
  max-height: 80vh;
}
.modal-detail-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid rgba(255,255,255,0.04);
}
.modal-detail-row:last-child { border-bottom: none; }
.modal-detail-lbl { font-size: 0.82rem; color: var(--text2); font-weight: 600; }
.modal-detail-val {
  font-size: 0.85rem;
  color: #fff;
  font-weight: 700;
  text-align: right;
  word-break: break-word;
  max-width: 62%;
}
.modal-proof-container {
  margin-top: 18px;
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 14px;
  text-align: center;
}
.modal-proof-img {
  max-width: 100%;
  max-height: 260px;
  border-radius: 6px;
  margin-top: 8px;
  object-fit: contain;
  border: 1px solid var(--border);
}

/* Threshold progress */
.wd-progress-bar {
  height: 10px;
  background: var(--bg3);
  border-radius: 8px;
  overflow: hidden;
  margin: 10px 0 8px;
}
.wd-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--accent), #a855f7);
  border-radius: 8px;
  transition: width 0.6s ease;
}

@media (max-width: 480px) {
  .wd-method-grid { grid-template-columns: repeat(2, 1fr); }
  .wd-country-list { grid-template-columns: 1fr 1fr; }
  .wd-review-lbl { min-width: 110px; }
}
</style>

<div class="container" style="max-width:960px; margin:24px auto; padding:0 16px">

  <?php foreach (get_flash() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>" style="border-radius:10px; margin-bottom:16px"><?= e($f['msg']) ?></div>
  <?php endforeach; ?>
  <?php if ($error): ?>
    <div class="alert alert-error" style="border-radius:10px; margin-bottom:16px"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!is_admin()): ?>

  <!-- ── Withdrawal Wizard ─────────────────────────────────── -->
  <div class="card" style="margin-bottom:24px; border-radius:16px; padding:28px 24px">

    <?php if ($pendingWd): ?>
      <!-- Pending notice -->
      <div style="background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.25); padding:18px 20px; border-radius:12px; display:flex; align-items:flex-start; gap:14px">
        <span style="font-size:1.6rem; flex-shrink:0">⏳</span>
        <div>
          <div style="font-weight:700; color:var(--yellow); font-size:0.92rem; margin-bottom:4px">Pending withdrawal in progress</div>
          <div class="text-sm" style="color:var(--text2); line-height:1.5">
            Your request of <strong><?= fh_format_money((float)$pendingWd['amount'], $pendingWd['currency']) ?></strong> is currently under review and will be processed within <strong><?= e(setting('withdrawal_days', '7')) ?> business days</strong>.
          </div>
        </div>
      </div>

    <?php elseif ($canWithdraw): ?>
      <!-- ── Multi-Step Wizard ─────────────────────────────── -->
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; flex-wrap:wrap; gap:12px">
        <div>
          <h3 style="font-family:var(--font2); font-weight:800; font-size:1.2rem; margin:0 0 4px">💳 Request Payout</h3>
          <div class="text-sm" style="color:var(--text2)">Your balance: <strong style="color:var(--green)"><?= fh_format_money((float)$user['balance'], $currency) ?></strong></div>
        </div>
        <div class="badge badge-green" style="font-size:0.8rem; padding:6px 14px">✅ Threshold Met</div>
      </div>

      <!-- Step Progress Bar -->
      <div class="wd-steps-bar" id="wizardStepsBar">
        <div class="wd-step-item active" id="stepItem1">
          <div class="wd-step-bubble">1</div>
          <div class="wd-step-label">Method</div>
        </div>
        <div class="wd-step-item" id="stepItem2">
          <div class="wd-step-bubble">2</div>
          <div class="wd-step-label">Country</div>
        </div>
        <div class="wd-step-item" id="stepItem3">
          <div class="wd-step-bubble">3</div>
          <div class="wd-step-label">Details</div>
        </div>
        <div class="wd-step-item" id="stepItem4">
          <div class="wd-step-bubble">4</div>
          <div class="wd-step-label">Confirm</div>
        </div>
      </div>

      <form id="wdForm" method="POST">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="withdraw">
        <input type="hidden" name="payment_method" id="fld_method">
        <input type="hidden" name="country" id="fld_country">
        <input type="hidden" name="country_code" id="fld_country_code">
        <input type="hidden" name="payment_details" id="fld_payment_details">

        <!-- ══ Step 1: Payment Method ══════════════════════════ -->
        <div class="wd-step-panel active" id="panel1">
          <div class="wd-step-heading">Select Payment Method</div>
          <div class="wd-step-sub">Choose how you'd like to receive your payout. Available options depend on your country.</div>

          <div class="wd-method-grid">
            <label class="wd-method-card" data-method="bank_transfer" onclick="selectMethod(this,'bank_transfer')">
              <input type="radio" name="_method_ui" value="bank_transfer">
              <span class="wd-step-check" style="display:none"></span>
              <span class="wd-method-check">✓</span>
              <span class="wd-method-icon">🏦</span>
              <div class="wd-method-name">Bank Transfer</div>
              <div class="wd-method-badge">SWIFT / IBAN</div>
            </label>
            <label class="wd-method-card" data-method="jazzcash" onclick="selectMethod(this,'jazzcash')">
              <input type="radio" name="_method_ui" value="jazzcash">
              <span class="wd-method-check">✓</span>
              <span class="wd-method-icon">📱</span>
              <div class="wd-method-name">JazzCash</div>
              <div class="wd-method-badge">Pakistan</div>
            </label>
            <label class="wd-method-card" data-method="easypaisa" onclick="selectMethod(this,'easypaisa')">
              <input type="radio" name="_method_ui" value="easypaisa">
              <span class="wd-method-check">✓</span>
              <span class="wd-method-icon">💚</span>
              <div class="wd-method-name">Easypaisa</div>
              <div class="wd-method-badge">Pakistan</div>
            </label>
            <label class="wd-method-card" data-method="paypal" onclick="selectMethod(this,'paypal')">
              <input type="radio" name="_method_ui" value="paypal">
              <span class="wd-method-check">✓</span>
              <span class="wd-method-icon">🅿️</span>
              <div class="wd-method-name">PayPal</div>
              <div class="wd-method-badge">Worldwide</div>
            </label>
            <label class="wd-method-card" data-method="wise" onclick="selectMethod(this,'wise')">
              <input type="radio" name="_method_ui" value="wise">
              <span class="wd-method-check">✓</span>
              <span class="wd-method-icon">🌍</span>
              <div class="wd-method-name">Wise</div>
              <div class="wd-method-badge">International</div>
            </label>
            <label class="wd-method-card" data-method="upi" onclick="selectMethod(this,'upi')">
              <input type="radio" name="_method_ui" value="upi">
              <span class="wd-method-check">✓</span>
              <span class="wd-method-icon">🇮🇳</span>
              <div class="wd-method-name">UPI</div>
              <div class="wd-method-badge">India</div>
            </label>
            <label class="wd-method-card" data-method="crypto" onclick="selectMethod(this,'crypto')">
              <input type="radio" name="_method_ui" value="crypto">
              <span class="wd-method-check">✓</span>
              <span class="wd-method-icon">₿</span>
              <div class="wd-method-name">Crypto</div>
              <div class="wd-method-badge">USDT / BTC</div>
            </label>
            <label class="wd-method-card" data-method="other" onclick="selectMethod(this,'other')">
              <input type="radio" name="_method_ui" value="other">
              <span class="wd-method-check">✓</span>
              <span class="wd-method-icon">💬</span>
              <div class="wd-method-name">Other</div>
              <div class="wd-method-badge">Custom</div>
            </label>
          </div>

          <div class="wd-nav-btns">
            <button type="button" class="wd-next-btn" id="btn1Next" onclick="goStep(2)" disabled>
              Continue to Country → 
            </button>
          </div>
        </div>

        <!-- ══ Step 2: Country ══════════════════════════════════ -->
        <div class="wd-step-panel" id="panel2">
          <div class="wd-step-heading">Select Your Country</div>
          <div class="wd-step-sub">Choose your country so we can show the correct payment fields for your region.</div>

          <div class="wd-country-search-wrap">
            <span class="wd-country-search-icon">🔍</span>
            <input type="text" id="countrySearch" class="form-input" placeholder="Search country…" oninput="filterCountries()" style="border-radius:9px; padding-left:40px">
          </div>

          <div class="wd-country-list" id="countryList">
            <!-- Rendered by JS from countryData array -->
          </div>

          <div class="wd-nav-btns">
            <button type="button" class="wd-back-btn" onclick="goStep(1)">← Back</button>
            <button type="button" class="wd-next-btn" id="btn2Next" onclick="goStep(3)" disabled>Continue to Details →</button>
          </div>
        </div>

        <!-- ══ Step 3: Account Details ═══════════════════════════ -->
        <div class="wd-step-panel" id="panel3">
          <div class="wd-step-heading">Enter Account Details</div>
          <div class="wd-step-sub" id="step3Sub">Fill in your payment account information below. These details will be used to send your payout.</div>

          <div id="dynamicFields">
            <!-- Rendered dynamically by JS based on method + country -->
          </div>

          <div class="wd-nav-btns">
            <button type="button" class="wd-back-btn" onclick="goStep(2)">← Back</button>
            <button type="button" class="wd-next-btn" id="btn3Next" onclick="validateDetailsAndNext()">Review Payout →</button>
          </div>
        </div>

        <!-- ══ Step 4: Review & Confirm ════════════════════════════ -->
        <div class="wd-step-panel" id="panel4">
          <div class="wd-step-heading">Review & Confirm Payout</div>
          <div class="wd-step-sub">Please verify all details below before submitting. Once submitted, your full balance will be locked for processing.</div>

          <div class="wd-review-card" id="reviewSummary">
            <!-- Populated by JS -->
          </div>

          <div style="background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.2); border-radius:10px; padding:14px 16px; margin-bottom:6px">
            <div style="font-size:0.8rem; color:var(--yellow); font-weight:700; margin-bottom:4px">⚠️ Important</div>
            <div style="font-size:0.78rem; color:var(--text2); line-height:1.5">
              Your entire available balance of <strong id="reviewBalanceNote"><?= fh_format_money((float)$user['balance'], $currency) ?></strong> will be requested. 
              A pending request locks your balance until processed by admin (within <?= (int)setting('withdrawal_days', '7') ?> business days).
            </div>
          </div>

          <div class="wd-nav-btns">
            <button type="button" class="wd-back-btn" onclick="goStep(3)">← Back</button>
            <button type="submit" class="wd-next-btn" id="btnSubmit" style="background:linear-gradient(135deg,#10b981,#059669)">
              📥 Submit Withdrawal Request
            </button>
          </div>
        </div>
      </form>

    <?php else: ?>
      <!-- Not yet reached threshold -->
      <h3 style="font-family:var(--font2); font-weight:800; font-size:1.2rem; margin:0 0 16px">💰 Payout Progress</h3>
      <div style="display:flex; justify-content:space-between; font-size:0.84rem; font-weight:700; margin-bottom:6px">
        <span class="text-muted">Threshold Progress</span>
        <span><?= fh_format_money((float)$user['balance'], $currency) ?> / <?= fh_format_money($minUsd, $currency) ?></span>
      </div>
      <div class="wd-progress-bar">
        <div class="wd-progress-fill" style="width:<?= $minUsd > 0 ? min(100, round(((float)$user['balance'] / $minUsd) * 100)) : 0 ?>%"></div>
      </div>
      <p class="text-sm text-muted" style="margin-top:10px; line-height:1.5">
        Once your earned balance reaches the minimum threshold of <strong><?= fh_format_money($minUsd, $currency) ?></strong>, the payout wizard will automatically unlock. Keep watching or uploading videos to grow your balance!
      </p>
    <?php endif; ?>

  </div>

  <!-- ── Withdrawal History ─────────────────────────────────── -->
  <div class="card" style="border-radius:16px; padding:24px">
    <h3 style="font-family:var(--font2); font-weight:800; font-size:1.2rem; margin-bottom:18px">🕐 Payout Transaction History</h3>
    <?php if ($withdrawals): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Status</th>
            <th>Timeline</th>
            <th>Date</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($withdrawals as $w):
          $modalData = htmlspecialchars(json_encode([
            'id'             => $w['id'],
            'username'       => auth_user()['username'],
            'channel_name'   => auth_user()['channel_name'] ?? auth_user()['username'],
            'role'           => auth_user()['role'],
            'amount'         => '$' . number_format((float)$w['amount'], 2) . ' ' . $w['currency'],
            'payment_method' => ucfirst(str_replace('_', ' ', $w['payment_method'])),
            'payment_details'=> $w['payment_details'],
            'country'        => $w['country'] ?? '—',
            'status'         => ucfirst($w['status']),
            'due_by'         => $w['due_by'] ?? '—',
            'admin_note'     => $w['admin_note'] ?? '',
            'payment_proof'  => $w['payment_proof'] ? BASE_URL . '/uploads/proofs/' . $w['payment_proof'] : null,
            'created_at'     => date('M j, Y H:i', strtotime($w['created_at'])),
          ]), ENT_QUOTES, 'UTF-8');
        ?>
        <tr style="cursor:pointer" onclick="showWithdrawalDetails(<?= $modalData ?>)">
          <td><strong style="color:var(--accent)">#<?= $w['id'] ?></strong></td>
          <td style="font-weight:700; color:var(--green)"><?= fh_format_money((float)$w['amount'], $w['currency']) ?></td>
          <td class="text-sm"><?= ucfirst(str_replace('_', ' ', $w['payment_method'])) ?></td>
          <td>
            <span class="badge badge-<?= $w['status']==='paid'?'green':($w['status']==='pending'?'yellow':($w['status']==='processing'?'blue':'gray')) ?>">
              <?= e($w['status']) ?>
            </span>
          </td>
          <td class="text-xs text-muted"><?= e($w['due_by'] ?? '—') ?></td>
          <td class="text-xs text-muted"><?= date('M j, Y', strtotime($w['created_at'])) ?></td>
          <td>
            <button type="button" class="btn btn-xs btn-outline" style="border-radius:6px" onclick="showWithdrawalDetails(<?= $modalData ?>);event.stopPropagation()">🔍 View</button>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p class="text-sm text-muted" style="margin:0; padding:8px 0">No withdrawal history yet. Earned balances will appear here for transparent tracking.</p>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <!-- Admin redirect block -->
  <div class="card" style="border-radius:14px; text-align:center; padding:40px 24px">
    <span style="font-size:2.5rem; display:block; margin-bottom:12px">💼</span>
    <h3 style="font-family:var(--font2); font-weight:800; font-size:1.3rem; margin-bottom:6px">Administrator Access</h3>
    <p class="text-sm text-muted" style="margin-bottom:20px; max-width:480px; margin:0 auto 20px">
      Withdrawal pipelines, receipt uploads, admin notes, and approvals are managed in the administrator portal.
    </p>
    <a href="<?= BASE_URL ?>/admin/withdrawals.php" class="btn btn-primary" style="font-weight:700; border-radius:8px">⚙️ Go to Admin Payouts Panel</a>
  </div>
  <?php endif; ?>

</div><!-- /container -->

<!-- ── Withdrawal Detail Modal ───────────────────────────── -->
<div id="wd-detail-modal" class="custom-modal-backdrop" onclick="closeModalOnBackdrop(event)">
  <div class="custom-modal-content fade-in">
    <div class="custom-modal-header">
      <h3>Withdrawal Request Details</h3>
      <button type="button" class="custom-modal-close" onclick="closeWithdrawalModal()">&times;</button>
    </div>
    <div class="custom-modal-body" id="modal-body-content"></div>
  </div>
</div>

<script>
// ══════════════════════════════════════════════════════════════
// Multi-Step Withdrawal Wizard — JavaScript Engine
// ══════════════════════════════════════════════════════════════

let wizardData = {
  method: '',
  methodLabel: '',
  country: '',
  countryCode: '',
  countryFlag: '',
  details: {},        // { label: value }
  currentStep: 1
};

// ── Countries Dataset ────────────────────────────────────────
const COUNTRIES = [
  { code:'PK', name:'Pakistan',       flag:'🇵🇰' },
  { code:'IN', name:'India',          flag:'🇮🇳' },
  { code:'US', name:'United States',  flag:'🇺🇸' },
  { code:'GB', name:'United Kingdom', flag:'🇬🇧' },
  { code:'CA', name:'Canada',         flag:'🇨🇦' },
  { code:'AU', name:'Australia',      flag:'🇦🇺' },
  { code:'DE', name:'Germany',        flag:'🇩🇪' },
  { code:'FR', name:'France',         flag:'🇫🇷' },
  { code:'AE', name:'UAE',            flag:'🇦🇪' },
  { code:'SA', name:'Saudi Arabia',   flag:'🇸🇦' },
  { code:'BD', name:'Bangladesh',     flag:'🇧🇩' },
  { code:'NG', name:'Nigeria',        flag:'🇳🇬' },
  { code:'EG', name:'Egypt',          flag:'🇪🇬' },
  { code:'TR', name:'Turkey',         flag:'🇹🇷' },
  { code:'BR', name:'Brazil',         flag:'🇧🇷' },
  { code:'MX', name:'Mexico',         flag:'🇲🇽' },
  { code:'ID', name:'Indonesia',      flag:'🇮🇩' },
  { code:'PH', name:'Philippines',    flag:'🇵🇭' },
  { code:'KE', name:'Kenya',          flag:'🇰🇪' },
  { code:'GH', name:'Ghana',          flag:'🇬🇭' },
  { code:'ZA', name:'South Africa',   flag:'🇿🇦' },
  { code:'MY', name:'Malaysia',       flag:'🇲🇾' },
  { code:'SG', name:'Singapore',      flag:'🇸🇬' },
  { code:'NP', name:'Nepal',          flag:'🇳🇵' },
  { code:'LK', name:'Sri Lanka',      flag:'🇱🇰' },
  { code:'AF', name:'Afghanistan',    flag:'🇦🇫' },
  { code:'OTHER', name:'Other Country', flag:'🌍' },
];

// ── Payment Method Definitions ───────────────────────────────
const METHOD_LABELS = {
  bank_transfer: 'Bank Transfer',
  jazzcash:      'JazzCash',
  easypaisa:     'Easypaisa',
  paypal:        'PayPal',
  wise:          'Wise',
  upi:           'UPI',
  crypto:        'Crypto',
  other:         'Other',
};

// Fields schema: method + country → [{id, label, placeholder, type, hint, required}]
function getFieldSchema(method, countryCode) {
  const base = [];

  if (method === 'bank_transfer') {
    base.push(
      { id:'account_name',   label:'Account Holder Name', placeholder:'Full name on bank account', type:'text', required:true },
      { id:'account_number', label:'Account / IBAN Number', placeholder:'e.g. PK36MUCB0010010123456789', type:'text', required:true },
      { id:'bank_name',      label:'Bank Name', placeholder:'e.g. HBL, MCB, Meezan Bank', type:'text', required:true },
    );
    if (['PK','BD','IN','NG','EG'].includes(countryCode)) {
      base.push({ id:'branch_code', label:'Branch Code (optional)', placeholder:'e.g. 0250', type:'text', required:false });
    } else {
      base.push(
        { id:'swift_bic', label:'SWIFT / BIC Code', placeholder:'e.g. MUCBPKKA', type:'text', required:true },
        { id:'routing',   label:'Routing / Sort Code (if applicable)', placeholder:'e.g. 026009593', type:'text', required:false }
      );
    }
    if (['PK','BD'].includes(countryCode)) {
      base.push({ id:'cnic',  label:'CNIC / NID (optional)', placeholder:'e.g. 12345-1234567-1', type:'text', required:false });
    }
    base.push({ id:'country_city', label:'Country & City', placeholder:'e.g. Pakistan, Karachi', type:'text', required:true });
  }

  else if (method === 'jazzcash') {
    base.push(
      { id:'mobile_number', label:'JazzCash Mobile Number', placeholder:'03XX-XXXXXXX', type:'tel', required:true, hint:'Must be a registered JazzCash account.' },
      { id:'account_name',  label:'Account Holder Name', placeholder:'Name on JazzCash account', type:'text', required:true },
    );
  }

  else if (method === 'easypaisa') {
    base.push(
      { id:'mobile_number', label:'Easypaisa Mobile Number', placeholder:'03XX-XXXXXXX', type:'tel', required:true, hint:'Must be a registered Easypaisa account.' },
      { id:'account_name',  label:'Account Holder Name', placeholder:'Name on Easypaisa account', type:'text', required:true },
    );
  }

  else if (method === 'paypal') {
    base.push(
      { id:'paypal_email', label:'PayPal Email Address', placeholder:'your@email.com', type:'email', required:true, hint:'Send-to PayPal email — must be a verified account.' },
    );
  }

  else if (method === 'wise') {
    base.push(
      { id:'wise_email',  label:'Wise Email / Account ID', placeholder:'your@email.com', type:'email', required:true },
      { id:'wise_name',   label:'Full Name on Wise', placeholder:'As shown in your Wise account', type:'text', required:true },
      { id:'wise_currency', label:'Preferred Payout Currency', placeholder:'e.g. USD, GBP, EUR, PKR', type:'text', required:true },
    );
  }

  else if (method === 'upi') {
    base.push(
      { id:'upi_id',    label:'UPI ID / VPA', placeholder:'yourname@upi or phone@bank', type:'text', required:true, hint:'e.g. 9876543210@ybl or name@okicici' },
      { id:'upi_name',  label:'Account Holder Name', placeholder:'Name linked with UPI', type:'text', required:true },
    );
  }

  else if (method === 'crypto') {
    base.push(
      { id:'crypto_currency', label:'Cryptocurrency', placeholder:'e.g. USDT (TRC20), Bitcoin, Ethereum', type:'text', required:true },
      { id:'wallet_address',  label:'Wallet Address', placeholder:'Your crypto wallet address', type:'text', required:true, hint:'Double-check the address — transactions are irreversible.' },
      { id:'network',         label:'Network / Chain', placeholder:'e.g. TRC20, ERC20, BEP20', type:'text', required:true },
    );
  }

  else if (method === 'other') {
    base.push(
      { id:'method_name',   label:'Payment Method Name', placeholder:'e.g. Western Union, MoneyGram', type:'text', required:true },
      { id:'account_info',  label:'Account / Transfer Details', placeholder:'Provide all necessary details for transfer', type:'textarea', required:true },
    );
  }

  return base;
}

// ── Wizard Navigation ────────────────────────────────────────
function goStep(step) {
  const panels    = document.querySelectorAll('.wd-step-panel');
  const stepItems = document.querySelectorAll('.wd-step-item');

  panels.forEach((p, i) => {
    p.classList.remove('active');
    if (i + 1 === step) p.classList.add('active');
  });

  stepItems.forEach((s, i) => {
    s.classList.remove('active', 'done');
    if (i + 1 < step) s.classList.add('done');
    if (i + 1 === step) s.classList.add('active');
  });

  // Step 2: render countries
  if (step === 2) renderCountries('');
  // Step 3: render dynamic fields
  if (step === 3) renderDynamicFields();
  // Step 4: render review
  if (step === 4) renderReview();

  wizardData.currentStep = step;
  window.scrollTo({ top: document.getElementById('wdForm').offsetTop - 80, behavior: 'smooth' });
}

// ── Step 1: Method Selection ─────────────────────────────────
function selectMethod(card, method) {
  document.querySelectorAll('.wd-method-card').forEach(c => c.classList.remove('selected'));
  card.classList.add('selected');
  wizardData.method = method;
  wizardData.methodLabel = METHOD_LABELS[method] || method;
  document.getElementById('fld_method').value = method;
  document.getElementById('btn1Next').disabled = false;
}

// ── Step 2: Country ─────────────────────────────────────────
function renderCountries(filter) {
  const list = document.getElementById('countryList');
  const q    = filter.toLowerCase();
  list.innerHTML = COUNTRIES
    .filter(c => c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q))
    .map(c => {
      const sel = wizardData.countryCode === c.code ? 'selected' : '';
      // Use data-* attributes to avoid any quote-escaping issues in onclick
      return `<button type="button"
        class="wd-country-btn ${sel}"
        data-code="${c.code}"
        data-name="${c.name.replace(/"/g, '&quot;')}"
        data-flag="${c.flag}">
        <span class="wd-flag">${c.flag}</span> ${c.name}
      </button>`;
    })
    .join('');
}

function filterCountries() {
  renderCountries(document.getElementById('countrySearch').value);
}

// Event delegation — handles clicks on any country button safely
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.wd-country-btn');
  if (!btn || !document.getElementById('countryList').contains(btn)) return;
  document.querySelectorAll('.wd-country-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  wizardData.country     = btn.dataset.name;
  wizardData.countryCode = btn.dataset.code;
  wizardData.countryFlag = btn.dataset.flag;
  document.getElementById('fld_country').value      = btn.dataset.name;
  document.getElementById('fld_country_code').value = btn.dataset.code;
  document.getElementById('btn2Next').disabled       = false;
});

// ── Step 3: Dynamic Fields ────────────────────────────────────
function renderDynamicFields() {
  const container = document.getElementById('dynamicFields');
  const schema    = getFieldSchema(wizardData.method, wizardData.countryCode);
  const methodIconMap = {
    bank_transfer:'🏦', jazzcash:'📱', easypaisa:'💚', paypal:'🅿️',
    wise:'🌍', upi:'🇮🇳', crypto:'₿', other:'💬'
  };
  const icon = methodIconMap[wizardData.method] || '💳';

  document.getElementById('step3Sub').textContent =
    `Enter your ${wizardData.methodLabel} account details for ${wizardData.countryFlag} ${wizardData.country}.`;

  container.innerHTML = schema.map(f => {
    const savedVal = wizardData.details[f.label] || '';
    const inputEl = f.type === 'textarea'
      ? `<textarea class="form-input" name="extra[${f.id}]" id="df_${f.id}" rows="3" ${f.required?'required':''} placeholder="${f.placeholder}" style="border-radius:8px; resize:vertical">${savedVal}</textarea>`
      : `<input class="form-input" type="${f.type}" name="extra[${f.id}]" id="df_${f.id}" ${f.required?'required':''} placeholder="${f.placeholder}" value="${savedVal}" style="border-radius:8px">`;
    return `
      <div class="wd-field-group">
        <label for="df_${f.id}">${f.label}${f.required?' <span style="color:#f87171">*</span>':''}</label>
        ${inputEl}
        ${f.hint ? `<div class="wd-field-hint">ℹ️ ${f.hint}</div>` : ''}
      </div>`;
  }).join('');
}

function validateDetailsAndNext() {
  const schema = getFieldSchema(wizardData.method, wizardData.countryCode);
  const details = {};
  let valid = true;
  let formattedParts = [];

  for (const f of schema) {
    const el = document.getElementById('df_' + f.id);
    if (!el) continue;
    const val = el.value.trim();
    if (f.required && val === '') {
      el.style.borderColor = '#f87171';
      el.focus();
      valid = false;
      break;
    }
    el.style.borderColor = '';
    if (val !== '') {
      details[f.label] = val;
      formattedParts.push(`${f.label}: ${val}`);
    }
  }

  if (valid) {
    wizardData.details = details;
    const hiddenField = document.getElementById('fld_payment_details');
    if (hiddenField) {
      hiddenField.value = formattedParts.join('\n');
    }
    goStep(4);
  }
}

// ── Step 4: Review ───────────────────────────────────────────
function renderReview() {
  const el = document.getElementById('reviewSummary');
  const amount = document.getElementById('reviewBalanceNote')?.textContent || '';

  let detailsHtml = Object.entries(wizardData.details).map(([lbl, val]) => `
    <div class="wd-review-row">
      <span class="wd-review-lbl">${lbl}</span>
      <span class="wd-review-val" style="font-family:monospace; font-size:0.82rem">${val}</span>
    </div>`).join('');

  el.innerHTML = `
    <div class="wd-review-row" style="background:rgba(16,185,129,0.04)">
      <span class="wd-review-lbl" style="font-size:0.85rem; font-weight:800">Payout Amount</span>
      <span class="wd-amount-highlight"><?= fh_format_money((float)$user['balance'], $currency) ?></span>
    </div>
    <div class="wd-review-row">
      <span class="wd-review-lbl">Payment Method</span>
      <span class="wd-review-val">${wizardData.methodLabel}</span>
    </div>
    <div class="wd-review-row">
      <span class="wd-review-lbl">Country</span>
      <span class="wd-review-val">${wizardData.countryFlag} ${wizardData.country}</span>
    </div>
    ${detailsHtml}
  `;
}

// ── Details Modal ─────────────────────────────────────────────
function showWithdrawalDetails(data) {
  const body = document.getElementById('modal-body-content');
  
  const statusLower = (data.status || '').toLowerCase();
  const badgeClass = statusLower === 'paid' ? 'green' : (statusLower === 'pending' ? 'yellow' : (statusLower === 'processing' ? 'blue' : 'gray'));
  
  let proofHtml = '';
  if (data.payment_proof) {
    const isPdf = data.payment_proof.toLowerCase().endsWith('.pdf');
    proofHtml = isPdf
      ? `<div class="modal-proof-container">
          <div style="font-weight:700;font-size:0.8rem;color:var(--text2);margin-bottom:6px;">📎 Payout Receipt (PDF Document)</div>
          <a href="${data.payment_proof}" target="_blank" class="btn btn-sm btn-outline" style="margin-top:8px;display:inline-flex;align-items:center;gap:6px">📥 Download PDF Receipt</a>
         </div>`
      : `<div class="modal-proof-container">
          <div style="font-weight:700;font-size:0.8rem;color:var(--text2);margin-bottom:8px">📸 Payout Receipt Proof</div>
          <a href="${data.payment_proof}" target="_blank"><img src="${data.payment_proof}" class="modal-proof-img" alt="Payment Proof"></a>
          <div style="font-size:0.7rem;color:var(--text3);margin-top:6px">Click image to inspect or download</div>
         </div>`;
  } else if (statusLower === 'paid') {
    proofHtml = `<div class="modal-proof-container" style="border-style:dashed;background:transparent"><div style="font-size:0.8rem;color:var(--text3)">No receipt uploaded by Administrator.</div></div>`;
  }

  // Format details beautifully as a structured grid/table
  const detailLines = (data.payment_details || '').split('\n').filter(l => l.trim());
  let detailHtml = '';
  if (detailLines.length > 0) {
    detailHtml = '<div style="display:flex; flex-direction:column; gap:8px; width:100%;">';
    detailLines.forEach(l => {
      const idx = l.indexOf(':');
      if (idx !== -1) {
        const lbl = l.substring(0, idx).trim();
        const val = l.substring(idx + 1).trim();
        detailHtml += `
          <div style="display:flex; justify-content:space-between; font-size:0.8rem; border-bottom:1px solid rgba(255,255,255,0.03); padding-bottom:6px;">
            <span style="color:var(--text2); font-weight:600;">${lbl}</span>
            <span style="color:#fff; font-weight:700; font-family:monospace; word-break:break-all; max-width:65%; text-align:right;">${val}</span>
          </div>`;
      } else {
        detailHtml += `
          <div style="font-size:0.8rem; font-family:monospace; color:#fff; word-break:break-all; border-bottom:1px solid rgba(255,255,255,0.03); padding-bottom:6px;">
            ${l}
          </div>`;
      }
    });
    detailHtml += '</div>';
  } else {
    detailHtml = `<span style="font-family:monospace; font-size:0.8rem; color:var(--text3)">No details provided</span>`;
  }

  body.innerHTML = `
    <div class="modal-detail-row"><span class="modal-detail-lbl">Request ID</span><span class="modal-detail-val">#${data.id}</span></div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Member</span><span class="modal-detail-val">${data.channel_name} (@${data.username})</span></div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Role</span><span class="modal-detail-val">${data.role.toUpperCase()}</span></div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Amount</span><span class="modal-detail-val" style="color:var(--green)">${data.amount}</span></div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Method</span><span class="modal-detail-val">${data.payment_method}</span></div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Country</span><span class="modal-detail-val">${data.country}</span></div>
    <div class="modal-detail-row" style="flex-direction:column;gap:6px;align-items:stretch">
      <span class="modal-detail-lbl" style="margin-bottom:2px">Account Details</span>
      <div style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:12px 14px">${detailHtml}</div>
    </div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Status</span><span class="modal-detail-val">
      <span class="badge badge-${badgeClass}">${data.status}</span>
    </span></div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Requested</span><span class="modal-detail-val">${data.created_at}</span></div>
    <div class="modal-detail-row"><span class="modal-detail-lbl">Due By</span><span class="modal-detail-val">${data.due_by}</span></div>
    
    ${data.admin_note ? `
    <div class="modal-detail-row" style="flex-direction:column; gap:6px; align-items:stretch">
      <span class="modal-detail-lbl">Administrator Note</span>
      <div style="text-align:left; background:rgba(16,185,129,0.06); padding:12px 14px; border-radius:10px; border:1px solid rgba(16,185,129,0.15); width:100%; white-space:pre-wrap; font-size:0.83rem; line-height:1.5; color:#fff">${data.admin_note}</div>
    </div>` : ''}
    
    ${proofHtml}
  `;

  document.getElementById('wd-detail-modal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeWithdrawalModal() {
  document.getElementById('wd-detail-modal').classList.remove('open');
  document.body.style.overflow = '';
}

function closeModalOnBackdrop(e) {
  if (e.target.id === 'wd-detail-modal') closeWithdrawalModal();
}

// Restore method selection on page back (if form had error)
<?php if ($error && isset($_POST['payment_method'])): ?>
(function() {
  const m = <?= json_encode($_POST['payment_method'] ?? '') ?>;
  if (m) {
    const card = document.querySelector(`.wd-method-card[data-method="${m}"]`);
    if (card) selectMethod(card, m);
  }
})();
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
