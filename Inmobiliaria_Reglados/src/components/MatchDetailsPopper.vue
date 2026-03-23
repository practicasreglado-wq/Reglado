<template>
  <div
    v-if="visible"
    class="popper-overlay"
    @click.self="$emit('close')"
  >

    <div
      class="popper-card"
      :style="{
        top: y + 'px',
        left: x + 'px'
      }"
    >

      <h3>Coincidencias de Interés (Mock)</h3>

      <ul>
        <!-- Datos de ejemplo estáticos para el desarrollo de la plantilla -->
        <li class="ok"><span class="icon">✔</span> Ubicación estratégica</li>
        <li class="ok"><span class="icon">✔</span> Precio de mercado optimizado</li>
        <li class="ok"><span class="icon">✔</span> Superficie y distribución</li>
        <li class="ok"><span class="icon">✔</span> Rentabilidad proyectada</li>
        <li class="fail"><span class="icon">✘</span> Disponibilidad inmediata</li>
      </ul>

    </div>

  </div>
</template>

<script>
export default {

  name:"MatchDetailsPopper",

  props:{
    visible:Boolean,
    details:Array,
    x:Number,
    y:Number
  }

}
</script>

<style scoped>

.popper-overlay{
  position:fixed;
  inset:0;
  z-index:2000;
}

/* CARD */

.popper-card{

  position:absolute;

  width:235px;
  max-height:420px;

  border:1px solid #172a5d;

  background:rgba(255, 255, 255, 0.815);
  backdrop-filter:blur(18px);
  -webkit-backdrop-filter:blur(18px);

  border-radius:14px;

  padding:18px 20px;

  box-shadow:0 18px 40px rgba(0,0,0,0.18);

  display:flex;
  flex-direction:column;

  transform:translateX(-220%);

}

.popper-card h3{
  margin-bottom:12px;
  color:#172a5d;
}

/* LIST */

ul{
  list-style:none;
  padding:0;
  margin:0;

  overflow-y:auto;
  max-height:300px;
}

li{

  display:flex;
  align-items:center;

  gap:8px;

  padding:4px 0;

  font-size:0.9rem;
  font-weight:600;

}

.ok{
  color:#1f9d55;
}

.fail{
  color:#e53e3e;
}

.icon{
  width:16px;
  font-size:0.9rem;
}

</style>


