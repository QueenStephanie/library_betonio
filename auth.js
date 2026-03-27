const toggleButtons = document.querySelectorAll(".toggle-password");

toggleButtons.forEach((button) => {
  const wrapper = button.closest(".password-field");
  const input = wrapper ? wrapper.querySelector("input") : null;

  if (!input) {
    return;
  }

  button.addEventListener("click", () => {
    const isHidden = input.type === "password";

    input.type = isHidden ? "text" : "password";
    button.setAttribute("aria-pressed", String(isHidden));
    button.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
  });
});

const otpBoxes = document.querySelectorAll(".otp-box");

otpBoxes.forEach((box, index) => {
  box.addEventListener("input", () => {
    box.value = box.value.replace(/\D/g, "").slice(0, 1);

    if (box.value && index < otpBoxes.length - 1) {
      otpBoxes[index + 1].focus();
    }
  });

  box.addEventListener("keydown", (event) => {
    if (event.key === "Backspace" && !box.value && index > 0) {
      otpBoxes[index - 1].focus();
    }
  });
});
