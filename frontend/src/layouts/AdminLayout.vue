<script setup lang="ts">
import '@/styles/admin.css'

import { RouterLink, RouterView, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const nav = [
  { name: 'admin-projects', label: 'Проекты' },
  { name: 'admin-technologies', label: 'Технологии' },
  { name: 'admin-skills', label: 'Навыки' },
  { name: 'admin-experience', label: 'Опыт' },
  { name: 'admin-messages', label: 'Сообщения' },
  { name: 'admin-analytics', label: 'Аналитика' },
]

async function logout(): Promise<void> {
  await auth.logout()
  await router.push({ name: 'admin-login' })
}
</script>

<template>
  <div class="admin">
    <aside class="admin__sidebar">
      <p class="admin__brand">Админка</p>
      <nav aria-label="Разделы админки">
        <ul class="admin__nav">
          <li v-for="item in nav" :key="item.name">
            <RouterLink :to="{ name: item.name }" class="admin__link" active-class="is-active">{{
              item.label
            }}</RouterLink>
          </li>
        </ul>
      </nav>
      <div class="admin__footer">
        <RouterLink :to="{ name: 'home' }" class="admin__link" target="_blank"
          >Открыть сайт ↗</RouterLink
        >
        <button type="button" class="admin__link admin__logout" @click="logout">Выйти</button>
      </div>
    </aside>
    <main class="admin__main">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.admin {
  display: grid;
  min-height: 100vh;
}

@media (min-width: 900px) {
  .admin {
    grid-template-columns: 220px 1fr;
  }
}

.admin__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  padding: var(--space-5) var(--space-4);
  border-right: 1px solid var(--border);
  background: var(--surface);
}

.admin__brand {
  padding-inline: var(--space-3);
  font-weight: 700;
}

.admin__nav {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-1);
  margin: 0;
  padding: 0;
  list-style: none;
}

@media (min-width: 900px) {
  .admin__nav {
    flex-direction: column;
  }
}

.admin__link {
  display: block;
  width: 100%;
  padding: var(--space-2) var(--space-3);
  border: none;
  border-radius: var(--radius-sm);
  background: none;
  color: var(--text-muted);
  font: inherit;
  font-size: var(--text-sm);
  text-align: left;
  text-decoration: none;
  cursor: pointer;
}

.admin__link:hover,
.admin__link.is-active {
  background: var(--surface-muted);
  color: var(--text);
}

.admin__footer {
  margin-top: auto;
  display: grid;
  gap: var(--space-1);
}

.admin__main {
  padding: var(--space-6) var(--space-4);
  min-width: 0;
}

@media (min-width: 720px) {
  .admin__main {
    padding: var(--space-6);
  }
}
</style>
