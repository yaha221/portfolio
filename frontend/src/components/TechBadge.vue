<script setup lang="ts">
import { computed, ref } from 'vue'

const props = defineProps<{
  name: string
  icon?: string | null
  count?: number
}>()

const iconFailed = ref(false)

// Simple Icons CDN: second and third colours are for light and dark mode (it reads prefers-color-scheme)
const iconUrl = computed(() =>
  props.icon && !iconFailed.value
    ? `https://cdn.simpleicons.org/${props.icon}/5a606b/9aa1ad`
    : null,
)
</script>

<template>
  <span class="badge">
    <img
      v-if="iconUrl"
      :src="iconUrl"
      alt=""
      width="14"
      height="14"
      loading="lazy"
      @error="iconFailed = true"
    />
    <span>{{ name }}</span>
    <span v-if="count !== undefined" class="badge__count">{{ count }}</span>
  </span>
</template>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.2rem 0.55rem;
  border: 1px solid var(--border);
  border-radius: 999px;
  background: var(--surface);
  font-family: var(--font-mono);
  font-size: var(--text-xs);
  color: var(--text-muted);
  white-space: nowrap;
}

.badge__count {
  color: var(--text);
  font-variant-numeric: tabular-nums;
}
</style>
