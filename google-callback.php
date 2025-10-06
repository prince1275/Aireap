<?php
// ======================================================
// Google OAuth 2.0 Callback Handler
// ======================================================

session_start();
require_once 'vendor/autoload.php';  // Google API Client Library
require_once 'config.php';           // Database connection

// ======================================================
// Google Client Setup
// ======================================================
$client = new Google_Client();
$client->setAuthConfig('client_secret.json');                         // Client credentials file
$client->setRedirectUri('http://localhost:8000/forms/google-callback.php'); // Redirect URI (must match Google Console)
$client->addScope('email');    // Request email
$client->addScope('profile');  // Request profile info

// ======================================================
// Handle Google OAuth Callback
// ======================================================
if (isset($_GET['code'])) {
    // Exchange authorization code for access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    // If token is valid (no error returned)
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);

        // Fetch user info from Google
        $oauth = new Google_Service_Oauth2($client);
        $google_account_info = $oauth->userinfo->get();

        // Extract user info
        $google_id = $google_account_info->id;
        $email     = $google_account_info->email;
        $name      = $google_account_info->name;
        $picture   = $google_account_info->picture;

        // ======================================================
        // Check if user already exists in DB
        // ======================================================
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? OR google_id=? LIMIT 1");
        $stmt->bind_param("ss", $email, $google_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Existing user found
            $user = $result->fetch_assoc();

            // If user signed up with email before, update with google_id + picture
            if (empty($user['google_id'])) {
                $update = $conn->prepare("UPDATE users SET google_id=?, picture=?, login_type='google' WHERE email=?");
                $update->bind_param("sss", $google_id, $picture, $email);
                $update->execute();
            }

            // Save user session
            $_SESSION['user'] = $user;

        } else {
            // ======================================================
            // If new user → Insert into DB
            // ======================================================
            $stmt = $conn->prepare("INSERT INTO users 
                (google_id, email, name, picture, login_type, created_at) 
                VALUES (?, ?, ?, ?, 'google', NOW())"
            );
            $stmt->bind_param("ssss", $google_id, $email, $name, $picture);
            $stmt->execute();

            // Save new user session
            $_SESSION['user'] = [
                "id"         => $stmt->insert_id,
                "google_id"  => $google_id,
                "email"      => $email,
                "name"       => $name,
                "picture"    => $picture,
                "login_type" => "google",
                "created_at" => date("Y-m-d H:i:s")
            ];
        }

        // ======================================================
        // Redirect user to profile page
        // ======================================================
        header("Location: profile.php");
        exit;
    }
}
?>
