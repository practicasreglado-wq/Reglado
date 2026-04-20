<template>
  <main>
    <section class="section">
      <div class="container">
        <div class="contact-layout">
          <div class="contact-info">
            <span class="badge">Contacto</span>
            <h1 class="h1" style="margin-top:16px">Hablemos de tu proyecto</h1>
            <p class="lead" style="margin-top:16px">Cuéntanos tu necesidad y te damos respuesta técnica en menos de 48 horas.</p>
            <div class="info-items" style="margin-top:40px">
              <div class="info-item" v-for="item in infoItems" :key="item.label">
                <div class="info-label">{{ item.label }}</div>
                <div class="info-value">{{ item.value }}</div>
              </div>
            </div>
          </div>

          <div class="contact-form-wrap">
            <div class="card">
              <form @submit.prevent="handleSubmit" novalidate>
                <div style="display:grid; gap:20px">
                  <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input id="nombre" v-model="form.nombre" type="text" placeholder="Tu nombre completo" required />
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label for="email">Email *</label>
                      <input id="email" v-model="form.email" type="email" placeholder="tu@empresa.com" required />
                    </div>
                    <div class="form-group">
                      <label for="telefono">Teléfono</label>
                      <input id="telefono" v-model="form.telefono" type="tel" placeholder="600 000 000" />
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="empresa">Empresa</label>
                    <input id="empresa" v-model="form.empresa" type="text" placeholder="Nombre de tu empresa" />
                  </div>
                  <div class="form-group">
                    <label for="mensaje">Mensaje *</label>
                    <textarea id="mensaje" v-model="form.mensaje" placeholder="Describe tu proyecto o necesidad..." required></textarea>
                  </div>
                  <div v-if="alert.msg" :class="['alert', alert.type]">{{ alert.msg }}</div>
                  <button type="submit" class="btn primary" :disabled="sending" style="width:100%; justify-content:center">
                    {{ sending ? "Enviando..." : "Enviar consulta" }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup>
import { ref, reactive } from "vue";
import { submitContact } from "@/services/api.js";

const form = reactive({ nombre: "", email: "", telefono: "", empresa: "", mensaje: "" });
const sending = ref(false);
const alert = reactive({ msg: "", type: "" });

const infoItems = [
  { label: "Email", value: "info@regladoingenieria.com" },
  { label: "Tiempo de respuesta", value: "Menos de 48 horas" },
  { label: "Servicio", value: "Ingeniería industrial" },
];

async function handleSubmit() {
  if (!form.nombre.trim() || !form.email.trim() || !form.mensaje.trim()) {
    alert.msg = "Por favor, completa los campos obligatorios.";
    alert.type = "error";
    return;
  }

  sending.value = true;
  alert.msg = "";

  try {
    await submitContact({ ...form });
    alert.msg = "Consulta enviada correctamente. Te responderemos en menos de 48 horas.";
    alert.type = "success";
    Object.assign(form, { nombre: "", email: "", telefono: "", empresa: "", mensaje: "" });
  } catch (err) {
    alert.msg = err.message || "Error al enviar. Inténtalo de nuevo.";
    alert.type = "error";
  } finally {
    sending.value = false;
  }
}
</script>

<style scoped>
.contact-layout { display: grid; grid-template-columns: 1fr 1.2fr; gap: 64px; align-items: start; }
.info-items { display: flex; flex-direction: column; gap: 24px; }
.info-label { font-size: 0.8125rem; text-transform: uppercase; letter-spacing: .06em; color: var(--steel); font-weight: 600; }
.info-value { margin-top: 4px; font-size: 1rem; color: var(--text-muted); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

@media (max-width: 768px) {
  .contact-layout { grid-template-columns: 1fr; gap: 40px; }
  .form-row { grid-template-columns: 1fr; }
}
</style>
