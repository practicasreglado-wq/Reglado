<template>
<div class="section">

<h3>Estrellas</h3>
<div class="options">
<label v-for="star in ['3 estrellas','4 estrellas','5 estrellas']" :key="star">
<input type="checkbox" :value="star" v-model="form.estrellas" />
{{ star }}
</label>
</div>

<h3>Servicios</h3>
<div class="options">
<label v-for="serv in ['Spa','Piscina','Gimnasio','Parking privado','Restaurante','Room Service','Vista al mar']" :key="serv">
<input type="checkbox" :value="serv" v-model="form.servicios" />
{{ serv }}
</label>
</div>

<h3>Ubicación</h3>
<div class="options">
<label v-for="loc in ['Centro ciudad','Playa','Montaña','Zona rural']" :key="loc">
<input type="checkbox" :value="loc" v-model="form.ubicacion" />
{{ loc }}
</label>
</div>

</div>
</template>

<script>
export default {
props:["form"]
}
</script>