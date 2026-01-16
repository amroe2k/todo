// Users Page JavaScript
$(function () {
  initPasswordToggles();

  // Event listener untuk update URL saat tab berubah
  $(document).on("shown.bs.tab", 'button[data-bs-toggle="pill"]', function (e) {
    const targetId = $(e.target).attr("data-bs-target").substring(1);
    const url = new URL(window.location);
    const params = url.searchParams;

    // Hapus semua parameter tab
    params.delete("pending_page");
    params.delete("approved_page");
    params.delete("inactive_page");
    params.delete("tab");

    // Tambahkan parameter sesuai tab yang aktif
    if (targetId === "pending") {
      params.set("pending_page", "1");
    } else if (targetId === "approved") {
      params.set("approved_page", "1");
    } else if (targetId === "inactive") {
      params.set("inactive_page", "1");
    } else if (targetId === "all-users") {
      params.set("tab", "all-users");
    }

    // Update URL tanpa reload
    window.history.pushState({}, "", url.toString());
  });

  // Fallback delegation for show/hide password toggles
  $(document).on("click", ".password-toggle", function (e) {
    e.preventDefault();
    const inputId = $(this).data("target");
    if (typeof togglePasswordVisibility === "function") {
      togglePasswordVisibility(inputId, this);
    }
  });

  const toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    background: "#ffffff",
    color: "#1a1a1a",
  });

  function showResult(response) {
    toast.fire({
      icon: response && response.success ? "success" : "error",
      title: (response && response.message) || "Operation failed",
    });
  }

  // Match backend rule: minimum 6 chars
  function isPasswordStrong(pwd) {
    return pwd && pwd.length >= 6;
  }

  function checkPasswordMatch() {
    const password = $("#new_password").val();
    const confirm = $("#confirm_password").val();
    const matchEl = $("#passwordMatch");
    if (!confirm) {
      matchEl.html("");
      return false;
    }
    if (password === confirm) {
      matchEl.html(
        '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Passwords match</span>'
      );
      return true;
    }
    matchEl.html(
      '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Passwords do not match</span>'
    );
    return false;
  }

  function checkAddPasswordMatch() {
    const password = $("#add_password").val();
    const confirm = $("#add_confirm_password").val();
    const matchEl = $("#addPasswordMatch");
    if (!confirm) {
      matchEl.html("");
      return false;
    }
    if (password === confirm) {
      matchEl.html(
        '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Passwords match</span>'
      );
      return true;
    }
    matchEl.html(
      '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Passwords do not match</span>'
    );
    return false;
  }

  $("#new_password").on("input", function () {
    const result = checkPasswordStrength(this.value);
    $("#passwordStrength")
      .attr("class", "password-strength-meter " + result.level)
      .css("width", result.width)
      .css("background-color", result.color);
    $("#passwordStrengthText")
      .attr("class", "password-strength-text " + result.level)
      .text(
        result.level.charAt(0).toUpperCase() +
          result.level.slice(1) +
          " password"
      );
    checkPasswordMatch();
  });

  $("#confirm_password").on("input", checkPasswordMatch);

  $("#add_password").on("input", checkAddPasswordMatch);
  $("#add_confirm_password").on("input", checkAddPasswordMatch);

  $(document).on("click", ".edit-user", function () {
    const btn = $(this);
    $("#edit_id").val(btn.data("id"));
    $("#edit_username").val(btn.data("username"));
    $("#edit_email").val(btn.data("email"));
    $("#edit_role").val(btn.data("role"));
    $("#edit_status").val(btn.data("status"));
    $("#editUserModal").modal("show");
  });

  $("#addUserBtn").on("click", function () {
    $("#addUserForm")[0].reset();
    $("#addPasswordMatch").html("");
    $("#addUserModal").modal("show");
  });

  $("#importXlsxBtn").on("click", function () {
    $("#importXlsxForm")[0].reset();
    $("#importXlsxModal").modal("show");
  });

  $("#importXlsxForm").on("submit", function (e) {
    e.preventDefault();

    const fileInput = $("#xlsx_file")[0];
    if (!fileInput.files || !fileInput.files[0]) {
      toast.fire({ icon: "error", title: "Please select an Excel file" });
      return;
    }

    Swal.fire({
      title: "Importing...",
      text: "Please wait while we import users",
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => {
        Swal.showLoading();
      },
    });

    const formData = new FormData(this);
    formData.append("action", "import_xlsx");

    $.ajax({
      url: window.location.href,
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
    })
      .done(function (res) {
        Swal.close();
        if (res && res.success) {
          Swal.fire({
            icon: "success",
            title: "Import Successful!",
            html: `<p>${res.imported} users imported successfully</p>${
              res.errors > 0
                ? '<p class="text-warning">' +
                  res.errors +
                  " errors occurred</p>"
                : ""
            }`,
            background: "#ffffff",
            color: "#1a1a1a",
          }).then(() => {
            $("#importXlsxModal").modal("hide");
            location.reload();
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Import Failed",
            text: res.message || "Failed to import users",
            background: "#ffffff",
            color: "#1a1a1a",
          });
        }
      })
      .fail(function () {
        Swal.close();
        toast.fire({ icon: "error", title: "Network error" });
      });
  });

  $("#addUserForm").on("submit", function (e) {
    e.preventDefault();
    const password = $("#add_password").val();
    if (!isPasswordStrong(password) || !checkAddPasswordMatch()) {
      toast.fire({
        icon: "error",
        title: "Password minimal 6 karakter dan harus sama",
      });
      return;
    }
    const formData = $(this).serialize() + "&action=create";
    $.ajax({
      url: window.location.href,
      method: "POST",
      data: formData,
      dataType: "json",
    })
      .done(function (res) {
        showResult(res);
        if (res && res.success) {
          $("#addUserModal").modal("hide");
          setTimeout(() => location.reload(), 800);
        }
      })
      .fail(function () {
        showResult({ success: false, message: "Network error" });
      });
  });

  $("#editUserForm").on("submit", function (e) {
    e.preventDefault();
    const formData = $(this).serialize() + "&action=update";
    $.ajax({
      url: window.location.href,
      method: "POST",
      data: formData,
      dataType: "json",
    })
      .done(function (res) {
        showResult(res);
        if (res && res.success) {
          $("#editUserModal").modal("hide");
          setTimeout(() => {
            // Reload dengan URL yang sudah ada (termasuk parameter tab)
            window.location.reload();
          }, 800);
        }
      })
      .fail(function () {
        showResult({ success: false, message: "Network error" });
      });
  });

  $(document).on("click", ".change-password", function () {
    const btn = $(this);
    $("#password_user_id").val(btn.data("id"));
    $("#passwordUsername").text(btn.data("username"));
    $("#changePasswordForm")[0].reset();
    $("#passwordStrength")
      .attr("class", "password-strength-meter")
      .css("width", "0");
    $("#passwordStrengthText").text("");
    $("#passwordMatch").html("");
    $("#changePasswordModal").modal("show");
  });

  $("#changePasswordForm").on("submit", function (e) {
    e.preventDefault();
    const password = $("#new_password").val();
    if (!isPasswordStrong(password) || !checkPasswordMatch()) {
      toast.fire({
        icon: "error",
        title: "Password minimal 6 karakter dan harus sama",
      });
      return;
    }
    const formData = $(this).serialize() + "&action=change_password";
    $.ajax({
      url: window.location.href,
      method: "POST",
      data: formData,
      dataType: "json",
    })
      .done(function (res) {
        showResult(res);
        if (res && res.success) {
          $("#changePasswordModal").modal("hide");
        }
      })
      .fail(function () {
        showResult({ success: false, message: "Network error" });
      });
  });

  $(document).on("click", ".generate-password", function () {
    const btn = $(this);
    const id = btn.data("id");
    const username = btn.data("username");
    Swal.fire({
      title: "Generate password?",
      text: "Password baru akan dibuat untuk " + username,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Generate",
      cancelButtonText: "Cancel",
      background: "#ffffff",
      color: "#1a1a1a",
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: window.location.href,
          method: "POST",
          data: { id, action: "generate_password" },
          dataType: "json",
        })
          .done(function (res) {
            if (res && res.success) {
              Swal.fire({
                title: "Password Generated",
                html: `<p><code>${res.password}</code></p><p class="text-muted">Copy and share securely.</p>`,
                icon: "success",
                confirmButtonText: "Copy",
                background: "#ffffff",
                color: "#1a1a1a",
              }).then(() => {
                copyToClipboard(res.password);
              });
            } else {
              showResult(res);
            }
          })
          .fail(function () {
            showResult({ success: false, message: "Network error" });
          });
      }
    });
  });

  $(document).on("click", ".delete-user", function () {
    const btn = $(this);
    const id = btn.data("id");
    const username = btn.data("username");
    Swal.fire({
      title: "Delete user?",
      html: `<p class="text-dark">Are you sure you want to delete <strong>${username}</strong>?</p><p class="text-danger fw-semibold">This action cannot be undone.</p>`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Delete",
      background: "#ffffff",
      color: "#1b1f23",
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: window.location.href,
          method: "POST",
          data: { id, action: "delete" },
          dataType: "json",
        })
          .done(function (res) {
            showResult(res);
            if (res && res.success) {
              setTimeout(() => location.reload(), 800);
            }
          })
          .fail(function () {
            showResult({ success: false, message: "Network error" });
          });
      }
    });
  });

  // Approve user from table or pending tab
  $(document).on("click", ".approve-user-btn, .approve-btn", function () {
    const btn = $(this);
    const id = btn.data("id");
    const username = btn.data("username");

    Swal.fire({
      title: "Approve User?",
      text: `Are you sure you want to approve "${username}"?`,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#28a745",
      cancelButtonColor: "#6c757d",
      confirmButtonText: '<i class="fas fa-check me-2"></i>Yes, Approve!',
      cancelButtonText: '<i class="fas fa-times me-2"></i>Cancel',
      background: "#ffffff",
      color: "#1a1a1a",
      buttonsStyling: false,
      customClass: {
        confirmButton: "btn btn-success",
        cancelButton: "btn btn-secondary ms-2",
      },
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: window.location.href,
          method: "POST",
          data: { id, action: "approve" },
          dataType: "json",
        })
          .done(function (res) {
            if (res && res.success) {
              showSuccessToast("User Approved!");
              setTimeout(() => location.reload(), 1000);
            } else {
              showErrorToast(res.message || "Failed to approve user");
            }
          })
          .fail(function () {
            showErrorToast("Network error");
          });
      }
    });
  });

  // Reject user
  $(document).on("click", ".reject-btn", function () {
    const btn = $(this);
    const id = btn.data("id");
    const username = btn.data("username");

    Swal.fire({
      title: "Reject User?",
      text: `Are you sure you want to reject "${username}"? This will delete their registration.`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: '<i class="fas fa-check me-2"></i>Yes, Reject!',
      cancelButtonText: '<i class="fas fa-times me-2"></i>Cancel',
      background: "#ffffff",
      color: "#1a1a1a",
      buttonsStyling: false,
      customClass: {
        confirmButton: "btn btn-danger",
        cancelButton: "btn btn-secondary ms-2",
      },
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: window.location.href,
          method: "POST",
          data: { id, action: "reject" },
          dataType: "json",
        })
          .done(function (res) {
            if (res && res.success) {
              showSuccessToast("User Rejected");
              setTimeout(() => location.reload(), 1000);
            } else {
              showErrorToast(res.message || "Failed to reject user");
            }
          })
          .fail(function () {
            showErrorToast("Network error");
          });
      }
    });
  });

  // View as User
  $(document).on("click", ".view-as-user", function () {
    const btn = $(this);
    const id = btn.data("id");
    const username = btn.data("username");
    Swal.fire({
      title: "View as user?",
      html: `<p>You will see the dashboard from <strong>${username}</strong>'s perspective.</p><p class="text-muted">You can return to admin by clicking "Back to Admin" in the profile menu.</p>`,
      icon: "info",
      showCancelButton: true,
      confirmButtonColor: "#0d6efd",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Continue",
      background: "#ffffff",
      color: "#1a1a1a",
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: window.location.href,
          method: "POST",
          data: { user_id: id, action: "view_as_user" },
          dataType: "json",
        })
          .done(function (res) {
            if (res && res.success) {
              showSuccessToast("Switched to user view");
              setTimeout(() => (location.href = "?page=dashboard"), 1000);
            } else {
              showErrorToast(res.message || "Failed to switch user view");
            }
          })
          .fail(function () {
            showErrorToast("Network error");
          });
      }
    });
  });
});

// Keep view at filter box on pagination and filter actions
document.addEventListener("DOMContentLoaded", function () {
  const paginationLinks = document.querySelectorAll(
    "#users-pagination-list a.page-link"
  );
  const filterForm = document.getElementById("users-filter-form");
  const clearFilterBtn = document.getElementById("clear-filter-btn");
  const filterBox = document.getElementById("users-filter-box");

  // Save filter box position on pagination click
  paginationLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      if (filterBox) {
        const filterBoxTop =
          filterBox.getBoundingClientRect().top + window.pageYOffset;
        sessionStorage.setItem("usersFilterPosition", filterBoxTop - 80); // 80px offset for navbar
      }
    });
  });

  // Save filter box position on filter submit
  if (filterForm) {
    filterForm.addEventListener("submit", function (e) {
      if (filterBox) {
        const filterBoxTop =
          filterBox.getBoundingClientRect().top + window.pageYOffset;
        sessionStorage.setItem("usersFilterPosition", filterBoxTop - 80);
      }
    });
  }

  // Save filter box position on clear filter click
  if (clearFilterBtn) {
    clearFilterBtn.addEventListener("click", function (e) {
      if (filterBox) {
        const filterBoxTop =
          filterBox.getBoundingClientRect().top + window.pageYOffset;
        sessionStorage.setItem("usersFilterPosition", filterBoxTop - 80);
      }
    });
  }

  // Restore scroll position to filter box after page load
  const savedFilterPosition = sessionStorage.getItem("usersFilterPosition");
  if (savedFilterPosition !== null) {
    setTimeout(function () {
      window.scrollTo({
        top: parseInt(savedFilterPosition),
        behavior: "instant",
      });
      sessionStorage.removeItem("usersFilterPosition");
    }, 50);
  }

  // AJAX Pagination for Users Table
  let isLoadingUsers = false; // Flag to prevent multiple requests
  let isLoadingPending = false;
  let isLoadingApproved = false;
  let isLoadingInactive = false;

  // AJAX Pagination untuk Pending Approvals
  $(document).on("click", "#pending-pagination a.page-link", function (e) {
    e.preventDefault();
    if (isLoadingPending) return;

    const link = $(this).attr("href");
    if (!link || link === "#") return;

    isLoadingPending = true;
    const loadingOverlay = document.getElementById("pending-loading");
    const cardsWrapper = document.getElementById("pending-cards-wrapper");
    const paginationNav = document.getElementById("pending-pagination");

    if (!loadingOverlay || !cardsWrapper) return;

    // Sembunyikan konten lama terlebih dahulu untuk menghindari flicker
    cardsWrapper.style.opacity = "0";
    if (paginationNav) paginationNav.style.opacity = "0";

    // Tampilkan loading overlay dengan smooth
    loadingOverlay.style.display = "flex";
    const loadingStartTime = Date.now();
    requestAnimationFrame(() => {
      loadingOverlay.style.opacity = "1";
    });

    fetch(link)
      .then((response) => response.text())
      .then((html) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const newCards = doc.querySelector("#pending-cards-wrapper");
        const newPagination = doc.querySelector("#pending-pagination");

        // Update URL dengan parameter halaman baru
        const url = new URL(link, window.location.origin);
        window.history.pushState({}, "", url.toString());

        // Minimum loading duration 400ms agar spinner terlihat
        const loadingDuration = Date.now() - loadingStartTime;
        const minDuration = 400;
        const remainingTime = Math.max(0, minDuration - loadingDuration);

        setTimeout(() => {
          // Update konten tanpa delay untuk menghindari flicker
          if (newCards) {
            cardsWrapper.innerHTML = newCards.innerHTML;
            cardsWrapper.style.opacity = "0";

            // Animasi fade in untuk cards
            requestAnimationFrame(() => {
              cardsWrapper.style.opacity = "1";
              const cards = cardsWrapper.querySelectorAll(".col-md-4");
              cards.forEach((card, index) => {
                card.style.opacity = "0";
                card.style.transform = "translateY(20px)";
                setTimeout(() => {
                  card.style.transition =
                    "opacity 0.4s ease, transform 0.4s ease";
                  card.style.opacity = "1";
                  card.style.transform = "translateY(0)";
                }, index * 100);
              });
            });
          }

          if (newPagination && paginationNav) {
            paginationNav.innerHTML = newPagination.innerHTML;
            paginationNav.style.opacity = "1";
          }

          // Sembunyikan loading overlay
          loadingOverlay.style.opacity = "0";
          setTimeout(() => {
            loadingOverlay.style.display = "none";
            isLoadingPending = false;
            document
              .querySelector("#pending")
              .scrollIntoView({ behavior: "smooth", block: "start" });
          }, 300);
        }, remainingTime);
      })
      .catch((error) => {
        console.error("Error loading pending users:", error);
        loadingOverlay.style.opacity = "0";
        setTimeout(() => {
          loadingOverlay.style.display = "none";
          cardsWrapper.style.opacity = "1";
          if (paginationNav) paginationNav.style.opacity = "1";
          isLoadingPending = false;
        }, 300);
      });
  });

  // AJAX Pagination untuk Approved Users
  $(document).on("click", "#approved-pagination a.page-link", function (e) {
    e.preventDefault();
    if (isLoadingApproved) return;

    const link = $(this).attr("href");
    if (!link || link === "#") return;

    isLoadingApproved = true;
    const loadingOverlay = document.getElementById("approved-loading");
    const cardsWrapper = document.getElementById("approved-cards-wrapper");
    const paginationNav = document.getElementById("approved-pagination");

    if (!loadingOverlay || !cardsWrapper) return;

    // Sembunyikan konten lama terlebih dahulu untuk menghindari flicker
    cardsWrapper.style.opacity = "0";
    if (paginationNav) paginationNav.style.opacity = "0";

    // Tampilkan loading overlay dengan smooth
    loadingOverlay.style.display = "flex";
    const loadingStartTime = Date.now();
    requestAnimationFrame(() => {
      loadingOverlay.style.opacity = "1";
    });

    fetch(link)
      .then((response) => response.text())
      .then((html) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const newCards = doc.querySelector("#approved-cards-wrapper");
        const newPagination = doc.querySelector("#approved-pagination");

        // Update URL dengan parameter halaman baru
        const url = new URL(link, window.location.origin);
        window.history.pushState({}, "", url.toString());

        // Minimum loading duration 400ms agar spinner terlihat
        const loadingDuration = Date.now() - loadingStartTime;
        const minDuration = 400;
        const remainingTime = Math.max(0, minDuration - loadingDuration);

        setTimeout(() => {
          // Update konten tanpa delay untuk menghindari flicker
          if (newCards) {
            cardsWrapper.innerHTML = newCards.innerHTML;
            cardsWrapper.style.opacity = "0";

            // Animasi fade in untuk cards
            requestAnimationFrame(() => {
              cardsWrapper.style.opacity = "1";
              const cards = cardsWrapper.querySelectorAll(".col");
              cards.forEach((card, index) => {
                card.style.opacity = "0";
                card.style.transform = "translateY(20px)";
                setTimeout(() => {
                  card.style.transition =
                    "opacity 0.4s ease, transform 0.4s ease";
                  card.style.opacity = "1";
                  card.style.transform = "translateY(0)";
                }, index * 100);
              });
            });
          }

          if (newPagination && paginationNav) {
            paginationNav.innerHTML = newPagination.innerHTML;
            paginationNav.style.opacity = "1";
          }

          // Sembunyikan loading overlay
          loadingOverlay.style.opacity = "0";
          setTimeout(() => {
            loadingOverlay.style.display = "none";
            isLoadingApproved = false;
            document
              .querySelector("#approved")
              .scrollIntoView({ behavior: "smooth", block: "start" });
          }, 300);
        }, remainingTime);
      })
      .catch((error) => {
        console.error("Error loading approved users:", error);
        loadingOverlay.style.opacity = "0";
        setTimeout(() => {
          loadingOverlay.style.display = "none";
          cardsWrapper.style.opacity = "1";
          if (paginationNav) paginationNav.style.opacity = "1";
          isLoadingApproved = false;
        }, 300);
      });
  });

  // AJAX Pagination untuk Inactive Users
  $(document).on("click", "#inactive-pagination a.page-link", function (e) {
    e.preventDefault();
    if (isLoadingInactive) return;

    const link = $(this).attr("href");
    if (!link || link === "#") return;

    isLoadingInactive = true;
    const loadingOverlay = document.getElementById("inactive-loading");
    const cardsWrapper = document.getElementById("inactive-cards-wrapper");
    const paginationNav = document.getElementById("inactive-pagination");

    if (!loadingOverlay || !cardsWrapper) return;

    // Sembunyikan konten lama terlebih dahulu untuk menghindari flicker
    cardsWrapper.style.opacity = "0";
    if (paginationNav) paginationNav.style.opacity = "0";

    // Tampilkan loading overlay dengan smooth
    loadingOverlay.style.display = "flex";
    const loadingStartTime = Date.now();
    requestAnimationFrame(() => {
      loadingOverlay.style.opacity = "1";
    });

    fetch(link)
      .then((response) => response.text())
      .then((html) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const newCards = doc.querySelector("#inactive-cards-wrapper");
        const newPagination = doc.querySelector("#inactive-pagination");

        // Update URL dengan parameter halaman baru
        const url = new URL(link, window.location.origin);
        window.history.pushState({}, "", url.toString());

        // Minimum loading duration 400ms agar spinner terlihat
        const loadingDuration = Date.now() - loadingStartTime;
        const minDuration = 400;
        const remainingTime = Math.max(0, minDuration - loadingDuration);

        setTimeout(() => {
          // Update konten tanpa delay untuk menghindari flicker
          if (newCards) {
            cardsWrapper.innerHTML = newCards.innerHTML;
            cardsWrapper.style.opacity = "0";

            // Animasi fade in untuk cards
            requestAnimationFrame(() => {
              cardsWrapper.style.opacity = "1";
              const cards = cardsWrapper.querySelectorAll(".col");
              cards.forEach((card, index) => {
                card.style.opacity = "0";
                card.style.transform = "translateY(20px)";
                setTimeout(() => {
                  card.style.transition =
                    "opacity 0.4s ease, transform 0.4s ease";
                  card.style.opacity = "1";
                  card.style.transform = "translateY(0)";
                }, index * 100);
              });
            });
          }

          if (newPagination && paginationNav) {
            paginationNav.innerHTML = newPagination.innerHTML;
            paginationNav.style.opacity = "1";
          }

          // Sembunyikan loading overlay
          loadingOverlay.style.opacity = "0";
          setTimeout(() => {
            loadingOverlay.style.display = "none";
            isLoadingInactive = false;
            // Tetap fokus pada pagination (bagian bawah) tanpa scroll ke atas
            if (paginationNav) {
              paginationNav.scrollIntoView({
                behavior: "smooth",
                block: "end",
              });
            }
          }, 300);
        }, remainingTime);
      })
      .catch((error) => {
        console.error("Error loading inactive users:", error);
        loadingOverlay.style.opacity = "0";
        setTimeout(() => {
          loadingOverlay.style.display = "none";
          cardsWrapper.style.opacity = "1";
          if (paginationNav) paginationNav.style.opacity = "1";
          isLoadingInactive = false;
        }, 300);
      });
  });

  $(document).on("click", "#users-pagination a.page-link", function (e) {
    if (!$(this).attr("data-page") || isLoadingUsers) {
      return;
    }

    e.preventDefault();
    e.stopPropagation();

    isLoadingUsers = true; // Set loading flag
    const pageNum = $(this).attr("data-page");
    const url = new URL(window.location.href);
    url.searchParams.set("p", pageNum);

    // Show loading overlay with smooth animation
    const usersLoading = document.getElementById("users-loading");
    const tableWrapper = document.querySelector("#users-table-wrapper");

    if (usersLoading) {
      usersLoading.style.display = "flex";
      usersLoading.offsetHeight; // Force reflow
      usersLoading.classList.add("active");
    }

    // Don't fade table during loading to prevent double fade effect
    if (tableWrapper) {
      tableWrapper.style.transition = "none";
    }

    // Fetch new page content
    fetch(url.toString())
      .then((response) => response.text())
      .then((html) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const newTableWrapper = doc.querySelector("#users-table-wrapper");
        const newPagination = doc.querySelector("#users-pagination");

        if (newTableWrapper && tableWrapper) {
          setTimeout(() => {
            tableWrapper.innerHTML = newTableWrapper.innerHTML;
            tableWrapper.style.transition = "";

            // Trigger staggered animation for new table rows
            const rows = tableWrapper.querySelectorAll("tbody tr");
            rows.forEach((row, index) => {
              row.style.animation = "none";
              row.offsetHeight; // Force reflow
              row.style.animation = `slideInActivity 0.4s ease forwards ${
                index * 0.1
              }s`;
            });
          }, 100);
        }

        if (newPagination) {
          const currentPagination = document.getElementById("users-pagination");
          if (currentPagination) {
            setTimeout(() => {
              currentPagination.innerHTML = newPagination.innerHTML;
            }, 100);
          }
        }

        // Hide loading overlay with optimized timing
        setTimeout(() => {
          if (usersLoading) {
            usersLoading.classList.remove("active");
            setTimeout(() => {
              usersLoading.style.display = "none";
              isLoadingUsers = false; // Reset loading flag
            }, 250);
          } else {
            isLoadingUsers = false; // Reset loading flag
          }

          // Scroll to users table with smooth behavior
          requestAnimationFrame(() => {
            const usersCard = document.getElementById("users-table-card");
            if (usersCard) {
              const rect = usersCard.getBoundingClientRect();
              const offset = 100;
              window.scrollTo({
                top: window.pageYOffset + rect.top - offset,
                behavior: "smooth",
              });
            }
          });
        }, 350); // Optimized timing

        // Update URL without reload
        history.pushState({ page: pageNum }, "", url.toString());
      })
      .catch((error) => {
        console.error("Error loading page:", error);
        if (usersLoading) {
          usersLoading.classList.remove("active");
          setTimeout(() => {
            usersLoading.style.display = "none";
            isLoadingUsers = false; // Reset loading flag
          }, 250);
        } else {
          isLoadingUsers = false; // Reset loading flag
        }
        if (tableWrapper) {
          tableWrapper.style.transition = "";
        }
        toast.fire({
          icon: "error",
          title: "Failed to load page",
        });
      });
  });

  // Handle browser back/forward buttons
  window.addEventListener("popstate", function () {
    location.reload();
  });
});

// Disable Bootstrap autofill overlay
(function () {
  if (!window.bootstrap || !bootstrap.Autofill) return;
  try {
    if (bootstrap.Autofill._observer) {
      bootstrap.Autofill._observer.disconnect();
    }
    document
      .querySelectorAll(".bs-autofill-overlay")
      .forEach((el) => el.remove());
    bootstrap.Autofill._elements = [];
  } catch (e) {
    // swallow
  }
})();
