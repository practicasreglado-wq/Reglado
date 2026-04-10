import { defineStore } from "pinia";

export const useProfileMenuStore = defineStore("profileMenu", {
  state: () => ({
    isOpen: false,
  }),

  actions: {
    open() {
      this.isOpen = true;
    },

    close() {
      this.isOpen = false;
    },

    toggle() {
      this.isOpen = !this.isOpen;
    },
  },
});
