<template>
  <RegisterForm
    title="Registro de usuario"
    description="Completa tus datos. Te enviaremos un correo para activar tu cuenta."
    button-text="Crear cuenta"
  />
</template>

<script setup>
import { onMounted } from "vue";
import { useRoute } from "vue-router";
import RegisterForm from "../components/auth/RegisterForm.vue";

const route = useRoute();

onMounted(() => {
  const returnTo = typeof route.query.returnTo === "string" ? route.query.returnTo.trim() : "";
  if (returnTo) {
    sessionStorage.setItem("auth_return_to", returnTo);
  }
});
</script>
