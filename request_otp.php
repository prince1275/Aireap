<?php
// ======================================================
// Password Recovery: Send OTP Email (with CSRF protection)
// ======================================================

session_start();

// regenerate session ID for security
session_regenerate_id(true);

// optional: extend session lifetime
ini_set('session.gc_maxlifetime', 1800);   // 30 minutes
ini_set('session.cookie_lifetime', 1800); // 30 minutes


// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

require_once 'config.php'; // DB connection

// Always return JSON
header("Content-Type: application/json");
error_reporting(0);
ini_set('display_errors', 0);

// ======================================================
// 0. CSRF Token Validation
// ======================================================
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        "type" => "error",
        "msg"  => "Invalid request (CSRF verification failed)."
    ]);
    exit;
}

// ======================================================
// 1. Validate email input
// ======================================================
if (!isset($_POST['recovery-mail']) || empty($_POST['recovery-mail'])) {
    echo json_encode([
        "type" => "error",
        "msg"  => "Email is required!"
    ]);
    exit;
}

$email = filter_var(trim($_POST['recovery-mail']), FILTER_VALIDATE_EMAIL);
if (!$email) {
    echo json_encode([
        "type" => "error",
        "msg"  => "Invalid email address!"
    ]);
    exit;
}

// ======================================================
// 2. Check if user exists in database
// ======================================================
$stmt = $conn->prepare("SELECT id, login_type FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "type" => "error",
        "msg"  => "No account found with this email."
    ]);
    exit;
}

$user = $result->fetch_assoc();

// ======================================================
// 3. Block password recovery for Google accounts
// ======================================================
if (strtolower($user['login_type']) === 'google') {
    echo json_encode([
        "type" => "error",
        "msg"  => "This account was created with Google login. Please sign in using Google."
    ]);
    exit;
}

// ======================================================
// 4. Generate OTP and expiry
// ======================================================
$otp    = random_int(100000, 999999); // 6-digit OTP
$expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

// Save OTP in DB
$stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ?, otp_used = 0 WHERE email = ?");
$stmt->bind_param("sss", $otp, $expiry, $email);
if (!$stmt->execute()) {
    echo json_encode([
        "type" => "error",
        "msg"  => "Failed to save OTP. Try again later."
    ]);
    exit;
}

$_SESSION['reset_email'] = $email;
session_regenerate_id(true);


// ======================================================
// 5. Send OTP email using PHPMailer
// ======================================================
$mail = new PHPMailer(true);

try {
    // SMTP configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'rajbhaie99@gmail.com';   // Your Gmail
    $mail->Password   = 'zdrj jxvu hmlw kniu';   // App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->SMTPDebug  = 0;

    // Sender & recipient
    $mail->setFrom('rajbhaie99@gmail.com', 'AiReap OTP');
    $mail->addAddress($email);

    // Email body
    $mail->isHTML(true);
    $mail->Subject = 'Your AiReap verification code';
    $year = date("Y");

    $mail->Body = <<<EOD
    <!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AiReap OTP Verification</title>
<style>
    body, html {margin:0;padding:0;font-family:Arial,sans-serif;background:#f4f6f8;color:#333;}
    .email-wrapper {max-width:600px;margin:40px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.1);}
    .email-header {background:#2EC92F;padding:20px 30px;color:#fff;font-size:22px;font-weight:700;}
    .email-body {padding:30px;text-align:center;}
    .email-body h1 {font-size:22px;margin-bottom:15px;}
    .otp-code {font-size:28px;font-weight:bold;color:#2EC92F;letter-spacing:4px;padding:15px 25px;border:2px dashed #2EC92F;border-radius:8px;display:inline-block;margin:15px 0;}
    .small-text {font-size:14px;color:#777;}
    .email-footer {background:#f1f3f5;text-align:center;padding:15px 30px;font-size:12px;color:#888;}
</style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-header">AiReap</div>
    <div class="email-body">
        <h1>OTP Verification Code</h1>
        <p>Hello,</p>
        <p>Use the OTP below to verify your account:</p>
        <div class="otp-code">{$otp}</div>
        <p class="small-text">Valid for 5 minutes. If you didn’t request this, ignore this email.</p>
    </div>
    <div class="email-footer">
        &copy; {$year} AiReap. All rights reserved.
    </div>
</div>
</body>
</html>
EOD;

    $mail->AltBody = "Your AiReap code is: $otp (valid for 5 minutes)";

    // Send email
    $mail->send();

    echo json_encode([
        "type"  => "success",
        "msg"   => "OTP has been sent to your email!",
        "email" => $email
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        "type" => "error",
        "msg"  => "Email could not be sent. Error: " . $mail->ErrorInfo
    ]);
    exit;
}
