<script setup lang="ts">
import { computed, watch } from 'vue'
import { RouterLink } from 'vue-router'

import { api } from '@/api/client'
import { ApiError, unwrap } from '@/api/problem'
import ImageGallery from '@/components/ImageGallery.vue'
import LoadState from '@/components/LoadState.vue'
import MarkdownContent from '@/components/MarkdownContent.vue'
import TechBadge from '@/components/TechBadge.vue'
import { useAsync } from '@/composables/useAsync'
import { site } from '@/config/site'

const props = defineProps<{ slug: string }>()

const {
  data: project,
  loading,
  error,
  run,
} = useAsync(async () =>
  unwrap(await api.GET('/api/projects/{slug}', { params: { path: { slug: props.slug } } })),
)

const notFound = computed(() => error.value instanceof ApiError && error.value.status === 404)
const cover = computed(
  () => project.value?.coverThumbnails?.large ?? project.value?.coverImageUrl ?? null,
)

watch(() => props.slug, run, { immediate: true })
watch(project, (loaded) => {
  if (loaded) document.title = `${loaded.title} — ${site.name}`
})
</script>

<template>
  <div class="container">
    <RouterLink :to="{ name: 'projects' }" class="back">← Все проекты</RouterLink>

    <section v-if="notFound" class="missing">
      <h1>Проект не найден</h1>
      <p class="muted">Возможно, его сняли с публикации или ссылка устарела.</p>
    </section>
    <LoadState v-else :loading="loading" :error="error" @retry="run" />

    <article v-if="project && !loading" class="project">
      <header class="project__head">
        <h1 class="project__title">{{ project.title }}</h1>
        <p class="project__lead">{{ project.description }}</p>
        <ul v-if="project.technologies.length" class="project__badges">
          <li v-for="technology in project.technologies" :key="technology.slug">
            <RouterLink :to="{ name: 'projects', query: { technology: technology.slug } }">
              <TechBadge :name="technology.name" :icon="technology.icon" />
            </RouterLink>
          </li>
        </ul>
        <div v-if="project.repoUrl || project.demoUrl" class="project__links">
          <a
            v-if="project.demoUrl"
            :href="project.demoUrl"
            class="button"
            target="_blank"
            rel="noopener"
            >Открыть демо</a
          >
          <a
            v-if="project.repoUrl"
            :href="project.repoUrl"
            class="button button--ghost"
            target="_blank"
            rel="noopener"
          >
            Исходный код
          </a>
        </div>
      </header>

      <img v-if="cover" :src="cover" alt="" class="project__cover" />

      <MarkdownContent v-if="project.content" :source="project.content" />

      <section
        v-if="project.images.length"
        class="project__gallery"
        aria-labelledby="gallery-title"
      >
        <h2 id="gallery-title">Скриншоты</h2>
        <ImageGallery :images="project.images" />
      </section>
    </article>
  </div>
</template>

<style scoped>
.back {
  display: inline-block;
  margin-bottom: var(--space-5);
  font-size: var(--text-sm);
  text-decoration: none;
}

.missing {
  display: grid;
  gap: var(--space-3);
}

.project {
  display: grid;
  gap: var(--space-6);
}

.project__head {
  display: grid;
  gap: var(--space-4);
  max-width: 48rem;
}

.project__title {
  font-size: var(--text-3xl);
  letter-spacing: -0.02em;
}

.project__lead {
  font-size: var(--text-lg);
  color: var(--text-muted);
}

.project__badges {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
  margin: 0;
  padding: 0;
  list-style: none;
}

.project__badges a {
  text-decoration: none;
}

.project__links {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
}

.project__cover {
  width: 100%;
  max-height: 560px;
  object-fit: cover;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
}

.project__gallery {
  display: grid;
  gap: var(--space-4);
}

.project__gallery h2 {
  font-size: var(--text-xl);
}
</style>
