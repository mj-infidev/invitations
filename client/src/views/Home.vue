<template>
  <!-- A simple start page until the landing page's design (Step 7). -->
  <main class="page">
    <h1>Animated wedding invitations</h1>
    <p class="muted">Pick a template, fill in your details, and share the link on WhatsApp.</p>

    <p v-if="error" class="notice" role="alert">{{ error }}</p>

    <ul class="templates">
      <li v-for="template in templates" :key="template.slug" class="card">
        <h2>{{ template.name }}</h2>
        <p class="muted">₹{{ (template.price_paise / 100).toLocaleString('en-IN') }} · up to {{ template.max_photos }} photos</p>
        <div class="actions">
          <a v-if="template.demo_url" :href="template.demo_url" target="_blank" rel="noopener" class="button button--quiet">Try the demo</a>
          <router-link :to="{ name: 'create', query: { template: template.slug } }" class="button">Create my invitation</router-link>
        </div>
      </li>
    </ul>
  </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../lib/api'

const templates = ref([])
const error = ref('')

onMounted(async () => {
  try {
    templates.value = (await api('/api/templates')).data
  } catch {
    error.value = "Couldn't load the templates. Please refresh to try again."
  }
})
</script>

<style scoped>
.templates { margin: 24px 0 0; padding: 0; list-style: none; display: grid; gap: 16px; }
.card h2 { margin: 0 0 4px; font-size: 1.2rem; }
.actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
</style>
