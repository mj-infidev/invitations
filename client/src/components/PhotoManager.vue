<template>
  <!--
    The couple's photos: thumbnails, remove, and an upload button that takes
    several files. Each upload shows its progress; the server re-saves every
    photo smaller (and without its hidden location data).
  -->
  <div class="photos">
    <ul v-if="photos.length" class="photos__grid">
      <li v-for="photo in photos" :key="photo.id" class="photos__item">
        <img :src="photo.url" alt="" loading="lazy" />
        <button
          type="button"
          class="photos__remove"
          :disabled="removing === photo.id"
          aria-label="Remove photo"
          @click="remove(photo)"
        >×</button>
      </li>
    </ul>

    <ul v-if="queue.length" class="queue">
      <li v-for="item in queue" :key="item.id" class="queue__item">
        <span class="queue__name">{{ item.name }}</span>
        <span v-if="item.error" class="queue__error">{{ item.error }}</span>
        <progress v-else :value="item.progress" max="1" />
      </li>
    </ul>

    <p class="hint">JPEG, PNG or WebP, up to 5 MB each. {{ photos.length }} of {{ max }} added.</p>

    <label v-if="photos.length + uploading < max" class="upload">
      <input type="file" accept="image/jpeg,image/png,image/webp" multiple @change="pick" />
      + Add photos
    </label>
    <p v-else-if="!uploading" class="hint">That's the most this invitation can hold.</p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { api, upload } from '../lib/api'

const props = defineProps({
  invitationId: { type: Number, required: true },
  photos: { type: Array, required: true },
  max: { type: Number, required: true },
})

/** The invitation as the server now has it, after each upload or removal. */
const emit = defineEmits(['changed'])

const TYPES = ['image/jpeg', 'image/png', 'image/webp']
const MAX_BYTES = 5 * 1024 * 1024

const queue = ref([])
const removing = ref(null)
const uploading = computed(() => queue.value.filter((item) => !item.error && item.progress < 1).length)
let nextId = 1

async function pick(event) {
  const files = [...event.target.files]
  event.target.value = '' // the same file can be picked again after an error

  const room = props.max - props.photos.length - uploading.value
  const items = files.map((file, i) => ({
    id: nextId++,
    file,
    name: file.name,
    progress: 0,
    error: !TYPES.includes(file.type) ? 'Not a JPEG, PNG or WebP photo.'
      : file.size > MAX_BYTES ? 'Over 5 MB. Please choose a smaller photo.'
        : i >= room ? `Only ${props.max} photos fit.`
          : null,
  }))
  queue.value.push(...items)
  // The reactive copies, so progress and errors show as they change.
  const ids = items.map((item) => item.id)
  const live = queue.value.filter((q) => ids.includes(q.id))

  // One at a time, in the order picked: that is the order guests see them.
  for (const item of live.filter((i) => !i.error)) {
    const form = new FormData()
    form.append('photo', item.file)
    try {
      const { data } = await upload(`/api/invitations/${props.invitationId}/media`, form, (p) => { item.progress = Math.min(p, 0.99) })
      item.progress = 1
      emit('changed', data)
      queue.value = queue.value.filter((q) => q.id !== item.id)
    } catch (error) {
      item.error = error.errors?.photo?.[0] ?? error.message
    }
  }

  // Leave failed ones listed for a moment, then clear them.
  setTimeout(() => { queue.value = queue.value.filter((q) => !ids.includes(q.id)) }, 6000)
}

async function remove(photo) {
  removing.value = photo.id
  try {
    const { data } = await api(`/api/invitations/${props.invitationId}/media/${photo.id}`, { method: 'DELETE' })
    emit('changed', data)
  } finally {
    removing.value = null
  }
}
</script>

<style scoped>
.photos { display: flex; flex-direction: column; gap: 12px; }

.photos__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
  gap: 8px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.photos__item { position: relative; aspect-ratio: 4 / 5; }

.photos__item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 8px;
}

.photos__remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 32px;
  height: 32px;
  border: 0;
  border-radius: 50%;
  background: rgb(0 0 0 / 0.6);
  color: #fff;
  font-size: 1.2rem;
  line-height: 1;
  cursor: pointer;
}

.queue { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 6px; }
.queue__item { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; }
.queue__name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.queue__error { color: var(--danger, #b42318); }
progress { width: 40%; }

.hint { margin: 0; font-size: 0.85rem; color: var(--muted, #6b5b55); }

.upload {
  align-self: flex-start;
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  padding: 8px 16px;
  border: 1px solid var(--accent, #8c1d3a);
  border-radius: 999px;
  color: var(--accent, #8c1d3a);
  cursor: pointer;
}

.upload:focus-within { outline: 2px solid var(--accent, #8c1d3a); outline-offset: 2px; }

.upload input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
}
</style>
