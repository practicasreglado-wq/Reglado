import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";

export function useAnchoredPanel({ maxWidth = 360 } = {}) {
  const anchorRef = ref(null);
  const panelRef = ref(null);
  const visible = ref(false);
  const panelStyle = ref({});

  const position = () => {
    const anchor = anchorRef.value;
    const panel = panelRef.value;
    if (!anchor || !panel) return;

    const margin = 12;
    const width = Math.min(maxWidth, window.innerWidth - margin * 2);
    panelStyle.value = { width: `${width}px`, top: "0px", left: "0px" };

    window.requestAnimationFrame(() => {
      const panelHeight = panel.offsetHeight || 280;
      const anchorRect = anchor.getBoundingClientRect();
      const siteHeader = document.querySelector("header.site-header");
      const headerBottom = siteHeader
        ? siteHeader.getBoundingClientRect().bottom
        : 0;
      const minTop = Math.max(margin, headerBottom + margin);

      const spaceBelow = window.innerHeight - anchorRect.bottom - margin;
      const openBelow = spaceBelow >= panelHeight;
      const desiredTop = openBelow
        ? anchorRect.bottom + margin
        : anchorRect.top - panelHeight - margin;

      const top = Math.max(
        minTop,
        Math.min(desiredTop, window.innerHeight - panelHeight - margin)
      );

      let left = anchorRect.right - width;
      left = Math.max(margin, Math.min(left, window.innerWidth - width - margin));

      panelStyle.value = {
        width: `${width}px`,
        top: `${top}px`,
        left: `${left}px`,
      };
    });
  };

  const close = () => {
    visible.value = false;
  };

  const open = () => {
    visible.value = true;
  };

  const toggle = () => {
    if (visible.value) close();
    else open();
  };

  const handleDocumentClick = (event) => {
    if (
      panelRef.value?.contains(event.target) ||
      anchorRef.value?.contains(event.target)
    ) {
      return;
    }
    close();
  };

  const handleKeyDown = (event) => {
    if (event.key === "Escape") close();
  };

  const handleViewport = () => {
    if (visible.value) position();
  };

  watch(visible, async (value) => {
    if (value) {
      document.addEventListener("mousedown", handleDocumentClick);
      document.addEventListener("keydown", handleKeyDown);
      await nextTick();
      position();
    } else {
      document.removeEventListener("mousedown", handleDocumentClick);
      document.removeEventListener("keydown", handleKeyDown);
    }
  });

  onMounted(() => {
    window.addEventListener("resize", handleViewport);
    window.addEventListener("scroll", handleViewport, true);
  });

  onBeforeUnmount(() => {
    document.removeEventListener("mousedown", handleDocumentClick);
    document.removeEventListener("keydown", handleKeyDown);
    window.removeEventListener("resize", handleViewport);
    window.removeEventListener("scroll", handleViewport, true);
  });

  return {
    anchorRef,
    panelRef,
    visible,
    panelStyle,
    open,
    close,
    toggle,
    reposition: position,
  };
}
