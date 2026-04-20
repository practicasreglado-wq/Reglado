export default {
  mounted(el) {
    if (!el.classList.contains("glow")) return;

    const onMove = (e) => {
      const r = el.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width) * 100;
      const y = ((e.clientY - r.top) / r.height) * 100;
      el.style.setProperty("--gx", `${x}%`);
      el.style.setProperty("--gy", `${y}%`);
    };

    const onEnter = () => el.classList.add("glow-on");
    const onLeave = () => el.classList.remove("glow-on");

    el.addEventListener("pointermove", onMove);
    el.addEventListener("pointerenter", onEnter);
    el.addEventListener("pointerleave", onLeave);

    el._glowCleanup = () => {
      el.removeEventListener("pointermove", onMove);
      el.removeEventListener("pointerenter", onEnter);
      el.removeEventListener("pointerleave", onLeave);
    };
  },
  unmounted(el) {
    el._glowCleanup?.();
  },
};
