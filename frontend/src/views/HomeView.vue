<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'

import { api } from '@/api/client'
import { unwrap } from '@/api/problem'
import LoadState from '@/components/LoadState.vue'
import ProjectCard from '@/components/ProjectCard.vue'
import { useAsync } from '@/composables/useAsync'
import { site } from '@/config/site'
import { groupByCategory } from '@/utils/format'

const {
  data: featured,
  loading: featuredLoading,
  error: featuredError,
  run: loadFeatured,
} = useAsync(async () =>
  unwrap(await api.GET('/api/projects', { params: { query: { perPage: 3 } } })),
)

const { data: skills, run: loadSkills } = useAsync(async () => unwrap(await api.GET('/api/skills')))

const skillGroups = computed(() => groupByCategory(skills.value?.items ?? []))

onMounted(() => {
  void loadFeatured()
  void loadSkills()
})
</script>

<template>
  <div class="container home">
    <section class="hero">
      <p class="eyebrow">{{ site.role }}</p>
      <h1 class="hero__title">{{ site.name }}</h1>
      <p class="hero__tagline">{{ site.tagline }}</p>
      <div class="hero__actions">
        <RouterLink :to="{ name: 'projects' }" class="button">Смотреть проекты</RouterLink>
        <RouterLink :to="{ name: 'contact' }" class="button button--ghost">Написать мне</RouterLink>
      </div>
    </section>

    <section class="section" aria-labelledby="featured-title">
      <div class="section__head">
        <h2 id="featured-title">Проекты</h2>
        <RouterLink :to="{ name: 'projects' }" class="section__more">Все проекты →</RouterLink>
      </div>
      <LoadState :loading="featuredLoading" :error="featuredError" @retry="loadFeatured" />
      <div v-if="featured" class="grid">
        <ProjectCard v-for="project in featured.items" :key="project.slug" :project="project" />
      </div>
    </section>

    <section v-if="skillGroups.length" class="section" aria-labelledby="skills-title">
      <div class="section__head">
        <h2 id="skills-title">Чем работаю</h2>
        <RouterLink :to="{ name: 'about' }" class="section__more">Опыт и навыки →</RouterLink>
      </div>
      <dl class="skills">
        <div v-for="group in skillGroups" :key="group.category" class="skills__group">
          <dt class="eyebrow">{{ group.category }}</dt>
          <dd>{{ group.items.map((skill) => skill.name).join(' · ') }}</dd>
        </div>
      </dl>
    </section>
  </div>
</template>

<style scoped>
.home {
  display: grid;
  gap: var(--space-8);
}

.hero {
  display: grid;
  gap: var(--space-4);
  padding-block: var(--space-6) var(--space-4);
  max-width: 46rem;
}

.hero__title {
  font-size: var(--text-3xl);
  letter-spacing: -0.03em;
}

.hero__tagline {
  font-size: var(--text-lg);
  color: var(--text-muted);
}

.hero__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  margin-top: var(--space-2);
}

.section {
  display: grid;
  gap: var(--space-5);
}

.section__head {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: var(--space-3);
}

.section__head h2 {
  font-size: var(--text-2xl);
}

.section__more {
  font-size: var(--text-sm);
  font-weight: 500;
  text-decoration: none;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
  gap: var(--space-5);
}

.skills {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
  gap: var(--space-5);
  margin: 0;
}

.skills__group {
  display: grid;
  gap: var(--space-2);
  padding-top: var(--space-3);
  border-top: 1px solid var(--border);
}

.skills__group dd {
  margin: 0;
}
</style>
