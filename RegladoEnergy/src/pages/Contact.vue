<!-- pagina Contacto en ruta /contacto -->
<template>
  <section class="section">
    <div class="container">
      <div class="grid grid-2">
        <div class="card soft glow" v-glow v-reveal="{ from: 'left', delay: 50 }">
          <div class="badge" v-reveal="{ from: 'up', delay: 80 }">Contacto</div>
          <h1 class="h1" v-reveal="{ from: 'up', delay: 120 }">Realizamos un análisis gratuito de facturas.</h1>
          <p class="p" v-reveal="{ from: 'right', delay: 170 }">
            Completa el formulario o sube tu factura en PDF o imagen y te llamamos con los resultados.
          </p>

          <div class="card" style="margin-top:14px;" v-reveal="{ from: 'up', delay: 210 }">
            <h2 class="h2" style="margin-bottom:10px;" v-reveal="{ from: 'up', delay: 240 }">Estudios personalizados</h2>
            <p class="p" v-reveal="{ from: 'up', delay: 270 }">
              Cada cliente tiene un consumo distinto. Realizamos estudios personalizados en función del perfil y las necesidades reales.
            </p>

            <h2 class="h2" style="margin:14px 0 10px;" v-reveal="{ from: 'up', delay: 300 }">Subida de facturas</h2>
            <p class="p" v-reveal="{ from: 'up', delay: 330 }">
              Sube tu factura en PDF o imagen. Realizaremos un análisis previo y te contactaremos para comentarte los resultados y las opciones de mejora.
            </p>
          </div>
        </div>

        <div class="card glow" v-glow v-reveal="{ from: 'right', delay: 70 }">
          <h2 class="h2" v-reveal="{ from: 'up', delay: 100 }">Formulario</h2>
          <p class="p" v-reveal="{ from: 'up', delay: 130 }">Tus datos se guardan en base de datos y el archivo de factura se almacena en el servidor.</p>

          <form class="form" @submit.prevent="submit" v-reveal="{ from: 'up', delay: 160 }">
            <div class="grid grid-2">
              <div class="field" v-reveal="{ from: 'left', delay: 190 }">
                <label>Nombre *</label>
                <input v-model="f.name" :disabled="isLoggedIn" required placeholder="Tu nombre" />
              </div>
              <div class="field" v-reveal="{ from: 'right', delay: 220 }">
                <label>Teléfono *</label>
                <input
                  v-model="f.phone"
                  :disabled="isPhoneLocked"
                  required
                  type="tel"
                  inputmode="numeric"
                  pattern="[0-9]{9}"
                  minlength="9"
                  maxlength="9"
                  title="Introduce un teléfono de 9 dígitos"
                  placeholder="Ej: 612345678"
                  @input="onPhoneInput"
                />
              </div>
            </div>

            <div class="field" v-reveal="{ from: 'up', delay: 250 }">
              <label>Email *</label>
              <input v-model="f.email" :disabled="isLoggedIn" required type="email" placeholder="tu@email.com" />
            </div>

            <div class="field" v-reveal="{ from: 'up', delay: 280 }">
              <label>Mensaje (opcional)</label>
              <textarea v-model="f.msg" placeholder="Cuéntanos tu caso (tarifa, potencia, incidencias, etc.)"></textarea>
            </div>

            <div class="field" v-reveal="{ from: 'up', delay: 310 }">
              <label>Factura (PDF o imagen) (opcional)</label>
              <input ref="fileInput" type="file" accept="application/pdf,image/*" @change="onFile" class="file" />
            </div>

            <button class="btn primary glow" :disabled="sending" v-glow v-reveal="{ from: 'up', delay: 340 }" type="submit">
              {{ sending ? "Enviando..." : "Enviar solicitud" }}
            </button>
          </form>

          <div v-if="errorMsg" class="error" v-reveal="{ from: 'up', delay: 80 }">
            <strong>Error:</strong> {{ errorMsg }}
          </div>

          <div v-if="sent" class="sent" v-reveal="{ from: 'up', delay: 100 }">
            <strong>{{ successMsg }}</strong>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { setSeo } from "../seo.js";
import { auth } from "../services/auth";

const API_ENDPOINT =
  import.meta.env.VITE_CONTACT_ENDPOINT ||
  "http://localhost/Reglado/RegladoEnergy/BACKEND/contact.php";

const f = reactive({ name: "", phone: "", email: "", msg: "", file: null });
const fileInput = ref(null);
const sent = ref(false);
const sending = ref(false);
const errorMsg = ref("");
const successMsg = ref("");
const isLoggedIn = computed(() => Boolean(auth.state.user));
const isPhoneLocked = computed(() => {
  if (!auth.state.user) {
    return false;
  }

  return Boolean(String(auth.state.user.phone || "").replace(/\D/g, "").slice(0, 9));
});

function fillLockedFields() {
  const user = auth.state.user;
  if (!user) return;

  const firstName = (user.first_name || "").trim();
  const lastName = (user.last_name || "").trim();
  const fullName = `${firstName} ${lastName}`.trim() || user.name || "";

  f.name = fullName;
  f.phone = (user.phone || "").replace(/\D/g, "").slice(0, 9);
  f.email = user.email || "";
}

function resetForm() {
  if (!isLoggedIn.value) {
    f.name = "";
    f.phone = "";
    f.email = "";
  } else {
    fillLockedFields();
  }

  f.msg = "";
  f.file = null;

  if (fileInput.value) {
    fileInput.value.value = "";
  }
}

function onFile(e) {
  errorMsg.value = "";
  successMsg.value = "";
  const selectedFile = e.target.files?.[0] || null;

  if (!selectedFile) {
    f.file = null;
    return;
  }

  const maxBytes = 10 * 1024 * 1024;
  const isAllowedType =
    selectedFile.type === "application/pdf" ||
    selectedFile.type.startsWith("image/");

  if (!isAllowedType) {
    errorMsg.value = "El archivo debe ser PDF o imagen.";
    e.target.value = "";
    f.file = null;
    return;
  }

  if (selectedFile.size > maxBytes) {
    errorMsg.value = "El archivo supera el límite de 10 MB.";
    e.target.value = "";
    f.file = null;
    return;
  }

  f.file = selectedFile;
}

function onPhoneInput(e) {
  const rawValue = e.target.value || "";
  const onlyDigits = rawValue.replace(/\D/g, "").slice(0, 9);
  f.phone = onlyDigits;
}

async function submit() {
  if (sending.value) return;

  sent.value = false;
  errorMsg.value = "";
  successMsg.value = "";
  sending.value = true;

  const phone = f.phone.trim();
  if (!/^\d{9}$/.test(phone)) {
    errorMsg.value = "El teléfono debe tener exactamente 9 dígitos numéricos.";
    sending.value = false;
    return;
  }

  const formData = new FormData();
  formData.append("nombre", f.name.trim());
  formData.append("telefono", phone);
  formData.append("email", f.email.trim());
  formData.append("mensaje", f.msg.trim());

  if (f.file) {
    formData.append("pdf", f.file);
  }

  try {
    const response = await fetch(API_ENDPOINT, {
      method: "POST",
      body: formData,
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok || !payload.ok) {
      throw new Error(payload.message || "No se pudo guardar la solicitud.");
    }

    successMsg.value = payload.message || "Solicitud recibida. Te contactaremos lo antes posible.";
    sent.value = true;
    resetForm();
    setTimeout(() => {
      sent.value = false;
    }, 4200);
  } catch (err) {
    errorMsg.value = err?.message || "No se pudo enviar el formulario.";
  } finally {
    sending.value = false;
  }
}

onMounted(() => {
  fillLockedFields();

  setSeo({
    title: "Contacto | Análisis gratuito de facturas | Reglado Energy",
    description:
      "Solicita un análisis gratuito de facturas. Completa el formulario o sube tu PDF o imagen y te llamamos con resultados y opciones de mejora.",
    canonical: "/#/contacto",
  });
});

watch(
  () => auth.state.user,
  () => {
    fillLockedFields();
  }
);
</script>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 10px 14px;
  border: 1px solid rgba(242, 197, 61, 0.26);
  background: rgba(242, 197, 61, 0.08);
  width: fit-content;
  margin-bottom: 10px;
}

.form {
  display: grid;
  gap: 12px;
  margin-top: 12px;
}

.field {
  display: grid;
  gap: 7px;
}

label {
  font-weight: 800;
  font-size: 14px;
  color: rgba(233, 238, 246, 0.92);
}

input,
textarea {
  padding: 12px 12px;
  border-radius: var(--radius-sm);
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: rgba(8, 10, 13, 0.65);
  color: var(--text);
  outline: none;
}

textarea {
  min-height: 110px;
  resize: vertical;
}

input:focus,
textarea:focus {
  border-color: rgba(242, 197, 61, 0.35);
  box-shadow: 0 0 0 4px rgba(242, 197, 61, 0.1);
}

input:disabled,
textarea:disabled,
button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.sent {
  margin-top: 14px;
  padding: 14px;
  border-radius: var(--radius-md);
  border: 1px solid rgba(242, 197, 61, 0.32);
  background: rgba(242, 197, 61, 0.08);
}

.error {
  margin-top: 14px;
  padding: 14px;
  border-radius: var(--radius-md);
  border: 1px solid rgba(230, 90, 90, 0.45);
  background: rgba(230, 90, 90, 0.12);
  color: #ffd3d3;
}

.file::file-selector-button {
  display: none;
}

.file:hover {
  cursor: pointer;
}
</style>
