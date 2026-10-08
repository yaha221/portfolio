<script setup lang="ts">
import { RouterView } from 'vue-router'

import SiteFooter from '@/components/SiteFooter.vue'
import SiteHeader from '@/components/SiteHeader.vue'
</script>

<template>
  <div class="layout">
    <SiteHeader />
    <main class="layout__main">
      <RouterView />
    </main>
    <SiteFooter />
  </div>
</template>

<style scoped>
.layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.layout__main {
  flex: 1;
  padding-block: var(--space-6) var(--space-8);
}
</style>
