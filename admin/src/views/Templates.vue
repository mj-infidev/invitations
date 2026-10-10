<template>
  <div class="page">
    <h1>Templates</h1>
    <p class="muted">A new price applies to the next checkout; orders already made keep theirs.</p>

    <p v-if="error" class="notice" role="alert">{{ error }}</p>

    <div class="templates">
      <form v-for="template in templates" :key="template.id" class="card stack" @submit.prevent="save(template)">
        <div class="head">
          <h2>{{ template.name }}</h2>
          <span class="badge" :class="template.form.is_active ? 'badge--published' : ''">{{ template.form.is_active ? 'On' : 'Off' }}</span>
        </div>

        <label class="field">
          Price (₹)
          <input v-model.number="template.form.rupees" type="number" min="1" max="100000" step="1" required />
        </label>

        <label class="field">
          Photos allowed
          <input v-model.number="template.form.max_photos" type="number" min="1" max="20" required />
        </label>

        <label class="toggle">
          <input v-model="template.form.is_active" type="checkbox" />
          Offered to customers
        </label>

        <p v-if="template.message" class="small" :class="template.failed ? 'notice' : 'muted'" role="status">{{ template.message }}</p>

        <button type="submit" class="button" :disabled="!changed(template) || template.saving">
          {{ template.saving ? 'Saving…' : 'Save' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../lib/api'
import { rupees } from '../lib/format'

const templates = ref([])
const error = ref('')

// The form edits rupees; the API stores paise.
const toForm = (t) => ({ rupees: t.price_paise / 100, max_photos: t.max_photos, is_active: t.is_active })
const toBody = (form) => ({ price_paise: Math.round(form.rupees * 100), max_photos: form.max_photos, is_active: form.is_active })

const changed = (t) => JSON.stringify(toBody(t.form)) !== JSON.stringify(toBody(toForm(t)))

onMounted(async () => {
  try {
    templates.value = (await api('/api/admin/templates')).data.map((t) => ({ ...t, form: toForm(t), message: '', saving: false }))
  } catch {
    error.value = "Couldn't load the templates. Please refresh to try again."
  }
})

async function save(template) {
  const body = toBody(template.form)
  const summary = [
    body.price_paise !== template.price_paise && `price ${rupees(template.price_paise)} → ${rupees(body.price_paise)}`,
    body.max_photos !== template.max_photos && `photos ${template.max_photos} → ${body.max_photos}`,
    body.is_active !== template.is_active && (body.is_active ? 'turn it on' : 'turn it off (hidden from customers)'),
  ].filter(Boolean).join(', ')
  if (!window.confirm(`Save ${template.name}: ${summary}?`)) return

  template.saving = true
  template.message = ''
  try {
    const { data } = await api(`/api/admin/templates/${template.id}`, { method: 'PUT', body })
    Object.assign(template, data, { form: toForm(data), failed: false, message: 'Saved.' })
  } catch (e) {
    template.failed = true
    template.message = e.message
  } finally {
    template.saving = false
  }
}
</script>

<style scoped>
.templates { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-top: 16px; }
.head { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.head h2 { margin: 0; font-size: 1.1rem; }
.toggle { display: flex; align-items: center; gap: 8px; }
</style>
