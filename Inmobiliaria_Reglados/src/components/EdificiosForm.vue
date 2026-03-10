<template>
<div class="section">

<h3>Uso</h3>
<div class="options">
<label v-for="uso in ['Residencial','Oficinas','Comercial','Industrial']" :key="uso">
<input type="checkbox" :value="uso" v-model="form.uso" />
{{ uso }}
</label>
</div>

<h3>Características</h3>
<div class="options">
<label v-for="car in ['Ascensor','Garaje','Terraza','Reformado','Nuevo']" :key="car">
<input type="checkbox" :value="car" v-model="form.caracteristicas" />
{{ car }}
</label>
</div>

<h3>Zona</h3>
<div class="options">
<label v-for="zona in ['Centro','Periferia','Zona financiera']" :key="zona">
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