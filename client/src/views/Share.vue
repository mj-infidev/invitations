<template>
  <!-- After paying: the link to send, ready to copy or share on WhatsApp. -->
  <main class="page">
    <p v-if="error" class="notice" role="alert">{{ error }}</p>
    <p v-else-if="!invitation" class="muted">Loading…</p>

    <template v-else>
      <h1>Your invitation is live 🎉</h1>
      <p class="muted">{{ invitation.title }}</p>

      <div class="card share">
        <label for="share-link" class="label">Your invitation link</label>
        <div class="link-row">
          <input id="share-link" ref="linkInput" :value="invitation.public_url" readonly @focus="$event.target.select()" />
          <button type="button" class="button" @click="copy">{{ copied ? 'Copied ✓' : 'Copy' }}</button>
        </div>

        <a :href="whatsAppUrl" target="_blank" rel="noopener" class="button button--whatsapp">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a.9.9 0 0 0-.7.3 2.8 2.8 0 0 0-.9 2.1 4.9 4.9 0 0 0 1 2.6 11.2 11.2 0 0 0 4.3 3.8c1.6.7 2.2.7 3 .6a2.6 2.6 0 0 0 1.7-1.2 2.1 2.1 0 0 0 .2-1.2c-.1-.1-.3-.2-.5-.3z" /></svg>
          Share on WhatsApp
        </a>

        <div class="actions">
          <a :href="invitation.public_url" target="_blank" rel="noopener" class="button button--quiet">Open invitation</a>
          <router-link v-if="editable" :to="{ name: 'builder', params: { id: invitation.id } }" class="button button--quiet">Edit details</router-link>
        </div>
        <p v-if="editable" class="muted small">You can correct details until the event date. The link stays the same.</p>
      </div>

      <router-link to="/invitations">← My invitations</router-link>
    </template>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../lib/api'

const route = useRoute()
const router = useRouter()

const invitation = ref(null)
const error = ref('')
const copied = ref(false)
const linkInput = ref(null)

// The message guests receive with the link, in the invitation's language.
const whatsAppUrl = computed(() => {
  const message = invitation.value.tamil_enabled && invitation.value.language === 'ta'
    ? 'எங்கள் திருமணத்திற்கு உங்களை அன்புடன் அழைக்கிறோம் 💐'
    : "You're warmly invited to our wedding 💐"
  return `https://wa.me/?text=${encodeURIComponent(`${message}\n${invitation.value.public_url}`)}`
})

const editable = computed(() => {
  const date = invitation.value?.event_date
  return !date || new Date(`${date}T23:59:59`) >= new Date()
})

onMounted(async () => {
  try {
    const { data } = await api(`/api/invitations/${route.params.id}`)
    // Not paid for yet: there is nothing to share, so back to the builder.
    if (data.status !== 'published') return router.replace({ name: 'builder', params: { id: data.id } })
    invitation.value = data
  } catch (e) {
    error.value = e.status === 404 ? "We couldn't find that invitation." : "Couldn't load the invitation. Please refresh to try again."
  }
})

async function copy() {
  try {
    await navigator.clipboard.writeText(invitation.value.public_url)
  } catch {
    // Older browsers, or no permission: select it so the customer can copy by hand.
    linkInput.value.select()
    document.execCommand?.('copy')
  }
  copied.value = true
  setTimeout(() => { copied.value = false }, 2500)
}
</script>

<style scoped>
.share { display: flex; flex-direction: column; gap: 14px; margin: 20px 0; }
.label { font-weight: 600; }
.link-row { display: flex; gap: 8px; }

.link-row input {
  flex: 1;
  min-width: 0;
  min-height: 44px;
  padding: 10px 12px;
  border: 1px solid var(--color-line-strong);
  border-radius: var(--radius-md);
  font: inherit;
  background: var(--color-bg);
}

.button--whatsapp {
  gap: 8px;
  border-color: var(--color-whatsapp);
  background: var(--color-whatsapp);
  color: var(--color-whatsapp-text);
  font-weight: 600;
}

.button--whatsapp svg { width: 22px; height: 22px; fill: currentColor; }
.actions { display: flex; flex-wrap: wrap; gap: 10px; }
.small { margin: 0; font-size: 0.85rem; }
</style>
