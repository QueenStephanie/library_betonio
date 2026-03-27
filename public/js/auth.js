/**
 * Authentication Form Handlers
 *
 * This module handles UI interactions for authentication forms:
 * - Password visibility toggle
 * - OTP input handling
 *
 * Backend Integration:
 * - Forms use data-form-type attribute to identify form type
 * - Form submission is handled by the form's action attribute
 * - For AJAX implementation, attach to 'form-submit' event on the form element
 */

// ==================== Password Visibility Toggle ====================
const toggleButtons = document.querySelectorAll(".toggle-password");

toggleButtons.forEach((button) => {
  const wrapper = button.closest(".password-field");
  const input = wrapper ? wrapper.querySelector("input") : null;

  if (!input) {
    return;
  }

  button.addEventListener("click", (e) => {
    e.preventDefault();
    const isHidden = input.type === "password";

    input.type = isHidden ? "text" : "password";
    button.setAttribute("aria-pressed", String(isHidden));
    button.setAttribute(
      "aria-label",
      isHidden ? "Hide password" : "Show password",
    );
  });
});

// ==================== OTP Input Handler ====================
/**
 * Handles auto-advancing OTP inputs
 * Each box accepts a single digit and moves to the next
 * Backspace moves to the previous box
 */
const otpBoxes = document.querySelectorAll(".otp-box");

otpBoxes.forEach((box, index) => {
  box.addEventListener("input", () => {
    // Only allow numeric input and limit to 1 character
    box.value = box.value.replace(/\D/g, "").slice(0, 1);

    // Auto-advance to next box if value entered
    if (box.value && index < otpBoxes.length - 1) {
      otpBoxes[index + 1].focus();
    }
  });

  box.addEventListener("keydown", (event) => {
    // Allow backspace to move to previous box
    if (event.key === "Backspace" && !box.value && index > 0) {
      otpBoxes[index - 1].focus();
    }
  });
});

// ==================== Form Submission Handler ====================
/**
 * Backend developers:
 * To process forms via API instead of form action:
 * 1. Add data-submit-handler="api" to the form
 * 2. Listen to form submission and call apiCall() from config/api.config.js
 * 3. Redirect or show error based on response
 */
document.querySelectorAll(".auth-form").forEach((form) => {
  // Hook for backend integration
  if (form.dataset.submitHandler === "api") {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      // To be implemented by backend developer
      console.log("Form submitted:", form.dataset.formType);
    });
  }
});
