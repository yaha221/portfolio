<script setup lang="ts">
import DOMPurify from 'dompurify'
import { marked } from 'marked'
import { computed } from 'vue'

const props = defineProps<{ source: string }>()

// Markdown comes from the admin, but HTML is still sanitized: v-html must never run scripts
const html = computed(() => DOMPurify.sanitize(marked.parse(props.source, { async: false })))
</script>

<template>
  <!-- eslint-disable-next-line vue/no-v-html -- sanitized with DOMPurify above -->
  <div class="prose" v-html="html" />
</template>

<style scoped>
.prose {
  max-width: var(--reading-width);
  line-height: 1.7;
}

.prose :deep(> * + *) {
  margin-top: var(--space-4);
}

.prose :deep(h2) {
  margin-top: var(--space-6);
  font-size: var(--text-xl);
}

.prose :deep(h3) {
  margin-top: var(--space-5);
  font-size: var(--text-lg);
}

.prose :deep(ul),
.prose :deep(ol) {
  padding-left: 1.25rem;
}

.prose :deep(code) {
  font-family: var(--font-mono);
  font-size: 0.9em;
  padding: 0.1em 0.35em;
  border-radius: 4px;
  background: var(--surface-muted);
}

.prose :deep(pre) {
  padding: var(--space-4);
  border-radius: var(--radius);
  background: var(--surface-muted);
  overflow-x: auto;
}

.prose :deep(pre code) {
  padding: 0;
  background: none;
}

.prose :deep(blockquote) {
  margin-inline: 0;
  padding-left: var(--space-4);
  border-left: 3px solid var(--border);
  color: var(--text-muted);
}
</style>
