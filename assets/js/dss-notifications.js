/**
 * DSS Notifications Frontend Logic
 */
const DSS_Notifications = {
  container: null,
  queue: [],

  init() {
    if (this.container) return;

    this.container = document.createElement("div");
    this.container.className = "dss-notifications-container";
    document.body.appendChild(this.container);
  },

  show(options) {
    this.init();

    const {
      title = "",
      message = "",
      type = "info", // success, error, warning, info
      duration = 5000,
      autohide = true,
    } = options;

    const notification = document.createElement("div");
    notification.className = `dss-notification ${autohide ? "autohide" : ""}`;
    notification.dataset.type = type;
    notification.style.setProperty("--dss-notif-duration", `${duration}ms`);

    const iconMap = {
      success: "dashicons-yes-alt",
      error: "dashicons-warning",
      warning: "dashicons-warning",
      info: "dashicons-info",
    };

    notification.innerHTML = `
            <span class="dss-notif-icon dashicons ${iconMap[type] || "dashicons-info"}"></span>
            <div class="dss-notif-content">
                ${title ? `<div class="dss-notif-title">${title}</div>` : ""}
                <div class="dss-notif-message">${message}</div>
            </div>
            <button class="dss-notif-close dashicons dashicons-no-alt"></button>
        `;

    this.container.appendChild(notification);

    // trigger animation
    setTimeout(() => notification.classList.add("active"), 10);

    const closeFunc = () => {
      notification.classList.remove("active");
      setTimeout(() => notification.remove(), 500);
    };

    notification.querySelector(".dss-notif-close").onclick = closeFunc;

    if (autohide) {
      setTimeout(closeFunc, duration);
    }
  },
};

// Global accessor
window.dssNotify = (options) => DSS_Notifications.show(options);

// Handle server-queued notifications from localized object
document.addEventListener("DOMContentLoaded", () => {
  if (
    window.dssPendingNotifications &&
    Array.isArray(window.dssPendingNotifications)
  ) {
    window.dssPendingNotifications.forEach((notif) => {
      setTimeout(() => dssNotify(notif), 500);
    });
  }
});
