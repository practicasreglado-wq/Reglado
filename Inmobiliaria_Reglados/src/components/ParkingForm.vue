<template>
<div class="section">

<h3>Tipo</h3>
<div class="options">
<label v-for="tipo in ['Subterráneo','Exterior','Privado','Público']" :key="tipo">
<input type="checkbox" :value="tipo" v-model="form.tipo" />
{{ tipo }}
</label>
</div>

<h3>Características</h3>
<div class="options">
<label v-for="car in ['Vigilancia 24h','Acceso automático','Cámaras de seguridad','Carga eléctrica']" :key="car">
<input type="checkbox" :value="car" v-model="form.caracteristicas" />
{{ car }}
</label>
</div>

<h3>Ubicación</h3>
<div class="options">
<label v-for="zona in ['Centro','Residencial','Comercial']" :key="zona">
<input type="checkbox" :value="zona" v-model="form.zona" />
{{ zona }}
</label>
</div>

</div>
</template>

<script>
export default {
props:["form"]
}
</script>