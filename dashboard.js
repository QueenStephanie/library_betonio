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
