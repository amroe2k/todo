// Todos Page JavaScript

// Toast Configuration
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

// Info Toast Configuration
const InfoToast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 4000,
  timerProgressBar: true,
  iconColor: "#0dcaf0",
  background: "#d1ecf1",
  color: "#0c5460",
  customClass: {
    popup: "border-start border-info border-5",
  },
});

$(document).ready(function () {
  const emptyBtn = document.getElementById("emptyAddTodoBtn");
  if (emptyBtn && typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
    new bootstrap.Tooltip(emptyBtn, {
      title: "Create your first todo",
      placement: "top",
    });
  }

  // Set today as default min date for due date
  var today = new Date().toISOString().split("T")[0];
  $("#due_date").attr("min", today);
  $("#edit_due_date").attr("min", today);

  // Add Todo
  $("#addTodoForm").submit(function (e) {
    e.preventDefault();

    Swal.fire({
      title: "Creating Todo...",
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => {
        Swal.showLoading();
      },
    });

    const formData = $(this).serialize() + "&action=create";

    $.ajax({
      url: window.location.href,
      method: "POST",
      data: formData,
      dataType: "json",
    })
      .done(function (response) {
        Swal.close();
        if (response && response.success) {
          Toast.fire({
            icon: "success",
            title: "Todo created successfully!",
          });
          $("#addTodoModal").modal("hide");
          $("#addTodoForm")[0].reset();
          $("#addTodoModal #priority").val("medium");
          $("#addTodoModal #status").val("pending");

          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          Toast.fire({
            icon: "error",
            title: (response && response.message) || "Failed to create todo",
          });
        }
      })
      .fail(function () {
        Swal.close();
        Toast.fire({
          icon: "error",
          title: "Network error! Please try again.",
        });
      });
  });

  // Edit Todo
  $(document).on("click", ".edit-todo", function () {
    var id = $(this).data("id");
    var task = $(this).data("task");
    var description = $(this).data("description");
    var priority = $(this).data("priority");
    var status = $(this).data("status");
    var due_date = $(this).data("due_date");

    $("#edit_id").val(id);
    $("#edit_task").val(task);
    $("#edit_description").val(description);
    $("#edit_priority").val(priority);
    $("#edit_status").val(status);

    if (due_date && due_date !== "0000-00-00") {
      $("#edit_due_date").val(due_date);
    } else {
      $("#edit_due_date").val("");
    }

    $("#editTodoModal").modal("show");
  });

  // Update Todo
  $("#editTodoForm").submit(function (e) {
    e.preventDefault();

    Swal.fire({
      title: "Updating Todo...",
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => {
        Swal.showLoading();
      },
    });

    const formData = $(this).serialize() + "&action=update";

    $.ajax({
      url: window.location.href,
      method: "POST",
      data: formData,
      dataType: "json",
    })
      .done(function (response) {
        Swal.close();
        if (response && response.success) {
          Toast.fire({
            icon: "success",
            title: "Todo updated successfully!",
          });
          $("#editTodoModal").modal("hide");

          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          Toast.fire({
            icon: "error",
            title: (response && response.message) || "Failed to update todo",
          });
        }
      })
      .fail(function () {
        Swal.close();
        Toast.fire({
          icon: "error",
          title: "Network error! Please try again.",
        });
      });
  });

  // Update Status via checkbox
  $(document).on("change", ".status-checkbox", function () {
    const checkbox = $(this);
    const id = checkbox.data("id");
    const isCompleted = this.checked;
    const status = isCompleted ? "completed" : "pending";
    const todoItem = checkbox.closest(".todo-item");
    const statusBadge = todoItem.find(".status-badge");
    
    // Save original state
    const originalBadgeContent = statusBadge.html();
    
    // Disable interaction during update
    checkbox.prop("disabled", true);
    statusBadge.html('<i class="fas fa-spinner fa-spin me-1"></i> Updating...');

    $.ajax({
      url: window.location.href,
      method: "POST",
      data: { id: id, status: status, action: "update_status" },
      dataType: "json",
    })
      .done(function (response) {
        checkbox.prop("disabled", false);
        if (response && response.success) {
          Toast.fire({
            icon: "success",
            title: isCompleted ? "Tugas selesai!" : "Tugas dikembalikan ke pending",
          });
          
          // Update UI in-place
          todoItem.removeClass("pending completed in_progress").addClass(status);
          
          if (isCompleted) {
            statusBadge.removeClass("bg-warning bg-info").addClass("bg-success")
                       .html('<i class="fas fa-check-circle me-1"></i> Completed');
          } else {
            statusBadge.removeClass("bg-success bg-info").addClass("bg-warning")
                       .html('<i class="fas fa-clock me-1"></i> Pending');
          }
        } else {
          statusBadge.html(originalBadgeContent);
          checkbox.prop("checked", !isCompleted);
          Toast.fire({
            icon: "error",
            title: (response && response.message) || "Failed to update status",
          });
        }
      })
      .fail(function () {
        checkbox.prop("disabled", false);
        statusBadge.html(originalBadgeContent);
        checkbox.prop("checked", !isCompleted);
        Toast.fire({
          icon: "error",
          title: "Network error!",
        });
      });
  });

  // Archive Todo
  $(document).on("click", ".archive-todo", function () {
    const id = $(this).data("id");
    Swal.fire({
      title: "Archive Todo?",
      text: "Todo will be hidden from main list and dashboard stats.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Archive",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#6c757d",
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: window.location.href,
          method: "POST",
          data: { id: id, action: "archive" },
          dataType: "json",
        })
          .done(function (response) {
            if (response && response.success) {
              Toast.fire({ icon: "success", title: "Todo archived" });
              setTimeout(() => {
                window.location.reload();
              }, 600);
            } else {
              Toast.fire({
                icon: "error",
                title: (response && response.message) || "Failed to archive",
              });
            }
          })
          .fail(function (xhr) {
            const msg =
              xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : "Network error! Please try again.";
            Toast.fire({ icon: "error", title: msg });
          });
      }
    });
  });

  // Delete Todo
  $(document).on("click", ".delete-todo", function () {
    var id = $(this).data("id");
    var todoItem = $(this).closest(".todo-item");
    var taskTitle = todoItem.find(".todo-task").text();

    Swal.fire({
      title: "Delete Todo?",
      html: `<p>Are you sure you want to delete todo <strong>"${taskTitle}"</strong>?</p>
                  <p class="text-danger">
                      <i class="fas fa-exclamation-triangle me-1"></i>
                      This action cannot be undone!
                  </p>`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "Cancel",
      reverseButtons: true,
      customClass: {
        actions: "my-actions",
        confirmButton: "btn btn-danger",
        cancelButton: "btn btn-secondary",
      },
    }).then((result) => {
      if (result.isConfirmed) {
        // Show loading
        Swal.fire({
          title: "Deleting Todo...",
          text: "Please wait while we delete your todo",
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
            Swal.showLoading();
          },
          customClass: {},
        });

        $.ajax({
          url: window.location.href,
          method: "POST",
          data: { id: id, action: "delete" },
          dataType: "json",
        })
          .done(function (response) {
            Swal.close();
            if (response && response.success) {
              Toast.fire({
                icon: "success",
                title: "Todo deleted successfully!",
                customClass: {},
              });

              todoItem.fadeOut(400, function () {
                $(this).remove();

                if ($(".todo-item").length === 0) {
                  const currentFilter = new URLSearchParams(window.location.search).get('filter') || 'all';
                  if (currentFilter !== 'all') {
                      window.location.reload();
                  } else {
                      $(".card-body").first().html(`
                        <div class="py-5 text-center">
                            <div class="mb-4">
                                <i class="bi bi-clipboard-x text-muted opacity-25" style="font-size: 4.5rem;"></i>
                                <h4 class="fw-bold mt-3" style="color: #12305b;">Tugas Masih Kosong</h4>
                                <p class="text-muted">Klik tombol di bawah untuk mulai mencatat tugas Anda hari ini.</p>
                            </div>
                            <button class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center mx-auto shadow-sm" 
                                    style="width: 60px; height: 60px; border: none; transition: all 0.2s ease-in-out;"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#addTodoModal"
                                    onmouseover="this.style.transform='scale(1.1)';"
                                    onmouseout="this.style.transform='scale(1)';"
                                    title="Tambah Tugas Baru">
                                <i class="bi bi-plus-lg" style="font-size: 1.5rem; color: white;"></i>
                            </button>
                        </div>
                      `);
                  }
                }
              });
            } else {
              Swal.fire({
                title: "Failed to Delete Todo",
                text:
                  (response && response.message) ||
                  "There was an error deleting your todo. Please try again.",
                icon: "info",
                iconColor: "#0dcaf0",
                confirmButtonColor: "#0dcaf0",
                confirmButtonText: "OK",
                customClass: {
                  icon: "swal2-icon-info-custom",
                  title: "text-info",
                  confirmButton: "btn btn-info",
                },
              }).then(() => {
                InfoToast.fire({
                  icon: "info",
                  title: "Tip: Check if the todo is still in use elsewhere",
                });
              });
            }
          })
          .fail(function (xhr, status) {
            Swal.close();

            Swal.fire({
              title: "Network Error",
              html: `<p>Failed to delete todo due to network issues.</p>
                              <p><small>Status: ${status}</small></p>`,
              icon: "error",
              confirmButtonColor: "#dc3545",
              confirmButtonText: "Try Again",
              showCancelButton: true,
              cancelButtonText: "Cancel",
              customClass: {
                icon: "swal2-icon-error-custom",
              },
            }).then((result) => {
              if (result.isConfirmed) {
                $('.delete-todo[data-id="' + id + '"]').trigger("click");
              } else {
                const ErrorInfoToast = Swal.mixin({
                  toast: true,
                  position: "top-end",
                  showConfirmButton: false,
                  timer: 5000,
                  timerProgressBar: true,
                  iconColor: "#ffc107",
                  background: "#fff3cd",
                  color: "#856404",
                  customClass: {
                    popup:
                      "border-start border-warning border-5",
                  },
                });
                ErrorInfoToast.fire({
                  icon: "warning",
                  title:
                    "Delete operation failed. Please check your connection.",
                });
              }
            });
          });
      }
    });
  });

  // Auto-focus task input when modal opens
  $("#addTodoModal").on("shown.bs.modal", function () {
    $("#task").focus();
  });

  $("#editTodoModal").on("shown.bs.modal", function () {
    $("#edit_task").focus();
  });

  // Clear form when modal is hidden
  $("#addTodoModal").on("hidden.bs.modal", function () {
    $("#addTodoForm")[0].reset();
    $("#addTodoModal #priority").val("medium");
    $("#addTodoModal #status").val("pending");
  });

  // Update filter button active state
  $(".filter-btn").click(function () {
    $(".filter-btn").removeClass("active");
    $(this).addClass("active");
  });
});
