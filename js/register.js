// Register Page JavaScript

// Password strength checker
document.getElementById("password").addEventListener("input", function () {
  const password = this.value;
  const result = checkPasswordStrength(password);

  const strengthMeter = document.getElementById("passwordStrength");
  const strengthText = document.getElementById("passwordStrengthText");

  strengthMeter.className = "password-strength-meter " + result.level;
  strengthMeter.style.width = result.width;
  strengthMeter.style.backgroundColor = result.color;

  strengthText.className = "password-strength-text " + result.level;
  strengthText.textContent =
    result.level.charAt(0).toUpperCase() + result.level.slice(1) + " password";

  // Check password match
  checkPasswordMatch();
});

// Password match checker
function checkPasswordMatch() {
  const password = document.getElementById("password").value;
  const confirm = document.getElementById("confirm_password").value;
  const matchElement = document.getElementById("passwordMatch");

  if (confirm.length === 0) {
    matchElement.innerHTML = "";
  } else if (password === confirm) {
    matchElement.innerHTML =
      '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Passwords match</span>';
  } else {
    matchElement.innerHTML =
      '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Passwords do not match</span>';
  }
}

document
  .getElementById("confirm_password")
  .addEventListener("input", checkPasswordMatch);

// Form validation
document
  .getElementById("registerForm")
  .addEventListener("submit", function (e) {
    const password = document.getElementById("password").value;
    const confirm = document.getElementById("confirm_password").value;

    if (password !== confirm) {
      e.preventDefault();
      const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
      Toast.fire({
        icon: "error",
        title: "Passwords do not match!",
      });
    }
  });

// Show server-side messages as toast (will be called from inline PHP)
function showServerMessage(type, message) {
  const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
  });
  Toast.fire({ icon: type, title: message });
}
