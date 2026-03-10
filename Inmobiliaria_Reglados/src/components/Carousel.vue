<template>
  <div class="carousel">

    <!-- TITULOS -->
    <div class="categories">
      <span
        v-for="(item, index) in visibleItems"
        :key="item.value"
        :class="{ active: index === 1 }"
      >
        {{ item.title }}
      </span>
    </div>

    <div class="carousel-wrapper">

      <button class="arrow" @click="prev">❮</button>

      <!-- CARDS -->
      <div
        class="cards"
        @mousedown="startDrag"
        @mousemove="onDrag"
        @mouseup="endDrag"
        @mouseleave="endDrag"

        @touchstart="startTouch"
        @touchmove="onTouch"
        @touchend="endTouch"
      >
        <div
  v-for="(item, index) in visibleItems"
  :key="item.value"
  class="card-wrapper"
>
  <div class="mobile-title">
    {{ item.title }}
  </div>

  <div
    class="card"
    :class="cardClass(index)"
    :style="{ backgroundImage: `url(${item.image})` }"
    @click.stop="selectCategory(index)"
  ></div>
</div>
      </div>

      <button class="arrow" @click="next">❯</button>

    </div>
  </div>
</template>

<script>
import { useRouter } from "vue-router";
import { useUserStore } from "../stores/user";

export default {

  name: "Carousel",

  setup() {
    const router = useRouter();
    const userStore = useUserStore();
    return { router, userStore };
  },

  data() {
    return {
      isAnimating: false,

      startX: 0,
      endX: 0,
      startY: 0,
      endY: 0,
      isDragging: false,

      items: [
        { title: "Edificios", value: "Edificios", image: new URL('@/assets/edificios.png', import.meta.url).href },
        { title: "Hoteles", value: "Hoteles", image: new URL('@/assets/hotel.png', import.meta.url).href },
        { title: "Parking", value: "Parking", image: new URL('@/assets/parking.png', import.meta.url).href },
        { title: "Activos", value: "Activos", image: new URL('@/assets/activos.png', import.meta.url).href },
        { title: "Fincas", value: "Fincas", image: new URL('@/assets/finca.png', import.meta.url).href }
      ]
    };
  },

  computed: {

    visibleItems() {
      return this.items.slice(0, 3);
    }

  },

  mounted() {

    const savedCategory = this.userStore.selectedCategory;

    if (!savedCategory) return;

    const index = this.items.findIndex(
      item => item.value.toLowerCase() === savedCategory.toLowerCase()
    );

    if (index === -1) return;

    const rotations = (index - 1 + this.items.length) % this.items.length;

    for (let i = 0; i < rotations; i++) {
      const first = this.items.shift();
      this.items.push(first);
    }

  },

  methods: {

    /* -------------------------
       DRAG PC
    --------------------------*/

    startDrag(e) {

      this.isDragging = true;
      this.startX = e.clientX;
      this.endX = e.clientX;

    },

    onDrag(e) {

      if (!this.isDragging) return;

      this.endX = e.clientX;

    },

    endDrag() {

      if (!this.isDragging) return;

      const diff = this.endX - this.startX;

      if (Math.abs(diff) > 60) {

        if (diff > 0) {
          this.prev();
        } else {
          this.next();
        }

      }

      this.isDragging = false;

    },

    /* -------------------------
       TOUCH MOVIL
    --------------------------*/

    startTouch(e) {

      if (window.innerWidth <= 480) {
        this.startY = e.touches[0].clientY;
      } else {
        this.startX = e.touches[0].clientX;
      }

    },

    onTouch(e) {

      if (window.innerWidth <= 480) {
        this.endY = e.touches[0].clientY;
      } else {
        this.endX = e.touches[0].clientX;
      }

    },

    endTouch() {

      if (window.innerWidth <= 480) {

        const diff = this.endY - this.startY;

        if (Math.abs(diff) > 50) {

          if (diff > 0) {
            this.prev();
          } else {
            this.next();
          }

        }

      } else {

        const diff = this.endX - this.startX;

        if (Math.abs(diff) > 50) {

          if (diff > 0) {
            this.prev();
          } else {
            this.next();
          }

        }

      }

    },

    /* -------------------------
       CLASES
    --------------------------*/

    cardClass(index) {

      if (index === 1) return "center";
      if (index === 0) return "left";
      if (index === 2) return "right";

    },

    /* -------------------------
       MOVIMIENTO
    --------------------------*/

    next() {

      if (this.isAnimating) return;

      this.isAnimating = true;

      const first = this.items.shift();
      this.items.push(first);

      setTimeout(() => {
        this.isAnimating = false;
      }, 400);

    },

    prev() {

      if (this.isAnimating) return;

      this.isAnimating = true;

      const last = this.items.pop();
      this.items.unshift(last);

      setTimeout(() => {
        this.isAnimating = false;
      }, 400);

    },

    /* -------------------------
       CLICK CARD
    --------------------------*/

    selectCategory(index) {

      if (index !== 1) return;

      const selected = this.visibleItems[1].value;

      this.router.push({
        path: "/questions",
        query: { category: selected }
      });

    }

  }

};
</script>

<style scoped>

.card-wrapper{
  display:flex;
  flex-direction:column;
  align-items:center;
}

.mobile-title{
  display:none;
  font-size:1.4rem;
  font-weight:600;
  margin-bottom:10px;
  opacity:0.4;
  transition:0.1s;
}

/* TITULO ACTIVO EN MOVIL */
.card-wrapper:has(.center) .mobile-title{
  opacity:1;
  font-weight:700;
}

.carousel{
  text-align:center;
  padding:80px 0;
}

/* TITULOS */

.categories{
  display:flex;
  justify-content:center;
  gap:220px;
  margin-bottom:60px;
  font-size:2rem;
  font-weight:500;
}

.categories span{
  opacity:0.5;
  transition:0.1s;
}

.categories .active{
  opacity:1;
  font-weight:700;
}

/* WRAPPER */

.carousel-wrapper{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:50px;
}

/* BOTONES */

.arrow{
  background-color:var(--azul-principal);
  color:white;
  border:none;
  border-radius:50%;
  width:60px;
  height:60px;
  font-size:26px;
  cursor:pointer;
}

.arrow:hover{
  background-color:var(--azul-secundario);
}

/* CARDS */

.cards{
  display:flex;
  align-items:center;
  gap:40px;
  cursor:grab;
}

.cards:active{
  cursor:grabbing;
}

.card{
  width:280px;
  height:380px;
  background-size:cover;
  background-position:center;
  border-radius:20px;
  transition:0.4s ease;
  user-select:none;
}

/* POSICIONES */

.left{
  transform:scale(0.95);
  opacity:0.6;
}

.center{
  transform:scale(1.15);
  opacity:1;
  box-shadow:0 25px 45px rgba(0,0,0,0.25);
  cursor:pointer;
}

.right{
  transform:scale(0.95);
  opacity:0.6;
}

/* ------------------------
TABLETS
-------------------------*/

@media (max-width: 1024px) {

  .categories{
    gap:180px;
    font-size:1.6rem;
  }

  .card{
    width:220px;
    height:300px;
  }

  .carousel-wrapper{
    gap:30px;
  }

  .arrow{
    display:none;
  }

}

@media (max-width: 768px) {

  .categories{
    gap:110px;
    font-size:1.1rem;
    margin-bottom:35px;
  }

  .card{
    width:150px;
    height:200px;
  }

  .cards{
    gap:18px;
  }

  .arrow{
    display:none;
  }

}

@media (max-width: 480px) {

  .carousel{
    padding:30px 0;
  }

  .mobile-title{
    display:block;
  }

  .categories{
    flex-direction:column;
    gap:10px;
    font-size:1rem;
    margin-bottom:25px;
    display:none;
  }

  .carousel-wrapper{
    flex-direction:column;
    gap:25px;
  }

  .cards{
    flex-direction:column;
    gap:25px;
    cursor:default;
    touch-action:none;
  }

  .card{
    width:180px;
    height:120px;
  }

  .center{
    transform:scale(1.05);
  }

  .arrow{
    display:none;
  }

}

</style>