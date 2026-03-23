const REVEAL_SELECTOR = [
  "[data-animate]",
  "section",
  "article",
  "form",
  ".dashboard-card",
  ".profile-hero",
  ".preferences",
  ".no-pref",
  ".carousel",
  ".property-card",
  ".property-grid > *",
  ".results-grid > *",
  ".search-results > *",
  ".favorites-grid > *",
  ".messages-list > *",
].join(", ");

let observer;
let prefersReducedMotion = false;

const getObserver = () => {
  if (typeof window === "undefined" || prefersReducedMotion) {
    return null;
  }

  if (!observer) {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) {
            return;
          }

          entry.target.classList.add("reveal-visible");
          observer.unobserve(entry.target);
        });
      },
      {
        threshold: 0.06,
        rootMargin: "0px 0px -2% 0px",
      }
    );
  }

  return observer;
};

const shouldReveal = (element) => {
  if (!element || element.dataset.revealBound === "true") {
    return false;
  }

  if (element.closest("header, footer, .site-header, .site-footer")) {
    return false;
  }

   // Exclude containers that host fixed UI, otherwise their transform
   // changes the containing block and breaks fixed positioning.
  if (
    element.matches(".profile, .profile-content, .sidebar, .sidebar-panel") ||
    element.closest(".sidebar")
  ) {
    return false;
  }

  const rect = element.getBoundingClientRect();
  return rect.width >= 80 && rect.height >= 48;
};

export const observeReveal = (element, order = 0) => {
  if (!element) {
    return;
  }

  if (prefersReducedMotion) {
    element.classList.add("reveal-visible");
    return;
  }

  if (!shouldReveal(element)) {
    return;
  }

  element.dataset.revealBound = "true";
  element.style.setProperty("--reveal-delay", `${Math.min(order * 70, 280)}ms`);
  element.classList.add("reveal-hidden");

  const currentObserver = getObserver();
  currentObserver?.observe(element);
};

export const unobserveReveal = (element) => {
  observer?.unobserve(element);
  if (element) {
    delete element.dataset.revealBound;
  }
};

export const refreshRevealElements = (container = document) => {
  if (typeof window === "undefined") {
    return;
  }

  let order = 0;
  container.querySelectorAll(REVEAL_SELECTOR).forEach((element) => {
    observeReveal(element, order);
    order += 1;
  });
};

export const initRevealSystem = () => {
  if (typeof window === "undefined") {
    return;
  }

  prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  refreshRevealElements(document);
};

export const teardownRevealSystem = () => {
  observer?.disconnect();
  observer = null;
};
