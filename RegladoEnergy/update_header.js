import fs from 'fs';

const path = 'c:/xampp/htdocs/Reglado/RegladoEnergy/src/components/SiteHeader.vue';
let content = fs.readFileSync(path, 'utf8');

const newHtml = `        <router-link to="/sobre-nosotros" class="nav-link">Sobre nosotros</router-link>
      </nav>

      <div class="nav-actions">
        <router-link
          v-if="isAdmin"
          to="/admin"
          class="admin-pill"
          title="Panel de administración"
          aria-label="Panel de administración"
        >
          <img :src="adminUserIcon" alt="" class="admin-icon" />
        </router-link>

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
              <span class="user-initial" aria-hidden="true">{{ userInitial }}</span>
            </button>
            <div v-if="userMenuOpen" class="user-menu" role="menu" aria-label="Menu de usuario">
              <button class="user-menu-item" type="button" role="menuitem" @click="goToSettings">
                Configuración
              </button>
              <button class="user-menu-item danger" type="button" role="menuitem" @click="handleLogout">
                Cerrar sesión
              </button>
            </div>
          </div>
        </template>

        <template v-else>
          <button class="btn primary glow header-action" v-glow @click="goToLogin">
            Iniciar sesión
          </button>
        </template>

        <router-link to="/contacto" class="btn primary glow header-action" v-glow>
          Solicitar analisis
        </router-link>
      </div>`;

content = content.replace(/<router-link to="\/sobre-nosotros" class="nav-link">Sobre nosotros<\/router-link>[\s\S]*?<\/div>[\s]*<\/nav>/, newHtml);

// the CSS replacement
content = content.replace('.header-inner{ display:flex; align-items:center; justify-content:space-between; padding: 14px 0; gap: 14px; }', '.header-inner{ display:flex; align-items:center; justify-content:space-between; padding: 14px 0; gap: 14px; position: relative; }');
content = content.replace('.brand{ display:flex; align-items:center; gap: 12px; }', '.brand{ display:flex; align-items:center; gap: 12px; position: relative; z-index: 10; }');
content = content.replace('.nav{ display:flex; align-items:center; gap: 6px; }', '.nav{ display:flex; align-items:center; justify-content: center; gap: 6px; position: absolute; left: 50%; transform: translateX(-50%); z-index: 5; width: 100%; pointer-events: none; }');
content = content.replace('.nav-link{ color: rgba(233,238,246,.82); font-size: 14px; padding: 10px 10px; border-radius: 12px; border: 1px solid transparent; }', '.nav-link{ color: rgba(233,238,246,.82); font-size: 14px; padding: 10px 10px; border-radius: 12px; border: 1px solid transparent; pointer-events: auto; }');
content = content.replace('.nav-dropdown{ position: relative; }', '.nav-dropdown{ position: relative; pointer-events: auto; }');
content = content.replace('.nav-actions{ display: flex; align-items: center; gap: 10px; margin-left: 40px; }', '.nav-actions{ display: flex; align-items: center; justify-content: flex-end; gap: 10px; position: relative; z-index: 10; margin-left: auto; pointer-events: auto; }');

fs.writeFileSync(path, content, 'utf8');
console.log('Update complete!');
