// Dashboard AJAX Pagination
document.addEventListener("DOMContentLoaded", function () {
  // Use event delegation on document level to handle dynamically loaded pagination
  document.addEventListener("click", function (e) {
    const link = e.target.closest("#activity-pagination a.page-link");
    if (link && link.hasAttribute("data-page")) {
      e.preventDefault();
      e.stopPropagation();

      const pageNum = link.getAttribute("data-page");
      const url = new URL(window.location.href);
      url.searchParams.set("p", pageNum);

      // Save scroll position before loading
      const activityCard = document.getElementById("activity-card-section");
      const scrollPosition = activityCard
        ? activityCard.getBoundingClientRect().top + window.pageYOffset
        : 0;

      // Show loading overlay with smooth animation
      const activityLoading = document.getElementById("activity-loading");
      const activityLog = document.querySelector(".activity-log");

      if (activityLoading) {
        activityLoading.style.display = "flex";
        activityLoading.offsetHeight; // Force reflow
        activityLoading.classList.add("active");
      }

      // Don't fade activity-log to prevent double fade effect
      if (activityLog) {
        activityLog.style.transition = "none";
      }

      // Fetch new page content
      fetch(url.toString())
        .then((response) => response.text())
        .then((html) => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, "text/html");
          const newActivityLog = doc.querySelector(".activity-log");
          const newPagination = doc.querySelector("#activity-pagination");

          if (newActivityLog) {
            const activityLog = document.querySelector(".activity-log");
            // Fast content update
            setTimeout(() => {
              activityLog.innerHTML = newActivityLog.innerHTML;
              activityLog.style.transition = "";

              // Trigger staggered animation for new items
              const items = activityLog.querySelectorAll(".activity-item");
              items.forEach((item, index) => {
                item.style.animation = "none";
                item.offsetHeight; // Force reflow
                item.style.animation = `slideInActivity 0.4s ease forwards ${
                  index * 0.1
                }s`;
              });
            }, 100);
          }

          if (newPagination) {
            const currentPagination = document.getElementById(
              "activity-pagination"
            );
            if (currentPagination && currentPagination.parentElement) {
              setTimeout(() => {
                currentPagination.parentElement.innerHTML =
                  newPagination.parentElement.innerHTML;
              }, 100);
            }
          }

          // Hide loading overlay with optimized timing
          setTimeout(() => {
            if (activityLoading) {
              activityLoading.classList.remove("active");
              setTimeout(() => {
                activityLoading.style.display = "none";
              }, 250);
            }

            // Scroll to Recent Activity section after content is loaded
            requestAnimationFrame(() => {
              const activityCard = document.getElementById(
                "activity-card-section"
              );
              if (activityCard) {
                const rect = activityCard.getBoundingClientRect();
                const offset = 100; // navbar height + padding
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
          // Hide loading overlay on error with animation
          if (activityLoading) {
            activityLoading.classList.remove("active");
            setTimeout(() => {
              activityLoading.style.display = "none";
            }, 250);
          }
          if (activityLog) {
            activityLog.style.transition = "";
          }
          if (activityLog) {
            activityLog.style.opacity = "1";
          }
          showErrorToast("Failed to load page");
        });
    }
  });

  // Handle browser back/forward buttons
  window.addEventListener("popstate", function () {
    location.reload();
  });
});

// Welcome Toast
function showWelcomeToast() {
  const welcomeToast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true,
    background: "#ffffff",
    color: "#0d6efd",
  });
  welcomeToast.fire({
    icon: "success",
    title: "Welcome back!",
    text: "Anda berhasil login.",
  });
}
