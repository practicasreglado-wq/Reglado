<template>
  <div class="contacto">
    <div class="overlay"></div>

    <div class="contacto-texto">
      <template v-if="!user || Object.keys(user).length === 0">
        <h1>
          <span class="highlight">Crea tu cuenta</span>
        </h1>

        <p>
          Obten acceso a listados premium y recomendaciones personalizadas.
        </p>
      </template>

      <template v-else>
        <h1>
          <span class="highlight">Bienvenido {{ displayName }}</span>
        </h1>

        <p>
          Estamos encantados de tenerte de nuevo. Nuestro equipo respondera tu
          solicitud lo antes posible.
        </p>
      </template>
    </div>

    <div class="contacto-card">
      <h2>Contactanos</h2>
      <p class="subject-pill">Asunto: Solicitud de Usuario promocionar a Real</p>

      <form class="formulario" @submit.prevent="submitRequest">
        <div class="row">
          <div class="field">
            <label for="contact-name">Nombre</label>
            <input
              id="contact-name"
              type="text"
              :value="displayName"
              placeholder="Nombre de usuario"
              readonly
            />
          </div>

          <div class="field">
            <label for="contact-email">Correo</label>
            <input
              id="contact-email"
              type="email"
              :value="userEmail"
              placeholder="Correo del usuario"
              readonly
            />
          </div>
        </div>

        <div class="field">
          <label for="contact-message">Motivo:</label>
          <textarea
            id="contact-message"
            v-model.trim="message"
            rows="5"
            placeholder="Cuentanos por que quieres solicitar acceso como usuario real"
            :disabled="isSubmitting || !isLoggedIn"
          ></textarea>
        </div>

        <p v-if="feedback.message" :class="['feedback', feedback.type]">
          {{ feedback.message }}
        </p>

        <button type="submit" class="enviar-btn" :disabled="isSubmitDisabled">
          {{ isSubmitting ? "Enviando..." : "Enviar" }}
        </button>
      </form>

      <div class="info">
        <div class="item">
          <span class="dot"></span><strong>Telefono:</strong> +34 911 462 674
        </div>
        <div class="item">
          <span class="dot"></span><strong>Email:</strong> info@regladoconsultores.com
        </div>
      </div>
    </div>

    <div class="login-link" v-if="!user || Object.keys(user).length === 0">
      Ya tienes una cuenta?
      <span @click="$router.push('/login')">Login</span>
    </div>
  </div>
</template>

<script>
import { computed, reactive, ref } from "vue";
import { storeToRefs } from "pinia";
import { useUserStore } from "../stores/user";
import { backendJson } from "../services/backend";

export default {
  name: "Contacto",

  setup() {
    const userStore = useUserStore();
    const { user, isLoggedIn } = storeToRefs(userStore);
    const message = ref("");
    const isSubmitting = ref(false);
    const feedback = reactive({
      type: "",
      message: "",
    });

    const displayName = computed(() => {
      if (!user.value) return "";

      return (
        user.value.nombre_usuario ||
        [user.value.nombre, user.value.apellidos].filter(Boolean).join(" ")
      );
    });

    const userEmail = computed(() => user.value?.email || "");
    const firstName = computed(() => user.value?.nombre || "");
    const lastName = computed(() => user.value?.apellidos || "");
    const username = computed(() => user.value?.nombre_usuario || "");

    const isSubmitDisabled = computed(
      () => isSubmitting.value || !isLoggedIn.value
    );

    const setFeedback = (type, text) => {
      feedback.type = type;
      feedback.message = text;
    };

    const submitRequest = async () => {
      if (isSubmitting.value) return;

      if (!isLoggedIn.value) {
        setFeedback("error", "Debes iniciar sesion para enviar la solicitud.");
        return;
      }

      if (!userEmail.value) {
        setFeedback("error", "No se ha encontrado el email del usuario.");
        return;
      }

      if (!message.value.trim()) {
        setFeedback("error", "El mensaje no puede estar vacio.");
        return;
      }

      isSubmitting.value = true;
      setFeedback("", "");

      try {
        const response = await backendJson("api/send_real_user_request.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            name: firstName.value,
            lastName: lastName.value,
            username: username.value,
            email: userEmail.value,
            message: message.value.trim(),
          }),
        });

        setFeedback(
          "success",
          response.message || "Solicitud enviada correctamente"
        );
        message.value = "";
      } catch (error) {
        setFeedback(
          "error",
          error.message || "No se pudo enviar la solicitud."
        );
      } finally {
        isSubmitting.value = false;
      }
    };

    return {
      user,
      isLoggedIn,
      displayName,
      userEmail,
      firstName,
      lastName,
      username,
      message,
      isSubmitting,
      isSubmitDisabled,
      feedback,
      submitRequest,
    };
  },
};
</script>

<style scoped>
.contacto {
  position: relative;
  min-height: 100vh;
  background-color: var(--gris-claro);
  background-image: url("@/assets/fondito.png");
  background-size: cover;
  background-position: center;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 72px;
  padding: 128px 72px 40px;
}

.overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.516);
  z-index: 0;
}

.contacto-texto,
.contacto-card {
  position: relative;
  z-index: 1;
}

.contacto-texto {
  color: white;
  max-width: 560px;
  transform: translateY(-70px);
}

.contacto-texto h1 {
  font-size: 4rem;
  margin-bottom: 20px;
}

.highlight {
  color: #eabe2f;
}

.contacto-texto p {
  font-size: 1.8rem;
  margin-bottom: 24px;
}

.login-link {
  position: relative;
  color: white;
  transform: none;
  font-size: 1.1rem;
  z-index: 2;
  text-align: center;
  width: 100%;
  margin-top: 8px;
}

.login-link span {
  color: #eabe2f;
  cursor: pointer;
  font-weight: bold;
  margin-left: 5px;
}

.login-link span:hover {
  text-decoration: underline;
}

.contacto-card {
  width: 480px;
  height: auto;
  max-height: none;
  flex: 0 0 auto;
  align-self: center;
  background: rgba(255, 255, 255, 0.95);
  padding: 26px;
  border-radius: 20px;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
}

h2 {
  margin-bottom: 14px;
  font-size: 2rem;
  color: #111;
}

.subject-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
  padding: 10px 16px;
  border-radius: 999px;
  background: rgba(11, 61, 145, 0.08);
  color: var(--azul-principal);
  font-weight: 600;
  font-size: 1rem;
  line-height: 1.4;
  text-align: center;
  max-width: 100%;
}

.formulario {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.row {
  display: flex;
  gap: 12px;
}

.field {
  width: 100%;
}

.field label {
  display: block;
  margin-bottom: 6px;
  color: #213547;
  font-weight: 600;
  font-size: 0.95rem;
}

input,
textarea {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid var(--azul-principal);
  border-radius: 6px;
  font-size: 0.95rem;
  outline: none;
  box-sizing: border-box;
}

input[readonly] {
  display: flex;
  align-items: center;
  background: rgba(11, 61, 145, 0.08);
  color: var(--azul-principal);
  font-weight: 600;
  border-radius: 999px;
  border-color: rgba(11, 61, 145, 0.18);
  cursor: not-allowed;
  padding: 10px 14px;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

textarea {
  resize: none;
  min-height: 120px;
}

textarea:disabled,
input:disabled {
  opacity: 0.7;
}

.feedback {
  margin: 0;
  padding: 10px 12px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.9rem;
}

.feedback.success {
  background: #e9f9ef;
  color: #1f7a3d;
}

.feedback.error {
  background: #fdecec;
  color: #b42318;
}

.enviar-btn {
  font-size: 0.92em;
  background-color: var(--azul-principal);
  color: white;
  padding: 9px 18px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: 0.3s ease;
  align-self: flex-start;
}

.enviar-btn:hover {
  background-color: var(--azul-secundario);
}

.enviar-btn:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.info {
  margin-top: 16px;
}

.item {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 0.92rem;
}

.dot {
  width: 12px;
  height: 12px;
  background-color: var(--azul-principal);
  border-radius: 50%;
}

@media (max-width: 1440px) {
  h2 {
    font-size: 1.7rem;
  }

  .contacto {
    padding: 110px 48px 32px;
    gap: 34px;
  }

  .contacto-texto {
    max-width: 430px;
    transform: translateY(-36px);
  }

  .contacto-texto h1 {
    font-size: 2.8rem;
    margin-bottom: 14px;
  }

  .contacto-texto p {
    font-size: 1.25rem;
    margin-bottom: 16px;
  }

  .contacto-card {
    width: 380px;
    padding: 18px;
  }

  .subject-pill {
    font-size: 0.88rem;
    padding: 8px 12px;
  }

  input[readonly] {
    padding: 8px 11px;
    font-size: 0.84rem;
  }

  textarea {
    min-height: 92px;
  }

  .formulario {
    gap: 9px;
  }

  .row {
    gap: 10px;
  }

  .field label {
    font-size: 0.85rem;
  }

  input,
  textarea {
    padding: 8px 10px;
    font-size: 0.85rem;
  }

  .feedback {
    padding: 8px 10px;
    font-size: 0.8rem;
  }

  .enviar-btn {
    padding: 7px 12px;
    font-size: 0.82rem;
  }

  .info {
    margin-top: 10px;
  }

  .item {
    font-size: 0.8rem;
    margin-bottom: 5px;
  }
}

@media (max-width: 768px) {
  h2 {
    font-size: 1.45rem;
  }

  .contacto {
    flex-direction: column;
    justify-content: center;
    text-align: center;
    padding: 96px 20px 28px;
    gap: 26px;
  }

  .contacto-texto {
    max-width: 100%;
    transform: none;
  }

  .login-link {
    position: relative;
    left: auto;
    bottom: auto;
    transform: none;
    margin-top: 8px;
  }

  .contacto-card {
    width: 100%;
    max-width: 340px;
    height: auto;
    padding: 16px 14px;
  }

  .subject-pill {
    justify-content: center;
    width: 100%;
    font-size: 0.82rem;
    padding: 8px 12px;
    white-space: normal;
  }

  .row {
    flex-direction: column;
    gap: 12px;
  }

  .field label {
    text-align: left;
  }

  input[readonly] {
    font-size: 0.82rem;
    padding: 8px 10px;
  }

  textarea {
    min-height: 84px;
  }

  .enviar-btn {
    align-self: stretch;
  }
}

@media (max-width: 480px) {
  h2 {
    font-size: 1.05rem;
  }

  .contacto-texto {
    width: 100%;
    transform: none;
  }

  .contacto {
    padding: 88px 14px 24px;
    gap: 14px;
  }

  .contacto-texto h1 {
    font-size: 1.65rem;
  }

  .contacto-texto p {
    font-size: 0.86rem;
    margin-bottom: 12px;
  }

  .row {
    flex-direction: column;
    gap: 10px;
  }

  .contacto-card {
    height: auto;
    max-width: 280px;
    padding: 10px 8px;
    border-radius: 14px;
  }

  .subject-pill {
    font-size: 0.64rem;
    padding: 5px 8px;
    border-radius: 16px;
  }

  .field label {
    font-size: 0.74rem;
  }

  input,
  textarea {
    font-size: 0.74rem;
    padding: 6px 8px;
  }

  input[readonly] {
    padding: 6px 8px;
    border-radius: 14px;
    font-size: 0.72rem;
  }

  textarea {
    min-height: 66px;
  }

  .enviar-btn {
    width: 100%;
    align-self: stretch;
    font-size: 0.74rem;
    padding: 7px 10px;
  }

  .feedback {
    font-size: 0.72rem;
    padding: 7px 8px;
  }

  .info {
    margin-top: 6px;
  }

  .item {
    font-size: 0.58rem;
    gap: 4px;
    margin-bottom: 3px;
    line-height: 1.15;
  }

  .dot {
    width: 8px;
    height: 8px;
    margin-top: 2px;
  }

  .login-link {
    font-size: 0.82rem;
  }
}
</style>