<script setup>
import { ref } from 'vue'

const showTooltip = ref(false)
const phoneNumber = "34600000000" // Cambiar el número cuando se sepa
const message = encodeURIComponent("Hola, me gustaría recibir más información.")
const whatsappUrl = `https://wa.me/${phoneNumber}?text=${message}`
</script>

<!-- 
  Descripción: Botón flotante de contacto directo por WhatsApp.
-->
<template>
  <div class="whatsapp-wrapper">
    <transition name="fade">
      <div v-if="showTooltip" class="whatsapp-tooltip">
        ¿Tienes dudas? Envíanos un WhatsApp
      </div>
    </transition>
    
    <a 
      :href="whatsappUrl" 
      target="_blank" 
      class="whatsapp-btn"
      @mouseenter="showTooltip = true"
      @mouseleave="showTooltip = false"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="65" height="65" viewBox="0 0 24 24" fill="white">
        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766 0-3.18-2.587-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.171.823-.299.043-.687.072-1.114-.064-.26-.083-.585-.192-1.002-.357-1.768-.702-2.924-2.493-3.012-2.61-.088-.117-.714-.95-.714-1.808 0-.859.447-1.282.607-1.455.16-.174.348-.217.464-.217h.334c.101 0 .231-.014.333.232.101.246.348.84.377.912.03.072.043.159.014.246-.029.087-.058.159-.145.246-.087.087-.159.188-.246.275-.087.087-.188.174-.087.348.101.174.449.739.956 1.188.653.58 1.202.76 1.376.846.174.087.275.058.377-.043.101-.101.434-.507.55-.68.116-.174.232-.145.39-.087.16.058 1.015.478 1.188.565s.29.13.333.203c.043.072.043.464-.101.869zM12 1c6.075 0 11 4.925 11 11s-4.925 11-11 11-11-4.925-11-11 4.925-11 11-11zm0 2c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9z"/>
      </svg>
    </a>
  </div>
</template>

<style scoped>
.whatsapp-wrapper {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 10000;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.whatsapp-btn {
  background-color: #25D366;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  transition: transform 0.3s ease, background-color 0.3s ease;
  cursor: pointer;
  overflow: hidden;
}

.whatsapp-btn:hover {
  transform: scale(1.1) rotate(5deg);
  background-color: #22c35e;
}

.whatsapp-tooltip {
  background-color: #333;
  color: white;
  padding: 8px 15px;
  border-radius: 8px;
  font-size: 0.9rem;
  margin-bottom: 12px;
  position: relative;
  white-space: nowrap;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  font-weight: 500;
}

.whatsapp-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  right: 25px;
  border-width: 6px;
  border-style: solid;
  border-color: #333 transparent transparent transparent;
}

/* Transición suave para el tooltip */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
