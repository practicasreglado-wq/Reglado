<template>
  <div class="property-card">
    <img :src="property.image" alt="Property image" />
    <h3>{{ property.title }}</h3>
    <p class="price">{{ property.price }} €</p>
    <button @click="addToFavorites">Añadir a Favoritos</button>
    <button @click="sendMessage">Enviar mensaje</button>
    <button @click="viewDetails">Ver detalles</button>
  </div>
</template>

<script>
export default {
  name: "PropertyCard",
  props: {
    property: Object
  },
  methods: {
    addToFavorites() {
      this.$store.commit("addFavorite", this.property);
    },
    sendMessage() {
      // Lógica para enviar un mensaje al propietario
    },
    viewDetails() {
      this.$router.push({ name: 'propertyDetails', params: { id: this.property.id } });
    }
  }
};
</script>

<style scoped>
.property-card {
  width: 300px;
  margin: 20px;
}
</style>