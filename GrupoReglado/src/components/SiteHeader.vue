<template>
  <header ref="headerRef" class="topbar">
    <RouterLink class="brand-link" to="/" aria-label="Ir al inicio">
      <img :src="logoSrc" alt="Reglado Energy" class="brand-logo" />
      <span class="brand">Grupo Reglado</span>
    </RouterLink>

    <nav class="menu">
      <a :href="realstateUrl">Realstate</a>
      <a :href="energyUrl">Energy</a>
      <a :href="mapasUrl">Mapas</a>
      <a :href="enProcesoUrl">EnProceso</a>
    </nav>

    <div class="session-box">
      <template v-if="user">
        <div class="user-menu-wrap">
          <button
            class="user-pill user-menu-trigger"
            @click="toggleUserMenu"
            aria-haspopup="menu"
            :aria-expanded="userMenuOpen ? 'true' : 'false'"
            :title="displayUsername"
            aria-label="Menu de usuario"
          >
            <img :src="userIcon" alt="" class="user-icon" />
          </button>

          <div v-if="userMenuOpen" class="user-menu" role="menu" aria-label="Menu de usuario">
            <button class="user-menu-item" type="button" role="menuitem" @click="goToSettings">
              Configuracion
            </button>
            <button class="user-menu-item danger" type="button" role="menuitem" @click="logout">
              Cerrar sesion
            </button>
          </div>
        </div>
      </template>
      <button v-else class="btn-primary" @click="$emit('open-login')">Login</button>
    </div>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import logoSrc from "../assets/reglado-energy-logo.svg";
import userIcon from "../assets/user-icon.svg";

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["open-login", "logout"]);
const router = useRouter();
const realstateUrl = import.meta.env.VITE_REGLADO_REALSTATE_URL || "#";
const energyUrl = import.meta.env.VITE_REGLADO_ENERGY_URL || "http://localhost:5174";
const mapasUrl = import.meta.env.VITE_REGLADO_MAPAS_URL || "#";
const enProcesoUrl = import.meta.env.VITE_REGLADO_ENPROCESO_URL || "#";

const userMenuOpen = ref(false);
const headerRef = ref(null);

const displayUsername = computed(() => {
  const username = props.user?.username;
  if (typeof username === "string" && username.trim() !== "") {
    return username.trim();
  }
  return props.user?.name || "Usuario";
});

function toggleUserMenu() {
  userMenuOpen.value = !userMenuOpen.value;
}

function logout() {
  userMenuOpen.value = false;
  emit("logout");
}

function goToSettings() {
  userMenuOpen.value = false;
  router.push("/configuracion");
}

function handlePointerDown(event) {
  if (!userMenuOpen.value) {
    return;
  }

  const headerEl = headerRef.value;
  if (headerEl && !headerEl.contains(event.target)) {
    userMenuOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener("pointerdown", handlePointerDown);
});

onBeforeUnmount(() => {
  document.removeEventListener("pointerdown", handlePointerDown);
});
</script>

<style scoped>
.topbar {
  position: sticky;
  top: 0;
  z-index: 40;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 1rem;
  padding: 0.9rem 1.35rem;
  margin: 0.8rem 0.8rem 0;
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(23, 39, 61, 0.95), rgba(39, 61, 92, 0.88));
  border: 1px solid rgba(255, 255, 255, 0.16);
  box-shadow: 0 12px 25px rgba(16, 28, 47, 0.22);
  backdrop-filter: blur(8px);
}

.brand-link {
  display: inline-flex;
  align-items: center;
  gap: 0.62rem;
  text-decoration: none;
  justify-self: start;
}

.brand-logo {
  width: 36px;
  height: 36px;
  object-fit: contain;
}

.brand {
  font-family: "Manrope", "Trebuchet MS", sans-serif;
  font-weight: 700;
  color: #f6f8fc;
  letter-spacing: 0.02em;
}

.menu {
  display: flex;
  gap: 0.38rem;
  justify-self: center;
  align-items: center;
  padding: 0.22rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
}

.menu a {
  text-decoration: none;
  color: rgba(241, 246, 255, 0.9);
  font-size: 0.9rem;
  font-weight: 500;
  padding: 0.38rem 0.68rem;
  border-radius: 999px;
  transition: background 0.2s ease, color 0.2s ease;
}

.menu a:hover {
  background: rgba(255, 255, 255, 0.17);
  color: #fff;
}

.session-box {
  justify-self: end;
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
}

.user-menu-wrap {
  position: relative;
}

.user-pill {
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 999px;
  width: 39px;
  height: 39px;
  padding: 0;
  background: rgba(255, 255, 255, 0.08);
  display: grid;
  place-items: center;
}

.user-menu-trigger {
  cursor: pointer;
}

.user-icon {
  width: 19px;
  height: 19px;
  display: block;
}

.user-menu {
  position: absolute;
  top: calc(100% + 9px);
  right: 0;
  min-width: 180px;
  border: 1px solid rgba(39, 61, 92, 0.2);
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
  padding: 0.35rem;
  display: grid;
  gap: 0.25rem;
  z-index: 60;
}

.user-menu-item {
  width: 100%;
  border: 1px solid transparent;
  border-radius: 9px;
  background: #fff;
  color: #273d5c;
  text-align: left;
  padding: 0.54rem 0.62rem;
  cursor: pointer;
}

.user-menu-item:hover {
  background: #f1f5fb;
}

.user-menu-item.danger {
  color: #b42318;
}

@media (max-width: 900px) {
  .topbar {
    grid-template-columns: 1fr auto;
    row-gap: 0.7rem;
  }

  .menu {
    grid-column: 1 / -1;
    justify-self: stretch;
    justify-content: flex-start;
    overflow-x: auto;
  }
}

@media (max-width: 640px) {
  .topbar {
    margin: 0.6rem 0.6rem 0;
    border-radius: 14px;
    padding: 0.75rem 0.9rem;
  }

  .brand {
    font-size: 0.94rem;
  }
}
</style>
