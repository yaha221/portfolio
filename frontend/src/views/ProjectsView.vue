<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { api } from '@/api/client'
import { unwrap } from '@/api/problem'
import LoadState from '@/components/LoadState.vue'
import PaginationNav from '@/components/PaginationNav.vue'
import ProjectCard from '@/components/ProjectCard.vue'
import TechBadge from '@/components/TechBadge.vue'
import { useAsync } from '@/composables/useAsync'

const PER_PAGE = 12

const route = useRoute()

// The URL is the source of truth: filters and pages survive reloads and can be shared as links
const page = computed(() => {
  const value = Number(route.query.page)
  return Number.isInteger(value) && value > 0 ? value : 1
})
const technology = computed(() =>
  typeof route.query.technology === 'string' ? route.query.technology : undefined,
)

const { data, loading, error, run } = useAsync(async () =>
  unwrap(
    await api.GET('/api/projects', {
      params: { query: { page: page.value, perPage: PER_PAGE, technology: technology.value } },
    }),
  ),
)
const { data: technologies, run: loadTechnologies } = useAsync(async () =>
  unwrap(await api.GET('/api/technologies')),
)

watch([page, technology], run)
onMounted(() => {
  void run()
  void loadTechnologies()
})

function pageLink(target: number) {
  return {
    name: 'projects',
    query: { ...route.query, page: target > 1 ? String(target) : undefined },
  }
}
</script>

<template>
  <div class="container projects">
    <header class="projects__head">
      <h1>Проекты</h1>
      <p v-if="data" class="muted">
        {{ data.meta.total }} {{ technology ? 'с выбранной технологией' : 'всего' }}
      </p>
    </header>

    <nav v-if="technologies?.items.length" class="filters" aria-label="Фильтр по технологии">
      <RouterLink
        :to="{ name: 'projects' }"
        class="filters__item"
        :class="{ 'is-active': !technology }"
        >Все</RouterLink
      >
      <RouterLink
        v-for="item in technologies.items"
        :key="item.slug"
        :to="{ name: 'projects', query: { technology: item.slug } }"
        class="filters__item"
        :class="{ 'is-active': technology === item.slug }"
      >
        <TechBadge :name="item.name" :icon="item.icon" :count="item.projectCount" />
      </RouterLink>
    </nav>

    <LoadState :loading="loading && !data" :error="error" @retry="run" />

    <template v-if="data">
      <div v-if="data.items.length" class="grid" :class="{ 'is-loading': loading }">
        <ProjectCard v-for="project in data.items" :key="project.slug" :project="project" />
      </div>
      <p v-else class="muted">Проектов с этой технологией пока нет.</p>

      <PaginationNav :page="data.meta.page" :pages="data.meta.pages" :link-to="pageLink" />
    </template>
  </div>
</template>

<style scoped>
.projects {
  display: grid;
  gap: var(--space-6);
}

.projects__head {
  display: grid;
  gap: var(--space-2);
}

.projects__head h1 {
  font-size: var(--text-3xl);
  letter-spacing: -0.02em;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.filters__item {
  display: inline-flex;
  align-items: center;
  min-height: 1.9rem;
  padding: 0 0.75rem;
  border: 1px solid var(--border);
  border-radius: 999px;
  color: var(--text-muted);
  font-size: var(--text-xs);
  font-family: var(--font-mono);
  text-decoration: none;
}

/* The badge inside a filter chip loses its own frame: the chip draws it */
.filters__item :deep(.badge) {
  border: none;
  padding: 0;
  background: none;
}

.filters__item:hover {
  border-color: var(--text-muted);
}

.filters__item.is-active {
  border-color: var(--accent);
  background: var(--accent-soft);
  color: var(--text);
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
  gap: var(--space-5);
  transition: opacity 0.15s;
}

.grid.is-loading {
  opacity: 0.5;
}
</style>
