<template>
  <header>
    <div class="logo">
      <router-link to="/" class="logo-text">
        <h1>RS</h1>
      </router-link>
    </div>

    <nav>
      <ul>
        <li v-if="!user">
          <button class="catalog-btn" @click="goToLogin">
            Iniciar sesión
          </button>
        </li>

        <li v-if="isReal">
          <button class="catalog-btn" @click="goToCatalog">
            <span class="catalog-text">Búsqueda por catálogo</span>
            <svg class="catalog-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7"></rect>
              <rect x="14" y="3" width="7" height="7"></rect>
              <rect x="14" y="14" width="7" height="7"></rect>
              <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
          </button>
        </li>

        <li v-if="user" class="profile-nav-item">
          <!-- Icono especial para ADMIN -->
          <div v-if="isAdmin" class="admin-badge" title="Administrador">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <router-link to="/profile" class="bienvenido">
            <div class="user-avatar">
              <span>{{ getInitials() }}</span>
            </div>
          </router-link>
        </li>
      </ul>
    </nav>
  </header>
</template>

<script>
import { useUserStore } from "../stores/user";
import { storeToRefs } from "pinia";
import { useRouter } from "vue-router";

export default {
  name: "Header",

  setup() {
    const userStore = useUserStore();
    const router = useRouter();
    const { user, isAdmin, isReal } = storeToRefs(userStore);

    const goToCatalog = () => {
      router.push("/dashboard");
    };

    const goToLogin = () => {
      router.push("/login");
    };

    const getInitials = () => {
      if (!user.value) return "U";
      const first = user.value.nombre?.charAt(0).toUpperCase() || "";
      return first || "U";
    };

    return {
      user,
      isAdmin,
      isReal,
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
  padding: 0 60px;
  background: rgba(255, 255, 255, 0.639);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  box-sizing: border-box;
}

header .logo h1 {
  font-size: 2.8rem;
  font-weight: 700;
  margin: 0;
  letter-spacing: 2px;
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
  text-shadow: 0 2px 3px rgba(186, 129, 15, 0.532);
}

.logo {
  display: flex;
  align-items: center;
  gap: 12px;
}

.admin-badge {
  color: #bd9b2c;
  background: rgba(189, 155, 44, 0.1);
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(189, 155, 44, 0.3);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-right: 15px;
}

.admin-badge svg {
  width: 26px;
  height: 26px;
}

.profile-nav-item {
  display: flex;
  align-items: center;
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
  gap: 35px;
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
  padding: 10px 22px;
  color: var(--negro);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.catalog-icon {
  display: block;
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
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background-color: var(--azul-principal);
  text-align: center;
  color: white;
  line-height: 50px;
  font-weight: bold;
  margin-right: 10px;
  font-size: 1.3rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

nav a.router-link-exact-active {
  color: var(--azul-secundario);
}

@media (max-width: 768px) {
  header {
    height: 70px;
    padding: 0 30px;
  }

  header .logo h1 {
    font-size: 2rem;
  }

  nav ul {
    gap: 20px;
  }

  .catalog-btn {
    padding: 8px;
    font-size: 0.9rem;
  }

  .catalog-icon {
    display: block;
  }

  .bienvenido {
    font-size: 1rem;
  }

  .user-avatar {
    width: 42px;
    height: 42px;
    line-height: 42px;
    font-size: 1.1rem;
  }
}

@media (max-width: 480px) {
  header {
    height: 65px;
    padding: 0 18px;
  }

  header .logo h1 {
    font-size: 1.6rem;
    letter-spacing: 1px;
  }

  nav ul {
    gap: 12px;
  }

  .catalog-btn {
    padding: 6px;
  }

  .catalog-text {
    display: none;
  }
  .bienvenido {
    font-size: 0.9rem;
  }

  .user-avatar {
    width: 34px;
    height: 34px;
    line-height: 34px;
    font-size: 0.95rem;
    margin-right: 0;
  }
}
</style>
