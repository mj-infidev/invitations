<template>
  <main class="page">
    <div class="heading">
      <h1>My invitations</h1>
      <router-link :to="{ name: 'create', query: { template: 'tamil-hindu-wedding' } }" class="button">New invitation</router-link>
    </div>

    <p v-if="error" class="notice" role="alert">{{ error }}</p>
    <p v-else-if="!loaded" class="muted">Loading…</p>
    <p v-else-if="!invitations.length" class="muted">
      You haven't made an invitation yet. <router-link to="/">Pick a template</router-link> to start.
    </p>

    <ul class="list">
      <li v-for="item in invitations" :key="item.id" class="card invitation">
        <img v-if="item.cover_url" :src="item.cover_url" alt="" class="invitation__cover" />
        <div class="invitation__body">
          <h2>{{ item.title || 'Untitled draft' }}</h2>
          <p class="muted">
            <span class="status" :class="`status--${item.status}`">{{ STATUS[item.status] }}</span>
            · {{ item.template.name }}
            <template v-if="item.event_date"> · {{ formatDate(item.event_date) }}</template>
          </p>

          <div v-if="confirming === item.id" class="actions">
            <span>Delete this draft and its photos?</span>
            <button type="button" class="button button--danger" :disabled="deleting" @click="remove(item)">Delete</button>
            <button type="button" class="button button--quiet" @click="confirming = null">Keep it</button>
          </div>
          <div v-else class="actions">
            <router-link v-if="item.public_url" :to="{ name: 'share', params: { id: item.id } }" class="button">Share</router-link>
            <router-link :to="{ name: 'builder', params: { id: item.id } }" class="button" :class="{ 'button--quiet': item.public_url }">Edit</router-link>
            <a :href="item.public_url || item.preview_url" target="_blank" rel="noopener" class="button button--quiet">{{ item.public_url ? 'Open' : 'Preview' }}</a>
            <button v-if="item.can_delete" type="button" class="link-button" @click="confirming = item.id">Delete</button>
          </div>
        </div>
      </li>
    </ul>
  </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../lib/api'

const STATUS = { draft: 'Draft', published: 'Published', expired: 'Ended', disabled: 'Disabled' }

const invitations = ref([])
const loaded = ref(false)
const error = ref('')
const confirming = ref(null)
const deleting = ref(false)

const formatDate = (date) => new Date(`${date}T00:00:00`).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })

onMounted(async () => {
  try {
    invitations.value = (await api('/api/invitations')).data
  } catch {
    error.value = "Couldn't load your invitations. Please refresh to try again."
  } finally {
    loaded.value = true
  }
})

async function remove(item) {
  deleting.value = true
  try {
    await api(`/api/invitations/${item.id}`, { method: 'DELETE' })
    invitations.value = invitations.value.filter((other) => other.id !== item.id)
    confirming.value = null
  } catch (e) {
    error.value = e.message
  } finally {
    deleting.value = false
  }
}
</script>

<style scoped>
.heading { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
.list { margin: 16px 0 0; padding: 0; list-style: none; display: grid; gap: 12px; }
.invitation { display: flex; gap: 14px; }
.invitation__cover { width: 72px; height: 90px; object-fit: cover; border-radius: 8px; flex: none; }
.invitation__body { flex: 1; min-width: 0; }
.invitation h2 { margin: 0 0 4px; font-size: 1.1rem; overflow-wrap: anywhere; }
.actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-top: 12px; }
.status { font-weight: 600; }
.status--published { color: var(--ok); }
</style>
