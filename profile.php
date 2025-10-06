<?php
// ======================================================
// User Profile Page
// ======================================================

session_start();
require_once 'config.php'; // DB connection

// ======================================================
// 1. Check if user is logged in
// ======================================================
if (!isset($_SESSION['user']['id'])) {
    header("Location: index.php"); // Redirect to login
    exit();
}

// ======================================================
// 2. Always fetch the latest user data from DB
// ======================================================
$userId = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ======================================================
// 3. Format Join Date
// ======================================================
$joinDate = !empty($user['created_at']) 
    ? date("d/m/Y", strtotime($user['created_at'])) 
    : "N/A";

// ======================================================
// 4. Handle Profile Picture
// ======================================================
// If user has picture, use it. Otherwise, show default placeholder.
$picturePath = $user['picture'] ?? '';
$imageUrl = (!empty($picturePath)) 
    ? htmlspecialchars($picturePath) 
    : 'https://drive.google.com/uc?export=download&id=1H-C9w4Sn76C2q5LcRmaAmmDxFMr1O8vp';

// ======================================================
// 5. Login Type Icons
// ======================================================
$loginTypeIcons = [
    'email'    => '<i class="fa-solid fa-envelope" title="Email Login" style="color:#6c757d;"></i>',
    'google'   => '<i class="fa-brands fa-google" title="Google Login" style="color:#DB4437;"></i>',
    'facebook' => '<i class="fa-brands fa-facebook" title="Facebook Login" style="color:#1877F2;"></i>'
];

$loginType = strtolower($user['login_type'] ?? 'unknown');
$loginTypeIcon = $loginTypeIcons[$loginType] ?? '<i class="fa-solid fa-question" title="Unknown"></i>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />

    <!-- Custom Profile CSS -->
    <link rel="stylesheet" href="profile.css">

    <title>User Profile</title>
</head>
<body>
    <section class="main">
        <section class="card">
            
            <!-- Profile Picture -->
            <div class="profileImg" style="background-image:url('<?=$imageUrl?>');"></div>

            <!-- User Name -->
            <h3 class="name">Welcome! <?=htmlspecialchars($user['name']);?></h3>

            <!-- User Email -->
            <span class="email">
                <i class="fa-solid fa-envelope"></i> <?=htmlspecialchars($user['email']);?>
            </span>

            <!-- User Info Section -->
            <div class="infoWrapper">
                <span class="joinOn">
                    <i class="fa-solid fa-calendar-days"></i><strong>:</strong> <?=$joinDate;?>
                </span>
                <span class="signupType">
                    Login type: <?=$loginTypeIcon;?>
                </span>
            </div>

            <!-- Logout Button -->
            <div class="buttonWrapper">
                <a href="logout.php" class="logoutBtn">
                    <i class="fa-solid fa-user"></i> Logout
                </a>
            </div>

        </section>
    </section>
</body>
</html>
