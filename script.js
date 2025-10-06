/* ===========================================================
   🔹 LOGIN & SIGNUP HANDLING SCRIPT
   Description:
   - Handles UI layout switching (login, signup, recovery, OTP, reset)
   - Manages form submissions with CSRF protection
   - Displays modal messages
   - Handles OTP input logic
   - Includes password strength checking & reset functionality
   =========================================================== */

/* ====== DOM ELEMENT SELECTION ====== */
const welcomeLayout = document.querySelector(".welcomeLayout");
const signupLayout = document.querySelector(".signupLayout");
const mainCard = document.querySelector(".main-card");
const loginLabel = document.querySelector(".lgLabel");
const loginLayout = document.querySelector(".loginLayout");
const forgotPassBtn = document.querySelector(".forgotLabel");
const recoveryAccountLayout = document.querySelector(".accoutRecoveryLayout");
const otpVerifyLayout = document.querySelector(".otpVerifyLayout");
const changePasswordLayout = document.querySelector(".changePasswordLayout");
const createLabelBtn = document.querySelector(".accountCreatelb");
const getStartButton = document.getElementById("start");

const googleSignButton = document.getElementById("gButton");
const lgGoogle = document.getElementById("lgGoogle");
const loginUrl = googleSignButton.getAttribute("data-login-url");
const loginUrl2 = lgGoogle.getAttribute("data-login-url");

/* ===========================================================
   🔹 PASSWORD STRENGTH CHECKER (Works on signup + reset form)
   =========================================================== */
document.addEventListener("DOMContentLoaded", () => {
  // Try to grab password inputs (signup + reset form)
  const signupPassword = document.querySelector("#signupForm .passwordInput") 
                      || document.getElementById("regUpassInput");
  const fillStrength = document.getElementById("fillStrength");
  const strengthText = document.getElementById("strengthText");

  if (signupPassword && fillStrength && strengthText) {
    signupPassword.addEventListener("input", () => {
      const val = signupPassword.value;
      let strength = 0;

      if (val.length >= 8) strength++;
      if (/[A-Z]/.test(val)) strength++;
      if (/[a-z]/.test(val)) strength++;
      if (/\d/.test(val)) strength++;
      if (/[\W_]/.test(val)) strength++;

      fillStrength.style.width = `${strength * 20}%`;

      if (strength <= 2) {
        strengthText.textContent = "Weak";
        fillStrength.style.backgroundColor = "#F44336";
      } else if (strength === 3 || strength === 4) {
        strengthText.textContent = "Medium";
        fillStrength.style.backgroundColor = "#FF9800";
      } else if (strength === 5) {
        strengthText.textContent = "Strong";
        fillStrength.style.backgroundColor = "#2EC92F";
      }
    });
  }
});


/* ===========================================================
   🔹 MODAL BOX FOR USER MESSAGES
   =========================================================== */
function showMessage(type, title, msg, callback) {
  const modal = document.getElementById("messageModal");
  const msgBox = document.getElementById("modalMsg");

  // Icons
  const successIcon = modal.querySelector(".statusIcon .successIcon");
  const errorIcon = modal.querySelector(".statusIcon .errorIcon");
  const alertIcon = modal.querySelector(".statusIcon .alertIcon");

  // Hide all first
  [successIcon, errorIcon, alertIcon].forEach(icon => {
    if (icon) icon.style.display = "none";
  });

  // Show based on type
  switch (type) {
    case "success": if (successIcon) successIcon.style.display = "inline-block"; break;
    case "error": if (errorIcon) errorIcon.style.display = "inline-block"; break;
    case "alert": if (alertIcon) alertIcon.style.display = "inline-block"; break;
  }

  // Show message
  msgBox.textContent = msg;
  modal.style.display = "block";

  // Auto close modal after 2s
  if (modal.hideTimer) clearTimeout(modal.hideTimer);
  modal.hideTimer = setTimeout(() => {
    modal.style.display = "none";
    [successIcon, errorIcon, alertIcon].forEach(icon => icon.style.display = "none");
    if (callback) callback();
  }, 2000);
}

/* ===========================================================
   🔹 LAYOUT SWITCHING
   =========================================================== */
getStartButton?.addEventListener("click", () => {
  switchLayout(welcomeLayout, signupLayout);
  mainCard.style.height = "480px";
});

loginLabel?.addEventListener("click", () => {
  switchLayout(signupLayout, loginLayout);
  mainCard.style.height = "400px";
});

createLabelBtn?.addEventListener("click", () => {
  switchLayout(loginLayout, signupLayout);
  mainCard.style.height = "530px";
});

forgotPassBtn?.addEventListener("click", () => {
  switchLayout(loginLayout, recoveryAccountLayout);
  mainCard.style.height = "270px";
});

googleSignButton?.addEventListener("click", () => window.location.href = loginUrl);
lgGoogle?.addEventListener("click", () => window.location.href = loginUrl2);

document.querySelectorAll(".backBtn").forEach(btn => {
  btn.addEventListener("click", backBtnHandler);
});

/* Helper functions */
function backBtnHandler() {
  if (recoveryAccountLayout.classList.contains("active")) {
    switchLayout(recoveryAccountLayout, loginLayout);
  } else if (otpVerifyLayout.classList.contains("active")) {
    switchLayout(otpVerifyLayout, loginLayout);
  } else if (changePasswordLayout.classList.contains("active")) {
    switchLayout(changePasswordLayout, loginLayout);
  }
  mainCard.style.height = "450px";
}

function switchLayout(hideLayout, showLayout) {
  hideLayout.classList.remove("active");
  hideLayout.classList.add("hide");
  showLayout.classList.add("active");
  showLayout.classList.remove("hide");
}

/* ===========================================================
   🔹 OTP & PASSWORD RESET
   =========================================================== */
function otpLayout() {
  switchLayout(recoveryAccountLayout, otpVerifyLayout);
  mainCard.style.height = "250px";
}

function resetPasswordLayout() {
  switchLayout(otpVerifyLayout, changePasswordLayout);
  mainCard.style.height = "250px";
}

function showloginLayout() {
  switchLayout(changePasswordLayout, loginLayout);
  mainCard.style.height = "450px";
}

/* ===========================================================
   🔹 FORM HANDLERS (WITH CSRF)
   =========================================================== */
document.addEventListener("DOMContentLoaded", () => {
  
  /* SIGNUP */
  document.getElementById("signupForm")?.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append("csrf_token", this.querySelector("input[name='csrf_token']").value);

    fetch("signup_backend.php", { method: "POST", body: formData })
      .then(res => res.json())
      .then(data => {
        showMessage(data.type, data.title, data.msg, () => {
          if (data.type === "success" && data.redirect) {
            window.location.href = "profile.php";
          }
        });
      })
      .catch(() => showMessage("error", "", "Something went wrong."));
  });

  /* LOGIN */
  document.getElementById("lgForm")?.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append("csrf_token", this.querySelector("input[name='csrf_token']").value);

    fetch("user_login.php", { method: "POST", body: formData })
      .then(res => res.json())
      .then(data => {
        showMessage(data.type, data.title, data.msg, () => {
          if (data.type === "success" && data.redirect) {
            window.location.href = data.redirect;
          }
        });
      })
      .catch(() => showMessage("error", "", "Something went wrong."));
  });

  /* RECOVERY */
  document.getElementById("recoveryForm")?.addEventListener("submit", function (e) {
    e.preventDefault();
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    // Show spinner
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
    submitBtn.disabled = true;

    const formData = new FormData(this);
    formData.append("csrf_token", this.querySelector("input[name='csrf_token']").value);

    fetch("request_otp.php", { method: "POST", body: formData, credentials: "include" })
      .then(res => res.json())
      .then(data => {
        showMessage(data.type, data.title, data.msg);
        if (data.type === "success") setTimeout(otpLayout, 1000);
      })
      .catch(() => showMessage("error", "", "Something went wrong!"))
      .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      });
  });

  /* OTP VERIFY */
  document.getElementById("verifyOtpForm")?.addEventListener("submit", function (e) {
    e.preventDefault();
    let otp = "";
    document.querySelectorAll(".otpInputs input").forEach(input => otp += input.value);
    if (otp.length !== 6) return showMessage("error", "", "Enter 6-digit OTP!");

    const btn = this.querySelector("button[type='submit']");
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    const fd = new FormData();
    fd.append("csrf_token", this.querySelector("input[name='csrf_token']").value);
    fd.append("otp", otp);

    fetch("verify_otp.php", { method: "POST", body: fd, credentials: "include" })
      .then(res => res.json())
      .then(data => {
        showMessage(data.type, "", data.msg);
        if (data.type === "success") resetPasswordLayout();
      })
      .catch(() => showMessage("error", "", "Something went wrong!"))
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = "Verify";
      });
  });

  /* RESET PASSWORD */
  document.getElementById("resetPasswordForm")?.addEventListener("submit", function (e) {
    e.preventDefault();
    const resetPasswordInput = document.getElementById("enterPasInput");
    const confirmPasswordInput = document.getElementById("confInput");
    const passwordValue = resetPasswordInput.value.trim();
    const confirmPasswordValue = confirmPasswordInput.value.trim();

    // Password validation
    const strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
    if (!strongRegex.test(passwordValue)) {
      return showMessage("error", "", "Password must include uppercase, lowercase, number & special character!");
    }
    if (passwordValue !== confirmPasswordValue) {
      return showMessage("error", "", "Passwords do not match!");
    }

    const formData = new FormData();
    formData.append("csrf_token", this.querySelector("input[name='csrf_token']").value);
    formData.append("password", passwordValue);
    formData.append("confirm_password", confirmPasswordValue);

    fetch("reset_password_backend.php", { method: "POST", body: formData})
      .then(res => res.json())
      .then(data => {
        showMessage(data.type, data.title || "", data.msg);
        if (data.type === "success") setTimeout(showloginLayout, 1000);
      })
      .catch(() => showMessage("error", "", "Something went wrong!"));
  });

  /* TOGGLE PASSWORD VISIBILITY */
  const resetPasswordInput = document.getElementById("enterPasInput");
  const confirmPasswordInput = document.getElementById("confInput");
  const chPsToggle = document.getElementById("chPsToggle");

  if (chPsToggle) {
    chPsToggle.style.cursor = "pointer";
    chPsToggle.addEventListener("click", () => {
      const isHidden = resetPasswordInput.type === "password";
      resetPasswordInput.type = confirmPasswordInput.type = isHidden ? "text" : "password";
    });
  }
});

/* ===========================================================
   🔹 OTP INPUT AUTO-NAVIGATION
   =========================================================== */
const inputs = document.querySelectorAll(".otpInputs input");

inputs.forEach((input, index) => {
  input.addEventListener("input", e => {
    if (e.target.value.length === 1 && index < inputs.length - 1) {
      inputs[index + 1].focus();
    }
  });

  input.addEventListener("keydown", e => {
    if (e.key === "Backspace" && !e.target.value && index > 0) {
      inputs[index - 1].focus();
    }
  });
});

// Collect OTP manually on button click
document.getElementById("verifyBtn")?.addEventListener("click", () => {
  let otp = "";
  inputs.forEach(input => otp += input.value);
});
