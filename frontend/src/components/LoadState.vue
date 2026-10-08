<script setup lang="ts">
defineProps<{ loading: boolean; error: unknown }>()
defineEmits<{ retry: [] }>()
</script>

<template>
  <p v-if="loading" class="state muted" role="status">Загрузка…</p>
  <div v-else-if="error" class="state alert alert--error" role="alert">
    <p>Не получилось загрузить данные. Проверьте соединение и попробуйте ещё раз.</p>
    <button type="button" class="button button--ghost button--small" @click="$emit('retry')">
      Повторить
    </button>
  </div>
</template>

<style scoped>
.state {
  display: grid;
  justify-items: start;
  gap: var(--space-3);
  padding-block: var(--space-5);
}
</style>
