// Users Page JavaScript
$(function () {
  // Use global Toast and helper functions from utils.js
  // Helper clear local toast to use global Toast
  const localToast = typeof Toast !== 'undefined' ? Toast : Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
  });

  // Event listener for updating URL when tabs change
  $(document).on("shown.bs.tab", 'button[data-bs-toggle="pill"]', function (e) {
    const targetId = $(e.target).attr("data-bs-target").substring(1);
    const url = new URL(window.location);
    const params = url.searchParams;

    // Remove all tab parameters
    params.delete("pending_page");
    params.delete("approved_page");
    params.delete("inactive_page");
    params.delete("tab");

    // Add parameter according to active tab
    if (targetId === "pending") {
      params.set("pending_page", "1");
    } else if (targetId === "approved") {
      params.set("approved_page", "1");
    } else if (targetId === "inactive") {
      params.set("inactive_page", "1");
    } else if (targetId === "all-users") {
      params.set("tab", "all-users");
    }

    // Update URL without reload
    window.history.pushState({}, "", url.toString());
  });

  // Batal Approved (Unapprove) - Moved inside ready and fixed scope
  $(document).on("click", ".unapprove-btn", function () {
    const btn = $(this);
    const id = btn.data("id");
    const username = btn.data("username");
    
    Swal.fire({
      title: "Batalkan Approval?",
      text: `Yakin ingin membatalkan approval untuk "${username}"? User akan kembali ke status pending.`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Ya, Batalkan",
      cancelButtonText: "Batal",
      reverseButtons: true,
      customClass: {
        confirmButton: "btn btn-danger",
        cancelButton: "btn btn-secondary",
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: window.location.href,
          method: "POST",
          data: { id, action: "unapprove" },
          dataType: "json",
        })
          .done(function (res) {
            if (res && res.success) {
              if (typeof showSuccessToast === 'function') showSuccessToast("Approval dibatalkan!");
              else localToast.fire({ icon: 'success', title: 'Approval dibatalkan!' });
              setTimeout(() => location.reload(), 1000);
            } else {
              if (typeof showErrorToast === 'function') showErrorToast(res.message || "Gagal membatalkan approval");
              else localToast.fire({ icon: 'error', title: res.message || 'Gagal membatalkan approval' });
            }
          })
          .fail(function () {
             if (typeof showErrorToast === 'function') showErrorToast("Gagal menghubungi server");
             else localToast.fire({ icon: 'error', title: 'Network error' });
          });
      }
    });
  });

  // Password matching helpers
  function checkPasswordMatch() {
    const password = $("#new_password").val();
    const confirm = $("#confirm_password").val();
    const matchEl = $("#passwordMatch");
    if (!confirm) {
      matchEl.html("");
      return false;
    }
    if (password === confirm) {
      matchEl.html('<span class="text-success small"><i class="fas fa-check-circle me-1"></i>Passwords match</span>');
      return true;
    }
    matchEl.html('<span class="text-danger small"><i class="fas fa-times-circle me-1"></i>Passwords do not match</span>');
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
      matchEl.html('<span class="text-success small"><i class="fas fa-check-circle me-1"></i>Passwords match</span>');
      return true;
    }
    matchEl.html('<span class="text-danger small"><i class="fas fa-times-circle me-1"></i>Passwords do not match</span>');
    return false;
  }

  $("#new_password, #confirm_password").on("input", checkPasswordMatch);
  $("#add_password, #add_confirm_password").on("input", checkAddPasswordMatch);

  // User Actions: Edit
  $(document).on("click", ".edit-user", function () {
    const btn = $(this);
    $("#edit_id").val(btn.data("id"));
    $("#edit_username").val(btn.data("username"));
    $("#edit_email").val(btn.data("email"));
    $("#edit_role").val(btn.data("role"));
    $("#edit_status").val(btn.data("status"));
    $("#editUserModal").modal("show");
  });

  // User Actions: Add
  $("#addUserBtn").on("click", function () {
    $("#addUserForm")[0].reset();
    $("#addPasswordMatch").html("");
    $("#addUserModal").modal("show");
  });

  // User Actions: Import
  $("#importXlsxBtn").on("click", function () {
    $("#importXlsxForm")[0].reset();
    $("#importXlsxModal").modal("show");
  });

  // Form Submission: Import
  $("#importXlsxForm").on("submit", function (e) {
    e.preventDefault();
    const fileInput = $("#xlsx_file")[0];
    if (!fileInput.files || !fileInput.files[0]) {
      showErrorToast("Select an Excel file first");
      return;
    }

    if(typeof showLoadingToast === 'function') showLoadingToast("Importing users...");

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
            title: "Import Berhasil!",
            text: `${res.imported} pengguna berhasil diimpor.`,
            confirmButtonText: "Selesai"
          }).then(() => location.reload());
        } else {
          Swal.fire({
            icon: "error",
            title: "Gagal Impor",
            text: res.message || "Gagal mengimpor pengguna"
          });
        }
      })
      .fail(function () {
        Swal.close();
        showErrorToast("Network error");
      });
  });

  // Form Submission: Create User
  $("#addUserForm").on("submit", function (e) {
    e.preventDefault();
    if (!checkAddPasswordMatch()) {
      showErrorToast("Konfirmasi password tidak cocok");
      return;
    }
    const formData = $(this).serialize() + "&action=create";
    $.post(window.location.href, formData, function(res) {
        if(res && res.success) {
            showSuccessToast("User created successfully!");
            $("#addUserModal").modal("hide");
            setTimeout(() => location.reload(), 800);
        } else {
            showErrorToast(res.message || "Failed to create user");
        }
    }, "json").fail(() => showErrorToast("Network error"));
  });

  // Form Submission: Update User
  $("#editUserForm").on("submit", function (e) {
    e.preventDefault();
    const formData = $(this).serialize() + "&action=update";
    $.post(window.location.href, formData, function(res) {
        if(res && res.success) {
            showSuccessToast("User updated successfully!");
            $("#editUserModal").modal("hide");
            setTimeout(() => location.reload(), 800);
        } else {
            showErrorToast(res.message || "Failed to update user");
        }
    }, "json").fail(() => showErrorToast("Network error"));
  });

  // User Actions: Change Password Modal
  $(document).on("click", ".change-password", function () {
    const btn = $(this);
    $("#password_user_id").val(btn.data("id"));
    $("#passwordUsername").text(btn.data("username"));
    $("#changePasswordForm")[0].reset();
    $("#passwordMatch").html("");
    $("#changePasswordModal").modal("show");
  });

  // Form Submission: Change Password
  $("#changePasswordForm").on("submit", function (e) {
    e.preventDefault();
    if (!checkPasswordMatch()) {
      showErrorToast("Konfirmasi password tidak cocok");
      return;
    }
    const formData = $(this).serialize() + "&action=change_password";
    $.post(window.location.href, formData, function(res) {
        if(res && res.success) {
            showSuccessToast("Password changed!");
            $("#changePasswordModal").modal("hide");
        } else {
            showErrorToast(res.message || "Failed to change password");
        }
    }, "json").fail(() => showErrorToast("Network error"));
  });

  // User Actions: Generate Password
  $(document).on("click", ".generate-password", function () {
    const btn = $(this);
    const id = btn.data("id");
    const username = btn.data("username");
    Swal.fire({
      title: "Generate Password Baru?",
      text: `Sistem akan membuatkan password acak untuk "${username}".`,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Generate Now",
      customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-secondary" },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(window.location.href, { id, action: "generate_password" }, function(res) {
            if (res && res.success) {
                Swal.fire({
                    title: "Password Dibuat!",
                    html: `<p>Password baru adalah: <br><strong class="fs-4 d-block my-3 p-2 bg-light border rounded">${res.password}</strong></p><p class="small text-muted text-center">Simpan password ini baik-baik.</p>`,
                    icon: "success",
                    confirmButtonText: "Salin",
                    customClass: { confirmButton: "btn btn-success" },
                    buttonsStyling: false
                }).then(() => {
                    if(typeof copyToClipboard === 'function') copyToClipboard(res.password);
                });
            } else {
                showErrorToast(res.message || "Gagal membuat password");
            }
        }, "json").fail(() => showErrorToast("Network error"));
      }
    });
  });

  // User Actions: Delete
  $(document).on("click", ".delete-user", function () {
    const btn = $(this);
    const id = btn.data("id");
    const username = btn.data("username");
    Swal.fire({
      title: "Hapus Pengguna?",
      html: `Apakah Anda yakin ingin menghapus <strong>${username}</strong>?<br><span class="text-danger small">Tindakan ini tidak dapat dibatalkan.</span>`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Ya, Hapus",
      cancelButtonText: "Batal",
      reverseButtons: true,
      customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-secondary" },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(window.location.href, { id, action: "delete" }, function(res) {
            if (res && res.success) {
                showSuccessToast("Pengguna dihapus!");
                setTimeout(() => location.reload(), 800);
            } else {
                showErrorToast(res.message || "Gagal menghapus pengguna");
            }
        }, "json").fail(() => showErrorToast("Network error"));
      }
    });
  });

  // User Actions: Approve
  $(document).on("click", ".approve-user-btn, .approve-btn", function () {
    const btn = $(this);
    const id = btn.data("id");
    const username = btn.data("username");

    Swal.fire({
      title: "Approve User?",
      text: `Izinkan "${username}" untuk mengakses dashboard?`,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Ya, Izinkan",
      cancelButtonText: "Nanti",
      customClass: { confirmButton: "btn btn-success", cancelButton: "btn btn-secondary" },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(window.location.href, { id, action: "approve" }, function(res) {
            if (res && res.success) {
                showSuccessToast("User disetujui!");
                setTimeout(() => location.reload(), 1000);
            } else {
                showErrorToast(res.message || "Gagal menyetujui user");
            }
        }, "json").fail(() => showErrorToast("Network error"));
      }
    });
  });

  // User Actions: Reject
  $(document).on("click", ".reject-btn", function () {
    const btn = $(this);
    const id = btn.data("id");
    const username = btn.data("username");

    Swal.fire({
      title: "Reject Pendaftaran?",
      text: `Hapus pendaftaran "${username}" untuk selamanya?`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Ya, Tolak",
      cancelButtonText: "Batal",
      customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-secondary" },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(window.location.href, { id, action: "reject" }, function(res) {
            if (res && res.success) {
                showSuccessToast("Pendaftaran ditolak");
                setTimeout(() => location.reload(), 1000);
            } else {
                showErrorToast(res.message || "Gagal menolak");
            }
        }, "json").fail(() => showErrorToast("Network error"));
      }
    });
  });

  // User Actions: View as User
  $(document).on("click", ".view-as-user", function () {
    const btn = $(this);
    const id = btn.data("id");
    const username = btn.data("username");
    Swal.fire({
      title: "Lihat sebagai User?",
      html: `<p>Anda akan melihat dashboard sebagai <strong>${username}</strong>.</p><p class="text-muted small">Kembali ke Admin melalui menu profil.</p>`,
      icon: "info",
      showCancelButton: true,
      confirmButtonText: "Lanjutkan",
      cancelButtonText: "Batal",
      customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-secondary" },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(window.location.href, { user_id: id, action: "view_as_user" }, function(res) {
            if (res && res.success) {
                showSuccessToast("Beralih tampilan...");
                setTimeout(() => (location.href = "?page=dashboard"), 1000);
            } else {
                showErrorToast(res.message || "Gagal beralih");
            }
        }, "json").fail(() => showErrorToast("Network error"));
      }
    });
  });

  // AJAX Pagination logic
  let isLoadingData = false;
  
  $(document).on("click", ".pagination a.page-link", function (e) {
    if (isLoadingData) return;
    
    // Skip if disabled or already active
    if ($(this).parent().hasClass('disabled') || $(this).parent().hasClass('active')) return;

    e.preventDefault();
    const link = $(this).attr("href");
    if (!link || link === "#") return;

    isLoadingData = true;
    
    // Detect which tab we are paginating
    const tabPane = $(this).closest('.tab-pane');
    const tabId = tabPane.attr('id');
    const loadingOverlay = document.getElementById(`${tabId}-loading`) || document.getElementById('users-loading');
    const contentWrapper = document.getElementById(`${tabId}-cards-wrapper`) || document.getElementById('users-table-wrapper');
    const paginationNav = $(this).closest('nav');

    if (loadingOverlay) {
        loadingOverlay.classList.add('active');
    }

    fetch(link)
      .then((response) => response.text())
      .then((html) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        
        // Update content
        const newContent = doc.querySelector(`#${tabId}-cards-wrapper`) || doc.querySelector('#users-table-wrapper');
        const newPagination = doc.querySelector(`#${tabId}-pagination`) || doc.querySelector('#users-pagination');

        if (newContent && contentWrapper) {
          contentWrapper.innerHTML = newContent.innerHTML;
        }

        if (newPagination && paginationNav.length) {
          paginationNav[0].innerHTML = newPagination.innerHTML;
        }

        // URL Update
        window.history.pushState({}, "", link);

        setTimeout(() => {
          if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
          }
          isLoadingData = false;
          // Smooth scroll to top of tab
          tabPane[0].scrollIntoView({ behavior: "smooth", block: "start" });
        }, 400);
      })
      .catch(() => {
        isLoadingData = false;
        if (loadingOverlay) loadingOverlay.classList.remove('active');
        showErrorToast("Gagal memuat data");
      });
  });

  // Layout Switcher Logic
  $(document).on("click", ".btn-layout", function() {
    const btn = $(this);
    const layout = btn.data("layout");
    const switcher = btn.closest(".layout-switcher");
    const targetId = switcher.data("target");
    const target = $(`#${targetId}`);
    
    if (!target.length) return;
    
    // Toggle active state on buttons in THIS switcher
    switcher.find(".btn-layout").removeClass("active");
    btn.addClass("active");
    
    // Switch layout class
    if (layout === "list") {
      target.addClass("layout-list");
    } else {
      target.removeClass("layout-list");
    }
    
    // Save to localStorage for persistence
    localStorage.setItem(`user_layout_preference`, layout);
  });

  // Initialize layouts from localStorage on load
  function initLayouts() {
    const savedLayout = localStorage.getItem(`user_layout_preference`);
    if (savedLayout) {
      $(".layout-switcher").each(function() {
        const switcher = $(this);
        const btn = switcher.find(`.btn-layout[data-layout="${savedLayout}"]`);
        if (btn.length) {
          // Trigger logic without full click event to avoid side effects if any
          switcher.find(".btn-layout").removeClass("active");
          btn.addClass("active");
          const targetId = switcher.data("target");
          if (savedLayout === "list") $(`#${targetId}`).addClass("layout-list");
          else $(`#${targetId}`).removeClass("layout-list");
        }
      });
    }
  }

  initLayouts();
});
