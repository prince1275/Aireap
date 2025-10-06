<?php
// ======================================================
// User Registration Handler
// Handles user signup with strict validation, duplicate checks, 
// password hashing, and session creation.
// ======================================================

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
        "title" => "",
        "msg"   => "Invalid request method!"
    ]);
    exit;
}

// ======================================================
// 2. CSRF Token Validation
// ======================================================
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        "type" => "error",
        "msg"  => "Invalid CSRF token!"
    ]);
    exit;
}

// ======================================================
// 3. Collect and sanitize input
// ======================================================
$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// ======================================================
// 4. Strict Validation Functions
// ======================================================

/**
 * Validate Name
 * - Must be 2–50 characters
 * - Only letters, spaces, hyphens, apostrophes
 */
function is_strict_valid_name($name) {
    return preg_match("/^[A-Za-z][A-Za-z\s'\-]{1,49}$/u", $name);
}

/**
 * Validate Email
 * - RFC compliant filter_var check
 * - Local part: 1–64 chars, valid chars only
 * - Domain: 3–255 chars, must contain valid TLD
 * - No consecutive dots
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

    return true;
}

/**
 * Validate Password
 * - Minimum 8 characters
 * - Must include: uppercase, lowercase, number, special character
 */
function is_strict_valid_password($password) {
    $pattern = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    return preg_match($pattern, $password);
}

// ======================================================
// 5. Field Validations (Check blank + strict rules)
// ======================================================

// --- Name ---
if (!$name) {
    echo json_encode([
        "type"  => "error",
        "field" => "name",
        "msg"   => "Name is required!"
    ]);
    exit;
}
if (!is_strict_valid_name($name)) {
    echo json_encode([
        "type"  => "error",
        "field" => "name",
        "msg"   => "Name must be 2–50 characters and contain only letters, spaces, hyphens, or apostrophes."
    ]);
    exit;
}

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
        "msg"   => "Invalid email format!"
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
if (!is_strict_valid_password($password)) {
    echo json_encode([
        "type"  => "error",
        "field" => "password",
        "msg"   => "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character."
    ]);
    exit;
}

// ======================================================
// 6. Check for duplicate email in database
// ======================================================
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode([
        "type"  => "error",
        "field" => "email",
        "msg"   => "Email already exists!"
    ]);
    exit;
}
$stmt->close();

// ======================================================
// 7. Hash password and set default values
// ======================================================
$hashed          = password_hash($password, PASSWORD_BCRYPT);
$login_type      = 'email';
$default_picture = "https://www.svgrepo.com/show/384670/account-avatar-profile-user.svg";

// ======================================================
// 8. Insert new user into database
// ======================================================
$stmt = $conn->prepare("
    INSERT INTO users (name, email, password, login_type, picture) 
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("sssss", $name, $email, $hashed, $login_type, $default_picture);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;

    // Store session data for new user
    $_SESSION['user'] = [
        "id"         => $user_id,
        "name"       => $name,
        "email"      => $email,
        "login_type" => $login_type,
        "picture"    => $default_picture,
        "created_at" => date("Y-m-d H:i:s")
    ];

    // Success response
    echo json_encode([
        "type"     => "success",
        "msg"      => "Your account has been created successfully!",
        "redirect" => "profile.php"
    ]);
    exit;

} else {
    // Database error response
    echo json_encode([
        "type"  => "error",
        "msg"   => "Database error! Please try again later."
    ]);
    exit;
}
