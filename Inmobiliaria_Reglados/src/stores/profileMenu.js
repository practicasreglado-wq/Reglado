/**
 * Store mínimo para el estado del menú de perfil (abierto/cerrado).
 *
 * Vive en Pinia (en vez de en el componente) porque tanto Header.vue como
 * Profile.vue lo abren/cierran desde sitios distintos — hace falta estado
 * compartido y reactivo entre ellos sin pasar props arriba/abajo.
 */

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
