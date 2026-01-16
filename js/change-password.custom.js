// Enhance UX: show loading overlay on submit
$(function () {
  $("#changePasswordForm").on("submit", function () {
    $("#change-password-loading").fadeIn(150);
    $("#savePasswordBtn").attr("disabled", true);
    $("#savePasswordSpinner").removeClass("d-none");
  });
});
// Password toggle
$(document).on("click", ".password-toggle", function () {
  var target = $(this).data("target");
  var input = $("#" + target);
  var icon = $(this).find("i");
  if (input.attr("type") === "password") {
    input.attr("type", "text");
    icon.removeClass("fa-eye").addClass("fa-eye-slash");
  } else {
    input.attr("type", "password");
    icon.removeClass("fa-eye-slash").addClass("fa-eye");
  }
});

// Show server-side messages (called from inline PHP)
$(function () {
  if (window.changePasswordError) {
    showPasswordMessage("error", "Gagal", window.changePasswordError);
  }
  if (window.changePasswordSuccess) {
    showPasswordMessage("success", "Berhasil", window.changePasswordSuccess);
  }
});
