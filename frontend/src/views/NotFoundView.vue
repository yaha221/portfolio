<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="container missing">
    <p class="eyebrow">404</p>
    <h1>Такой страницы нет</h1>
    <p class="muted">Проверьте адрес или начните с главной.</p>
    <RouterLink :to="{ name: 'home' }" class="button">На главную</RouterLink>
  </div>
</template>

<style scoped>
.missing {
  display: grid;
  justify-items: start;
  gap: var(--space-4);
  padding-block: var(--space-7);
}

.missing h1 {
  font-size: var(--text-3xl);
}
</style>
