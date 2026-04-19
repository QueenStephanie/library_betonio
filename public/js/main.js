/**
 * Main Page Interactions
 *
 * Handles hero slide carousel on the landing page index.html
 */

const slides = document.querySelectorAll(".hero-slide");
const dots = document.querySelectorAll(".indicator-dot");

let activeIndex = 1;
let intervalId;

if (!slides.length || !dots.length) {
  // Skip carousel setup on non-landing pages.
  window.__queenlibCarouselDisabled = true;
} else {
  /**
   * Display specific slide by index
   */
  function showSlide(index) {
    slides.forEach((slide, slideIndex) => {
      slide.classList.toggle("is-active", slideIndex === index);
    });

    dots.forEach((dot, dotIndex) => {
      dot.classList.toggle("is-active", dotIndex === index);
    });

    activeIndex = index;
  }

  /**
   * Advance to next slide
   */
  function nextSlide() {
    const nextIndex = (activeIndex + 1) % slides.length;
    showSlide(nextIndex);
  }

  /**
   * Start auto-play carousel
   */
  function startAutoPlay() {
    intervalId = window.setInterval(nextSlide, 4500);
  }

  /**
   * Reset auto-play timer
   */
  function resetAutoPlay() {
    window.clearInterval(intervalId);
    startAutoPlay();
  }

  // Add click handlers to indicator dots
  dots.forEach((dot) => {
    dot.addEventListener("click", () => {
      showSlide(Number(dot.dataset.slide));
      resetAutoPlay();
    });
  });

  // Initialize carousel
  showSlide(activeIndex);
  startAutoPlay();
}

const mobileSidebar = document.querySelector(".admin-sidebar-mobile");

if (mobileSidebar) {
  const toggleButton = mobileSidebar.querySelector(".admin-sidebar-mobile-toggle");
  const overlay = mobileSidebar.querySelector(".admin-sidebar-mobile-overlay");
  const drawerId = toggleButton ? toggleButton.getAttribute("aria-controls") : "";
  const drawer = drawerId ? document.getElementById(drawerId) : null;

  if (toggleButton && overlay && drawer) {
    const mobileMedia = window.matchMedia("(max-width: 980px)");

    const setDrawerState = (isOpen) => {
      mobileSidebar.classList.toggle("is-open", isOpen);
      toggleButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
      drawer.setAttribute("aria-hidden", isOpen ? "false" : "true");
      overlay.hidden = !isOpen;

      if (isOpen) {
        drawer.removeAttribute("inert");
      } else {
        drawer.setAttribute("inert", "");
      }
    };

    setDrawerState(false);

    toggleButton.addEventListener("click", () => {
      const willOpen = toggleButton.getAttribute("aria-expanded") !== "true";
      setDrawerState(willOpen);
    });

    overlay.addEventListener("click", () => {
      setDrawerState(false);
    });

    drawer.addEventListener("click", (event) => {
      const target = event.target;
      if (target instanceof Element && target.closest("a")) {
        setDrawerState(false);
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        setDrawerState(false);
      }
    });

    const handleViewportChange = (event) => {
      if (!event.matches) {
        setDrawerState(false);
      }
    };

    if (typeof mobileMedia.addEventListener === "function") {
      mobileMedia.addEventListener("change", handleViewportChange);
    } else if (typeof mobileMedia.addListener === "function") {
      mobileMedia.addListener(handleViewportChange);
    }
  }
}
