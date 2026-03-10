<template>
<div class="section">

<h3>Tipo</h3>
<div class="options">
<label v-for="tipo in ['Rural','Agrícola','Forestal']" :key="tipo">
<input type="checkbox" :value="tipo" v-model="form.tipo" />
{{ tipo }}
</label>
</div>

<h3>Características</h3>
<div class="options">
<label v-for="car in ['Agua potable','Acceso por carretera','Parcela vallada']" :key="car">
<input type="checkbox" :value="car" v-model="form.caracteristicas" />
{{ car }}
</label>
</div>

<h3>Ubicación</h3>
<div class="options">
<label v-for="zona in ['Zona rural','Cerca de río','Montaña']" :key="zona">
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