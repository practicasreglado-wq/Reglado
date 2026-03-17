<template>
  <section class="favorites-view">

    <div class="favorites-view__header" v-reveal="0">
      <div>
        <p class="eyebrow">Tu shortlist</p>
        <h2>Mis propiedades favoritas</h2>
      </div>

      <span class="favorites-count">
        {{ filteredFavorites.length }} {{ filteredFavorites.length === 1 ? 'guardada' : 'guardadas' }}
      </span>
    </div>

    <!-- Controles de búsqueda y filtros -->
    <div class="favorites-controls" v-reveal="0.5">
      <div class="search-field">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8" />
          <path d="M21 21l-4.35-4.35" />
        </svg>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Buscar por título o ciudad..."
        >
      </div>

      <div class="filters-row">
        <select v-model="filterCategory" class="filter-select">
          <option value="">Todas las categorías</option>
          <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
        </select>

        <select v-model="sortBy" class="filter-select">
          <option value="newest">Más recientes</option>
          <option value="oldest">Más antiguos</option>
          <option value="price_asc">Precio: Menor al Mayor</option>
          <option value="price_desc">Precio: Mayor al Menor</option>
          <option value="match_desc">Mejor Match %</option>
          <option value="match_asc">Menor Match %</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="favorites-view__state" v-reveal="1">
      Cargando favoritos...
    </div>

    <div
      v-else-if="favorites.length === 0"
      class="favorites-view__state"
      v-reveal="1"
    >
      No tienes propiedades favoritas todavía.
    </div>

    <div v-else class="favorites-view__grid">

      <div
        v-for="(property,index) in filteredFavorites"
        :key="property.id"
        v-reveal="index+1"
        class="favorite-card-wrapper"
      >

        <PropertyCard
          :property="property"
          @toggle-favorite="toggleFavorite"
        />

      </div>

    </div>
  </section>
</template>

<script>

import PropertyCard from "../components/PropertyCard.vue"
import {
  fetchFavoriteProperties,
  removeFavorite
} from "../services/properties"

export default {

  name:"FavoriteProperties",

  components:{
    PropertyCard
  },

  data(){
    return{
      favorites:[],
      loading:true,
      searchQuery: '',
      filterCategory: '',
      sortBy: 'newest',
      categories: ['Hoteles', 'Fincas', 'Parking', 'Edificios', 'Activos']
    }
  },

  computed: {
    filteredFavorites() {
      let result = [...this.favorites];

      // Búsqueda textual
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        result = result.filter(p => 
          p.titulo.toLowerCase().includes(query) || 
          p.ubicacion_general.toLowerCase().includes(query)
        );
      }

      // Filtro por categoría
      if (this.filterCategory) {
        result = result.filter(p => p.categoria === this.filterCategory);
      }

      // Ordenación
      result.sort((a, b) => {
        if (this.sortBy === 'price_asc') return a.precio - b.precio;
        if (this.sortBy === 'price_desc') return b.precio - a.precio;
        
        if (this.sortBy === 'match_desc') {
          return (b.match_percentage || 0) - (a.match_percentage || 0);
        }
        if (this.sortBy === 'match_asc') {
          return (a.match_percentage || 0) - (b.match_percentage || 0);
        }

        // Ordenación cronológica (usamos favorited_at si está disponible, sino created_at)
        const dateA = new Date(a.favorited_at || a.created_at || 0).getTime();
        const dateB = new Date(b.favorited_at || b.created_at || 0).getTime();

        if (this.sortBy === 'oldest') {
          return dateA - dateB;
        }
        // newest (default)
        return dateB - dateA;
      });

      return result;
    }
  },

  mounted(){
    this.loadFavorites()
  },

  methods:{
    async loadFavorites(){
      this.loading=true
      try{
        this.favorites = await fetchFavoriteProperties()
      }catch(error){
        console.error(error)
        this.favorites=[]
      }finally{
        this.loading=false
      }
    },

    async toggleFavorite(property){
      try{
        await removeFavorite(property.id)
        this.favorites = this.favorites.filter(
          item => item.id !== property.id
        )
      }catch(error){
        console.error(error)
      }
    }
  }

}
</script>


<style scoped>

.favorites-view{
  display:grid;
  gap:24px;
  min-width:0;
}

.favorites-view__header{
  display:flex;
  justify-content:space-between;
  align-items:end;
  gap:20px;
}

.eyebrow{
  margin:0 0 8px;
  color:#6f7f98;
  font-size:0.78rem;
  font-weight:700;
  letter-spacing:0.12em;
  text-transform:uppercase;
}

.favorites-view__header h2{
  margin:0;
  color:#172a5d;
}

.favorites-count{
  padding:10px 14px;
  border-radius:999px;
  background:rgba(74,114,198,0.12);
  color:#214b8f;
  font-weight:700;
}

/* Controles */
.favorites-controls {
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: white;
  padding: 20px;
  border-radius: 16px;
  box-shadow: 0 4px 15px rgba(23,42,93,0.05);
  min-width: 0;
}

.search-field {
  position: relative;
  display: flex;
  align-items: center;
  min-width: 0;
}

.search-field svg {
  position: absolute;
  left: 15px;
  color: #94a3b8;
}

.search-field input {
  width: 100%;
  min-width: 0;
  padding: 12px 15px 12px 45px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  font-size: 1rem;
  transition: 0.3s;
}

.search-field input:focus {
  outline: none;
  border-color: #bd9b2c;
  box-shadow: 0 0 0 3px rgba(189, 155, 44, 0.1);
}

.filters-row {
  display: flex;
  gap: 12px;
  min-width: 0;
  align-items: stretch;
}

.filter-select {
  flex: 1;
  min-width: 0;
  width: 100%;
  padding: 10px 15px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 0.9rem;
  font-weight: 600;
  color: #172a5d;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
}

.filter-select:focus {
  outline: none;
  border-color: #bd9b2c;
}

.favorites-view__state{
  padding:24px;
  border-radius:18px;
  background:#fff;
  color:#5a6880;
  box-shadow:0 12px 26px rgba(23,42,93,0.08);
}

.favorites-view__grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(290px,1fr));
  gap:22px;
  min-width:0;
}

.favorite-card-wrapper{
  min-width:0;
}

/* =========================================
   RESPONSIVE (1440px / 768px / 480px)
   ========================================= */

@media (max-width: 1440px) {
  .favorites-view__header {
    gap: 16px;
  }
  .favorites-view__header h2 {
    font-size: 1.8rem;
  }
}

@media (max-width: 768px) {
  .favorites-view {
    gap: 20px;
  }
  .favorites-view__header {
    flex-direction: column;
    align-items: flex-start;
  }
  .favorites-view__header h2 {
    font-size: 1.6rem;
  }
  .favorites-count {
    padding: 8px 14px;
    font-size: 0.95rem;
  }
  .favorites-view__grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  .favorites-controls {
    padding: 18px;
    gap: 14px;
  }
  .filters-row {
    flex-direction: column;
    gap: 10px;
  }
  .filter-select,
  .search-field input {
    width: 100%;
  }
  .filter-select {
    min-height: 44px;
    font-size: 0.85rem;
    padding: 10px 36px 10px 12px;
    background-size: 18px;
  }
  .search-field input {
    min-height: 44px;
    padding-right: 14px;
  }
}

@media (max-width: 480px) {
  .favorites-view {
    gap: 16px;
  }
  .favorites-view__header h2 {
    font-size: 1.5rem;
  }
  .eyebrow {
    font-size: 0.72rem;
  }
  .favorites-count {
    padding: 6px 12px;
    font-size: 0.85rem;
  }
  .favorites-view__state {
    padding: 18px;
    font-size: 0.9rem;
  }
  .favorites-view__grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }
  
  /* Ajustes de filtros en móvil */
  .filters-row {
    flex-direction: column;
  }
  .favorites-controls {
    padding: 15px;
    border-radius: 14px;
    gap: 12px;
  }
  .search-field input,
  .filter-select {
    font-size: 0.8rem;
  }
  .search-field input {
    padding-left: 42px;
    min-height: 42px;
  }
  .search-field svg {
    left: 13px;
    width: 18px;
    height: 18px;
  }
  .filter-select {
    min-height: 42px;
    padding: 9px 34px 9px 10px;
    background-position: right 10px center;
    background-size: 14px;
  }
}

</style>




