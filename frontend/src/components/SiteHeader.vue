<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'

import { site } from '@/config/site'

const nav = [
  { to: { name: 'projects' }, section: '/projects', label: 'Проекты' },
  { to: { name: 'about' }, section: '/about', label: 'Обо мне' },
  { to: { name: 'contact' }, section: '/contact', label: 'Контакты' },
]

const route = useRoute()

// By path prefix: a project page is not nested under the list route but belongs to its section
function inSection(section: string): boolean {
  return route.path === section || route.path.startsWith(`${section}/`)
}
</script>

<template>
  <header class="header">
    <div class="container header__inner">
      <RouterLink :to="{ name: 'home' }" class="header__brand">{{ site.name }}</RouterLink>
      <nav aria-label="Основная навигация">
        <ul class="header__nav">
          <li v-for="item in nav" :key="item.label">
            <RouterLink
              :to="item.to"
              class="header__link"
              :class="{ 'is-active': inSection(item.section) }"
              >{{ item.label }}</RouterLink
            >
          </li>
        </ul>
      </nav>
    </div>
  </header>
</template>

<style scoped>
.header {
  position: sticky;
  top: 0;
  z-index: 10;
  background: color-mix(in srgb, var(--bg) 88%, transparent);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--border);
}

.header__inner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-1) var(--space-3);
  min-height: 64px;
  /* Keeps air around the brand when the nav wraps to its own line on phones */
  padding-block: var(--space-2);
}

.header__brand {
  font-weight: 700;
  color: var(--text);
  text-decoration: none;
  letter-spacing: -0.01em;
}

.header__nav {
  display: flex;
  gap: var(--space-1);
  margin: 0;
  padding: 0;
  list-style: none;
}

.header__link {
  display: block;
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-sm);
  color: var(--text-muted);
  font-size: var(--text-sm);
  font-weight: 500;
  text-decoration: none;
}

.header__link:hover {
  color: var(--text);
  background: var(--surface-muted);
}

.header__link.is-active {
  color: var(--text);
  background: var(--surface-muted);
}
</style>
