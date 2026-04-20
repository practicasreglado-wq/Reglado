export default {
  mounted(el, binding) {
    const opts = binding.value || {};
    const mods = binding.modifiers || {};
    const delay = opts.delay ?? 0;
    const once = opts.once ?? true;
    const distance = opts.distance ?? 32;
    const duration = opts.duration ?? 520;
    const from =
      opts.from ||
      (mods.left ? "left" : mods.right ? "right" : mods.down ? "down" : "up");

    const reducedMotion = window.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches;

    let x = "0px";
    let y = "0px";
    let bx = "0px";
    let by = "0px";
    if (from === "left") x = `-${distance}px`;
    if (from === "right") x = `${distance}px`;
    if (from === "up") y = `-${distance}px`;
    if (from === "down") y = `${distance}px`;
    if (from === "left") bx = `${Math.round(distance * 0.16)}px`;
    if (from === "right") bx = `-${Math.round(distance * 0.16)}px`;
    if (from === "up") by = `${Math.round(distance * 0.16)}px`;
    if (from === "down") by = `-${Math.round(distance * 0.16)}px`;

    el.style.setProperty("--reveal-delay", `${delay}ms`);
    el.style.setProperty("--reveal-duration", `${duration}ms`);
    el.style.setProperty("--reveal-x", x);
    el.style.setProperty("--reveal-y", y);
    el.style.setProperty("--reveal-bx", bx);
    el.style.setProperty("--reveal-by", by);
    el.classList.add("reveal");

    if (reducedMotion) {
      el.classList.add("is-visible");
      return;
    }

    const show = () => el.classList.add("is-visible");
    const hide = () => el.classList.remove("is-visible");

    if (!("IntersectionObserver" in window)) {
      show();
      return;
    }

    const io = new IntersectionObserver(
      (entries) => {
        for (const e of entries) {
          if (e.isIntersecting) {
            requestAnimationFrame(show);
            if (once) io.unobserve(el);
          } else if (!once) {
            hide();
          }
        }
      },
      { threshold: opts.threshold ?? 0.15, rootMargin: opts.rootMargin ?? "0px 0px -8% 0px" }
    );

    io.observe(el);
    el._revealIO = io;
  },
  unmounted(el) {
    el._revealIO?.disconnect?.();
  },
};
