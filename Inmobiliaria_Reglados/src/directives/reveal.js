import { observeReveal, unobserveReveal } from "../utils/reveal";

export const revealDirective = {
  mounted(element, binding) {
    const order = Number(binding.value) || 0;
    observeReveal(element, order);
  },
  unmounted(element) {
    unobserveReveal(element);
  },
};
