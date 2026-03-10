<template>
  <div class="questions-layout">

    <!-- IZQUIERDA (Visual) -->
    <div class="left-side">
      <div class="overlay"></div>

      <div class="left-content">
        <div class="logo">RS</div>
        <span class="label">Deja tu búsqueda a nosotros</span>
        <h1>Cuéntanos qué<br>es lo que buscas...</h1>
      </div>
    </div>

    <!-- DERECHA (Tu formulario real) -->
    <div class="right-side">
      <form @submit.prevent="submit" class="category-form">

        <h2 class="form-title">
        Tus Preferencias para {{ category }}
        </h2>

        <component :is="currentForm" :form="form" />

        <button type="submit" class="submit-btn">
        Guardar preferencias
        </button>
      </form>
    </div>

  </div>
</template>

<script>
import { useUserStore } from "../stores/user";
import { useRouter, useRoute } from "vue-router";
import { ref, computed } from "vue";
import { backendJson } from "../services/backend";
import HotelesForm from "../components/HotelesForm.vue";
import ParkingForm from "../components/ParkingForm.vue";
import EdificiosForm from "../components/EdificiosForm.vue";
import FincasForm from "../components/FincasForm.vue";
import ActivosForm from "../components/ActivosForm.vue";

export default {

setup(){

const userStore = useUserStore()
const router = useRouter()
const route = useRoute()

const category = ref(route.query.category || userStore.selectedCategory)

const form = ref({
estrellas:[],
servicios:[],
ubicacion:[],
tipo:[],
caracteristicas:[],
zona:[],
uso:[]
})

const forms={
Hoteles:HotelesForm,
Parking:ParkingForm,
Edificios:EdificiosForm,
Fincas:FincasForm,
Activos:ActivosForm
}

const currentForm = computed(()=>{
return forms[category.value]
})

const submit = async()=>{
await backendJson("save_preferences.php", {
method:"POST",
headers:{ "Content-Type":"application/json"},
body:JSON.stringify({
categoria:category.value,
preferencias:form.value
})
})

userStore.setCategory(category.value)
userStore.setPreferences({...form.value})

router.push("/profile")

}

return{category,form,currentForm,submit}

}
}
</script>

<style scoped>
.questions-layout {
  display: flex;
  min-height: calc(100vh - 90px);
  margin-top: 90px;
  background-image: url('@/assets/fondito.png');
  background-size: cover;
  background-position: center;
}

/* IZQUIERDA */
.left-side {
  flex: 1;
  position: relative;
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: center;
  padding: 80px;
  color: white;
}

.overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.498);
}

.left-content {
  position: relative;
  z-index: 1;
}

.logo {
  font-size: 3rem;
  font-weight: 600;
}

.label {
  color: #d4af37;
  font-size: 2.5rem;
}

.left-content h1 {
  font-size: 4rem;
  font-weight: 300;
  margin: 20px 0;
  line-height: 1.2;
}

.contact-info {
  margin-top: 40px;
  font-size: 1rem;
  opacity: 0.9;
}

/* DERECHA */
.right-side {
  width: 60%;
  background: #75727250;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 60px;
}

.category-form {
  background: rgba(255, 255, 255, 0.95);
  padding: 50px;
  border-radius: 30px;
  width: 100%;
  max-width: 650px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.35);
  max-height: 85vh;
  overflow-y: auto;
}

.form-title {
  text-align: center;
  margin-bottom: 20px;
}

/* 👇 ESTOS SON LOS QUE VIENEN DE LOS COMPONENTES HIJOS */

.category-form :deep(.section) {
  margin-bottom: 25px;
}

.category-form :deep(.options) {
  display: flex;
  flex-wrap: wrap;
  gap: 12px 25px;
}

.category-form :deep(label) {
  display: flex;
  gap: 8px;
  align-items: center;
  font-size: 0.95rem;
}

.submit-btn {
  margin-top: 30px;
  width: 60%;
  align-self: center;
  padding: 15px;
  border-radius: 30px;
  border: none;
  background-color: #24386b;
  color: white;
  font-size: 1.1rem;
  cursor: pointer;
  transition: 0.3s;
}

.submit-btn:hover {
  background-color: var(--azul-secundario);
}

@media (max-width: 1200px) {

.right-side{
  width: 65%;
  padding: 40px;
}

.left-side{
  padding: 50px;
}

.left-content h1{
  font-size: 3rem;
}

.logo{
  font-size: 2.4rem;
}

.label{
  font-size: 2rem;
}

.category-form{
  padding: 40px;
}

}


/* ============================= */
/* 992px */
/* ============================= */

@media (max-width: 992px) {

.left-side{
  padding: 40px;
}

.left-content h1{
  font-size: 2.6rem;
}

.right-side{
  width: 70%;
}

.category-form{
  padding: 35px;
}

.category-form :deep(.options){
  gap: 10px 18px;
}

}


/* ============================= */
/* 768px (tablet) */
/* ============================= */

@media (max-width: 768px) {

.questions-layout{
  flex-direction: column;
}

.left-side{
  min-height: 300px;
  justify-content: center;
  text-align: center;
  padding: 40px 30px;
}

.left-content h1{
  font-size: 2.2rem;
}

.logo{
  font-size: 2rem;
}

.label{
  font-size: 1.6rem;
}

.right-side{
  width: 100%;
  padding: 30px;
}

.category-form{
  max-width: 100%;
  padding: 30px;
  border-radius: 20px;
}

.submit-btn{
  width: 80%;
}

}


/* ============================= */
/* 480px (mobile) */
/* ============================= */

@media (max-width: 480px) {

.left-side{
  padding: 30px 20px;
}

.left-content h1{
  font-size: 1.8rem;
}

.logo{
  font-size: 1.7rem;
}

.label{
  font-size: 1.4rem;
}

.contact-info{
  font-size: 0.9rem;
}

.right-side{
  padding: 20px;
}

.category-form{
  padding: 22px;
}

.category-form :deep(.options){
  flex-direction: column;
  gap: 10px;
}

.submit-btn{
  width: 100%;
  padding: 14px;
  font-size: 1rem;
}

}

</style>
