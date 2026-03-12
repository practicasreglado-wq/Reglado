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
