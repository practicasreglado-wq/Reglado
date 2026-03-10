<template>
  <section class="form-page">
    <div class="form-card">
      <h1>{{ title }}</h1>
      <p>{{ description }}</p>

      <form class="clean-form" @submit.prevent="submitRegister">
        <label>
          Nombre de usuario
          <input v-model.trim="username" type="text" placeholder="usuario123" required minlength="3" />
        </label>

        <label>
          Nombre real
          <input v-model.trim="firstName" type="text" placeholder="Nombre" required />
        </label>

        <label>
          Apellido real
          <input v-model.trim="lastName" type="text" placeholder="Apellido" required />
        </label>

        <label>
          Correo real
          <input v-model.trim="email" type="email" placeholder="nombre@correo.com" required />
        </label>

        <label>
          Telefono
          <input v-model.trim="phone" type="tel" placeholder="+34 600 000 000" required />
        </label>

        <label>
          Contrasena
          <input v-model="password" type="password" placeholder="********" required minlength="6" />
        </label>

        <label>
          Repetir contrasena
          <input v-model="passwordConfirmation" type="password" placeholder="********" required minlength="6" />
        </label>

        <p v-if="error" class="feedback error">{{ error }}</p>
        <p v-if="success" class="feedback success">{{ success }}</p>

        <button class="btn-primary" type="submit" :disabled="loading">
          {{ loading ? "Creando..." : buttonText }}
        </button>
      </form>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue";
import { auth } from "../../services/auth";

defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  buttonText: {
    type: String,
    default: "Guardar",
  },
});

const username = ref("");
const firstName = ref("");
const lastName = ref("");
const email = ref("");
const phone = ref("");
const password = ref("");
const passwordConfirmation = ref("");
const loading = ref(false);
const error = ref("");
const success = ref("");

async function submitRegister() {
  loading.value = true;
  error.value = "";
  success.value = "";

  if (password.value !== passwordConfirmation.value) {
    error.value = "Las contrasenas no coinciden";
    loading.value = false;
    return;
  }

  try {
    await auth.register({
      username: username.value,
      first_name: firstName.value,
      last_name: lastName.value,
      email: email.value,
      phone: phone.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });

    success.value = "Registro completado. Revisa tu correo y confirma tu cuenta para iniciar sesion.";
    password.value = "";
    passwordConfirmation.value = "";
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No fue posible registrar el usuario";
  } finally {
    loading.value = false;
  }
}
</script>
