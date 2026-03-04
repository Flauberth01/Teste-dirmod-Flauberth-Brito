<template>
  <p v-if="message" class="field-error">{{ message }}</p>
</template>

<script setup>
defineProps({
  message: {
    type: String,
    default: '',
  },
})
</script>
