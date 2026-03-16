<template>
  <section class="favorites-view">

    <div class="favorites-view__header" v-reveal="0">
      <div>
        <p class="eyebrow">Tu shortlist</p>
        <h2>Mis propiedades favoritas</h2>
      </div>

      <span class="favorites-count">
        {{ favorites.length }} guardadas
      </span>
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
        v-for="(property,index) in favorites"
        :key="property.id"
        v-reveal="index+1"
        class="favorite-card-wrapper"
      >

        <PropertyCard
          :property="property"
          @toggle-favorite="toggleFavorite"
          @show-match="openDetails(property,$event)"
        />

      </div>

    </div>
 <MatchDetailsPopper
  :visible="popperVisible"
  :details="selectedDetails"
  :x="popperX"
  :y="popperY"
  @close="closePopper"
/>
  </section>
</template>

<script>

import PropertyCard from "../components/PropertyCard.vue"
import MatchDetailsPopper from "../components/MatchDetailsPopper.vue"
import {
  fetchFavoriteProperties,
  removeFavorite
} from "../services/properties"

export default {

  name:"FavoriteProperties",

  components:{
    PropertyCard,
    MatchDetailsPopper
  },

  data(){
    return{

      favorites:[],
      loading:true,

      popperVisible:false,
      selectedDetails:[],
      selectedProperty:null,

      popperX:0,
      popperY:0

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

    },


    openDetails(property,event){

  const rect = event.currentTarget.getBoundingClientRect()

  const popperWidth = 280
  const popperHeight = 420
  const margin = 30

  let x = rect.left + rect.width / 2
  let y = rect.top + window.scrollY - popperHeight

  const viewportWidth = window.innerWidth

  if (x + popperWidth/2 > viewportWidth - margin) {
    x = viewportWidth - popperWidth/2 - margin
  }

  if (x - popperWidth/2 < margin) {
    x = popperWidth/2 + margin
  }

  this.popperX = x
  this.popperY = y

  this.selectedProperty = property
  this.selectedDetails = property.match_details || []

  this.popperVisible = true
},


    closePopper(){

      this.popperVisible = false
      this.selectedDetails = []
      this.selectedProperty = null

    }

  }

}
</script>


<style scoped>

.favorites-view{
  display:grid;
  gap:24px;
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
}

</style>




