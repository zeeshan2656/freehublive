<?php
// ============================================================
// FreeHub.Live — Email / SMTP Mailer
// ============================================================
// Uses PHP's built-in mail() with SMTP settings from admin panel.
// For production, install PHPMailer via Composer for SMTP auth.
// ============================================================

function fh_send_email(string $to, string $subject, string $htmlBody, string $textBody = ''): bool {
    $smtpHost     = setting('smtp_host', '');
    $smtpUser     = setting('smtp_user', '');
    $smtpPass     = setting('smtp_pass', '');
    $smtpPort     = (int)setting('smtp_port', '587');
    $fromEmail    = setting('smtp_from_email', $smtpUser);
    $fromName     = setting('smtp_from_name', setting('site_name', 'FreeHub'));
    $encryption   = strtolower(setting('smtp_encryption', 'tls'));

    // Use PHPMailer if available (recommended for production)
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return fh_send_via_phpmailer($to, $subject, $htmlBody, $textBody, $smtpHost, $smtpPort, $smtpUser, $smtpPass, $fromEmail, $fromName, $encryption);
    }

    // Fallback: PHP mail() with SMTP ini settings
    if ($smtpHost) {
        ini_set('SMTP', $smtpHost);
        ini_set('smtp_port', (string)$smtpPort);
        ini_set('sendmail_from', $fromEmail);
    }

    if (!$textBody) {
        $textBody = strip_tags($htmlBody);
    }

    $boundary = md5(uniqid((string)time(), true));
    $headers  = implode("\r\n", [
        "From: " . ($fromName ? "$fromName <$fromEmail>" : $fromEmail),
        "Reply-To: $fromEmail",
        "MIME-Version: 1.0",
        "Content-Type: multipart/alternative; boundary=\"$boundary\"",
        "X-Mailer: FreeHub/1.0",
    ]);

    $body  = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= $textBody . "\r\n\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $body .= $htmlBody . "\r\n\r\n";
    $body .= "--$boundary--";

    return @mail($to, $subject, $body, $headers);
}

function fh_send_via_phpmailer(string $to, string $subject, string $htmlBody, string $textBody, string $host, int $port, string $user, string $pass, string $from, string $fromName, string $encryption): bool {
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = !empty($user);
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = $encryption === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $port;
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject  = $subject;
        $mail->Body     = $htmlBody;
        $mail->AltBody  = $textBody ?: strip_tags($htmlBody);
        $mail->CharSet  = 'UTF-8';
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('FreeHub Mailer error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send a password reset email with a secure token link.
 */
function fh_send_password_reset(string $email, string $username, string $token): bool {
    $siteName  = setting('site_name', 'FreeHub');
    $resetLink = BASE_URL . '/auth/reset.php?token=' . urlencode($token);
    $expiry    = '1 hour';

    $subject   = "Reset your $siteName password";

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f0f0f;color:#e5e5e5;margin:0;padding:0}
.wrap{max-width:520px;margin:40px auto;background:#1a1a1a;border-radius:12px;overflow:hidden;border:1px solid #2a2a2a}
.header{background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:32px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:1.5rem;font-weight:800}
.body{padding:36px 40px}
.btn{display:inline-block;background:#6366f1;color:#fff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:700;font-size:1rem;margin:20px 0}
.footer{padding:20px 40px;text-align:center;color:#888;font-size:.8rem;border-top:1px solid #2a2a2a}
p{line-height:1.7;color:#ccc}
.warning{color:#f97316;font-size:.85rem;margin-top:16px}
</style></head>
<body>
<div class="wrap">
  <div class="header"><h1>🔐 {$siteName}</h1></div>
  <div class="body">
    <p>Hi <strong>{$username}</strong>,</p>
    <p>We received a request to reset your password. Click the button below to set a new password. This link will expire in <strong>{$expiry}</strong>.</p>
    <div style="text-align:center">
      <a href="{$resetLink}" class="btn">Reset My Password</a>
    </div>
    <p>Or copy and paste this link into your browser:</p>
    <p style="word-break:break-all;font-size:.85rem;color:#6366f1">{$resetLink}</p>
    <p class="warning">⚠️ If you did not request a password reset, you can safely ignore this email. Your password will not be changed.</p>
  </div>
  <div class="footer">
    &copy; {$siteName} — This is an automated email, please do not reply.
  </div>
</div>
</body>
</html>
HTML;

    $text = "Hi {$username},\n\nReset your {$siteName} password by visiting:\n{$resetLink}\n\nThis link expires in {$expiry}.\n\nIf you did not request this, ignore this email.";

    return fh_send_email($email, $subject, $html, $text);
}
