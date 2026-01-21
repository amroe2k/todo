// Tampilkan error login jika variabel global LOGIN_ERROR diset
if (typeof LOGIN_ERROR !== "undefined" && LOGIN_ERROR) {
  Swal.fire({
    icon: "error",
    title: "Login gagal",
    text: LOGIN_ERROR,
    confirmButtonText: "OK",
    background: "#ffffff",
    color: "#1a1a1a",
  });
}
// Custom script khusus halaman login.php

window.addEventListener("load", function () {
  const passwordToggle = document.querySelector(".password-toggle");
  if (passwordToggle) {
    const newToggle = passwordToggle.cloneNode(true);
    passwordToggle.parentNode.replaceChild(newToggle, passwordToggle);
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
  }
});
// Custom script khusus halaman login.php
