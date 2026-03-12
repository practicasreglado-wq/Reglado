<template>
  <div class="password-field">
    <input
      v-bind="attrs"
      :type="visible ? 'text' : 'password'"
      :value="modelValue"
      class="password-field__input"
      @input="handleInput"
    />

    <button
      class="password-field__toggle"
      type="button"
      :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
      @click="visible = !visible"
    >
      <img
        class="password-field__icon"
        :src="visible ? eyeOffIcon : eyeIcon"
        :alt="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
      />
    </button>
  </div>
</template>

<script setup>
import { ref, useAttrs } from "vue";
import eyeIcon from "../assets/eye.svg";
import eyeOffIcon from "../assets/eye-off.svg";

defineOptions({
  inheritAttrs: false,
});

defineProps({
  modelValue: {
    type: String,
    default: "",
  },
});

const emit = defineEmits(["update:modelValue"]);
const attrs = useAttrs();
const visible = ref(false);

function handleInput(event) {
  const target = event.target;
  if (!(target instanceof HTMLInputElement)) {
    return;
  }

  emit("update:modelValue", target.value);
}
</script>

<style scoped>
.password-field {
  position: relative;
  display: flex;
  align-items: center;
}

.password-field__input {
  width: 100%;
  padding-right: 3rem;
}

.password-field__toggle {
  position: absolute;
  right: 0.55rem;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  padding: 0.2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.password-field__icon {
  width: 1.15rem;
  height: 1.15rem;
  opacity: 0.72;
}

.password-field__toggle:hover .password-field__icon,
.password-field__toggle:focus-visible .password-field__icon {
  opacity: 1;
}

.password-field__toggle:focus-visible {
  outline: 2px solid rgba(39, 61, 92, 0.24);
  border-radius: 999px;
}
</style>
