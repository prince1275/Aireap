<?php
// ======================================================
// OTP Verification Handler (Improved Version)
// ======================================================

// ------------------------------------------------------
// 0. Session Configuration (longer lifetime for UX)
// ------------------------------------------------------
ini_set('session.gc_maxlifetime', 1800);   // 30 minutes
ini_set('session.cookie_lifetime', 1800); // 30 minutes

session_start();
require_once 'config.php'; // Database connection

// Always return JSON response
header("Content-Type: application/json");

// ======================================================
// 1. Allow only POST requests
// ======================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "type"  => "error",
        "title" => "Invalid Request",
        "msg"   => "Only POST requests are allowed!"
    ]);
    exit;
}

// ======================================================
// 2. CSRF Token Validation
// ======================================================
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    $_POST['csrf_token'] !== $_SESSION['csrf_token']
) {
    echo json_encode([
        "type"  => "error",
        "title" => "Security Warning",
        "msg"   => "Invalid or missing CSRF token!"
    ]);
    exit;
}

// ======================================================
// 3. Validate OTP input
// ======================================================
$otp = $_POST['otp'] ?? null;

if (!$otp) {
    echo json_encode([
        "type"  => "alert",
        "title" => "Missing Field",
        "msg"   => "OTP is required!"
    ]);
    exit;
}

$otp = trim($otp);

// Ensure OTP is exactly 6 digits
if (!preg_match('/^[0-9]{6}$/', $otp)) {
    echo json_encode([
        "type"  => "alert",
        "title" => "Invalid OTP Format",
        "msg"   => "OTP must be exactly 6 digits!"
    ]);
    exit;
}

// ======================================================
// 4. Ensure OTP belongs to a specific email session
// ======================================================
if (empty($_SESSION['reset_email'])) {
    echo json_encode([
        "type"  => "error",
        "title" => "Session Expired",
        "msg"   => "Your session has expired. Please request a new OTP."
    ]);
    exit;
}

$email = $_SESSION['reset_email'];

// ======================================================
// 5. Verify OTP in database
// ======================================================
try {
    $stmt = $conn->prepare("
        SELECT id, otp_expiry, otp_used 
        FROM users 
        WHERE email = ? AND otp = ? 
        LIMIT 1
    ");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            "type"  => "alert",
            "title" => "Invalid OTP",
            "msg"   => "The OTP you entered is incorrect!"
        ]);
        exit;
    }

    $user = $result->fetch_assoc();

    // ==================================================
    // 6. Check OTP usage status
    // ==================================================
    if ((int)$user['otp_used'] === 1) {
        echo json_encode([
            "type"  => "alert",
            "title" => "Already Used",
            "msg"   => "This OTP has already been used!"
        ]);
        exit;
    }

    // ==================================================
    // 7. Check OTP expiry time
    // ==================================================
    if (strtotime($user['otp_expiry']) < time()) {
        echo json_encode([
            "type"  => "error",
            "title" => "Expired OTP",
            "msg"   => "This OTP has expired. Please request a new one!"
        ]);
        exit;
    }

    // ==================================================
    // 8. Mark OTP as verified + used
    // ==================================================
    $_SESSION['otp_verified'] = true; // Flag for next step (e.g., password reset)

    $stmt = $conn->prepare("UPDATE users SET otp_used = 1 WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();

    // ==================================================
    // 9. Success response
    // ==================================================
    echo json_encode([
        "type"  => "success",
        "title" => "Verified",
        "msg"   => "OTP verified successfully!"
    ]);
    exit;

} catch (Exception $e) {
    error_log("OTP Verification Error: " . $e->getMessage());

    echo json_encode([
        "type"  => "error",
        "title" => "System Error",
        "msg"   => "Something went wrong. Please try again later!"
    ]);
    exit;
}
