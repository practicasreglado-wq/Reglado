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
