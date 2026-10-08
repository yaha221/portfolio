<script setup lang="ts">
import { reactive, ref } from 'vue'

import { api } from '@/api/client'
import { ApiError, unwrap } from '@/api/problem'

const form = reactive({ name: '', email: '', message: '', website: '' })
const errors = ref<Record<string, string>>({})
const state = ref<'idle' | 'sending' | 'sent'>('idle')
const failure = ref<string | null>(null)

const FIELD_MESSAGES: Record<string, string> = {
  name: 'Укажите имя.',
  email: 'Нужен корректный email, чтобы я мог ответить.',
  message: 'Напишите сообщение: от 10 до 5000 символов.',
}

async function submit(): Promise<void> {
  state.value = 'sending'
  errors.value = {}
  failure.value = null

  try {
    unwrap(await api.POST('/api/contact', { body: { ...form } }))
    state.value = 'sent'
    Object.assign(form, { name: '', email: '', message: '' })
  } catch (caught) {
    state.value = 'idle'
    if (caught instanceof ApiError && caught.status === 422) {
      // Russian text per field instead of the API's English messages
      errors.value = Object.fromEntries(
        Object.keys(caught.fieldErrors).map((field) => [
          field,
          FIELD_MESSAGES[field] ?? 'Проверьте это поле.',
        ]),
      )
    } else if (caught instanceof ApiError && caught.status === 429) {
      failure.value = 'Слишком много сообщений подряд. Попробуйте через несколько минут.'
    } else {
      failure.value = 'Не получилось отправить. Проверьте соединение и попробуйте ещё раз.'
    }
  }
}
</script>

<template>
  <div class="container contact">
    <header class="contact__head">
      <h1>Контакты</h1>
      <p class="muted">
        Напишите о проекте, вакансии или просто вопрос — отвечу на email, обычно в течение дня.
      </p>
    </header>

    <div v-if="state === 'sent'" class="alert alert--success contact__sent" role="status">
      <p>Сообщение отправлено. Подтверждение придёт вам на почту.</p>
      <button type="button" class="button button--ghost button--small" @click="state = 'idle'">
        Написать ещё
      </button>
    </div>

    <form v-else class="contact__form" novalidate @submit.prevent="submit">
      <div class="field">
        <label class="field__label" for="contact-name">Имя</label>
        <input
          id="contact-name"
          v-model="form.name"
          class="input"
          autocomplete="name"
          maxlength="100"
          required
          :aria-invalid="Boolean(errors.name)"
          aria-describedby="contact-name-error"
        />
        <p v-if="errors.name" id="contact-name-error" class="field__error">{{ errors.name }}</p>
      </div>

      <div class="field">
        <label class="field__label" for="contact-email">Email</label>
        <input
          id="contact-email"
          v-model="form.email"
          class="input"
          type="email"
          autocomplete="email"
          maxlength="180"
          required
          :aria-invalid="Boolean(errors.email)"
          aria-describedby="contact-email-error"
        />
        <p v-if="errors.email" id="contact-email-error" class="field__error">{{ errors.email }}</p>
      </div>

      <div class="field">
        <label class="field__label" for="contact-message">Сообщение</label>
        <textarea
          id="contact-message"
          v-model="form.message"
          class="input"
          rows="7"
          maxlength="5000"
          required
          :aria-invalid="Boolean(errors.message)"
          aria-describedby="contact-message-error"
        />
        <p v-if="errors.message" id="contact-message-error" class="field__error">
          {{ errors.message }}
        </p>
      </div>

      <!-- Trap for bots: hidden from people and screen readers, bots fill every field -->
      <div class="visually-hidden" aria-hidden="true">
        <label for="contact-website">Сайт</label>
        <input id="contact-website" v-model="form.website" tabindex="-1" autocomplete="off" />
      </div>

      <p v-if="failure" class="alert alert--error" role="alert">{{ failure }}</p>

      <div>
        <button type="submit" class="button" :disabled="state === 'sending'">
          {{ state === 'sending' ? 'Отправляю…' : 'Отправить' }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.contact {
  display: grid;
  gap: var(--space-6);
  max-width: 40rem;
}

.contact__head {
  display: grid;
  gap: var(--space-3);
}

.contact__head h1 {
  font-size: var(--text-3xl);
  letter-spacing: -0.02em;
}

.contact__form {
  display: grid;
  gap: var(--space-5);
}

.contact__sent {
  display: grid;
  justify-items: start;
  gap: var(--space-3);
}
</style>
