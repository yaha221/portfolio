<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'

import { adminCall } from '@/api/admin'
import { api, type Schemas } from '@/api/client'
import ConfirmButton from '@/components/admin/ConfirmButton.vue'
import LoadState from '@/components/LoadState.vue'
import { useAsync } from '@/composables/useAsync'
import { useSubmit } from '@/composables/useSubmit'

type Skill = Schemas['AdminSkillView']

const LEVELS = [1, 2, 3, 4, 5]

const { data, loading, error, run } = useAsync(() =>
  adminCall((auth) => api.GET('/api/admin/skills', auth)),
)
const { busy, errors, failure, submit } = useSubmit()

const editingId = ref<number | null>(null)
const form = reactive({ name: '', category: '', level: 3, sortOrder: 0 })

// Suggest existing categories, so "Backend" doesn't turn into "backend" and "Бэкенд" over time
const categories = computed(() => [
  ...new Set((data.value?.items ?? []).map((skill) => skill.category)),
])

function edit(skill: Skill): void {
  editingId.value = skill.id
  Object.assign(form, {
    name: skill.name,
    category: skill.category,
    level: skill.level,
    sortOrder: skill.sortOrder,
  })
}

function reset(): void {
  editingId.value = null
  Object.assign(form, {
    name: '',
    category: '',
    level: 3,
    sortOrder: (data.value?.items.length ?? 0) + 1,
  })
}

async function save(): Promise<void> {
  const body = { ...form }
  const id = editingId.value
  const saved = await submit(() =>
    id === null
      ? adminCall((auth) => api.POST('/api/admin/skills', { ...auth, body }))
      : adminCall((auth) =>
          api.PUT('/api/admin/skills/{id}', {
            ...auth,
            params: { path: { id: String(id) } },
            body,
          }),
        ),
  )
  if (saved) {
    await run()
    reset()
  }
}

async function remove(skill: Skill): Promise<void> {
  await submit(() =>
    adminCall((auth) =>
      api.DELETE('/api/admin/skills/{id}', { ...auth, params: { path: { id: String(skill.id) } } }),
    ),
  )
  if (editingId.value === skill.id) reset()
  await run()
}

onMounted(async () => {
  await run()
  reset()
})
</script>

<template>
  <div class="admin-page">
    <header class="admin-page__head">
      <h1>Навыки</h1>
    </header>

    <div class="split">
      <div>
        <LoadState :loading="loading && !data" :error="error" @retry="run" />
        <div v-if="data" class="table-wrap">
          <table v-if="data.items.length" class="table">
            <thead>
              <tr>
                <th class="num">#</th>
                <th>Навык</th>
                <th>Категория</th>
                <th class="num">Уровень</th>
                <th><span class="visually-hidden">Действия</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="skill in data.items" :key="skill.id">
                <td class="num muted">{{ skill.sortOrder }}</td>
                <td>{{ skill.name }}</td>
                <td class="muted">{{ skill.category }}</td>
                <td class="num">{{ skill.level }} / 5</td>
                <td>
                  <div class="actions">
                    <button
                      type="button"
                      class="button button--ghost button--small"
                      @click="edit(skill)"
                    >
                      Изменить
                    </button>
                    <ConfirmButton :disabled="busy" @confirm="remove(skill)" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="empty">Навыков пока нет.</p>
        </div>
      </div>

      <form class="panel form-grid" novalidate @submit.prevent="save">
        <h2>{{ editingId === null ? 'Новый навык' : 'Изменить навык' }}</h2>
        <div class="field">
          <label class="field__label" for="skill-name">Название</label>
          <input
            id="skill-name"
            v-model="form.name"
            class="input"
            maxlength="100"
            :aria-invalid="Boolean(errors.name)"
          />
          <p v-if="errors.name" class="field__error">{{ errors.name }}</p>
        </div>
        <div class="field">
          <label class="field__label" for="skill-category">Категория</label>
          <input
            id="skill-category"
            v-model="form.category"
            class="input"
            maxlength="50"
            list="skill-categories"
            :aria-invalid="Boolean(errors.category)"
          />
          <datalist id="skill-categories">
            <option v-for="category in categories" :key="category" :value="category" />
          </datalist>
          <p v-if="errors.category" class="field__error">{{ errors.category }}</p>
        </div>
        <div class="form-grid form-grid--2">
          <div class="field">
            <label class="field__label" for="skill-level">Уровень</label>
            <select id="skill-level" v-model.number="form.level" class="input">
              <option v-for="level in LEVELS" :key="level" :value="level">{{ level }}</option>
            </select>
            <p v-if="errors.level" class="field__error">{{ errors.level }}</p>
          </div>
          <div class="field">
            <label class="field__label" for="skill-order">Порядок</label>
            <input
              id="skill-order"
              v-model.number="form.sortOrder"
              class="input"
              type="number"
              min="0"
            />
            <p v-if="errors.sortOrder" class="field__error">{{ errors.sortOrder }}</p>
          </div>
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
</style>
