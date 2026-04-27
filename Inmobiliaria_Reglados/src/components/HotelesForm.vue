<!--
  Cuestionario de preferencias para la categoría "Hoteles". Wrapper sobre
  PreferenceQuestionsForm con la lista de preguntas específica de esta
  categoría (estrellas, habitaciones, ubicación turística, etc.).

  Hay un wrapper análogo por categoría: FincasForm, ParkingForm,
  EdificiosForm, ActivosForm. Solo cambian las preguntas.
-->
<template>
  <PreferenceQuestionsForm
    :form="form"
    :prefix="prefix"
    :questions="questions"
  />
</template>

<script>
import PreferenceQuestionsForm from "./PreferenceQuestionsForm.vue";
import { getPreferenceSchema } from "../data/preferenceSchemas";

export default {
  components: {
    PreferenceQuestionsForm,
  },
  props: {
    form: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      prefix: "hoteles",
      questions: getPreferenceSchema("Hoteles")?.questions || [],
    };
  },
};
</script>
