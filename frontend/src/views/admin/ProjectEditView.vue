<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { adminCall } from '@/api/admin'
import { api, type Schemas } from '@/api/client'
import ConfirmButton from '@/components/admin/ConfirmButton.vue'
import MarkdownContent from '@/components/MarkdownContent.vue'
import { useAsync } from '@/composables/useAsync'
import { useSubmit } from '@/composables/useSubmit'
import { slugify } from '@/utils/slug'

type Project = Schemas['AdminProjectView']

const props = defineProps<{ id?: string }>()
const router = useRouter()

const DESCRIPTION_MAX = 500

const form = reactive({
  slug: '',
  title: '',
  description: '',
  content: '',
  repoUrl: '',
  demoUrl: '',
  isPublished: false,
  sortOrder: 0,
  technologies: [] as string[],
})
const project = ref<Project | null>(null)
const slugEdited = ref(false)
const preview = ref(false)
const saved = ref(false)

const { busy, errors, failure, submit } = useSubmit()
const media = useSubmit()
const { data: technologies, run: loadTechnologies } = useAsync(() =>
  adminCall((auth) => api.GET('/api/admin/technologies', auth)),
)

const isNew = computed(() => props.id === undefined)
const technologyError = computed(
  () =>
    errors.value.technologies ??
    Object.entries(errors.value).find(([field]) => field.startsWith('technologies['))?.[1],
)

// Until the slug is edited by hand it follows the title
watch(
  () => form.title,
  (title) => {
    if (!slugEdited.value) form.slug = slugify(title)
  },
)

function fill(loaded: Project): void {
  project.value = loaded
  Object.assign(form, {
    slug: loaded.slug,
    title: loaded.title,
    description: loaded.description,
    content: loaded.content,
    repoUrl: loaded.repoUrl ?? '',
    demoUrl: loaded.demoUrl ?? '',
    isPublished: loaded.isPublished,
    sortOrder: loaded.sortOrder,
    technologies: loaded.technologies.map((technology) => technology.slug),
  })
  slugEdited.value = true
}

async function load(): Promise<void> {
  const id = props.id
  if (id === undefined) return
  const loaded = await submit(() =>
    adminCall((auth) => api.GET('/api/admin/projects/{id}', { ...auth, params: { path: { id } } })),
  )
  if (loaded) fill(loaded)
}

function body(): Schemas['ProjectRequest'] {
  return { ...form, repoUrl: form.repoUrl || null, demoUrl: form.demoUrl || null }
}

async function save(): Promise<void> {
  saved.value = false
  const id = props.id
  const result = await submit(() =>
    id === undefined
      ? adminCall((auth) => api.POST('/api/admin/projects', { ...auth, body: body() }))
      : adminCall((auth) =>
          api.PUT('/api/admin/projects/{id}', { ...auth, params: { path: { id } }, body: body() }),
        ),
  )
  if (!result) return

  fill(result)
  saved.value = true
  if (id === undefined) {
    await router.replace({ name: 'admin-project-edit', params: { id: result.id } })
  }
}

function asForm(file: File): FormData {
  const data = new FormData()
  data.append('file', file)
  return data
}

async function uploadImages(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const id = String(project.value?.id ?? '')
  for (const file of Array.from(input.files ?? [])) {
    const uploaded = await media.submit(() =>
      adminCall((auth) =>
        api.POST('/api/admin/projects/{id}/images', {
          ...auth,
          params: { path: { id } },
          body: { file: '' },
          bodySerializer: () => asForm(file),
        }),
      ),
    )
    if (uploaded === undefined) break
  }
  input.value = ''
  await load()
}

async function uploadCover(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  const id = String(project.value?.id ?? '')
  if (file) {
    const updated = await media.submit(() =>
      adminCall((auth) =>
        api.POST('/api/admin/projects/{id}/cover', {
          ...auth,
          params: { path: { id } },
          body: { file: '' },
          bodySerializer: () => asForm(file),
        }),
      ),
    )
    if (updated) project.value = updated
  }
  input.value = ''
}

async function removeCover(): Promise<void> {
  const id = String(project.value?.id ?? '')
  await media.submit(() =>
    adminCall((auth) =>
      api.DELETE('/api/admin/projects/{id}/cover', { ...auth, params: { path: { id } } }),
    ),
  )
  await load()
}

async function removeImage(imageId: number): Promise<void> {
  const id = String(project.value?.id ?? '')
  await media.submit(() =>
    adminCall((auth) =>
      api.DELETE('/api/admin/projects/{id}/images/{imageId}', {
        ...auth,
        params: { path: { id, imageId: String(imageId) } },
      }),
    ),
  )
  await load()
}

onMounted(() => {
  void loadTechnologies()
  void load()
})
</script>

<template>
  <div class="admin-page">
    <header class="admin-page__head">
      <div>
        <RouterLink :to="{ name: 'admin-projects' }" class="back">← Проекты</RouterLink>
        <h1>{{ isNew ? 'Новый проект' : form.title || 'Проект' }}</h1>
      </div>
      <a
        v-if="project?.isPublished"
        :href="`/projects/${project.slug}`"
        target="_blank"
        rel="noopener"
        class="button button--ghost"
      >
        Открыть на сайте ↗
      </a>
    </header>

    <form class="panel form-grid" novalidate @submit.prevent="save">
      <div class="form-grid form-grid--2">
        <div class="field">
          <label class="field__label" for="project-title">Название</label>
          <input
            id="project-title"
            v-model="form.title"
            class="input"
            maxlength="255"
            :aria-invalid="Boolean(errors.title)"
          />
          <p v-if="errors.title" class="field__error">{{ errors.title }}</p>
        </div>
        <div class="field">
          <label class="field__label" for="project-slug">Адрес (slug)</label>
          <input
            id="project-slug"
            v-model="form.slug"
            class="input mono"
            maxlength="255"
            :aria-invalid="Boolean(errors.slug)"
            @input="slugEdited = true"
          />
          <p v-if="errors.slug" class="field__error">{{ errors.slug }}</p>
          <p v-else class="field__hint">/projects/{{ form.slug || '…' }}</p>
        </div>
      </div>

      <div class="field">
        <label class="field__label" for="project-description">Кратко (для карточки)</label>
        <textarea
          id="project-description"
          v-model="form.description"
          class="input"
          rows="3"
          :maxlength="DESCRIPTION_MAX"
          :aria-invalid="Boolean(errors.description)"
        />
        <p v-if="errors.description" class="field__error">{{ errors.description }}</p>
        <p v-else class="field__hint">{{ form.description.length }} / {{ DESCRIPTION_MAX }}</p>
      </div>

      <div class="field">
        <div class="content-head">
          <label class="field__label" for="project-content">Описание (Markdown)</label>
          <button
            type="button"
            class="button button--ghost button--small"
            @click="preview = !preview"
          >
            {{ preview ? 'Редактировать' : 'Предпросмотр' }}
          </button>
        </div>
        <div v-if="preview" class="preview">
          <MarkdownContent v-if="form.content" :source="form.content" />
          <p v-else class="muted">Пусто</p>
        </div>
        <textarea
          v-else
          id="project-content"
          v-model="form.content"
          class="input mono content"
          rows="14"
        />
      </div>

      <div class="form-grid form-grid--2">
        <div class="field">
          <label class="field__label" for="project-repo">Репозиторий</label>
          <input
            id="project-repo"
            v-model="form.repoUrl"
            class="input"
            type="url"
            placeholder="https://github.com/…"
            :aria-invalid="Boolean(errors.repoUrl)"
          />
          <p v-if="errors.repoUrl" class="field__error">{{ errors.repoUrl }}</p>
        </div>
        <div class="field">
          <label class="field__label" for="project-demo">Демо</label>
          <input
            id="project-demo"
            v-model="form.demoUrl"
            class="input"
            type="url"
            placeholder="https://…"
            :aria-invalid="Boolean(errors.demoUrl)"
          />
          <p v-if="errors.demoUrl" class="field__error">{{ errors.demoUrl }}</p>
        </div>
      </div>

      <fieldset class="field technologies">
        <legend class="field__label">Технологии</legend>
        <div class="technologies__list">
          <label
            v-for="technology in technologies?.items ?? []"
            :key="technology.slug"
            class="technologies__item"
          >
            <input v-model="form.technologies" type="checkbox" :value="technology.slug" />
            {{ technology.name }}
          </label>
        </div>
        <p v-if="technologyError" class="field__error">{{ technologyError }}</p>
        <p v-else-if="technologies && !technologies.items.length" class="field__hint">
          Сначала добавьте технологии в разделе «Технологии».
        </p>
      </fieldset>

      <div class="form-grid form-grid--2">
        <div class="field">
          <label class="field__label" for="project-order">Порядок в списке</label>
          <input
            id="project-order"
            v-model.number="form.sortOrder"
            class="input"
            type="number"
            min="0"
            :aria-invalid="Boolean(errors.sortOrder)"
          />
          <p v-if="errors.sortOrder" class="field__error">{{ errors.sortOrder }}</p>
          <p v-else class="field__hint">Меньше — выше</p>
        </div>
        <label class="publish">
          <input v-model="form.isPublished" type="checkbox" />
          Опубликован на сайте
        </label>
      </div>

      <p v-if="failure" class="alert alert--error" role="alert">{{ failure }}</p>
      <div class="form-actions">
        <button type="submit" class="button" :disabled="busy">
          {{ busy ? 'Сохраняю…' : isNew ? 'Создать' : 'Сохранить' }}
        </button>
        <span v-if="saved" class="chip chip--success" role="status">Сохранено</span>
      </div>
    </form>

    <section v-if="project" class="panel media" aria-labelledby="media-title">
      <div class="media__head">
        <h2 id="media-title">Обложка и скриншоты</h2>
        <button type="button" class="button button--ghost button--small" @click="load">
          Обновить
        </button>
      </div>
      <p class="field__hint">
        JPEG, PNG или WebP до 10 МБ. Уменьшенные копии делает фоновый обработчик — через пару секунд
        нажмите «Обновить».
      </p>
      <p v-if="media.failure.value" class="alert alert--error" role="alert">
        {{ media.errors.value.file ?? media.failure.value }}
      </p>

      <div class="cover">
        <img
          v-if="project.coverImageUrl"
          :src="project.coverThumbnails.small ?? project.coverImageUrl"
          alt="Обложка"
        />
        <div v-else class="cover__empty">Нет обложки</div>
        <div class="form-actions">
          <label class="button button--ghost button--small">
            {{ project.coverImageUrl ? 'Заменить обложку' : 'Загрузить обложку' }}
            <input
              type="file"
              accept="image/jpeg,image/png,image/webp"
              class="visually-hidden"
              :disabled="media.busy.value"
              @change="uploadCover"
            />
          </label>
          <ConfirmButton
            v-if="project.coverImageUrl"
            label="Убрать"
            confirm-label="Убрать обложку?"
            @confirm="removeCover"
          />
          <span
            v-if="project.coverImageUrl && !Object.keys(project.coverThumbnails).length"
            class="chip chip--warning"
            >превью готовится</span
          >
        </div>
      </div>

      <ul class="images">
        <li v-for="image in project.images" :key="image.id" class="images__item">
          <img :src="image.thumbnails.small ?? image.url" alt="" loading="lazy" />
          <div class="images__meta">
            <span v-if="!Object.keys(image.thumbnails).length" class="chip chip--warning"
              >превью готовится</span
            >
            <ConfirmButton @confirm="removeImage(image.id)" />
          </div>
        </li>
      </ul>
      <label class="button button--ghost">
        {{ media.busy.value ? 'Загружаю…' : 'Добавить скриншоты' }}
        <input
          type="file"
          accept="image/jpeg,image/png,image/webp"
          multiple
          class="visually-hidden"
          :disabled="media.busy.value"
          @change="uploadImages"
        />
      </label>
    </section>
    <p v-else-if="isNew" class="muted">
      Обложку и скриншоты можно добавить после создания проекта.
    </p>
  </div>
</template>

<style scoped>
.back {
  font-size: var(--text-sm);
  text-decoration: none;
}

.content-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}

.content {
  min-height: 18rem;
}

.preview {
  min-height: 18rem;
  padding: var(--space-4);
  border: 1px dashed var(--border);
  border-radius: var(--radius-sm);
}

.technologies {
  margin: 0;
  padding: 0;
  border: none;
}

.technologies__list {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2) var(--space-4);
  margin-top: var(--space-2);
}

.technologies__item,
.publish {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  font-size: var(--text-sm);
}

.publish {
  align-self: center;
  font-weight: 600;
}

.media {
  display: grid;
  gap: var(--space-4);
}

.media__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.media__head h2 {
  font-size: var(--text-lg);
}

.cover {
  display: grid;
  gap: var(--space-3);
  justify-items: start;
}

.cover img,
.cover__empty {
  width: min(100%, 360px);
  aspect-ratio: 16 / 9;
  object-fit: cover;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}

.cover__empty {
  display: grid;
  place-items: center;
  color: var(--text-muted);
  background: var(--surface-muted);
  font-size: var(--text-sm);
}

.images {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: var(--space-3);
  margin: 0;
  padding: 0;
  list-style: none;
}

.images__item {
  display: grid;
  gap: var(--space-2);
}

.images__item img {
  aspect-ratio: 4 / 3;
  object-fit: cover;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
}

.images__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-2);
}

label.button {
  position: relative;
  justify-self: start;
}
</style>
