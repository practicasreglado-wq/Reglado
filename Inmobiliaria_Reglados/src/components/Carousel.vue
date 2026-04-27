<!--
  Carrusel genérico de propiedades destacadas. Lo usa Home.vue para mostrar
  un slider de "últimas propiedades" o categorías destacadas en la landing.

  Recibe el array a mostrar como prop y maneja navegación + autoplay
  internamente.
-->
<template>
  <div class="carousel">

    <!-- TITULOS -->
    <div class="carousel-header">
      <p class="carousel-kicker">Explorar Categorías</p>
    </div>

    <div class="carousel-wrapper">

      <button class="arrow" @click="prev">&#10094;</button>

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
          :style="cardStyle(index)"
        >
          <div class="category-title" :class="{ active: index === 1 }">
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

      <button class="arrow" @click="next">&#10095;</button>

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
      dragOffset: 0,
      dragVelocity: 0,
      lastDragX: 0,
      lastDragTime: 0,

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
      if (this.isAnimating) return;

      this.isDragging = true;
      this.startX = e.clientX;
      this.endX = e.clientX;
      this.dragOffset = 0;
      this.dragVelocity = 0;
      this.lastDragX = e.clientX;
      this.lastDragTime = performance.now();

    },

    onDrag(e) {

      if (!this.isDragging) return;

      const now = performance.now();
      const deltaX = e.clientX - this.lastDragX;
      const deltaTime = Math.max(now - this.lastDragTime, 1);

      this.endX = e.clientX;
      this.dragOffset = this.endX - this.startX;
      this.dragVelocity = deltaX / deltaTime;
      this.lastDragX = e.clientX;
      this.lastDragTime = now;

    },

    endDrag() {

      if (!this.isDragging) return;

      const diff = this.endX - this.startX;
      const momentum = diff + this.dragVelocity * 180;

      if (Math.abs(momentum) > 70) {

        if (momentum > 0) {
          this.prev();
        } else {
          this.next();
        }

      }

      this.isDragging = false;
      this.dragOffset = 0;
      this.dragVelocity = 0;

    },

    /* -------------------------
       TOUCH MOVIL
    --------------------------*/

    startTouch(e) {
      if (this.isAnimating) return;

      if (window.innerWidth <= 480) {
        this.startY = e.touches[0].clientY;
      } else {
        this.startX = e.touches[0].clientX;
        this.endX = e.touches[0].clientX;
        this.dragOffset = 0;
        this.dragVelocity = 0;
        this.lastDragX = e.touches[0].clientX;
        this.lastDragTime = performance.now();
      }

    },

    onTouch(e) {

      if (window.innerWidth <= 480) {
        this.endY = e.touches[0].clientY;
      } else {
        const now = performance.now();
        const currentX = e.touches[0].clientX;
        const deltaX = currentX - this.lastDragX;
        const deltaTime = Math.max(now - this.lastDragTime, 1);

        this.endX = currentX;
        this.dragOffset = this.endX - this.startX;
        this.dragVelocity = deltaX / deltaTime;
        this.lastDragX = currentX;
        this.lastDragTime = now;
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
        const momentum = diff + this.dragVelocity * 180;

        if (Math.abs(momentum) > 60) {

          if (momentum > 0) {
            this.prev();
          } else {
            this.next();
          }

        }

      }

      this.dragOffset = 0;
      this.dragVelocity = 0;

    },

    /* -------------------------
       CLASES
    --------------------------*/

    cardClass(index) {

      if (index === 1) return "center";
      if (index === 0) return "left";
      if (index === 2) return "right";

    },

    cardStyle(index) {
      const zIndexMap = [1, 3, 1];

      if (!this.dragOffset || window.innerWidth <= 480) {
        return {
          zIndex: zIndexMap[index] ?? 1,
        };
      }

      const intensityMap = [0.55, 1, 0.55];
      const offset = Math.max(Math.min(this.dragOffset, 90), -90) * intensityMap[index];
      const tilt = Math.max(Math.min(this.dragOffset / 18, 6), -6);

      return {
        transform: `translateX(${offset}px) rotate(${tilt}deg)`,
        zIndex: zIndexMap[index] ?? 1,
      };
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
  position:relative;
  display:flex;
  flex-direction:column;
  align-items:center;
  transition:transform 0.32s ease-out;
  will-change:transform;
}

.category-title {
  display: block;
}

.carousel {
  position: relative;
  text-align: center;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}

/* TITULOS */
.carousel-header {
  position: absolute;
  top: 40px;
  left: 0;
  width: 100%;
}

.carousel-kicker {
  color: #d4af37; /* Brighter gold */
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.4em; /* More spaced */
  font-size: clamp(1.25rem, 1rem + 1vw, 2.1rem);
  text-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);
}

.category-title {
  font-size: clamp(1.2rem, 3vw, 2.2rem);
  font-weight: 700;
  color: #12244d;
  margin-bottom: 30px;
  opacity: 0.15;
  transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
  transform: translateY(24px) scale(0.85);
  filter: blur(1px);
  white-space: nowrap;
}

.category-title.active {
  opacity: 1;
  transform: translateY(0) scale(1.1);
  filter: blur(0);
  text-shadow: 0 10px 20px rgba(18, 36, 77, 0.1);
}

/* WRAPPER */
.carousel-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 50px;
}

/* BOTONES */

.arrow {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(12px);
  color: #12244d;
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  width: 64px;
  height: 64px;
  font-size: 24px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 32px rgba(18, 36, 77, 0.1);
}

.arrow:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(1.1);
  box-shadow: 0 12px 40px rgba(18, 36, 77, 0.2);
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

.card {
  width: 280px;
  height: 380px;
  background-size: cover;
  background-position: center;
  border-radius: 24px;
  transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
  user-select: none;
  border: 1px solid rgba(255, 255, 255, 0.2);
  position: relative;
  overflow: hidden;
}

.card::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, transparent 60%, rgba(18, 36, 77, 0.4) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.center::after {
  opacity: 1;
}

/* POSICIONES */

.left {
  transform: scale(0.9);
  opacity: 0.9; /* Semi-opaque to balance visibility and masking */
  filter: blur(2px) grayscale(0.2) brightness(0.9);
}

.center {
  transform: scale(1.1);
  opacity: 1;
  box-shadow: 
    0 30px 60px rgba(18, 36, 77, 0.3),
    0 10px 20px rgba(18, 36, 77, 0.15);
  cursor: pointer;
  border-color: rgba(255, 255, 255, 0.5);
}

.right {
  transform: scale(0.9);
  opacity: 0.9; /* Semi-opaque to balance visibility and masking */
  filter: blur(2px) grayscale(0.2) brightness(0.9);
}

.center:hover {
  transform: scale(1.12) translateY(-5px);
  box-shadow: 
    0 40px 80px rgba(18, 36, 77, 0.4),
    0 15px 30px rgba(18, 36, 77, 0.2);
}

/* ------------------------
TABLETS / LAPTOPS
-------------------------*/
@media (max-width: 1440px) {
  .carousel {
    padding: 60px 0;
  }
  .carousel-header {
    margin-bottom: 40px;
  }
  .categories {
    width: 100%;
  }
  .card {
    width: 220px;
    height: 300px;
  }
  .arrow {
    width: 54px;
    height: 54px;
  }
}

@media (max-width: 980px) {
  .categories {
    width: 100%;
  }
  .arrow {
    display: none;
  }
}

@media (max-width: 768px) {
  .carousel-wrapper {
    gap: 20px;
  }
  .card {
    width: 160px;
    height: 220px;
  }
  .carousel-header {
    margin-bottom: 20px;
  }
}

@media (max-width: 480px) {
  .carousel {
    padding: 20px 0 30px;
  }
  .category-title {
    font-size: 1.2rem;
    color: #12244d;
    margin-bottom: 8px;
    opacity: 0.4;
    transform: none;
    filter: none;
  }
  .card-wrapper:has(.center) .category-title {
    opacity: 1;
    font-weight: 700;
  }
  .carousel-header {
    display: none;
  }
  .carousel-wrapper {
    flex-direction: column;
    gap: 20px;
  }
  .cards {
    flex-direction: column;
    gap: 20px;
    cursor: default;
    touch-action: none;
  }
  .card {
    width: 150px;
    height: 95px;
  }
}

</style>
