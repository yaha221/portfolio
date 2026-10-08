<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

import type { Schemas } from '@/api/client'
import TechBadge from '@/components/TechBadge.vue'

const props = defineProps<{ project: Schemas['ProjectListItem'] }>()

const MAX_BADGES = 4

// The small thumbnail is ~480 px wide: plenty for a card, and far lighter than the original
const cover = computed(
  () => props.project.coverThumbnails?.small ?? props.project.coverImageUrl ?? null,
)
const badges = computed(() => props.project.technologies.slice(0, MAX_BADGES))
const hiddenBadges = computed(() => props.project.technologies.length - badges.value.length)
</script>

<template>
  <article class="card">
    <RouterLink :to="{ name: 'project', params: { slug: project.slug } }" class="card__link">
      <div class="card__cover">
        <img v-if="cover" :src="cover" alt="" loading="lazy" />
        <span v-else class="card__placeholder" aria-hidden="true">{{
          project.title.charAt(0)
        }}</span>
      </div>
      <div class="card__body">
        <h3 class="card__title">{{ project.title }}</h3>
        <p class="card__description">{{ project.description }}</p>
      </div>
    </RouterLink>
    <ul v-if="project.technologies.length" class="card__badges">
      <li v-for="technology in badges" :key="technology.slug">
        <TechBadge :name="technology.name" :icon="technology.icon" />
      </li>
      <li v-if="hiddenBadges > 0" class="muted card__more">+{{ hiddenBadges }}</li>
    </ul>
  </article>
</template>

<style scoped>
.card {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  overflow: hidden;
  transition:
    border-color 0.15s,
    box-shadow 0.15s;
}

.card:hover {
  border-color: color-mix(in srgb, var(--accent) 40%, var(--border));
  box-shadow: var(--shadow);
}

.card__link {
  color: inherit;
  text-decoration: none;
  flex: 1;
}

.card__cover {
  aspect-ratio: 16 / 9;
  background: var(--surface-muted);
  border-bottom: 1px solid var(--border);
}

.card__cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card__placeholder {
  display: grid;
  place-items: center;
  height: 100%;
  font-size: var(--text-3xl);
  font-weight: 700;
  color: var(--accent);
  background: var(--accent-soft);
}

.card__body {
  display: grid;
  gap: var(--space-2);
  padding: var(--space-4) var(--space-4) var(--space-3);
}

.card__title {
  font-size: var(--text-lg);
}

.card__description {
  color: var(--text-muted);
  font-size: var(--text-sm);
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card__badges {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
  margin: 0;
  padding: 0 var(--space-4) var(--space-4);
  list-style: none;
}

.card__more {
  align-self: center;
  font-size: var(--text-xs);
  font-family: var(--font-mono);
}
</style>
