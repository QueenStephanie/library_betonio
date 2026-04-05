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

// ==================== Password Strength / Match Validation ====================
function scorePassword(value) {
  if (!value) {
    return 0;
  }

  let score = 0;
  if (value.length >= 8) {
    score += 1;
  }
  if (/[A-Z]/.test(value)) {
    score += 1;
  }
  if (/[a-z]/.test(value)) {
    score += 1;
  }
  if (/[0-9]/.test(value)) {
    score += 1;
  }
  if (/[^A-Za-z0-9]/.test(value)) {
    score += 1;
  }

  return score;
}

function getStrengthState(score, value) {
  if (!value) {
    return { label: "Too weak", level: "empty", percent: 0 };
  }
  if (score <= 1) {
    return { label: "Too weak", level: "weak", percent: 20 };
  }
  if (score === 2) {
    return { label: "Fair", level: "fair", percent: 40 };
  }
  if (score === 3) {
    return { label: "Good", level: "good", percent: 60 };
  }
  if (score === 4) {
    return { label: "Strong", level: "strong", percent: 80 };
  }
  return { label: "Very strong", level: "very-strong", percent: 100 };
}

function initPasswordValidation(form) {
  const primary = form.querySelector("input[data-password-primary]");
  const confirm = form.querySelector("input[data-password-confirm]");
  const confirmError = form.querySelector("[data-confirm-error]");
  const strengthWrap = form.querySelector("[data-password-strength]");
  const strengthText = form.querySelector("[data-password-strength-text]");
  const strengthFill = form.querySelector("[data-password-strength-fill]");

  function updateStrength() {
    if (!primary || !strengthWrap || !strengthText || !strengthFill) {
      return;
    }

    const value = primary.value || "";
    const state = getStrengthState(scorePassword(value), value);
    strengthWrap.dataset.strengthLevel = state.level;
    strengthText.textContent = state.label;
    strengthFill.style.width = state.percent + "%";
  }

  function validatePasswordMatch() {
    if (!primary || !confirm) {
      return true;
    }

    const matches = confirm.value === "" || primary.value === confirm.value;
    if (!matches) {
      confirm.setCustomValidity("Passwords do not match.");
      if (confirmError) {
        confirmError.hidden = false;
      }
      return false;
    }

    confirm.setCustomValidity("");
    if (confirmError) {
      confirmError.hidden = true;
    }
    return true;
  }

  if (primary) {
    primary.addEventListener("input", () => {
      updateStrength();
      validatePasswordMatch();
    });
  }

  if (confirm) {
    confirm.addEventListener("input", () => {
      validatePasswordMatch();
    });
    confirm.addEventListener("blur", () => {
      validatePasswordMatch();
    });
  }

  updateStrength();
  validatePasswordMatch();

  return validatePasswordMatch;
}

const passwordValidators = new WeakMap();

document.querySelectorAll(".auth-form").forEach((form) => {
  const validateMatch = initPasswordValidation(form);
  if (typeof validateMatch === "function") {
    passwordValidators.set(form, validateMatch);
  }
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
  const submitButton = form.querySelector(
    'button[type="submit"], input[type="submit"]',
  );

  form.addEventListener("submit", (event) => {
    if (form.dataset.submitting === "1") {
      event.preventDefault();
      return;
    }

    const matchValidator = passwordValidators.get(form);
    if (typeof matchValidator === "function" && !matchValidator()) {
      event.preventDefault();
      const confirmInput = form.querySelector("input[data-password-confirm]");
      if (confirmInput) {
        confirmInput.focus();
      }
      return;
    }

    if (!form.checkValidity()) {
      event.preventDefault();
      form.reportValidity();
      return;
    }

    form.dataset.submitting = "1";

    if (submitButton) {
      if (!submitButton.dataset.defaultLabel) {
        submitButton.dataset.defaultLabel =
          submitButton.textContent || "Submit";
      }

      submitButton.disabled = true;
      submitButton.classList.add("is-loading");
      submitButton.textContent = "Please wait...";
    }
  });

  // Hook for backend integration
  if (form.dataset.submitHandler === "api") {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      // To be implemented by backend developer
      console.log("Form submitted:", form.dataset.formType);
    });
  }
});

window.addEventListener("pageshow", () => {
  document.querySelectorAll(".auth-form").forEach((form) => {
    form.dataset.submitting = "0";

    const submitButton = form.querySelector(
      'button[type="submit"], input[type="submit"]',
    );
    if (!submitButton) {
      return;
    }

    submitButton.disabled = false;
    submitButton.classList.remove("is-loading");

    if (submitButton.dataset.defaultLabel) {
      submitButton.textContent = submitButton.dataset.defaultLabel;
    }
  });
});

// ==================== Verification Resend Cooldown ====================
const resendButton = document.querySelector("[data-resend-button]");
const resendStatus = document.querySelector("[data-resend-status]");

if (resendButton) {
  const defaultLabel =
    resendButton.dataset.defaultLabel || resendButton.textContent || "Resend";
  let remaining = Number.parseInt(
    resendButton.dataset.cooldownSeconds || "0",
    10,
  );
  if (!Number.isFinite(remaining) || remaining < 0) {
    remaining = 0;
  }

  const renderCooldown = () => {
    if (remaining > 0) {
      resendButton.disabled = true;
      resendButton.classList.add("is-cooldown");
      resendButton.textContent = "Resend in " + remaining + "s";
      if (resendStatus) {
        resendStatus.hidden = false;
        resendStatus.textContent =
          "You can request another verification email in " + remaining + "s.";
      }
      return;
    }

    resendButton.disabled = false;
    resendButton.classList.remove("is-cooldown");
    resendButton.textContent = defaultLabel;
    if (resendStatus) {
      resendStatus.hidden = true;
      resendStatus.textContent = "";
    }
  };

  renderCooldown();

  if (remaining > 0) {
    const cooldownTimer = window.setInterval(() => {
      remaining -= 1;
      if (remaining <= 0) {
        remaining = 0;
        renderCooldown();
        window.clearInterval(cooldownTimer);
        return;
      }

      renderCooldown();
    }, 1000);
  }
}
