<?php
// Start PHP session
session_start();

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Load Google API Client library
require_once 'vendor/autoload.php';

// Initialize Google Client
$client = new Google_Client();
$client->setAuthConfig('client_secret.json');  // Path to your client secret JSON file
$client->setRedirectUri('http://localhost:8000/forms/google-callback.php'); // Callback URL after authentication
$client->addScope('email');   // Request email scope
$client->addScope('profile'); // Request profile scope

// Generate Google Login URL
$login_url = $client->createAuthUrl();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/js/all.min.js"></script>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&" rel="stylesheet">

  <!-- Custom Stylesheet -->
  <link rel="stylesheet" href="style.css" type="text/css" media="all" />

  <title>Advance Signup Form</title>
</head>
<body>
<main>
  <section class="main-card">
    
    <!-- ================== WELCOME LAYOUT ================== -->
    <div class="welcomeLayout active">
      <div class="shape1"></div>
      <div class="normal-shape">
        <button class="start" id="start"><i class="fa-solid fa-arrow-right"></i></button>
        <span class="stTxt" id="getStartBtn">Get started!</span>
      </div>
      <div class="topContent">
        <h2 style="color:#ffffff;"><i class="fa-solid fa-cube"></i> AiReap</h2>
        <span>* Creativity Meets Intelligence *</span>
        <p>
         Welcome aboard! With AiReap, secure sign-up has never been simpler. Choose Email or Google, track your profile instantly, and recover access with OTP when needed. AiReap combines modern design, strict security, and seamless login for a truly smarter experience.
        </p>
      </div>
    </div>
    <!-- ================== END WELCOME ================== -->

    <!-- ================== SIGNUP LAYOUT ================== -->
    <div class="signupLayout">
      <div class="hero1">
        <h2><i class="fa-solid fa-cube"></i> AiReap</h2>
        <span>Start your AI-powered journey today.</span>
      </div>

      <!-- Signup Form -->
      <form class="signupForm" id="signupForm" action="signup_backend.php" method="POST">
        <div class="inputBox">
          <i class="fa-solid fa-user"></i>
          <input type="text" name="name" placeholder="Name" id="regUnameInput" />
        </div>

        <div class="inputBox">
          <i class="fa-solid fa-envelope"></i>
          <input type="email" name="email" placeholder="Email" id="regUemailInput" />
        </div>

        <div class="inputBox">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" placeholder="Password" id="regUpassInput" />
        </div>

        <!-- Password strength indicator -->
        <div class="bars">
          <div class="checkStrength">
            <div class="fillStrength" id="fillStrength"></div>
          </div>
          <span class="psText" id="strengthText"></span>
        </div>
        <!-- CSRF TOKEN -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <button type="submit" class="formSbutton">Signup</button>
      </form>

      <div class="or">
        <hr />
        <span>or</span>
      </div>

      <!-- Google Signup -->
      <div class="socialSign">
        <button class="googleButton" id="gButton" data-login-url="<?php echo $login_url; ?>">
          <img src="google-icon.svg" alt="Google Icon" />
          <span class="gText">Continue with Google</span>
        </button>
      </div>

      <!-- Redirect to Login -->
      <div class="forLableBtn">
        <span>Already have an account? <a class="lgLabel">Login</a></span>
      </div>

      <!-- Terms & Conditions -->
      <div class="consetTextContainer">
        <span>
          By creating an account or logging in, you confirm that you agree to abide by our 
          <a href="#">Terms & Conditions</a> and acknowledge that you have read and understood our 
          <a href="#">Privacy Policy</a>.
        </span>
      </div>
    </div>
    <!-- ================== END SIGNUP ================== -->

    <!-- ================== LOGIN LAYOUT ================== -->
    <div class="loginLayout">
      <div class="hero1">
        <h2><i class="fa-solid fa-cube"></i> AiReap</h2>
        <span>Welcome back!</span>
      </div>

      <!-- Login Form -->
      <form class="loginForm" id="lgForm">
        <div class="inputBox">
          <i class="fa-solid fa-envelope"></i>
          <input type="email" name="email" id="lgEmailInput" placeholder="Email" />
        </div>

        <div class="inputBox">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" id="lgPassInput" placeholder="Password" />
        </div>

        <div class="frPassword">
          <span class="forgotLabel">Forgot password</span>
        </div>
        <!-- CSRF TOKEN -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="btn">
          <button type="submit" class="LGbtn">Login</button>
        </div>
      </form>

      <div class="or">
        <hr />
        <span>or</span>
      </div>

      <!-- Google Login -->
      <div class="socialSign">
        <button class="googleButton" id="lgGoogle" data-login-url="<?php echo $login_url; ?>">
          <img src="google-icon.svg" alt="Google Icon" />
          <span class="gText">Continue with Google</span>
        </button>
      </div>

      <!-- Redirect to Signup -->
      <div class="forLableBtn">
        <span>Don't have an account? <label class="accountCreatelb">Create</label></span>
      </div>
    </div>
    <!-- ================== END LOGIN ================== -->

    <!-- ================== ACCOUNT RECOVERY ================== -->
    <div class="accoutRecoveryLayout">
      <div class="back"><span class="backBtn"><i class="fa-solid fa-left-long"></i></span></div>
      <h2><i class="fa-solid fa-recycle"></i> Account Recovery</h2>

      <form class="recoveryForm" id="recoveryForm">
        <div class="helper">
          <span>We’ll send you a 6-digit code to reset your password.</span>
        </div>
        <div class="inputBox">
          <i class="fa-solid fa-envelope"></i>
          <input type="email" name="recovery-mail" id="recoveryInput" placeholder="Enter registered email" />
        </div>
        <!-- CSRF TOKEN -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="btn">
          <button class="LGbtn continue" type="submit" name="getOtp">Continue</button>
        </div>
      </form>
    </div>
    <!-- ================== END RECOVERY ================== -->

    <!-- ================== OTP VERIFICATION ================== -->
    <div class="otpVerifyLayout">
      <div class="back"><span class="backBtn"><i class="fa-solid fa-left-long"></i></span></div>
      <h2>Verify OTP</h2>
      <span>Enter 6-digit OTP.</span>

      <form class="otpForm" id="verifyOtpForm">
        <!-- Email is passed from PHP -->
        <input type="hidden" name="recUemail" value="<?php echo $_GET['email']; ?>">

        <div class="otpInputs">
          <input type="text" maxlength="1" name="otp1" pattern="[0-9]" inputmode="numeric" required />
          <input type="text" maxlength="1" name="otp2" pattern="[0-9]" inputmode="numeric" required />
          <input type="text" maxlength="1" name="otp3" pattern="[0-9]" inputmode="numeric" required />
          <input type="text" maxlength="1" name="otp4" pattern="[0-9]" inputmode="numeric" required />
          <input type="text" maxlength="1" name="otp5" pattern="[0-9]" inputmode="numeric" required />
          <input type="text" maxlength="1" name="otp6" pattern="[0-9]" inputmode="numeric" required />
        </div>
        <!-- CSRF TOKEN -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="btn">
          <button class="LGbtn verify" type="submit" name="verifyBtn">Verify</button>
        </div>

        <div id="otpContainer" style="text-align:center; margin-top:20px;">
          <label class="otpTxt">
            Didn't receive code? <span id="resendOtpLabel">Request again</span>
          </label>
        </div>
      </form>
    </div>
    <!-- ================== END OTP VERIFICATION ================== -->

    <!-- ================== CHANGE PASSWORD ================== -->
    <div class="changePasswordLayout">
      <div class="back"><span class="backBtn"><i class="fa-solid fa-left-long"></i></span></div>
      <h2>Change Password</h2>
      <span class="p">Create a strong 8-digit password with a mix of characters.</span>

      <form class="changePassForm" id="resetPasswordForm">
        <div class="inputBox">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" id="enterPasInput" placeholder="Password" />
          <span id="chPsToggle"><i class="fa-solid fa-eye"></i></span>
        </div>

        <div class="inputBox">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="confirm_password" id="confInput" placeholder="Confirm password" />
        </div>
        <!-- CSRF TOKEN -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="btn">
          <button class="LGbtn chBtn" id="setPassBtn" name="setPassBtn" type="submit">Change</button>
        </div>
      </form>
    </div>
    <!-- ================== END CHANGE PASSWORD ================== -->

    <!-- ================== MODAL MESSAGE ================== -->
    <section id="messageModal" class="messageModal">
      <div class="statusIcon" id="modalIcon">
        <i class="fa-solid fa-circle-check successIcon" style="display:none;font-size:30px;margin-bottom:15px;"></i>
        <i class="fa-solid fa-circle-exclamation alertIcon" style="display:none;font-size:30px;margin-bottom:15px;"></i>
        <i class="fa-solid fa-circle-xmark errorIcon" style="display:none;font-size:30px;margin-bottom:15px;"></i>
      </div>
      <span class="msgTagline" id="modalMsg"></span>
    </section>
    <!-- ================== END MODAL ================== -->

  </section>
</main>

<!-- External JS -->
<script src="script.js"></script>
</body>
</html>
