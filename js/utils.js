/**
 * Utility functions for Todo Talenta Digital
 */

// SweetAlert Toast Functions
const Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener("mouseenter", Swal.stopTimer);
    toast.addEventListener("mouseleave", Swal.resumeTimer);
  },
  customClass: {
    container: "floating-alert-container",
  },
});

function showSuccessToast(message) {
  Toast.fire({
    icon: "success",
    title: message,
    background: "#198754",
    color: "white",
    iconColor: "white",
  });
}

function showErrorToast(message) {
  Toast.fire({
    icon: "error",
    title: message,
    background: "#dc3545",
    color: "white",
    iconColor: "white",
  });
}

function showWarningToast(message) {
  Toast.fire({
    icon: "warning",
    title: message,
    background: "#ffc107",
    color: "#212529",
    iconColor: "#212529",
  });
}

function showInfoToast(message) {
  Toast.fire({
    icon: "info",
    title: message,
    background: "#0dcaf0",
    color: "white",
    iconColor: "white",
  });
}

function showLoadingToast(message = "Loading...") {
  return Swal.fire({
    title: message,
    allowOutsideClick: false,
    showConfirmButton: false,
    willOpen: () => {
      Swal.showLoading();
    },
  });
}

// Password Strength Checker
function checkPasswordStrength(password) {
  let strength = 0;
  let messages = [];

  // Length check
  if (password.length >= 8) strength++;
  else messages.push("At least 8 characters");

  // Lowercase check
  if (/[a-z]/.test(password)) strength++;
  else messages.push("One lowercase letter");

  // Uppercase check
  if (/[A-Z]/.test(password)) strength++;
  else messages.push("One uppercase letter");

  // Number check
  if (/[0-9]/.test(password)) strength++;
  else messages.push("One number");

  // Special character check
  if (/[^A-Za-z0-9]/.test(password)) strength++;
  else messages.push("One special character");

  let level, color, width;
  switch (strength) {
    case 5:
      level = "strong";
      color = "#198754";
      width = "100%";
      break;
    case 4:
      level = "medium";
      color = "#ffc107";
      width = "80%";
      break;
    case 3:
      level = "medium";
      color = "#ffc107";
      width = "60%";
      break;
    default:
      level = "weak";
      color = "#dc3545";
      width = "40%";
  }

  return {
    level: level,
    color: color,
    width: width,
    strength: strength,
    messages: messages,
  };
}

// Copy to Clipboard
function copyToClipboard(text) {
  navigator.clipboard
    .writeText(text)
    .then(() => {
      showSuccessToast("Copied to clipboard!");
    })
    .catch((err) => {
      console.error("Failed to copy: ", err);
      showErrorToast("Failed to copy to clipboard");
    });
}

// Toggle Password Visibility
function togglePasswordVisibility(inputId, toggleIcon) {
  const input = document.getElementById(inputId);
  const icon = toggleIcon.querySelector("i");
  if (!input || !icon) return;

  const isPassword = input.type === "password";
  input.type = isPassword ? "text" : "password";
  
  // Support FontAwesome
  if (icon.classList.contains("fas") || icon.classList.contains("fa-regular") || icon.classList.contains("fa-solid")) {
    icon.classList.toggle("fa-eye", !isPassword);
    icon.classList.toggle("fa-eye-slash", isPassword);
  }
  
  // Support Bootstrap Icons
  if (icon.classList.contains("bi")) {
    icon.classList.toggle("bi-eye", !isPassword);
    icon.classList.toggle("bi-eye-slash", isPassword);
  }

  toggleIcon.setAttribute("title", isPassword ? "Hide password" : "Show password");
}

// Initialize Password Toggles
function initPasswordToggles() {
  // Use event delegation on document so it works for all current and future toggles
  document.addEventListener("click", function (e) {
    const toggle = e.target.closest(".password-toggle");
    if (toggle) {
      e.preventDefault();
      const inputId = toggle.getAttribute("data-target");
      if (inputId) {
        togglePasswordVisibility(inputId, toggle);
      }
    }
  });
}

// Form Validation Helper
function validateForm(formId) {
  const form = document.getElementById(formId);
  let isValid = true;
  let firstInvalid = null;

  // Check required fields
  form.querySelectorAll("[required]").forEach((field) => {
    if (!field.value.trim()) {
      isValid = false;
      field.classList.add("is-invalid");
      if (!firstInvalid) firstInvalid = field;
    } else {
      field.classList.remove("is-invalid");
    }
  });

  // Scroll to first invalid field
  if (firstInvalid) {
    firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
    firstInvalid.focus();
  }

  return isValid;
}

// Confirm Dialog
function confirmDialog(
  title,
  text,
  confirmButtonText = "Yes",
  cancelButtonText = "Cancel"
) {
  return Swal.fire({
    title: title,
    text: text,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#198754",
    cancelButtonColor: "#dc3545",
    confirmButtonText: confirmButtonText,
    cancelButtonText: cancelButtonText,
    reverseButtons: true,
  });
}

// Auto-dismiss alerts
function initAutoDismissAlerts() {
  setTimeout(() => {
    document
      .querySelectorAll(".alert:not(.alert-permanent)")
      .forEach((alert) => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
      });
  }, 5000);
}

// Initialize when document is ready
document.addEventListener("DOMContentLoaded", function () {
  initPasswordToggles();
  initAutoDismissAlerts();

  // Add fade out animation to alerts
  const style = document.createElement("style");
  style.textContent = `
        .alert {
            transition: opacity 0.5s ease-out;
        }
        .alert.fade-out {
            opacity: 0;
        }
    `;
  document.head.appendChild(style);
});

// Export functions for use in other files
if (typeof module !== "undefined" && module.exports) {
  module.exports = {
    showSuccessToast,
    showErrorToast,
    showWarningToast,
    showInfoToast,
    showLoadingToast,
    checkPasswordStrength,
    copyToClipboard,
    togglePasswordVisibility,
    validateForm,
    confirmDialog,
  };
}
