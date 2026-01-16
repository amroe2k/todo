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
    popup: "animated bounceInRight border-start border-info border-5",
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
    const status = this.checked ? "completed" : "pending";
    const todoItem = checkbox.closest(".todo-item");

    const originalHTML = todoItem.html();
    todoItem.html(`
            <div class="card-body text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Updating...</span>
                </div>
                <p class="mt-2 mb-0">Updating status...</p>
            </div>
        `);

    $.ajax({
      url: window.location.href,
      method: "POST",
      data: { id: id, status: status, action: "update_status" },
      dataType: "json",
    })
      .done(function (response) {
        if (response && response.success) {
          Toast.fire({
            icon: "success",
            title: "Status updated successfully!",
          });
          setTimeout(() => {
            window.location.reload();
          }, 800);
        } else {
          todoItem.html(originalHTML);
          Toast.fire({
            icon: "error",
            title: (response && response.message) || "Failed to update status",
          });
          checkbox.prop("checked", !checkbox.prop("checked"));
        }
      })
      .fail(function () {
        todoItem.html(originalHTML);
        Toast.fire({
          icon: "error",
          title: "Network error! Please try again.",
        });
        checkbox.prop("checked", !checkbox.prop("checked"));
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
        popup: "animated bounceIn",
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
          customClass: {
            popup: "animated pulse",
          },
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
                customClass: {
                  popup: "animated bounceInRight",
                },
              });

              todoItem.animate(
                {
                  opacity: 0,
                  marginTop: -todoItem.outerHeight(),
                  marginBottom: 0,
                },
                300,
                function () {
                  $(this).remove();

                  if ($(".todo-item").length === 0) {
                    $(".card-body").html(`
                                    <div class="empty-state animated fadeIn">
                                        <i class="fas fa-check-circle"></i>
                                        <h4 class="mt-3">No todos found</h4>
                                        <p class="mb-4">Add your first todo item to get started!</p>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTodoModal">
                                            <i class="fas fa-plus-circle me-2"></i> Create Your First Todo
                                        </button>
                                    </div>
                                `);
                  }
                }
              );
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
                  popup: "animated shake",
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
                popup: "animated wobble",
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
                      "animated bounceInRight border-start border-warning border-5",
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
