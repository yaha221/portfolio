<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { adminCall } from '@/api/admin'
import { api, type Schemas } from '@/api/client'
import ConfirmButton from '@/components/admin/ConfirmButton.vue'
import LoadState from '@/components/LoadState.vue'
import { useAsync } from '@/composables/useAsync'
import { useSubmit } from '@/composables/useSubmit'

type Project = Schemas['AdminProjectView']

const { data, loading, error, run } = useAsync(async () =>
  adminCall((auth) => api.GET('/api/admin/projects', auth)),
)
const { busy, failure, submit } = useSubmit()
const pendingId = ref<number | null>(null)

onMounted(run)

function replace(updated: Project): void {
  if (!data.value) return
  data.value.items = data.value.items.map((item) => (item.id === updated.id ? updated : item))
}

async function patch(project: Project, body: Schemas['ProjectPatchRequest']): Promise<void> {
  pendingId.value = project.id
  const updated = await submit(() =>
    adminCall((auth) =>
      api.PATCH('/api/admin/projects/{id}', {
        ...auth,
        params: { path: { id: String(project.id) } },
        body,
      }),
    ),
  )
  pendingId.value = null
  // On failure reload, so the table shows the real state again
  if (updated) replace(updated)
  else await run()
}

async function remove(project: Project): Promise<void> {
  await submit(() =>
    adminCall((auth) =>
      api.DELETE('/api/admin/projects/{id}', {
        ...auth,
        params: { path: { id: String(project.id) } },
      }),
    ),
  )
  await run()
}

function onSortOrderChange(project: Project, event: Event): void {
  const value = Number((event.target as HTMLInputElement).value)
  if (Number.isInteger(value) && value >= 0 && value !== project.sortOrder) {
    // null = leave unchanged (the API's PATCH semantics)
    void patch(project, { isPublished: null, sortOrder: value })
  }
}

const updatedAt = new Intl.DateTimeFormat('ru', { day: 'numeric', month: 'short', year: 'numeric' })
</script>

<template>
  <div class="admin-page">
    <header class="admin-page__head">
      <h1>Проекты</h1>
      <RouterLink :to="{ name: 'admin-project-new' }" class="button">Новый проект</RouterLink>
    </header>

    <p v-if="failure" class="alert alert--error" role="alert">{{ failure }}</p>
    <LoadState :loading="loading && !data" :error="error" @retry="run" />

    <div v-if="data" class="table-wrap">
      <table v-if="data.items.length" class="table">
        <thead>
          <tr>
            <th class="num">Порядок</th>
            <th>Проект</th>
            <th>Статус</th>
            <th>Обновлён</th>
            <th><span class="visually-hidden">Действия</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="project in data.items" :key="project.id">
            <td class="num">
              <input
                class="input order"
                type="number"
                min="0"
                :value="project.sortOrder"
                :aria-label="`Порядок проекта ${project.title}`"
                :disabled="pendingId === project.id"
                @change="onSortOrderChange(project, $event)"
              />
            </td>
            <td class="main">
              <RouterLink :to="{ name: 'admin-project-edit', params: { id: project.id } }">{{
                project.title
              }}</RouterLink>
              <div class="mono muted">/{{ project.slug }}</div>
            </td>
            <td>
              <span class="chip" :class="project.isPublished ? 'chip--success' : 'chip--muted'">
                {{ project.isPublished ? 'Опубликован' : 'Черновик' }}
              </span>
            </td>
            <td class="muted nowrap">{{ updatedAt.format(new Date(project.updatedAt)) }}</td>
            <td>
              <div class="actions">
                <button
                  type="button"
                  class="button button--ghost button--small"
                  :disabled="busy && pendingId === project.id"
                  @click="patch(project, { isPublished: !project.isPublished, sortOrder: null })"
                >
                  {{ project.isPublished ? 'Снять с публикации' : 'Опубликовать' }}
                </button>
                <ConfirmButton :disabled="busy" @confirm="remove(project)" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else class="empty">Проектов пока нет. Создайте первый.</p>
    </div>
  </div>
</template>

<style scoped>
.order {
  width: 4.5rem;
  padding: 0.3rem 0.5rem;
  text-align: right;
}
</style>
