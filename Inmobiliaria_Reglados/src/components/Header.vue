<template>
  <header :class="{ 'is-scrolled': scrolled && !isInProfile, 'at-top-home': isHome && !scrolled }">
    <div class="logo">
      <router-link to="/" class="logo-link">
        <div class="logo-wrapper">
          <img src="@/assets/Logo_RegladoRS.svg" alt="Reglado Logo" class="brand-icon" />
          <h1 class="brand-text">RS</h1>
        </div>
      </router-link>
    </div>

    <nav>
      <ul>
        <li v-if="showLoginButton">
  <button class="catalog-btn" @click="goToLogin">
    Iniciar sesion
  </button>
</li>

        <li>
          <a href="http://regladogroup.com" class="grupo-link" target="_blank">
            Reglado Group
          </a>
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

        <li v-if="user" class="notification-nav-item">
          <NotificationBell />
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
                {{ getInitials() }}
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
import { computed, watch, onMounted, onUnmounted, ref } from "vue";
import { storeToRefs } from "pinia";
import { useRouter, useRoute } from "vue-router";
import { useUserStore } from "../stores/user";
import { useProfileMenuStore } from "../stores/profileMenu";
import NotificationBell from "./NotificationBell.vue";

export default {
  name: "Header",
  components: {
    NotificationBell,
  },

  setup() {
    const userStore = useUserStore();
    const profileMenuStore = useProfileMenuStore();
    const router = useRouter();
    const route = useRoute();

    const { user, isAdmin, isReal } = storeToRefs(userStore);
    const { isOpen: isProfileMenuOpen } = storeToRefs(profileMenuStore);

    const isInProfile = computed(() => route.path.startsWith("/profile"));
    const isHome = computed(() => route.path === "/");

    const authRoutes = ["/login", "/register", "/forgot-password", "/reset-password"];
    const isAuthRoute = computed(() =>
      authRoutes.some((authPath) => route.path.startsWith(authPath))
    );

    const showLoginButton = computed(() => !user.value && !isAuthRoute.value);

    const goToCatalog = () => {
      if (route.path !== "/dashboard") {
        router.push("/dashboard");
      }
    };

   const getCallbackUrl = () => {
      return `${window.location.origin}/#/auth/callback`;
    };

    const buildExternalAuthUrl = (path) => {
      const base =
        import.meta.env.VITE_GRUPO_REGLADO_BASE_URL || "http://localhost:5173";
      const url = new URL(path, base);
      url.searchParams.set("returnTo", getCallbackUrl());
      return url.toString();
    };

const goToLogin = () => {
  const loginPath =
    import.meta.env.VITE_GRUPO_REGLADO_LOGIN_PATH || "/login";
  window.location.href = buildExternalAuthUrl(loginPath);
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

      const username =
        user.value.nombre_usuario ||
        user.value.username ||
        user.value.nombre ||
        user.value.name ||
        "";

      return username?.charAt(0)?.toUpperCase() || "U";
    };

    const scrolled = ref(false);

    const handleScroll = () => {
      const threshold = isHome.value ? window.innerHeight * 0.9 : 20;
      scrolled.value = window.scrollY > threshold;
    };

    onMounted(() => {
      window.addEventListener("scroll", handleScroll);
      handleScroll();
    });

    onUnmounted(() => {
      window.removeEventListener("scroll", handleScroll);
    });

    return {
      user,
      isAdmin,
      isReal,
      isInProfile,
      isProfileMenuOpen,
      isHome,
      scrolled,
      showLoginButton,
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
  height: clamp(70px, 8vw, 90px);
  z-index: 1000;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 var(--spacing-md);
  box-sizing: border-box;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  user-select: none;
  
  /* DEFAULT STATE: Greyish-White Glassmorphism (Other pages / Scrolled Home) */
  background: rgba(233, 233, 233, 0.7); /* Sync with #e9e9e9 but semi-transparent */
  backdrop-filter: blur(30px); /* Much stronger blur for premium glass effect */
  -webkit-backdrop-filter: blur(30px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06); 
  border-bottom: 1px solid rgba(0, 0, 0, 0.04);
}

.grupo-link {
  position: relative;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.4px;
  display: inline-block; 
  transition: all 0.3s ease;
}

/* Línea elegante animada */
.grupo-link::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: -2px; /* 👈 más pegado al texto */
  width: 0%;
  height: 2px;
  border-radius: 2px;
  background: linear-gradient(90deg, #c9a227, #f2d46b, #c9a227);
  transition: width 0.3s ease;
}

/* Hover */
.grupo-link:hover::after {
  width: 100%;
}

.grupo-link:hover {
  transform: translateY(-2px);
}

/* MODO HOME (sobre fondo oscuro/video) */
header.at-top-home .grupo-link {
  color: #ffffff;
  text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

/* MODO NORMAL */
header:not(.at-top-home) .grupo-link {
  color: #1a2545;
}

/* Opcional: efecto dorado al hover en modo normal */
header:not(.at-top-home) .grupo-link:hover {
  background: linear-gradient(
    135deg,
    #5f4b08,
    #c9a227,
    #cfad30,
    #c8a12d
  );
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
/* TRANSPARENT HOME STATE */
header.at-top-home {
  background: transparent;
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
  box-shadow: none;
  border-bottom: 1px solid transparent;
  padding-left: 10rem;
}

header.is-scrolled {
  height: clamp(65px, 7vw, 75px);
  background: rgba(255, 255, 255, 0.75); /* Opaque enough but still glassmorphic */
  backdrop-filter: blur(30px);
  -webkit-backdrop-filter: blur(30px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08); /* More subtle than 15px/40px */
  border-bottom: 1px solid rgba(74, 114, 198, 0.1);
}

/* LOGO & NAVIGATION COLORS */

header.at-top-home .brand-text,
header.at-top-home nav a,
header.at-top-home .catalog-btn {
  color: #ffffff;
  text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

/* Restored Gold Gradient for Standard Mode */
header:not(.at-top-home) .brand-text {
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
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  color: #bd9b2c; /* Fallback */
}

/* Forced reset for all links to avoid purple/visited styles */
header a, 
header a:visited,
header a:active,
header a:hover {
  text-decoration: none !important;
  outline: none;
}

header:not(.at-top-home) nav a,
header:not(.at-top-home) .catalog-btn {
  color: #1a2545;
  text-shadow: none;
}

/* CATALOG BUTTON ALIGNMENT */
.catalog-btn {
  padding: 8px 22px;
  border-radius: 99px;
  border: 1px solid transparent;
  transition: all 0.3s ease;
  display: flex;
  align-items: center; /* Center vertically */
  justify-content: center;
  gap: 12px; /* Increased gap */
  line-height: 1; /* Ensure text isn't pushed down */
}

.grupo-link {
  font-size: 0.95rem;
  font-weight: 700;
  transition: all 0.3s ease;
}

/* En home transparente */
header.at-top-home .grupo-link {
  color: #ffffff;
}

/* En modo normal */
header:not(.at-top-home) .grupo-link {
  color: #1a2545;
}

.grupo-link:hover {
  opacity: 0.7;
}

.catalog-text {
  display: inline-block;
  transform: translateY(-1px); /* Subtle nudge for visual alignment */
}

header.at-top-home .catalog-btn {
  border-color: rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.1);
}

header:not(.at-top-home) .catalog-btn {
  background: var(--azul-secundario);
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(74, 114, 198, 0.2);
}

.catalog-btn:hover .catalog-icon {
  transform: rotate(180deg) scale(1.1);
}

/* PROFILE ICON */
.user-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  transition: all 0.4s ease;
  font-weight: 700;
}

/* Video Mode Profile Icon */
header.at-top-home .user-avatar {
  background: #ffffff;
  color: #1a2545;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Standard Mode Profile Icon */
header:not(.at-top-home) .user-avatar {
  background: #1a2545;
  color: #ffffff;
}

/* REST OF STYLES */

.logo-wrapper {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  cursor: pointer;
  transition: transform 0.3s ease;
}

.logo-link, .logo-link:visited {
  color: inherit;
  text-decoration: none;
}

.logo-link:hover .logo-wrapper {
  transform: scale(1.02);
}

.logo-link:hover .brand-icon {
  transform: rotate(180deg) scale(1.1);
}

.admin-badge {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  border: 1px solid transparent;
  margin-right: 25px;
}

header.at-top-home .admin-badge {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.2);
}

header:not(.at-top-home) .admin-badge {
  background: rgba(26, 37, 69, 0.05);
  color: #1a2545;
  border-color: rgba(26, 37, 69, 0.1);
}

.admin-badge:hover {
  transform: translateY(-2px);
  background: var(--azul-secundario) !important;
  color: #ffffff !important;
}

.brand-icon {
  width: clamp(35px, 5vw, 52px);
  height: clamp(35px, 5vw, 52px);
  object-fit: contain;
  transition: transform 1.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

header.at-top-home .brand-icon {
  filter: brightness(0) invert(1);
}

.brand-text {
  font-size: clamp(1.4rem, 3vw, 2.5rem);
  font-weight: 800;
  letter-spacing: 0.5px;
  margin: 0;
}

nav ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

nav a {
  font-size: 0.95rem;
  font-weight: 700;
}

.catalog-icon {
  width: 20px;
  height: 20px;
  stroke: currentColor;
  transition: transform 1.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.profile-menu-trigger {
  display: none;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

header.at-top-home .profile-menu-trigger {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.2);
}

header:not(.at-top-home) .profile-menu-trigger {
  background: rgba(26, 37, 69, 0.05);
  color: #1a2545;
  border-color: rgba(26, 37, 69, 0.1);
}

@media (max-width: 768px) {
  .catalog-text {
    display: none;
  }
  
  .profile-menu-trigger {
    display: flex;
  }
}

@media (max-width: 768px) {
  .bienvenido.hide-on-mobile-profile {
    display: none;
  }

  .user-avatar {
    width: 36px; /* Matching the catalog icon button size */
    height: 36px;
    font-size: 0.8rem; /* Smaller font for smaller avatar */
  }

  .admin-badge {
    width: 36px;
    height: 36px;
  }
}

@media (max-width: 768px) {
  header {
    height: 75px; /* Fixed undefined variable and added space */
    padding: 0 var(--spacing-md);
  }
  header.at-top-home {
    padding-left: var(--spacing-md);
  }
}

@media (max-width: 480px) {
  header {
    height: 70px; /* Slightly taller for better icon centering */
    padding: 0 15px;
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

  .catalog-icon {
    width: 24px !important;
    height: 24px !important;
  }

}
.notification-nav-item,
.profile-nav-item,
.user-menu-container {
  display: flex;
  align-items: center;
}

.notification-nav-item {
  margin-left: -4px;
  margin-right: -4px;
}

header.at-top-home :deep(.notification-bell__button) {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

header:not(.at-top-home) :deep(.notification-bell__button) {
  color: #1a2545;
  background: rgba(26, 37, 69, 0.05);
  border-color: rgba(26, 37, 69, 0.1);
}

header.at-top-home :deep(.notification-bell__button:hover) {
  background: rgba(255, 255, 255, 0.16);
}

header:not(.at-top-home) :deep(.notification-bell__button:hover) {
  background: rgba(26, 37, 69, 0.1);
}

@media (max-width: 768px) {
  .notification-nav-item :deep(.notification-bell__button) {
    width: 36px;
    height: 36px;
    border-radius: 12px;
  }
}
</style>