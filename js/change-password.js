// Change Password Page JavaScript

$(function () {
  // Cleanup: Shared logic from utils.js is now used for toggles

  function checkPasswordStrength(password) {
    const len = password.length;
    const isValid = len >= 6;
    let color = "#e2e8f0"; // default
    let width = "0%";

    if (len > 0) {
        width = Math.min(len * 10, 100) + "%";
        if (len < 6) color = "#ef4444"; // red-500
        else if (len < 10) color = "#f59e0b"; // amber-500
        else color = "#10b981"; // emerald-500
    }

    $("#passwordStrength")
      .css("width", width)
      .css("background-color", color);
      
    let strengthLabel = "";
    if (len > 0) {
        if (len < 6) strengthLabel = "Weak";
        else if (len < 10) strengthLabel = "Good";
        else strengthLabel = "Strong";
    }
    
    $("#passwordStrengthText")
      .css("color", color)
      .text(strengthLabel);
      
    return isValid;
  }

  function checkPasswordMatch() {
    const p = $("#new_password").val();
    const c = $("#confirm_password").val();
    if (!c) {
      $("#passwordMatch").html("");
      return false;
    }
    if (p === c) {
      $("#passwordMatch").html(
        '<span class="text-success small fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Passwords match</span>'
      );
      return true;
    }
    $("#passwordMatch").html(
      '<span class="text-danger small fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Passwords do not match</span>'
    );
    return false;
  }

  $("#new_password").on("input", function () {
    checkPasswordStrength(this.value);
    checkPasswordMatch();
  });
  $("#confirm_password").on("input", checkPasswordMatch);

  // High-end submission transition
  $("#changePasswordForm").on("submit", function() {
    const overlay = document.getElementById("change-password-loading");
    if (overlay) {
        overlay.classList.add("active");
    }
    $("#savePasswordBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
  });

  // Page Transition for Batal link
  $(document).on("click", "a.btn-outline-secondary", function (e) {
    const href = $(this).attr("href");
    if (href && href !== "#") {
      const pageLoading = document.getElementById("pageSkeletonOverlay");
      if (pageLoading) {
        pageLoading.classList.add("active");
      }
    }
  });
});

// Show server-side messages (called from inline PHP)
function showPasswordMessage(type, title, message) {
  Swal.fire({
    icon: type,
    title: title,
    text: message,
    confirmButtonText: "OK",
    background: "#ffffff",
    color: "#1a1a1a",
  });
}
