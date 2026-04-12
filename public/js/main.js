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
