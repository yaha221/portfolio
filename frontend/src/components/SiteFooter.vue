<script setup lang="ts">
import { site } from '@/config/site'

const year = new Date().getFullYear()
</script>

<template>
  <footer class="footer">
    <div class="container footer__inner">
      <p>© {{ year }} {{ site.name }}</p>
      <ul v-if="site.links.length" class="footer__links">
        <li v-for="link in site.links" :key="link.url">
          <a :href="link.url" target="_blank" rel="noopener">{{ link.label }}</a>
        </li>
      </ul>
    </div>
  </footer>
</template>

<style scoped>
.footer {
  border-top: 1px solid var(--border);
  padding-block: var(--space-5);
  color: var(--text-muted);
  font-size: var(--text-sm);
}

.footer__inner {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: var(--space-3);
}

.footer__links {
  display: flex;
  gap: var(--space-4);
  margin: 0;
  padding: 0;
  list-style: none;
}
</style>
