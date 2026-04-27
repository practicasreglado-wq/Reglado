<!--
  Versión "admin only" de RestrictedAccessView. Se muestra cuando un usuario
  no-admin intenta entrar a una ruta exclusiva del panel administrativo.
-->
<template>
  <RestrictedAdminAccess />
</template>

<script>
import RestrictedAdminAccess from '../components/RestrictedAdminAccess.vue';

export default {
  name: 'RestrictedAdminView',
  components: {
    RestrictedAdminAccess
  }
};
</script>
