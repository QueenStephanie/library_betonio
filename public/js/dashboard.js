/**
 * Dashboard Interactions
 *
 * This module handles interactive elements on the dashboard:
 * - Collapsible sections
 * - Sidebar navigation
 *
 * Backend Integration:
 * - Data loading should be implemented in separate service layer
 * - Use data attributes on elements to bind to backend data
 */

// ==================== Collapsible Sections ====================
/**
 * Toggles collapsible sections (e.g., Loan History)
 * Uses data-collapsible attribute to identify sections
 */
const collapsibles = document.querySelectorAll("[data-collapsible]");

collapsibles.forEach((section) => {
  const button = section.querySelector(".panel-toggle");

  if (!button) {
    return;
  }

  button.addEventListener("click", () => {
    const isOpen = section.classList.toggle("is-open");
    button.setAttribute("aria-expanded", String(isOpen));
  });
});

// ==================== Sidebar Navigation ====================
/**
 * Highlights active nav item based on current page
 * Backend developers: Update nav-item data-page attribute to match current route
 */
document.querySelectorAll(".nav-item").forEach((item) => {
  item.addEventListener("click", (e) => {
    // Remove active class from all items
    document.querySelectorAll(".nav-item").forEach((i) => {
      i.classList.remove("is-active");
    });

    // Add active class to clicked item
    item.classList.add("is-active");
  });
});

// ==================== Dynamic Data Binding ====================
/**
 * Backend developers:
 * To bind dynamic data to the dashboard:
 * 1. Add data-bind attribute to elements (e.g., data-bind="user.name")
 * 2. Call updateDashboardData(userData) with fetched data
 * 3. Function will automatically populate elements with matching data-bind
 */
function updateDashboardData(data) {
  // Find all elements with data-bind attribute
  document.querySelectorAll("[data-bind]").forEach((element) => {
    const bindPath = element.dataset.bind;
    const value = getNestedValue(data, bindPath);

    if (value !== undefined) {
      // Update text content or value based on element type
      if (element.tagName === "INPUT" || element.tagName === "TEXTAREA") {
        element.value = value;
      } else {
        element.textContent = value;
      }
    }
  });
}

/**
 * Helper function to get nested object values using dot notation
 * Example: getNestedValue(obj, 'user.profile.name')
 */
function getNestedValue(obj, path) {
  return path.split(".").reduce((current, prop) => current?.[prop], obj);
}

// ==================== User Session ====================
/**
 * Backend developers:
 * Set user data in session storage after login
 * This will be used for UI updates (avatar, name, etc.)
 */
function setUserSession(userData) {
  sessionStorage.setItem("user", JSON.stringify(userData));
  updateDashboardData(userData);
}

function getUserSession() {
  const user = sessionStorage.getItem("user");
  return user ? JSON.parse(user) : null;
}

function clearUserSession() {
  sessionStorage.removeItem("user");
}
