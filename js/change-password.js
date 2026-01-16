// Change Password Page JavaScript

$(function () {
  // Ensure password toggles work even if utils.js is cached or not yet loaded
  function bindPasswordToggles() {
    $(".password-toggle")
      .off("click")
      .on("click", function () {
        const targetId = $(this).data("target");
        const input = document.getElementById(targetId);
        const icon = $(this).find("i");
        if (!input) return;
        const isPassword = input.type === "password";
        input.type = isPassword ? "text" : "password";
        icon.toggleClass("fa-eye fa-eye-slash");
        $(this).attr("title", isPassword ? "Hide password" : "Show password");
      });
  }

  // Bind toggles (fallback-friendly) and also call shared helper when present
  bindPasswordToggles();
  if (typeof initPasswordToggles === "function") {
    initPasswordToggles();
  }

  function checkPasswordStrength(password) {
    const len = password.length;
    const isValid = len >= 6;
    let level = "weak";
    let color = "#dc3545";
    let width = Math.min(len * 12, 100) + "%";

    if (len >= 10) {
      level = "strong";
      color = "#198754";
    } else if (len >= 6) {
      level = "medium";
      color = "#ffc107";
    }

    $("#passwordStrength")
      .attr("class", "password-strength-meter")
      .css("width", width)
      .css("background-color", color);
    $("#passwordStrengthText")
      .attr("class", "password-strength-text")
      .text(len ? `${len} karakter` : "");
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
        '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Passwords match</span>'
      );
      return true;
    }
    $("#passwordMatch").html(
      '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Passwords do not match</span>'
    );
    return false;
  }

  $("#new_password").on("input", function () {
    checkPasswordStrength(this.value);
    checkPasswordMatch();
  });
  $("#confirm_password").on("input", checkPasswordMatch);

  // Spinner loading saat tombol Batal ditekan (konsisten dengan dashboard)
  $(document).on("click", "a.btn.btn-outline-secondary", function (e) {
    if (this.href && this.href !== "#") {
      var pageLoading = document.getElementById("pageTransitionLoading");
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
