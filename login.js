const passwordInput = document.querySelector("#password");
const toggleButton = document.querySelector(".toggle-password");

if (passwordInput && toggleButton) {
  toggleButton.addEventListener("click", () => {
    const isHidden = passwordInput.type === "password";

    passwordInput.type = isHidden ? "text" : "password";
    toggleButton.setAttribute("aria-pressed", String(isHidden));
    toggleButton.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
  });
}
