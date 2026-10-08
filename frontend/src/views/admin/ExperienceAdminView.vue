<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

import { adminCall } from '@/api/admin'
import { api, type Schemas } from '@/api/client'
import ConfirmButton from '@/components/admin/ConfirmButton.vue'
import LoadState from '@/components/LoadState.vue'
import { useAsync } from '@/composables/useAsync'
import { useSubmit } from '@/composables/useSubmit'
import { formatPeriod } from '@/utils/format'

type Experience = Schemas['AdminExperienceView']

const { data, loading, error, run } = useAsync(() =>
  adminCall((auth) => api.GET('/api/admin/experience', auth)),
)
const { busy, errors, failure, submit } = useSubmit()

const editingId = ref<number | null>(null)
const current = ref(true)
const form = reactive({ company: '', position: '', startDate: '', endDate: '', description: '' })

function edit(job: Experience): void {
  editingId.value = job.id
  current.value = job.isCurrent
  Object.assign(form, {
    company: job.company,
    position: job.position,
    startDate: job.startDate,
    endDate: job.endDate ?? '',
    description: job.description,
  })
}

function reset(): void {
  editingId.value = null
  current.value = true
  Object.assign(form, { company: '', position: '', startDate: '', endDate: '', description: '' })
}

async function save(): Promise<void> {
  const body = { ...form, endDate: current.value || form.endDate === '' ? null : form.endDate }
  const id = editingId.value
  const saved = await submit(() =>
    id === null
      ? adminCall((auth) => api.POST('/api/admin/experience', { ...auth, body }))
      : adminCall((auth) =>
          api.PUT('/api/admin/experience/{id}', {
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

async function remove(job: Experience): Promise<void> {
  await submit(() =>
    adminCall((auth) =>
      api.DELETE('/api/admin/experience/{id}', {
        ...auth,
        params: { path: { id: String(job.id) } },
      }),
    ),
  )
  if (editingId.value === job.id) reset()
  await run()
}

onMounted(run)
</script>

<template>
  <div class="admin-page">
    <header class="admin-page__head">
      <h1>Опыт работы</h1>
    </header>

    <div class="split">
      <div>
        <LoadState :loading="loading && !data" :error="error" @retry="run" />
        <div v-if="data" class="table-wrap">
          <table v-if="data.items.length" class="table">
            <thead>
              <tr>
                <th>Должность и компания</th>
                <th>Период</th>
                <th><span class="visually-hidden">Действия</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="job in data.items" :key="job.id">
                <td class="main">
                  {{ job.position }}
                  <div class="muted">{{ job.company }}</div>
                </td>
                <td class="mono nowrap">{{ formatPeriod(job.startDate, job.endDate) }}</td>
                <td>
                  <div class="actions">
                    <button
                      type="button"
                      class="button button--ghost button--small"
                      @click="edit(job)"
                    >
                      Изменить
                    </button>
                    <ConfirmButton :disabled="busy" @confirm="remove(job)" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="empty">Пока пусто.</p>
        </div>
      </div>

      <form class="panel form-grid" novalidate @submit.prevent="save">
        <h2>{{ editingId === null ? 'Новое место работы' : 'Изменить' }}</h2>
        <div class="field">
          <label class="field__label" for="job-position">Должность</label>
          <input
            id="job-position"
            v-model="form.position"
            class="input"
            maxlength="150"
            :aria-invalid="Boolean(errors.position)"
          />
          <p v-if="errors.position" class="field__error">{{ errors.position }}</p>
        </div>
        <div class="field">
          <label class="field__label" for="job-company">Компания</label>
          <input
            id="job-company"
            v-model="form.company"
            class="input"
            maxlength="150"
            :aria-invalid="Boolean(errors.company)"
          />
          <p v-if="errors.company" class="field__error">{{ errors.company }}</p>
        </div>
        <div class="form-grid form-grid--2">
          <div class="field">
            <label class="field__label" for="job-start">Начало</label>
            <input
              id="job-start"
              v-model="form.startDate"
              class="input"
              type="date"
              :aria-invalid="Boolean(errors.startDate)"
            />
            <p v-if="errors.startDate" class="field__error">{{ errors.startDate }}</p>
          </div>
          <div class="field">
            <label class="field__label" for="job-end">Окончание</label>
            <input
              id="job-end"
              v-model="form.endDate"
              class="input"
              type="date"
              :disabled="current"
              :aria-invalid="Boolean(errors.endDate)"
            />
            <p v-if="errors.endDate" class="field__error">{{ errors.endDate }}</p>
          </div>
        </div>
        <label class="current">
          <input v-model="current" type="checkbox" />
          Работаю здесь сейчас
        </label>
        <div class="field">
          <label class="field__label" for="job-description">Чем занимался</label>
          <textarea id="job-description" v-model="form.description" class="input" rows="5" />
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
    grid-template-columns: 1fr 24rem;
  }
}

.current {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  font-size: var(--text-sm);
}
</style>
