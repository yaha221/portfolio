<script setup lang="ts">
import { ref, watch } from 'vue'

import { adminCall } from '@/api/admin'
import { api } from '@/api/client'
import LoadState from '@/components/LoadState.vue'
import { useAsync } from '@/composables/useAsync'

const PER_PAGE = 20
const STATUS = {
  new: { label: 'Письмо в очереди', chip: 'chip--warning' },
  sent: { label: 'Письмо отправлено', chip: 'chip--success' },
  failed: { label: 'Письмо не ушло', chip: 'chip--danger' },
} as const

const page = ref(1)
const { data, loading, error, run } = useAsync(() =>
  adminCall((auth) =>
    api.GET('/api/admin/messages', {
      ...auth,
      params: { query: { page: page.value, perPage: PER_PAGE } },
    }),
  ),
)

const sentAt = new Intl.DateTimeFormat('ru', {
  day: 'numeric',
  month: 'long',
  hour: '2-digit',
  minute: '2-digit',
})

watch(page, run, { immediate: true })

function status(value: string) {
  return STATUS[value as keyof typeof STATUS] ?? { label: value, chip: 'chip--muted' }
}
</script>

<template>
  <div class="admin-page">
    <header class="admin-page__head">
      <h1>Сообщения</h1>
      <p v-if="data" class="muted">Всего: {{ data.meta.total }}</p>
    </header>

    <LoadState :loading="loading && !data" :error="error" @retry="run" />

    <template v-if="data">
      <p v-if="!data.items.length" class="panel empty">Сообщений пока нет.</p>
      <ul class="messages">
        <li v-for="message in data.items" :key="message.id" class="panel message">
          <div class="message__head">
            <div>
              <strong>{{ message.name }}</strong>
              <a :href="`mailto:${message.email}`" class="message__email">{{ message.email }}</a>
            </div>
            <div class="message__meta">
              <span class="chip" :class="status(message.status).chip">{{
                status(message.status).label
              }}</span>
              <time :datetime="message.createdAt" class="muted">{{
                sentAt.format(new Date(message.createdAt))
              }}</time>
            </div>
          </div>
          <p class="message__text">{{ message.message }}</p>
        </li>
      </ul>

      <div v-if="data.meta.pages > 1" class="form-actions pager">
        <button
          type="button"
          class="button button--ghost button--small"
          :disabled="page <= 1"
          @click="page--"
        >
          ← Новее
        </button>
        <span class="muted mono">{{ data.meta.page }} / {{ data.meta.pages }}</span>
        <button
          type="button"
          class="button button--ghost button--small"
          :disabled="page >= data.meta.pages"
          @click="page++"
        >
          Старше →
        </button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.messages {
  display: grid;
  gap: var(--space-3);
  margin: 0;
  padding: 0;
  list-style: none;
}

.message {
  display: grid;
  gap: var(--space-3);
}

.message__head {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: var(--space-2) var(--space-4);
}

.message__email {
  margin-left: var(--space-2);
  font-size: var(--text-sm);
}

.message__meta {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  font-size: var(--text-sm);
}

.message__text {
  white-space: pre-line;
}

.pager {
  justify-content: center;
}
</style>
