import { defineStore } from "pinia";
import { auth } from "../services/auth";
import { backendJson } from "../services/backend";

export const useUserStore = defineStore("user", {
  state: () => ({
    user: JSON.parse(localStorage.getItem("user")) || null,
    selectedCategory: localStorage.getItem("selectedCategory") || null,
    preferences: JSON.parse(localStorage.getItem("preferences")) || null,
  }),

  getters: {
    isLoggedIn: (state) => !!state.user,
  },

  actions: {
    setUser(userData) {
      this.user = {
        ...userData,
        apellidos: userData.apellidos || "",
        telefono: userData.telefono || "",
      };

      localStorage.setItem("user", JSON.stringify(this.user));

      if (userData.categoria !== undefined) {
        this.selectedCategory = userData.categoria;
        localStorage.setItem("selectedCategory", this.selectedCategory ?? "");
      }

      if (userData.preferencias !== undefined) {
        this.preferences = userData.preferencias;
        localStorage.setItem("preferences", JSON.stringify(this.preferences));
      }
    },

    syncFromAuthUser(authUser, localData = {}) {
      if (!authUser) {
        return;
      }

      this.setUser({
        id: authUser.id,
        iduser: localData.iduser ?? authUser.id,
        nombre: authUser.first_name || localData.nombre || "",
        apellidos: authUser.last_name || localData.apellidos || "",
        email: authUser.email || localData.email || "",
        telefono: authUser.phone || localData.telefono || "",
        nombre_usuario: authUser.username || localData.nombre_usuario || authUser.name || "",
        categoria: localData.categoria ?? this.selectedCategory,
        preferencias: localData.preferencias ?? this.preferences,
      });
    },

    async initializeSession() {
      const authUser = await auth.initialize();

      if (!authUser) {
        this.logoutLocal();
        return;
      }

      this.syncFromAuthUser(authUser);

      try {
        const payload = await backendJson("get_user_data.php");
        if (payload.success && payload.user) {
          this.syncFromAuthUser(authUser, payload.user);
        }
      } catch {
        this.syncFromAuthUser(authUser);
      }
    },

    setCategory(category) {
      this.selectedCategory = category;
      localStorage.setItem("selectedCategory", category);
    },

    setPreferences(preferences) {
      this.preferences = preferences;
      localStorage.setItem("preferences", JSON.stringify(preferences));
    },

    logoutLocal() {
      this.user = null;
      this.selectedCategory = null;
      this.preferences = null;
      localStorage.removeItem("user");
      localStorage.removeItem("selectedCategory");
      localStorage.removeItem("preferences");
    },

    async logout() {
      await auth.logout();
      this.logoutLocal();
    },
  },

  persist: true,
});
