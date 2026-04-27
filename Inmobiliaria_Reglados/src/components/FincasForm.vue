<!--
  Cuestionario de preferencias para "Fincas". Mismo patrón que HotelesForm
  (wrapper sobre PreferenceQuestionsForm con preguntas específicas).
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
      prefix: "fincas",
      questions: getPreferenceSchema("Fincas")?.questions || [],
    };
  },
};
</script>
