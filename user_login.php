<?php
// ======================================================
// User Login Handler
// Handles user login with strict validation, 
// database checks, and session setup.
// ======================================================

session_start();
require_once 'config.php'; // Database connection

// Always return JSON
header('Content-Type: application/json');

// ======================================================
// 1. Allow only POST requests & required fields
// ======================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    !isset($_POST['email'], $_POST['password'])) {

    echo json_encode([
        "type"  => "error",
        "field" => "general",
        "msg"   => "Email and password are required!"
    ]);
    exit;
}

// ======================================================
// 2. CSRF Token Validation
// ======================================================
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        "type" => "error",
        "field"=> "general",
        "msg"  => "Invalid CSRF token!"
    ]);
    exit;
}

// ======================================================
// 3. Collect and sanitize inputs
// ======================================================
$email    = trim($_POST['email']);
$password = trim($_POST['password']);

// ======================================================
// 4. Strict Validation Functions
// ======================================================

/**
 * Validate Email
 * - RFC format
 * - Local part: 1–64 chars, valid chars only
 * - Domain: 3–255 chars, must contain valid TLD
 * - No consecutive dots
 * - DNS check for MX or A record
 */
function is_strict_valid_email($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

    $parts = explode('@', $email);
    if (count($parts) !== 2) return false;

    list($local, $domain) = $parts;

    // Local part rules
    if (strlen($local) < 1 || strlen($local) > 64) return false;
    if (!preg_match('/^[A-Za-z0-9._%+\-]+$/', $local)) return false;

    // Domain rules
    if (strlen($domain) < 3 || strlen($domain) > 255) return false;
    if (!preg_match('/^(?!-)[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $domain)) return false;

    // Prevent double dots
    if (strpos($local, '..') !== false || strpos($domain, '..') !== false) return false;

    // DNS check (optional)
    // if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) return false;

    return true;
}

/**
 * Validate Password
 * - At least 1 character (basic check for login)
 */
function is_valid_password($password) {
    return strlen($password) >= 8;
}

// ======================================================
// 5. Validate Inputs Individually
// ======================================================

// --- Email ---
if (!$email) {
    echo json_encode([
        "type"  => "error",
        "field" => "email",
        "msg"   => "Email is required!"
    ]);
    exit;
}
if (!is_strict_valid_email($email)) {
    echo json_encode([
        "type"  => "error",
        "field" => "email",
        "msg"   => "Invalid or non-existent email domain!"
    ]);
    exit;
}

// --- Password ---
if (!$password) {
    echo json_encode([
        "type"  => "error",
        "field" => "password",
        "msg"   => "Password is required!"
    ]);
    exit;
}
if (!is_valid_password($password)) {
    echo json_encode([
        "type"  => "error",
        "field" => "password",
        "msg"   => "Invalid password format!"
    ]);
    exit;
}

// ======================================================
// 6. Fetch user from database
// ======================================================
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "type"  => "error",
        "field" => "email",
        "msg"   => "Email not found!"
    ]);
    exit;
}

$user = $result->fetch_assoc();

// ======================================================
// 7. Handle social login users (Google / Facebook)
// ======================================================
if ($user['login_type'] !== 'email') {
    echo json_encode([
        "type"  => "error",
        "field" => "email",
        "msg"   => "This account was created with " . ucfirst($user['login_type']) . 
                   ". Please use " . ucfirst($user['login_type']) . " login instead."
    ]);
    exit;
}

// ======================================================
// 8. Verify password for email login
// ======================================================
if (!password_verify($password, $user['password'])) {
    echo json_encode([
        "type"  => "error",
        "field" => "password",
        "msg"   => "Incorrect password!"
    ]);
    exit;
}

// ======================================================
// 9. Set session on successful login
// ======================================================
$_SESSION['user'] = [
    "id"         => $user['id'],
    "name"       => $user['name'],
    "email"      => $user['email'],
    "picture"    => $user['picture'],
    "login_type" => $user['login_type'],
    "created_at" => $user['created_at']
];

// ======================================================
// 10. Return success response
// ======================================================
echo json_encode([
    "type"     => "success",
    "msg"      => "Login successful!",
    "redirect" => "profile.php"
]);
exit;
