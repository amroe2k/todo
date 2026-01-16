// Custom script khusus halaman register.php

window.addEventListener("load", function () {
  const passwordToggles = document.querySelectorAll(".password-toggle");
  passwordToggles.forEach(function (toggle) {
    const newToggle = toggle.cloneNode(true);
    toggle.parentNode.replaceChild(newToggle, toggle);
    newToggle.addEventListener("click", function () {
      const inputId = this.getAttribute("data-target");
      const input = document.getElementById(inputId);
      const icon = this.querySelector("i");
      if (input && icon) {
        if (input.type === "password") {
          input.type = "text";
          icon.classList.remove("fa-eye");
          icon.classList.add("fa-eye-slash");
        } else {
          input.type = "password";
          icon.classList.remove("fa-eye-slash");
          icon.classList.add("fa-eye");
        }
      }
    });
  });
});

// Show server messages (success/error) using SweetAlert2
function showServerMessage(type, message) {
  Swal.fire({
    icon: type,
    title: type === "success" ? "Registrasi Berhasil" : "Registrasi Gagal",
    text: message,
    confirmButtonText: "OK",
    background: "#ffffff",
    color: "#1a1a1a",
  });
}
// Custom script khusus halaman register.php
