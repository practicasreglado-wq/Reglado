<template>
  <section class="properties-sale">
    <div class="properties-sale__hero">
      <div class="properties-sale__copy">
        <p class="eyebrow">Gestión Personal</p>
        <h2>Mis propiedades</h2>
        <p>
          Administra y supervisa tus activos inmobiliarios publicados.
          Aquí puedes ver el estado de tus anuncios y gestionar tus propiedades.
        </p>
      </div>

      <div class="hero-actions">
        <button class="hero-action hero-action--primary" @click="goCreate">
          Crear nueva propiedad
        </button>
      </div>
    </div>

    <div v-if="loading" class="properties-sale__state">
      Cargando tus propiedades...
    </div>

    <div v-else-if="properties.length === 0" class="properties-sale__state">
      <p>Todavía no tienes propiedades publicadas. ¡Crea la primera!</p>
    </div>

    <div v-else class="properties-sale__grid">
      <div
  v-for="property in properties"
  :key="property.id"
  class="properties-sale__item"
>
  <PropertyCard
    :property="property"
    :favorite-loading="pendingFavorites.has(property.id)"
    :show-delete-button="true"
    :delete-loading="deletingPropertyId === property.id"
    @toggle-favorite="toggleFavorite"
    @delete-property="askDelete"
  />
</div>
    </div>

    <teleport to="body">
  <transition name="fade">
    <div
      v-if="showDeleteModal && propertyToDelete"
      class="delete-modal-overlay"
      @click.self="closeDeleteModal"
    >
      <div class="delete-modal">
        <p class="delete-modal__eyebrow">Solicitud de eliminación</p>
        <h3>¿Solicitar eliminación?</h3>
        <p class="delete-modal__text">
          Vas a solicitar la eliminación de
          <strong>{{ propertyToDelete.titulo || propertyToDelete.tipo_propiedad || "esta propiedad" }}</strong>.
          Un administrador la revisará y decidirá si aprobar o rechazar la
          petición. Recibirás el resultado por correo y en tus notificaciones.
        </p>

        <label class="delete-modal__reason-label">
          Motivo (opcional)
          <textarea
            v-model="deletionReason"
            class="delete-modal__reason"
            rows="3"
            maxlength="1000"
            placeholder="Indícale al admin por qué quieres eliminarla..."
            :disabled="deletingPropertyId === propertyToDelete.id"
          ></textarea>
        </label>

        <p v-if="deletionFeedback" class="delete-modal__feedback" :class="{ 'is-error': deletionFeedbackIsError }">
          {{ deletionFeedback }}
        </p>

        <div class="delete-modal__actions">
          <button
            class="modal-button modal-button--secondary"
            :disabled="deletingPropertyId === propertyToDelete.id"
            @click="closeDeleteModal"
          >
            Cancelar
          </button>

          <button
            class="modal-button modal-button--danger"
            :disabled="deletingPropertyId === propertyToDelete.id"
            @click="confirmDelete"
          >
            {{ deletingPropertyId === propertyToDelete.id ? "Enviando..." : "Enviar solicitud" }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</teleport>
  </section>
</template>

<script>
import PropertyCard from "../components/PropertyCard.vue";
import {
  fetchUserPropertiesForSale,
  removeFavorite,
  saveFavorite,
  requestUserPropertyDeletion,
} from "../services/properties";

export default {
  name: "MyPropertiesForSale",

  components: {
    PropertyCard,
  },

  data() {
    return {
      properties: [],
      loading: true,
      pendingFavorites: new Set(),
      showDeleteModal: false,
      propertyToDelete: null,
      deletingPropertyId: null,
      deletionReason: "",
      deletionFeedback: "",
      deletionFeedbackIsError: false,
    };
  },

  mounted() {
    this.getProperties();
  },

  methods: {
    goCreate() {
      this.$router.push("/profile/create-property");
    },

    async getProperties() {
      this.loading = true;

      try {
        this.properties = await fetchUserPropertiesForSale();
      } catch (error) {
        console.error("Error cargando propiedades:", error);
        this.properties = [];
      } finally {
        this.loading = false;
      }
    },

    async toggleFavorite(property) {
      if (!property?.id || this.pendingFavorites.has(property.id)) {
        return;
      }

      this.pendingFavorites.add(property.id);

      try {
        if (property.is_favorite) {
          await removeFavorite(property.id);
        } else {
          await saveFavorite(property.id);
        }

        await this.getProperties();
      } catch (error) {
        console.error("Error actualizando favorito:", error);
      } finally {
        this.pendingFavorites.delete(property.id);
      }
    },

    askDelete(property) {
      if (!property?.id) {
        return;
      }

      this.propertyToDelete = property;
      this.showDeleteModal = true;
    },

    closeDeleteModal(force = false) {
      if (!force && this.deletingPropertyId) {
        return;
      }

      this.showDeleteModal = false;
      this.propertyToDelete = null;
      this.deletionReason = "";
      this.deletionFeedback = "";
      this.deletionFeedbackIsError = false;
    },

    async confirmDelete() {
      if (!this.propertyToDelete?.id) {
        return;
      }

      const propertyId = this.propertyToDelete.id;
      this.deletingPropertyId = propertyId;
      this.deletionFeedback = "";
      this.deletionFeedbackIsError = false;

      try {
        await requestUserPropertyDeletion(propertyId, this.deletionReason);

        this.deletionFeedback =
          "Solicitud enviada. Un administrador la revisará en breve y recibirás el resultado por email y notificación.";
        this.deletionFeedbackIsError = false;
        this.deletingPropertyId = null;

        setTimeout(() => {
          this.closeDeleteModal(true);
        }, 2200);
      } catch (error) {
        console.error("Error solicitando eliminación:", error);
        this.deletionFeedback =
          error?.message || "No se pudo enviar la solicitud. Inténtalo de nuevo.";
        this.deletionFeedbackIsError = true;
        this.deletingPropertyId = null;
      }
    },
  },
};
</script>

<style scoped>
.properties-sale {
  display: grid;
  gap: 28px;
}

.properties-sale__hero {
  position: relative;
  overflow: hidden;
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(220px, 0.7fr);
  gap: 20px;
  padding: 28px 30px;
  border-radius: 28px;
  background:
    radial-gradient(circle at 14% 80%, transparent 0 9px, transparent 10px),
    radial-gradient(circle at 86% 16%, transparent 0 11px, transparent 12px),
    radial-gradient(circle at top right, rgba(244, 208, 120, 0.24), transparent 30%),
    linear-gradient(135deg, #12244d 0%, #20386b 55%, #3a5ca9 100%);
  color: #fff;
  box-shadow: 0 22px 48px rgba(18, 36, 77, 0.22);
  background-repeat: no-repeat;
}

.properties-sale__hero::before,
.properties-sale__hero::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  opacity: 0.72;
  transition: opacity 0.28s ease;
}

.properties-sale__hero::before {
  width: 220px;
  height: 220px;
  right: -70px;
  top: -90px;
  background: rgba(255, 255, 255, 0.08);
}

.properties-sale__hero::after {
  width: 160px;
  height: 160px;
  left: -50px;
  bottom: -90px;
  background: rgba(255, 204, 84, 0.14);
}

.properties-sale__hero > * {
  position: relative;
  z-index: 2;
}

.properties-sale__copy {
  display: grid;
  align-content: center;
}

.eyebrow {
  margin: 0 0 10px;
  opacity: 0.82;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.78rem;
  font-weight: 700;
}

.properties-sale__hero h2 {
  margin: 0 0 10px;
  font-size: 2.2rem;
  line-height: 1.05;
}

.properties-sale__hero p:last-child {
  margin: 0;
  max-width: 62ch;
  color: rgba(255, 255, 255, 0.82);
  line-height: 1.6;
}

.hero-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.hero-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 46px;
  padding: 0 24px;
  border-radius: 14px;
  text-decoration: none;
  font-weight: 700;
  transition: all 0.22s ease;
  cursor: pointer;
  border: none;
}

.hero-action--primary {
  background: #f4d078;
  color: #172a5d;
  box-shadow: 0 14px 28px rgba(244, 208, 120, 0.22);
}

.hero-action:hover {
  transform: translateY(-2px);
  filter: brightness(1.05);
}

.properties-sale__state {
  padding: 28px;
  border-radius: 24px;
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  border: 1px solid #dfe6f2;
  color: #5a6880;
  box-shadow: 0 14px 32px rgba(23, 42, 93, 0.08);
}

.properties-sale__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
  gap: 22px;
}

.delete-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;

  background: transparent; /* sin gris */
  backdrop-filter: blur(6px); /* 🔥 solo distorsión */
}

.delete-modal {
  width: 100%;
  max-width: 460px;
  padding: 26px;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 30px 80px rgba(15, 23, 42, 0.25);
}

.delete-modal__eyebrow {
  margin: 0 0 8px;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #64748b;
}

.delete-modal h3 {
  margin: 0 0 10px;
  font-size: 1.4rem;
  color: #0f172a;
}

.delete-modal__text {
  margin: 0;
  line-height: 1.6;
  color: #475569;
}

.delete-modal__reason-label {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #1e293b;
}

.delete-modal__reason {
  width: 100%;
  padding: 10px 12px;
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  background: #fff;
  font: inherit;
  color: #0f172a;
  resize: vertical;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.delete-modal__reason:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.delete-modal__feedback {
  margin: 0;
  padding: 10px 12px;
  border-radius: 10px;
  background: #ecfdf5;
  border: 1px solid #86efac;
  color: #15803d;
  font-size: 0.85rem;
  line-height: 1.5;
}

.delete-modal__feedback.is-error {
  background: #fef2f2;
  border-color: #fca5a5;
  color: #b91c1c;
}

.delete-modal__actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 22px;
}

.modal-button {
  min-height: 44px;
  padding: 0 18px;
  border-radius: 12px;
  border: none;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.modal-button--secondary {
  background: #e2e8f0;
  color: #1e293b;
}

.modal-button--danger {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
  color: #ffffff;
}

.modal-button:hover:not(:disabled) {
  transform: translateY(-1px);
}

.modal-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.22s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@media (max-width: 768px) {
  .properties-sale__hero {
    grid-template-columns: 1fr;
    padding: 22px;
  }

  .hero-actions {
    justify-content: flex-start;
  }

  .properties-sale__grid {
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
  }

  .delete-modal__actions {
    flex-direction: column;
  }

  .modal-button {
    width: 100%;
  }
}
</style>