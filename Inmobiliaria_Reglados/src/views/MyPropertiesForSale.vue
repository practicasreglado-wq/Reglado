<template>
  <div>

    <button class="create-btn" @click="goCreate">
      Crear propiedad
    </button>

    <h2>Mis propiedades en venta</h2>

    <div v-if="loading">
      Cargando propiedades...
    </div>

    <div v-else-if="properties.length === 0">
      <p>No tienes propiedades en venta.</p>
    </div>

    <div v-else class="properties">

      <div
        v-for="property in properties"
        :key="property.id"
        class="property-card"
      >
        <h3>{{ property.nombre }}</h3>
        <p>{{ property.ubicacion }}</p>
        <p><strong>Tipo:</strong> {{ property.tipo }}</p>
        <p><strong>Precio:</strong> {{ property.precio }} €</p>
      </div>

    </div>

  </div>
</template>

<script>
import axios from "axios";

export default {

  name: "MyPropertiesForSale",

  data() {
    return {
      properties: [],
      loading: true
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

      try {

        const res = await axios.get(
          "http://localhost/inmobiliaria/backend/api/get_user_properties_for_sale.php",
          { withCredentials: true }
        );

        // Si el backend devuelve directamente el array
        this.properties = res.data;

      } catch (error) {

        console.error("Error cargando propiedades:", error);

      } finally {

        this.loading = false;

      }

    }

  }

};
</script>

<style scoped>

.create-btn{
  margin-bottom:20px;
  padding:10px 15px;
  background:#f0c14b;
  border:none;
  border-radius:6px;
  cursor:pointer;
}

.properties{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(250px,1fr));
  gap:16px;
}

.property-card{
  background:white;
  padding:15px;
  border-radius:8px;
  box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

</style>