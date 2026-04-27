/**
 * Store global del USUARIO autenticado (Pinia, persistido en localStorage).
 *
 * Diferencia con services/auth.js:
 *  - auth.js gestiona el JWT en sí (token, login/logout/cookies).
 *  - user.js gestiona los DATOS del usuario logueado (nombre, rol,
 *    categoría seleccionada, preferencias) y los expone como getters
 *    convenientes (isAdmin, isReal, isLoggedIn).
 *
 * `initializeSession()` es el flujo de bootstrapping: pide datos a ApiLoging
 * (auth.initialize) y luego completa con datos propios de Inmobiliaria
 * (get_user_data.php). Si la segunda llamada falla, mantiene los datos
 * básicos del JWT.
 *
 * `persist: true` viene de pinia-plugin-persistedstate → guarda el state
 * automáticamente en localStorage para sobrevivir refrescos.
 */

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
    userRole: (state) => state.user?.rol || "user",
    isAdmin: (state) => state.user?.rol === "admin",
    isReal: (state) => state.user?.rol === "real" || state.user?.rol === "admin",
  },

  actions: {
    setUser(userData) {
      this.user = {
        ...userData,
        rol: userData.rol || userData.role || "user",
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
        rol: authUser.role || localData.rol || "user",
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

      // Primero se hidrata con Auth API y despues se completa con datos propios de inmobiliaria.
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
