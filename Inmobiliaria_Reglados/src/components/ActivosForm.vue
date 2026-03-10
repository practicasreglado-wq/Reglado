<template>
<div class="section">

<h3>Tipo de activo</h3>
<div class="options">
<label v-for="tipo in ['Comercial','Industrial','Residencial']" :key="tipo">
<input type="checkbox" :value="tipo" v-model="form.tipo" />
{{ tipo }}
</label>
</div>

<h3>Características</h3>
<div class="options">
<label v-for="car in ['Fachada renovada','Cercano a transporte público','Espacios adaptados']" :key="car">
<input type="checkbox" :value="car" v-model="form.caracteristicas" />
{{ car }}
</label>
</div>

<h3>Ubicación</h3>
<div class="options">
<label v-for="zona in ['Centro de la ciudad','Zona industrial']" :key="zona">
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
