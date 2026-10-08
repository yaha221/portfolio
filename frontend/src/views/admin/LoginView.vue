<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { ApiError } from '@/api/problem'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const busy = ref(false)
const failure = ref<string | null>(null)

// Only paths inside the admin: a crafted ?redirect=https://… must not send the user elsewhere
function target(): string {
  const redirect = route.query.redirect
  return typeof redirect === 'string' && redirect.startsWith('/admin') ? redirect : '/admin'
}

async function login(): Promise<void> {
  busy.value = true
  failure.value = null
  try {
    await auth.login(form.email, form.password)
    await router.replace(target())
  } catch (caught) {
    failure.value =
      caught instanceof ApiError && caught.status === 401
        ? 'Неверный email или пароль.'
        : caught instanceof ApiError && caught.status === 429
          ? 'Слишком много попыток. Подождите 15 минут.'
          : 'Не получилось войти. Проверьте соединение.'
  } finally {
    busy.value = false
  }
}

// Still signed in (refresh cookie alive): skip the form
onMounted(async () => {
  if ((await auth.validToken()) !== null) {
    await router.replace(target())
  }
})
</script>

<template>
  <div class="login">
    <form class="login__form" @submit.prevent="login">
      <h1>Вход в админку</h1>
      <div class="field">
        <label class="field__label" for="login-email">Email</label>
        <input
          id="login-email"
          v-model="form.email"
          class="input"
          type="email"
          autocomplete="username"
          required
        />
      </div>
      <div class="field">
        <label class="field__label" for="login-password">Пароль</label>
        <input
          id="login-password"
          v-model="form.password"
          class="input"
          type="password"
          autocomplete="current-password"
          required
        />
      </div>
      <p v-if="failure" class="alert alert--error" role="alert">{{ failure }}</p>
      <button type="submit" class="button" :disabled="busy">{{ busy ? 'Вхожу…' : 'Войти' }}</button>
    </form>
  </div>
</template>

<style scoped>
.login {
  display: grid;
  place-items: center;
  min-height: 100vh;
  padding: var(--space-4);
}

.login__form {
  display: grid;
  gap: var(--space-4);
  width: min(100%, 24rem);
  padding: var(--space-6);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  box-shadow: var(--shadow);
}

.login__form h1 {
  font-size: var(--text-xl);
}
</style>
