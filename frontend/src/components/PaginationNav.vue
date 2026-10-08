<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, type RouteLocationRaw } from 'vue-router'

const props = defineProps<{
  page: number
  pages: number
  // Builds the link for a page, so the list keeps its other query parameters
  linkTo: (page: number) => RouteLocationRaw
}>()

const numbers = computed(() => Array.from({ length: props.pages }, (_, index) => index + 1))
</script>

<template>
  <nav v-if="pages > 1" class="pagination" aria-label="Страницы">
    <RouterLink v-if="page > 1" :to="linkTo(page - 1)" class="pagination__item" rel="prev"
      >←</RouterLink
    >
    <template v-for="number in numbers" :key="number">
      <span v-if="number === page" class="pagination__item is-current" aria-current="page">{{
        number
      }}</span>
      <RouterLink v-else :to="linkTo(number)" class="pagination__item">{{ number }}</RouterLink>
    </template>
    <RouterLink v-if="page < pages" :to="linkTo(page + 1)" class="pagination__item" rel="next"
      >→</RouterLink
    >
  </nav>
</template>

<style scoped>
.pagination {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: var(--space-1);
}

.pagination__item {
  min-width: 2.25rem;
  padding: var(--space-2);
  border-radius: var(--radius-sm);
  text-align: center;
  font-family: var(--font-mono);
  font-size: var(--text-sm);
  color: var(--text-muted);
  text-decoration: none;
}

a.pagination__item:hover {
  background: var(--surface-muted);
  color: var(--text);
}

.pagination__item.is-current {
  background: var(--accent);
  color: var(--on-accent);
}
</style>
