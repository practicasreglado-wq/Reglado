<template>
  <div v-if="visible" class="popper-overlay" @click.self="$emit('close')">
    <div class="popper-card">

      <h3>Coincidencias con tu búsqueda</h3>

      <ul>
        <li
          v-for="item in details"
          :key="item.label"
          :class="item.match ? 'ok' : 'fail'"
        >
          <span class="icon">
            {{ item.match ? "✔" : "✘" }}
          </span>

          {{ item.label }}
        </li>
      </ul>

    </div>
  </div>
</template>

<script>
export default {
  name: "MatchDetailsPopper",

  props: {
    visible: Boolean,
    details: Array
  }
}
</script>
