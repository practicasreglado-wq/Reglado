<template>
  <section class="favorites-view">

    <div class="favorites-view__header" v-reveal="0">
      <div class="header-content">
        <div class="header-main">
          <p class="eyebrow">Tu shortlist</p>
          <h2>Mis propiedades favoritas</h2>
        </div>
        <span class="favorites-count">
          {{ filteredFavorites.length }} {{ filteredFavorites.length === 1 ? 'guardada' : 'guardadas' }}
        </span>
      </div>
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
        </select>
      </div>
    </div>

    <transition name="content-fade" mode="out-in">
    <div v-if="loading" key="loading" class="favorites-view__state" v-reveal="1">
      Cargando favoritos...
    </div>

    <div
      v-else-if="favorites.length === 0"
      key="empty"
      class="favorites-view__state"
      v-reveal="1"
    >
      No tienes propiedades favoritas todavía.
    </div>

    <transition-group v-else key="results" name="stagger-list" tag="div" class="favorites-view__grid">

      <div
        v-for="(property,index) in filteredFavorites"
        :key="property.id"
        v-reveal="index+1"
        class="favorite-card-wrapper"
        :style="{ transitionDelay: `${Math.min(index * 70, 420)}ms` }"
      >

        <PropertyCard
          :property="property"
          @toggle-favorite="toggleFavorite"
        />

      </div>

    </transition-group>
    </transition>
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

.favorites-view {
  display: grid;
  gap: 24px;
  min-width: 0;
}

.favorites-view__header {
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 140px;
  padding: 24px 34px;
  border-radius: 28px;
  background:
    radial-gradient(circle at top right, rgba(255, 215, 126, 0.28), transparent 34%),
    linear-gradient(135deg, #12244d 0%, #20386b 55%, #3a5ca9 100%);
  box-shadow: 0 22px 48px rgba(18, 36, 77, 0.24);
  color: #fff;
}

.favorites-view__header::before,
.favorites-view__header::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  opacity: 0.72;
  transition: opacity 0.28s ease;
}

.favorites-view__header::before {
  width: 200px;
  height: 200px;
  right: -60px;
  top: -80px;
  background: rgba(255, 255, 255, 0.08);
}

.favorites-view__header::after {
  width: 140px;
  height: 140px;
  left: -50px;
  bottom: -80px;
  background: rgba(255, 204, 84, 0.14);
}

.header-content {
  position: relative;
  z-index: 2;
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.header-main {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  width: max-content;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.14);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin: 0;
  color: #ffffff;
}

.favorites-view__header h2 {
  margin: 0;
  font-size: clamp(1.6rem, 1.2rem + 1vw, 2.2rem);
  line-height: 1.1;
  color: #ffffff;
}

.favorites-count {
  display: inline-flex;
  align-items: center;
  padding: 8px 16px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(10px);
  color: #ffffff;
  font-weight: 700;
  font-size: 0.9rem;
  white-space: nowrap;
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

.favorites-view__state {
  padding: 24px;
  border-radius: 18px;
  background: #fff;
  color: #5a6880;
  box-shadow: 0 12px 26px rgba(23, 42, 93, 0.08);
}

.favorites-view__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
  gap: 22px;
  min-width: 0;
  position: relative;
}

.favorite-card-wrapper {
  min-width: 0;
}

/* =========================================
   RESPONSIVE (1440px / 768px / 480px)
   ========================================= */

@media (max-width: 1440px) {
  .favorites-view__header {
    padding: 22px 30px;
    min-height: 120px;
  }
}

@media (max-width: 980px) {
  .favorites-view__header {
    padding: 20px 24px;
    min-height: 100px;
  }
}

@media (max-width: 768px) {
  .favorites-view {
    gap: 20px;
  }
  .favorites-view__header {
    padding: 24px;
    border-radius: 22px;
  }
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  .favorites-count {
    padding: 8px 14px;
    font-size: 0.9rem;
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
}

@media (max-width: 480px) {
  .favorites-view {
    gap: 16px;
  }
  .favorites-view__header {
    padding: 20px;
    min-height: auto;
  }
  .favorites-view__header h2 {
    font-size: 1.5rem;
  }
  .eyebrow {
    font-size: 0.7rem;
    padding: 5px 9px;
  }
}
</style>
