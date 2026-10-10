<template>
  <!-- Makes the draft, then hands over to the builder. Only seen if that fails. -->
  <main class="page">
    <p v-if="!error" class="muted">Creating your invitation…</p>
    <template v-else>
      <p class="notice" role="alert">{{ error }}</p>
      <router-link to="/">← Back to the templates</router-link>
    </template>
  </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../lib/api'

const route = useRoute()
const router = useRouter()
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await api('/api/invitations', { method: 'POST', body: { template: route.query.template } })
    // replace: Back from the builder shouldn't land here and make another draft.
    router.replace({ name: 'builder', params: { id: data.id } })
  } catch (e) {
    error.value = e.status === 429
      ? "You've started a lot of invitations just now. Please wait a while and try again."
      : e.status === 422 ? "That template isn't available." : "Couldn't create the invitation. Please try again."
  }
})
</script>
