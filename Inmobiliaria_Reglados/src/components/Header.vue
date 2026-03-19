<template>
  <header>
    <div class="logo">
      <router-link to="/" class="logo-link">
        <div class="logo-wrapper">
          <img src="@/assets/reglado-RS-logo.svg" alt="Reglado Logo" class="brand-icon" />
          <h1 class="brand-text">RS</h1>
        </div>
      </router-link>
    </div>

    <nav>
      <ul>
        <li v-if="!user">
          <button class="catalog-btn" @click="goToLogin">
            Iniciar sesion
          </button>
        </li>

        <li v-if="isReal">
          <button class="catalog-btn" @click="goToCatalog">
            <span class="catalog-text">Busqueda por catalogo</span>
            <svg
              class="catalog-icon"
              viewBox="0 0 24 24"
              width="32"
              height="32"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <rect x="3" y="3" width="7" height="7"></rect>
              <rect x="14" y="3" width="7" height="7"></rect>
              <rect x="14" y="14" width="7" height="7"></rect>
              <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
          </button>
        </li>

        <li v-if="user" class="profile-nav-item" :class="{ 'in-profile': isInProfile }">
          <router-link
            v-if="isAdmin"
            to="/admin/properties"
            class="admin-badge"
            title="Panel de administracion"
          >
            <svg
              viewBox="0 0 24 24"
              width="20"
              height="20"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
            >
              <path
                d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </router-link>

          <div class="user-menu-container">
            <router-link
              to="/profile"
              class="bienvenido"
              :class="{ 'hide-on-mobile-profile': isInProfile }"
            >
              <div class="user-avatar">
                <span>{{ getInitials() }}</span>
              </div>
            </router-link>

            <button
              v-if="isInProfile"
              class="profile-menu-trigger"
              :class="{ active: isProfileMenuOpen }"
              aria-label="Abrir menu de perfil"
              @click="toggleProfileMenu"
            >
              <svg
                viewBox="0 0 24 24"
                width="24"
                height="24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
              >
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
              </svg>
            </button>
          </div>
        </li>
      </ul>
    </nav>
  </header>
</template>

<script>
import { computed, watch } from "vue";
import { storeToRefs } from "pinia";
import { useRouter, useRoute } from "vue-router";
import { useUserStore } from "../stores/user";
import { useProfileMenuStore } from "../stores/profileMenu";

export default {
  name: "Header",

  setup() {
    const userStore = useUserStore();
    const profileMenuStore = useProfileMenuStore();
    const router = useRouter();
    const route = useRoute();
    const { user, isAdmin, isReal } = storeToRefs(userStore);
    const { isOpen: isProfileMenuOpen } = storeToRefs(profileMenuStore);

    const isInProfile = computed(() => route.path.startsWith("/profile"));

    const goToCatalog = () => {
      router.push("/dashboard");
    };

    const goToLogin = () => {
      router.push("/login");
    };

    const toggleProfileMenu = () => {
      if (!isProfileMenuOpen.value && window.innerWidth <= 768) {
        window.scrollTo({ top: 0, behavior: "smooth" });
      }
      profileMenuStore.toggle();
    };

    watch(
      () => route.path,
      (path) => {
        if (!path.startsWith("/profile")) {
          profileMenuStore.close();
        }
      }
    );

    const getInitials = () => {
      if (!user.value) return "U";
      const first = user.value.nombre_usuario?.charAt(0).toUpperCase() || "";
      return first || "U";
    };

    return {
      user,
      isAdmin,
      isReal,
      isInProfile,
      isProfileMenuOpen,
      toggleProfileMenu,
      goToCatalog,
      goToLogin,
      getInitials,
    };
  },
};
</script>

<style scoped>
header {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 90px;
  z-index: 1000;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 40px 0 30px; /* Reducido de 60px y ajustado izquierda */
  background: rgba(255, 255, 255, 0.639);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  box-sizing: border-box;
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}

header .logo .logo-link {
  text-decoration: none;
  display: block;
}

.logo-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 5px 0; /* Eliminado padding lateral para estar más pegado a la izq */
  transition: transform 0.3s ease;
}

.brand-icon {
  width: 45px;
  height: 45px;
  object-fit: contain;
  filter: drop-shadow(0 0 2px rgba(189, 155, 44, 0.6));
  transition: transform 1.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.brand-text {
  font-size: 2.2rem;
  font-weight: 800;
  margin: 0;
  letter-spacing: 1px;
  background: linear-gradient(
    135deg,
    #5f4b08 0%,
    #bd9b2c 20%,
    #c9a227 45%,
    #f2d46b 55%,
    #c6a233 75%,
    #6e560c 100%
  );
  background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 1px 2px rgba(186, 129, 15, 0.4);
}

/* LOGO HOVER */
.logo-link:hover .logo-wrapper {
  transform: scale(1.02);
}

.logo-link:hover .brand-icon {
  transform: rotate(180deg) scale(1.1);
}

.logo {
  display: flex;
  align-items: center;
  gap: 8px; /* Reducido gap global */
}

.admin-badge {
  color: #bd9b2c;
  background: #ffffff;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(189, 155, 44, 0.3);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.admin-badge:hover,
.admin-badge.router-link-active {
  color: var(--azul-secundario);
  border-color: var(--azul-secundario);
  background: rgba(74, 114, 198, 0.08);
  box-shadow: 0 0 18px rgba(74, 114, 198, 0.4), 0 4px 8px rgba(0, 0, 0, 0.1);
}

.admin-badge:hover {
  animation: badgeWobble 0.6s ease-in-out;
}

@keyframes badgeWobble {
  0% { transform: rotate(0deg) scale(1.05); }
  25% { transform: rotate(-8deg) scale(1.1); }
  50% { transform: rotate(8deg) scale(1.1); }
  75% { transform: rotate(-4deg) scale(1.05); }
  100% { transform: rotate(0deg) scale(1); }
}

.admin-badge svg {
  width: 26px;
  height: 26px;
}

.profile-nav-item {
  display: flex;
  align-items: center;
  gap: 25px;
}

.logo-text {
  text-decoration: none;
}

.logo-text:hover {
  text-decoration: none;
}

nav ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  align-items: center;
  gap: 25px;
}

nav a,
.catalog-btn {
  text-decoration: none;
  color: var(--negro);
  font-size: 1.1rem;
  font-weight: 600;
  transition: 0.3s ease;
}

nav a:hover,
.catalog-btn:hover {
  color: var(--azul-secundario);
}

.catalog-btn {
  background: rgba(255, 255, 255, 0);
  font-size: 1rem;
  border: none;
  cursor: pointer;
  padding: 5px;
  color: var(--negro);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
}

.catalog-icon {
  display: block;
  transition: transform 1.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.catalog-btn:hover .catalog-icon {
  transform: rotate(180deg) scale(1.1);
}

.bienvenido {
  color: #d4af37;
  font-weight: bold;
  font-size: 1.1rem;
  text-decoration: none;
  cursor: pointer;
  transition: 0.3s;
}

.bienvenido:hover {
  color: var(--azul-secundario);
}

.user-avatar {
  display: inline-block;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background-color: var(--azul-principal);
  text-align: center;
  color: white;
  line-height: 60px;
  font-weight: bold;
  font-size: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.bienvenido:hover .user-avatar {
  transform: translateY(-3px);
  box-shadow: 0 0 18px rgb(52, 145, 192), 0 6px 12px rgba(0, 0, 0, 0.15);
}

.profile-nav-item.in-profile .user-avatar {
  box-shadow: 0 0 20px rgb(52, 145, 192), 0 6px 12px rgba(0, 0, 0, 0.15);
  border: 2px solid rgb(29, 136, 218);
}

nav a.router-link-exact-active {
  color: var(--azul-secundario);
}

.user-menu-container {
  display: flex;
  align-items: center;
  gap: 25px;
}

.profile-menu-trigger {
  display: none;
  background: rgba(23, 42, 93, 0.05);
  border: 1px solid rgba(23, 42, 93, 0.1);
  color: #172a5d;
  width: 50px;
  height: 50px;
  border-radius: 12px;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.profile-menu-trigger svg {
  width: 28px;
  height: 28px;
}

.profile-menu-trigger:hover,
.profile-menu-trigger.active {
  background: #172a5d;
  color: white;
}

@media (max-width: 768px) {
  .profile-menu-trigger {
    display: flex;
  }

  .catalog-text {
    display: none;
  }

  .catalog-icon {
    width: 42px !important;
    height: 42px !important;
  }
}

@media (max-width: 768px) {
  .bienvenido.hide-on-mobile-profile {
    display: none;
  }
}

@media (max-width: 480px) {
  header {
    height: 65px;
    padding: 0 10px; /* Aún más pegado en móviles */
  }

  .brand-icon {
    width: 28px;
    height: 28px;
  }

  .brand-text {
    font-size: 1.3rem;
    letter-spacing: 0px;
  }

  nav ul {
    gap: 8px;
  }

  .profile-nav-item {
    gap: 8px;
  }

  .user-menu-container {
    gap: 8px;
  }

  .catalog-btn {
    padding: 6px;
  }

  .bienvenido {
    font-size: 0.9rem;
  }

  .admin-badge {
    width: 36px;
    height: 36px;
  }

  .admin-badge svg {
    width: 18px;
    height: 18px;
  }

  .catalog-icon {
    width: 24px !important;
    height: 24px !important;
  }

  .user-avatar {
    width: 36px;
    height: 36px;
    line-height: 36px;
    font-size: 1rem;
    margin-right: 0;
  }

  .profile-menu-trigger {
    width: 36px;
    height: 36px;
  }

  .profile-menu-trigger svg {
    width: 18px;
    height: 18px;
  }
}
</style>
