<script setup lang="ts">
import { ref } from 'vue'

import type { Schemas } from '@/api/client'

defineProps<{ images: Schemas['ProjectImageView'][] }>()

const dialog = ref<HTMLDialogElement | null>(null)
const opened = ref<string | null>(null)

function open(image: Schemas['ProjectImageView']): void {
  // The large thumbnail (1280 px) is enough for a screen; the original may be a 10 MB photo
  opened.value = image.thumbnails.large ?? image.url
  dialog.value?.showModal()
}
</script>

<template>
  <div class="gallery">
    <button
      v-for="(image, index) in images"
      :key="image.url"
      type="button"
      class="gallery__item"
      :aria-label="`Открыть изображение ${index + 1}`"
      @click="open(image)"
    >
      <img :src="image.thumbnails.small ?? image.url" alt="" loading="lazy" />
    </button>
  </div>

  <dialog ref="dialog" class="lightbox" @click="dialog?.close()">
    <img v-if="opened" :src="opened" alt="" />
    <p class="lightbox__hint">Нажмите в любом месте или Esc, чтобы закрыть</p>
  </dialog>
</template>

<style scoped>
.gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: var(--space-3);
}

.gallery__item {
  padding: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  background: var(--surface-muted);
  cursor: zoom-in;
  aspect-ratio: 4 / 3;
}

.gallery__item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.lightbox {
  max-width: min(1280px, 94vw);
  padding: 0;
  border: none;
  border-radius: var(--radius);
  background: transparent;
  cursor: zoom-out;
}

.lightbox::backdrop {
  background: rgb(0 0 0 / 80%);
}

.lightbox img {
  max-height: 85vh;
  border-radius: var(--radius);
}

.lightbox__hint {
  margin-top: var(--space-2);
  color: #e8eaee;
  font-size: var(--text-xs);
  text-align: center;
}
</style>
