const slides = document.querySelectorAll(".hero-slide");
const dots = document.querySelectorAll(".indicator-dot");

let activeIndex = 1;
let intervalId;

function showSlide(index) {
  slides.forEach((slide, slideIndex) => {
    slide.classList.toggle("is-active", slideIndex === index);
  });

  dots.forEach((dot, dotIndex) => {
    dot.classList.toggle("is-active", dotIndex === index);
  });

  activeIndex = index;
}

function nextSlide() {
  const nextIndex = (activeIndex + 1) % slides.length;
  showSlide(nextIndex);
}

function startAutoPlay() {
  intervalId = window.setInterval(nextSlide, 4500);
}

function resetAutoPlay() {
  window.clearInterval(intervalId);
  startAutoPlay();
}

dots.forEach((dot) => {
  dot.addEventListener("click", () => {
    showSlide(Number(dot.dataset.slide));
    resetAutoPlay();
  });
});

showSlide(activeIndex);
startAutoPlay();
