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

      <h3>Coincidencias con tu búsqueda</h3>

      <ul>

        <li
          v-for="item in details"
          :key="item.label"
          :class="item.match ? 'ok' : 'fail'"
        >

          <span class="icon">
            {{ item.match ? "✔" : "✘" }}
          </span>

          {{ item.label }}

        </li>

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

  width:280px;
  max-height:420px;

  border:2px solid #172a5d;

  background:rgba(255,255,255,0.9);
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


