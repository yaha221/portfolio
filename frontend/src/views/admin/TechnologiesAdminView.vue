<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'

import { adminCall } from '@/api/admin'
import { api, type Schemas } from '@/api/client'
import ConfirmButton from '@/components/admin/ConfirmButton.vue'
import LoadState from '@/components/LoadState.vue'
import TechBadge from '@/components/TechBadge.vue'
import { useAsync } from '@/composables/useAsync'
import { useSubmit } from '@/composables/useSubmit'
import { slugify } from '@/utils/slug'

type Technology = Schemas['AdminTechnologyView']

const { data, loading, error, run } = useAsync(() =>
  adminCall((auth) => api.GET('/api/admin/technologies', auth)),
)
const { busy, errors, failure, submit } = useSubmit()

const editingId = ref<number | null>(null)
const slugEdited = ref(false)
const form = reactive({ name: '', slug: '', icon: '' })

watch(
  () => form.name,
  (name) => {
    if (!slugEdited.value) form.slug = slugify(name)
  },
)

function edit(technology: Technology): void {
  editingId.value = technology.id
  slugEdited.value = true
  Object.assign(form, { name: technology.name, slug: technology.slug, icon: technology.icon ?? '' })
}

function reset(): void {
  editingId.value = null
  slugEdited.value = false
  Object.assign(form, { name: '', slug: '', icon: '' })
}

async function save(): Promise<void> {
  const body = { name: form.name, slug: form.slug, icon: form.icon || null }
  const id = editingId.value
  const saved = await submit(() =>
    id === null
      ? adminCall((auth) => api.POST('/api/admin/technologies', { ...auth, body }))
      : adminCall((auth) =>
          api.PUT('/api/admin/technologies/{id}', {
            ...auth,
            params: { path: { id: String(id) } },
            body,
          }),
        ),
  )
  if (saved) {
    reset()
    await run()
  }
}

async function remove(technology: Technology): Promise<void> {
  await submit(() =>
    adminCall((auth) =>
      api.DELETE('/api/admin/technologies/{id}', {
        ...auth,
        params: { path: { id: String(technology.id) } },
      }),
    ),
  )
  if (editingId.value === technology.id) reset()
  await run()
}

onMounted(run)
</script>

<template>
  <div class="admin-page">
    <header class="admin-page__head">
      <h1>Технологии</h1>
    </header>

    <div class="split">
      <div>
        <LoadState :loading="loading && !data" :error="error" @retry="run" />
        <div v-if="data" class="table-wrap">
          <table v-if="data.items.length" class="table">
            <thead>
              <tr>
                <th>Технология</th>
                <th>Slug</th>
                <th><span class="visually-hidden">Действия</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="technology in data.items" :key="technology.id">
                <td><TechBadge :name="technology.name" :icon="technology.icon" /></td>
                <td class="mono">{{ technology.slug }}</td>
                <td>
                  <div class="actions">
                    <button
                      type="button"
                      class="button button--ghost button--small"
                      @click="edit(technology)"
                    >
                      Изменить
                    </button>
                    <ConfirmButton :disabled="busy" @confirm="remove(technology)" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="empty">Технологий пока нет.</p>
        </div>
      </div>

      <form class="panel form-grid" novalidate @submit.prevent="save">
        <h2>{{ editingId === null ? 'Новая технология' : 'Изменить технологию' }}</h2>
        <div class="field">
          <label class="field__label" for="tech-name">Название</label>
          <input
            id="tech-name"
            v-model="form.name"
            class="input"
            maxlength="100"
            :aria-invalid="Boolean(errors.name)"
          />
          <p v-if="errors.name" class="field__error">{{ errors.name }}</p>
        </div>
        <div class="field">
          <label class="field__label" for="tech-slug">Slug</label>
          <input
            id="tech-slug"
            v-model="form.slug"
            class="input mono"
            maxlength="100"
            :aria-invalid="Boolean(errors.slug)"
            @input="slugEdited = true"
          />
          <p v-if="errors.slug" class="field__error">{{ errors.slug }}</p>
          <p v-else class="field__hint">Для фильтра: /projects?technology={{ form.slug || '…' }}</p>
        </div>
        <div class="field">
          <label class="field__label" for="tech-icon">Иконка</label>
          <input
            id="tech-icon"
            v-model="form.icon"
            class="input mono"
            maxlength="100"
            :aria-invalid="Boolean(errors.icon)"
          />
          <p v-if="errors.icon" class="field__error">{{ errors.icon }}</p>
          <p v-else class="field__hint">
            Slug из
            <a href="https://simpleicons.org" target="_blank" rel="noopener">simpleicons.org</a>,
            например symfony
          </p>
          <TechBadge
            v-if="form.name"
            :name="form.name"
            :icon="form.icon || null"
            class="icon-preview"
          />
        </div>
        <p v-if="failure" class="alert alert--error" role="alert">{{ failure }}</p>
        <div class="form-actions">
          <button type="submit" class="button" :disabled="busy">
            {{ editingId === null ? 'Добавить' : 'Сохранить' }}
          </button>
          <button
            v-if="editingId !== null"
            type="button"
            class="button button--ghost"
            @click="reset"
          >
            Отмена
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.split {
  display: grid;
  gap: var(--space-5);
  align-items: start;
}

@media (min-width: 1100px) {
  .split {
    grid-template-columns: 1fr 22rem;
  }
}

.icon-preview {
  justify-self: start;
  margin-top: var(--space-2);
}
</style>
