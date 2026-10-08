<script setup lang="ts">
import { computed, onMounted } from 'vue'

import { api } from '@/api/client'
import { unwrap } from '@/api/problem'
import LoadState from '@/components/LoadState.vue'
import { useAsync } from '@/composables/useAsync'
import { site } from '@/config/site'
import { formatPeriod, groupByCategory } from '@/utils/format'

const MAX_LEVEL = 5

const {
  data: skills,
  loading: skillsLoading,
  error: skillsError,
  run: loadSkills,
} = useAsync(async () => unwrap(await api.GET('/api/skills')))
const {
  data: experience,
  loading: experienceLoading,
  error: experienceError,
  run: loadExperience,
} = useAsync(async () => unwrap(await api.GET('/api/experience')))

const skillGroups = computed(() => groupByCategory(skills.value?.items ?? []))

onMounted(() => {
  void loadSkills()
  void loadExperience()
})
</script>

<template>
  <div class="container about">
    <header class="about__head">
      <p class="eyebrow">{{ site.role }}</p>
      <h1>Обо мне</h1>
      <p class="about__lead">{{ site.tagline }}</p>
    </header>

    <section class="block" aria-labelledby="experience-title">
      <h2 id="experience-title">Опыт работы</h2>
      <LoadState :loading="experienceLoading" :error="experienceError" @retry="loadExperience" />
      <ol v-if="experience" class="timeline">
        <li
          v-for="job in experience.items"
          :key="`${job.company}-${job.startDate}`"
          class="timeline__item"
        >
          <p class="timeline__period">
            {{ formatPeriod(job.startDate, job.endDate) }}
          </p>
          <div class="timeline__body">
            <h3>{{ job.position }}</h3>
            <p class="muted">{{ job.company }}</p>
            <p v-if="job.description" class="timeline__description">{{ job.description }}</p>
          </div>
        </li>
      </ol>
    </section>

    <section class="block" aria-labelledby="skills-title">
      <h2 id="skills-title">Навыки</h2>
      <LoadState :loading="skillsLoading" :error="skillsError" @retry="loadSkills" />
      <div class="skills">
        <div v-for="group in skillGroups" :key="group.category" class="skills__group">
          <h3 class="eyebrow">{{ group.category }}</h3>
          <ul class="skills__list">
            <li v-for="skill in group.items" :key="skill.name" class="skill">
              <span>{{ skill.name }}</span>
              <span class="skill__level" :aria-label="`Уровень ${skill.level} из ${MAX_LEVEL}`">
                <span
                  v-for="step in MAX_LEVEL"
                  :key="step"
                  class="skill__dot"
                  :class="{ 'is-filled': step <= skill.level }"
                />
              </span>
            </li>
          </ul>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.about {
  display: grid;
  gap: var(--space-8);
}

.about__head {
  display: grid;
  gap: var(--space-3);
  max-width: 46rem;
}

.about__head h1 {
  font-size: var(--text-3xl);
  letter-spacing: -0.02em;
}

.about__lead {
  font-size: var(--text-lg);
  color: var(--text-muted);
}

.block {
  display: grid;
  gap: var(--space-5);
}

.block h2 {
  font-size: var(--text-2xl);
}

.timeline {
  margin: 0;
  padding: 0;
  list-style: none;
}

.timeline__item {
  display: grid;
  gap: var(--space-2) var(--space-6);
  padding-block: var(--space-5);
  border-top: 1px solid var(--border);
}

@media (min-width: 720px) {
  .timeline__item {
    grid-template-columns: 15rem 1fr;
  }
}

.timeline__period {
  font-family: var(--font-mono);
  font-size: var(--text-xs);
  color: var(--text-muted);
  padding-top: 0.3rem;
}

.timeline__body {
  display: grid;
  gap: var(--space-1);
  max-width: var(--reading-width);
}

.timeline__body h3 {
  font-size: var(--text-lg);
}

.timeline__description {
  margin-top: var(--space-2);
  white-space: pre-line;
}

.skills {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
  gap: var(--space-6);
}

.skills__group {
  display: grid;
  align-content: start;
  gap: var(--space-3);
}

.skills__list {
  display: grid;
  gap: var(--space-2);
  margin: 0;
  padding: 0;
  list-style: none;
}

.skill {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  padding-bottom: var(--space-2);
  border-bottom: 1px dashed var(--border);
}

.skill__level {
  display: inline-flex;
  gap: 3px;
}

.skill__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--border);
}

.skill__dot.is-filled {
  background: var(--accent);
}
</style>
