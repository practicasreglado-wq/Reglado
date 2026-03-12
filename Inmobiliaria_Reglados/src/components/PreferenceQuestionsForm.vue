<template>
  <div class="section">
    <div
      v-for="(question, index) in questions"
      :key="question.key"
      class="question-container"
    >
      <h3>{{ index + 1 }}. {{ question.prompt }}</h3>

      <div class="options">
        <label
          v-for="option in question.options"
          :key="option"
          class="option-item"
        >
          <input
            :name="`${prefix}_${question.key}`"
            :value="option"
            type="radio"
            v-model="form[question.key]"
          />
          <span>{{ option }}</span>
        </label>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    form: {
      type: Object,
      required: true,
    },
    prefix: {
      type: String,
      required: true,
    },
    questions: {
      type: Array,
      required: true,
    },
  },
};
</script>

<style scoped>
.question-container {
  margin-bottom: 22px;
  padding: 18px;
  background: linear-gradient(180deg, #ffffff, #f7f9fc);
  border-radius: 16px;
  border: 1px solid #d9e1ee;
}

.question-container h3 {
  margin: 0 0 12px;
  color: #1a2d57;
  font-size: 1.02rem;
  line-height: 1.45;
}

.options {
  display: grid;
  gap: 10px;
}

.option-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #e3e8f2;
  background: #fff;
  cursor: pointer;
  transition: border-color 0.2s ease, background 0.2s ease, transform 0.2s ease;
}

.option-item:hover {
  border-color: #3557b8;
  background: #f3f6fd;
  transform: translateY(-1px);
}

input[type="radio"] {
  margin-top: 3px;
  accent-color: #1f4aa8;
}

.option-item span {
  color: #3b4a67;
  line-height: 1.4;
}
</style>
