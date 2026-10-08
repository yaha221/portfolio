<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'

const props = withDefaults(
  defineProps<{ label?: string; confirmLabel?: string; disabled?: boolean }>(),
  {
    label: 'Удалить',
    confirmLabel: 'Точно удалить?',
    disabled: false,
  },
)
const emit = defineEmits<{ confirm: [] }>()

// Two clicks instead of window.confirm(): no blocking dialog, and a slip of the mouse deletes nothing
const armed = ref(false)
let timer: ReturnType<typeof setTimeout> | undefined

function click(): void {
  if (armed.value) {
    disarm()
    emit('confirm')
    return
  }
  armed.value = true
  timer = setTimeout(disarm, 4000)
}

function disarm(): void {
  armed.value = false
  clearTimeout(timer)
}

onBeforeUnmount(disarm)
</script>

<template>
  <button
    type="button"
    class="button button--small"
    :class="armed ? 'button--danger' : 'button--ghost'"
    :disabled="props.disabled"
    @click="click"
  >
    {{ armed ? confirmLabel : label }}
  </button>
</template>
