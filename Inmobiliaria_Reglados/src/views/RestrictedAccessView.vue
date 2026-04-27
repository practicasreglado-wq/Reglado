<!--
  Página que muestra el router cuando el usuario intenta acceder a una ruta
  protegida sin tener el rol/permisos necesarios. Renderiza el componente
  RestrictedAccess (mensaje + CTA "Solicitar promoción a Premium").
-->
<template>
  <div class="restricted-page">
    <RestrictedAccess />
  </div>
</template>

<script>
import RestrictedAccess from "../components/RestrictedAccess.vue";

export default {
  name: "RestrictedAccessView",
  components: { RestrictedAccess }
};
</script>

<style scoped>
.restricted-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: linear-gradient(180deg, #b6c6d6, #eef2f7);
  padding-top: 100px;
}
</style>
