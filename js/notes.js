// Notes Page JavaScript

// Toast Configuration
const Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  background: "#ffffff",
  color: "#1a1a1a",
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
  background: "#ffffff",
  color: "#1a1a1a",
  customClass: {
    popup: "animated bounceInRight border-start border-info border-5",
  },
});

$(document).ready(function () {
  function extractText(htmlString) {
    const temp = document.createElement("div");
    temp.innerHTML = htmlString || "";
    return (temp.textContent || temp.innerText || "").trim();
  }

  function decodeHtml(str) {
    return $("<textarea/>")
      .html(str || "")
      .text();
  }

  function htmlToExcerpt(htmlString) {
    const temp = document.createElement("div");
    temp.innerHTML = htmlString || "";
    temp.querySelectorAll("li").forEach((li) => {
      li.textContent = "• " + li.textContent;
    });
    return (temp.textContent || "").replace(/\s+/g, " ").trim();
  }

  // Quill editors for add/edit
  const quillAdd = new Quill("#contentEditor", {
    theme: "snow",
    placeholder:
      "Enter note content... (supports bullets, numbering, checklist)",
    modules: {
      toolbar: [
        ["bold", "italic", "underline"],
        [{ list: "ordered" }, { list: "bullet" }, { list: "check" }],
        ["clean"],
      ],
    },
  });
  const quillEdit = new Quill("#editContentEditor", {
    theme: "snow",
    placeholder: "Edit note content...",
    modules: {
      toolbar: [
        ["bold", "italic", "underline"],
        [{ list: "ordered" }, { list: "bullet" }, { list: "check" }],
        ["clean"],
      ],
    },
  });

  // Initialize draggable notes
  $(".sticky-note").draggable({
    containment: "#noteContainer",
    stack: ".sticky-note",
    cursor: "move",
    revert: false,
    start: function (event, ui) {
      $(this).addClass("ui-draggable-dragging");
    },
    stop: function (event, ui) {
      $(this).removeClass("ui-draggable-dragging");
      var noteId = $(this).data("id");
      var positionX = Math.round(ui.position.left);
      var positionY = Math.round(ui.position.top);

      $.post(
        window.location.href,
        {
          id: noteId,
          position_x: positionX,
          position_y: positionY,
          action: "update_position",
        },
        function (response) {
          console.log("Note position updated");
        }
      ).fail(function () {
        Toast.fire({
          icon: "error",
          title: "Failed to update note position",
        });
      });
    },
  });

  // Color Picker for Add Modal
  $("#addNoteModal .color-option").click(function () {
    var color = $(this).data("color");
    $("#addNoteModal .color-option").removeClass("active");
    $(this).addClass("active");
    $("#addNoteModal #color").val(color);
  });

  // Color Picker for Edit Modal
  $("#editNoteModal .color-option").click(function () {
    var color = $(this).data("color");
    $("#editNoteModal .color-option").removeClass("active");
    $(this).addClass("active");
    $("#editNoteModal #edit_color").val(color);
  });

  // Add Note
  $("#addNoteForm").submit(function (e) {
    e.preventDefault();

    // Show loading
    Swal.fire({
      title: "Creating Note...",
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => {
        Swal.showLoading();
      },
    });

    const titleVal = $("#title").val();
    const colorVal = $("#color").val();
    const fullContentHtml = quillAdd.root.innerHTML;
    const fullContentText = htmlToExcerpt(fullContentHtml);
    const excerpt =
      fullContentText.length > 160
        ? fullContentText.substring(0, 160) + "..."
        : fullContentText;
    $("#content").val(fullContentHtml);
    var formData = $(this).serialize() + "&action=create";

    $.post(window.location.href, formData, null, "json")
      .done(function (response) {
        Swal.close();
        if (response.success) {
          Toast.fire({
            icon: "success",
            title: "Note created successfully!",
          });
          $("#addNoteModal").modal("hide");
          $("#addNoteForm")[0].reset();
          quillAdd.setContents([]);
          $("#addNoteModal .color-option").removeClass("active");
          $("#addNoteModal .color-option:first").addClass("active");
          $("#addNoteModal #color").val("#fffacd");

          // Add new note to container
          var safeTitle = $("<div>").text(titleVal).html();
          var safeDataContent = $("<div>").text(fullContentHtml).html();
          var noteHtml = `
                                <div class="sticky-note ui-widget-content" 
                                     style="background-color: ${colorVal}; left: 100px; top: 100px;"
                                     data-id="${response.id}"
                                     data-title="${safeTitle}"
                                     data-content="${safeDataContent}"
                                     data-color="${colorVal}"
                                     data-updated="Just now">
                                    <div class="note-title">${safeTitle}</div>
                                    <div class="note-content">${$("<div>")
                                      .text(excerpt)
                                      .html()
                                      .replace(/\n/g, "<br>")}</div>
                                    <div class="note-footer">
                                        <div class="note-date">
                                            <small><i class="fas fa-clock me-1"></i>Just now</small>
                                        </div>
                                        <div class="note-actions">
                                            <button class="btn btn-sm btn-outline-dark note-action-btn edit-note" 
                                                    data-id="${response.id}"
                                                    data-title="${safeTitle}"
                                                    data-content="${safeDataContent}"
                                                    data-color="${colorVal}"
                                                    title="Edit Note">
                                                <i class="fas fa-edit fa-sm"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning note-action-btn archive-note" 
                                                    data-id="${response.id}"
                                                    title="Archive Note">
                                                <i class="fas fa-archive fa-sm"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger note-action-btn delete-note" 
                                                    data-id="${response.id}"
                                                    title="Delete Note">
                                                <i class="fas fa-trash fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>`;

          if ($("#noteContainer .no-notes").length) {
            $("#noteContainer").html(noteHtml);
          } else {
            $("#noteContainer").prepend(noteHtml);
          }

          // Attach raw HTML content to the new element for modal view
          const newNoteEl = $("#noteContainer .sticky-note:first");
          newNoteEl.data("content", fullContentHtml);
          newNoteEl.attr("data-content", safeDataContent);
          newNoteEl
            .find(".note-content")
            .html($("<div>").text(excerpt).html().replace(/\n/g, "<br>"));

          // Make new note draggable
          $("#noteContainer .sticky-note:first").draggable({
            containment: "#noteContainer",
            stack: ".sticky-note",
            cursor: "move",
            stop: function (event, ui) {
              var noteId = $(this).data("id");
              var positionX = Math.round(ui.position.left);
              var positionY = Math.round(ui.position.top);

              $.post(window.location.href, {
                id: noteId,
                position_x: positionX,
                position_y: positionY,
                action: "update_position",
              });
            },
          });

          // Add click handlers to new buttons
          $("#noteContainer .sticky-note:first .edit-note").click(
            editNoteHandler
          );
          $("#noteContainer .sticky-note:first .archive-note").click(
            archiveNoteHandler
          );
          $("#noteContainer .sticky-note:first .delete-note").click(
            deleteNoteHandler
          );
        } else {
          Toast.fire({
            icon: "error",
            title: response.message || "Failed to create note",
          });
        }
      })
      .fail(function (xhr, status, error) {
        Swal.close();
        Toast.fire({
          icon: "error",
          title: "Network error! Please try again.",
        });
      });
  });

  // Edit Note Handler
  function editNoteHandler() {
    var id = $(this).data("id");
    var title = $(this).data("title");
    var contentRaw = $(this).data("content");
    var color = $(this).data("color");
    var contentHtml = contentRaw || decodeHtml($(this).attr("data-content"));

    $("#edit_id").val(id);
    $("#edit_title").val(title);
    $("#edit_color").val(color);
    $("#edit_content").val(contentHtml);
    quillEdit.root.innerHTML = contentHtml || "";

    // Set active color
    $("#editNoteModal .color-option").removeClass("active");
    $('#editNoteModal .color-option[data-color="' + color + '"]').addClass(
      "active"
    );

    $("#editNoteModal").modal("show");
  }

  // Update Note
  $("#editNoteForm").submit(function (e) {
    e.preventDefault();

    // Show loading
    Swal.fire({
      title: "Updating Note...",
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => {
        Swal.showLoading();
      },
    });

    $("#edit_content").val(quillEdit.root.innerHTML);
    var formData = $(this).serialize() + "&action=update";

    $.post(window.location.href, formData, null, "json")
      .done(function (response) {
        Swal.close();
        if (response.success) {
          Toast.fire({
            icon: "success",
            title: "Note updated successfully!",
          });
          $("#editNoteModal").modal("hide");

          // Update the note in UI
          var id = $("#edit_id").val();
          var newTitle = $("#edit_title").val();
          var newContent = $("#edit_content").val();
          var newColor = $("#edit_color").val();
          var noteElement = $(`[data-id="${id}"]`);
          const excerptText = htmlToExcerpt(newContent);
          const excerpt =
            excerptText.length > 160
              ? excerptText.substring(0, 160) + "..."
              : excerptText;

          noteElement.find(".note-title").text(newTitle);
          noteElement
            .find(".note-content")
            .html($("<div>").text(excerpt).html().replace(/\n/g, "<br>"));
          noteElement.css("background-color", newColor);
          noteElement.attr("data-title", newTitle);
          noteElement.attr("data-content", $("<div>").text(newContent).html());
          noteElement.attr("data-color", newColor);
          noteElement.attr("data-updated", "Updated just now");
          noteElement.data("title", newTitle);
          noteElement.data("content", newContent);
          noteElement.data("color", newColor);
          noteElement.data("updated", "Updated just now");

          // Update button data attributes
          noteElement
            .find(".edit-note")
            .data("title", newTitle)
            .data("content", newContent)
            .data("color", newColor);

          // Update note date
          noteElement
            .find(".note-date small")
            .html('<i class="fas fa-clock me-1"></i>Updated just now');
        } else {
          Toast.fire({
            icon: "error",
            title: response.message || "Failed to update note",
          });
        }
      })
      .fail(function (xhr, status, error) {
        Swal.close();
        Toast.fire({
          icon: "error",
          title: "Network error! Please try again.",
        });
      });
  });

  // Archive Note Handler
  function archiveNoteHandler(e) {
    e.stopPropagation(); // Prevent triggering view note
    var noteId = $(this).data("id");
    var noteElement = $(this).closest(".sticky-note");
    var noteTitle = noteElement.find(".note-title").text();

    Swal.fire({
      title: "Archive Note?",
      html: `<p>Are you sure you want to archive note <strong>"${noteTitle}"</strong>?</p>
                          <p class="text-muted">
                              <i class="fas fa-info-circle me-1"></i>
                              Archived notes will be hidden from active view but can be restored later.
                          </p>`,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#ffc107",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, archive it!",
      cancelButtonText: "Cancel",
      reverseButtons: true,
      customClass: {
        popup: "animated bounceIn",
        actions: "my-actions",
        confirmButton: "btn btn-warning",
        cancelButton: "btn btn-secondary",
      },
    }).then((result) => {
      if (result.isConfirmed) {
        // Show loading
        Swal.fire({
          title: "Archiving Note...",
          text: "Please wait",
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
            Swal.showLoading();
          },
          customClass: {
            popup: "animated pulse",
          },
        });

        $.post(
          window.location.href,
          { id: noteId, action: "archive" },
          null,
          "json"
        )
          .done(function (response) {
            Swal.close();
            if (response.success) {
              // Success Toast
              Toast.fire({
                icon: "success",
                title: "Note archived successfully!",
                customClass: {
                  popup: "animated bounceInRight",
                },
              });

              // Remove note from UI with animation
              noteElement.animate(
                {
                  opacity: 0,
                  scale: 0.8,
                },
                300,
                function () {
                  $(this).remove();

                  // If no notes left, show empty state
                  if ($("#noteContainer .sticky-note").length === 0) {
                    $("#noteContainer").html(`
                                                <div class="no-notes animated fadeIn">
                                                    <i class="fas fa-sticky-note"></i>
                                                    <h4>No notes yet</h4>
                                                    <p>Create your first sticky note by clicking the button above</p>
                                                </div>
                                            `);
                  }
                }
              );
            } else {
              Swal.fire({
                title: "Error!",
                text:
                  response.message ||
                  "Failed to archive note. Please try again.",
                icon: "error",
                confirmButtonText: "OK",
                customClass: {
                  popup: "animated shake",
                  confirmButton: "btn btn-danger",
                },
              });
            }
          })
          .fail(function (xhr) {
            Swal.close();
            Swal.fire({
              title: "Connection Error!",
              text: "Please check your internet connection and try again.",
              icon: "error",
              confirmButtonText: "OK",
              customClass: {
                popup: "animated shake",
                confirmButton: "btn btn-danger",
              },
            });
          });
      }
    });
  }

  // Delete Note Handler
  function deleteNoteHandler() {
    var noteId = $(this).data("id");
    var noteElement = $(this).closest(".sticky-note");
    var noteTitle = noteElement.find(".note-title").text();

    Swal.fire({
      title: "Delete Note?",
      html: `<p>Are you sure you want to delete note <strong>"${noteTitle}"</strong>?</p>
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
          title: "Deleting Note...",
          text: "Please wait while we delete your note",
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
            Swal.showLoading();
          },
          customClass: {
            popup: "animated pulse",
          },
        });

        $.post(
          window.location.href,
          { id: noteId, action: "delete" },
          null,
          "json"
        )
          .done(function (response) {
            Swal.close();
            if (response.success) {
              // Success Toast with animation
              Toast.fire({
                icon: "success",
                title: "Note deleted successfully!",
                customClass: {
                  popup: "animated bounceInRight",
                },
              });

              // Remove note from UI with animation
              noteElement.animate(
                {
                  opacity: 0,
                  scale: 0.8,
                },
                300,
                function () {
                  $(this).remove();

                  // If no notes left, show empty state
                  if ($("#noteContainer .sticky-note").length === 0) {
                    $("#noteContainer").html(`
                                                <div class="no-notes animated fadeIn">
                                                    <i class="fas fa-sticky-note"></i>
                                                    <h4>No notes yet</h4>
                                                    <p>Create your first sticky note by clicking the button above</p>
                                                </div>
                                            `);
                  }
                }
              );
            } else {
              // Failed Delete - Info Toast
              Swal.fire({
                title: "Failed to Delete Note",
                text:
                  response.message ||
                  "There was an error deleting your note. Please try again.",
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
                // Show additional info toast
                InfoToast.fire({
                  icon: "info",
                  title: "Tip: Try refreshing the page and try again",
                });
              });
            }
          })
          .fail(function (xhr, status, error) {
            Swal.close();

            // Network Error Toast
            Swal.fire({
              title: "Network Error",
              html: `<p>Failed to delete note due to network issues.</p>
                                          <p><small>Error: ${error}</small></p>`,
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
                // Retry delete
                deleteNoteHandler.call(this);
              }
            });
          });
      }
    });
  }

  // View Note Handler
  function viewNoteHandler(e) {
    // Ignore clicks on action buttons to prevent accidental opens
    if ($(e.target).closest(".note-actions").length) return;
    if ($(this).hasClass("ui-draggable-dragging")) return;

    var noteElement = $(this);
    var title = noteElement.data("title") || "";
    var contentRaw = noteElement.data("content");
    var contentEncoded = noteElement.attr("data-content");
    var content = contentRaw || decodeHtml(contentEncoded);
    var color = noteElement.data("color") || "#fffacd";
    var updated =
      noteElement.data("updated") ||
      noteElement.find(".note-date small").text();

    $("#viewNoteTitle").text(title);
    $("#viewNoteContent").html(content || "");
    $("#viewNoteUpdated").text(updated);
    $("#viewNoteModal .modal-header").css("background-color", color);
    $("#viewNoteModal").modal("show");
  }

  // Assign event handlers
  $(document).on("click", ".edit-note", editNoteHandler);
  $(document).on("click", ".archive-note", archiveNoteHandler);
  $(document).on("click", ".delete-note", deleteNoteHandler);
  $(document).on("click", ".sticky-note", viewNoteHandler);

  // View Archived Notes
  $("#viewArchivedBtn").click(function () {
    $("#archivedNotesModal").modal("show");
    loadArchivedNotes();
  });

  // Load Archived Notes
  function loadArchivedNotes() {
    $("#archivedNotesContainer").html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2">Loading archived notes...</p>
                    </div>
                `);

    $.ajax({
      url: window.location.href,
      type: "POST",
      data: { action: "get_archived" },
      dataType: "json",
      success: function (response) {
        if (response.success && response.notes.length > 0) {
          let html = '<div class="row g-3">';
          response.notes.forEach(function (note) {
            const htmlToText = (str) => {
              const temp = document.createElement("div");
              temp.innerHTML = str;
              return temp.textContent || temp.innerText || "";
            };
            const excerpt = htmlToText(note.content).substring(0, 100) + "...";

            html += `
                                    <div class="col-md-6">
                                        <div class="card h-100" style="border-left: 4px solid ${
                                          note.color
                                        };">
                                            <div class="card-body">
                                                <h6 class="card-title fw-bold">${$(
                                                  "<div>"
                                                )
                                                  .text(note.title)
                                                  .html()}</h6>
                                                <p class="card-text text-muted small">${$(
                                                  "<div>"
                                                )
                                                  .text(excerpt)
                                                  .html()}</p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        ${new Date(
                                                          note.updated_at
                                                        ).toLocaleDateString(
                                                          "id-ID"
                                                        )}
                                                    </small>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn restore-note" data-id="${
                                                          note.id
                                                        }" title="Restore Note" style="color: #0f5132; background: linear-gradient(135deg, #e9f9ef, #d5f2e0); border: 1px solid #c3e6cb;">
                                                            <i class="fas fa-undo"></i> Restore
                                                        </button>
                                                        <button class="btn view-archived-note" 
                                                                data-id="${
                                                                  note.id
                                                                }"
                                                                data-title="${$(
                                                                  "<div>"
                                                                )
                                                                  .text(
                                                                    note.title
                                                                  )
                                                                  .html()}"
                                                                data-content="${$(
                                                                  "<div>"
                                                                )
                                                                  .text(
                                                                    note.content
                                                                  )
                                                                  .html()}"
                                                                data-color="${
                                                                  note.color
                                                                }"
                                                                title="View Note"
                                                                style="color: #055160; background: linear-gradient(135deg, #d1ecf1, #b8e5ee); border: 1px solid #9fd9e3;">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn delete-archived-note" data-id="${
                                                          note.id
                                                        }" title="Delete Permanently" style="color: #842029; background: linear-gradient(135deg, #f8d7da, #f1c2c7); border: 1px solid #e9afb5;">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
          });
          html += "</div>";
          $("#archivedNotesContainer").html(html);
        } else {
          $("#archivedNotesContainer").html(`
                            <div class="text-center py-5">
                                <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No archived notes</h5>
                                <p class="text-muted">Notes you archive will appear here</p>
                            </div>
                        `);
        }
      },
      error: function () {
        $("#archivedNotesContainer").html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Failed to load archived notes. Please try again.
                        </div>
                    `);
      },
    });
  }

  // Restore Note
  $(document).on("click", ".restore-note", function () {
    const noteId = $(this).data("id");
    const btn = $(this);

    Swal.fire({
      title: "Restore Note?",
      text: "This note will be moved back to active notes.",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#28a745",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, restore it!",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(
          window.location.href,
          { id: noteId, action: "unarchive" },
          null,
          "json"
        )
          .done(function (response) {
            if (response.success) {
              Toast.fire({
                icon: "success",
                title: "Note restored successfully!",
              });
              btn.closest(".col-md-6").fadeOut(300, function () {
                $(this).remove();
                if ($("#archivedNotesContainer .col-md-6").length === 0) {
                  loadArchivedNotes();
                }
              });
              // Update archived count badge and reload page to show restored note
              setTimeout(function () {
                location.reload();
              }, 500);
            } else {
              Swal.fire("Error!", response.message, "error");
            }
          })
          .fail(function () {
            Swal.fire("Error!", "Failed to restore note.", "error");
          });
      }
    });
  });

  // View Archived Note
  $(document).on("click", ".view-archived-note", function (e) {
    e.stopPropagation();
    const title = $(this).data("title");
    const content = $(this).data("content");
    const color = $(this).data("color");

    // Temporarily adjust z-index to ensure view modal appears on top
    $("#viewNoteModal").css("z-index", 1065);

    $("#viewNoteTitle").text(title);
    $("#viewNoteContent").html(content);
    $("#viewNoteModal .modal-header").css("background-color", color);
    $("#viewNoteModal").modal("show");

    // Ensure backdrop appears correctly
    setTimeout(function () {
      $(".modal-backdrop").last().css("z-index", 1064);
    }, 100);
  });

  // Reset z-index when view modal closes
  $("#viewNoteModal").on("hidden.bs.modal", function () {
    $(this).css("z-index", "");
  });

  // Delete Archived Note Permanently
  $(document).on("click", ".delete-archived-note", function () {
    const noteId = $(this).data("id");
    const btn = $(this);

    Swal.fire({
      title: "Delete Permanently?",
      html: '<p>This action cannot be undone!</p><p class="text-danger"><strong>The note will be deleted permanently.</strong></p>',
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, delete permanently!",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(
          window.location.href,
          { id: noteId, action: "delete" },
          null,
          "json"
        )
          .done(function (response) {
            if (response.success) {
              Toast.fire({
                icon: "success",
                title: "Note deleted permanently!",
              });
              btn.closest(".col-md-6").fadeOut(300, function () {
                $(this).remove();
                if ($("#archivedNotesContainer .col-md-6").length === 0) {
                  loadArchivedNotes();
                }
              });
              // Update archived count badge
              const badge = $("#viewArchivedBtn .badge");
              const currentCount = parseInt(badge.text()) || 0;
              if (currentCount > 1) {
                badge.text(currentCount - 1);
              } else {
                badge.remove();
              }
            } else {
              Swal.fire("Error!", response.message, "error");
            }
          })
          .fail(function () {
            Swal.fire("Error!", "Failed to delete note.", "error");
          });
      }
    });
  });

  // Auto-focus title input when modal opens
  $("#addNoteModal").on("shown.bs.modal", function () {
    $("#title").focus();
  });

  $("#editNoteModal").on("shown.bs.modal", function () {
    $("#edit_title").focus();
  });

  // Clear form when modal is hidden
  $("#addNoteModal").on("hidden.bs.modal", function () {
    $("#addNoteForm")[0].reset();
  });
});
