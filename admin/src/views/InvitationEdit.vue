<template>
  <div class="page">
    <router-link to="/invitations" class="small">← Invitations</router-link>

    <p v-if="loadError" class="notice" role="alert">{{ loadError }}</p>
    <p v-else-if="!invitation" class="muted">Loading…</p>

    <template v-else>
      <header class="head">
        <div>
          <h1>{{ invitation.title || 'Untitled draft' }}</h1>
          <p class="muted small">
            <span class="badge" :class="`badge--${invitation.status}`">{{ STATUS_LABELS[invitation.status] }}</span>
            · {{ invitation.customer?.name }} ({{ invitation.customer?.email ?? 'no email' }})
            · {{ invitation.template }} · event {{ date(invitation.event_date) }} · {{ invitation.view_count }} views
          </p>
        </div>
        <div class="actions">
          <a :href="invitation.public_url || invitation.preview_url" target="_blank" rel="noopener" class="button button--quiet">Open</a>
          <button v-if="invitation.public_url" type="button" class="button button--quiet" @click="copyLink">{{ copied ? 'Copied ✓' : 'Copy link' }}</button>
          <button v-if="invitation.status === 'draft'" type="button" class="button" @click="dialog = 'mark-paid'">Mark paid by hand</button>
          <button v-if="['draft', 'published'].includes(invitation.status)" type="button" class="button button--quiet" @click="dialog = 'disable'">Disable</button>
          <button v-if="invitation.status === 'disabled'" type="button" class="button button--quiet" @click="dialog = 'enable'">Enable</button>
        </div>
      </header>

      <div class="body">
        <form class="stack" @submit.prevent="askToSave">
          <FormSection v-if="invitation.tamil_enabled" id="section-language" title="Language guests see">
            <div class="choices" role="radiogroup" aria-label="Language guests see">
              <label v-for="option in LANGUAGE_OPTIONS" :key="option.value" class="choice">
                <input v-model="form.language" type="radio" name="language" :value="option.value" />
                <span :lang="option.lang">{{ option.label }}</span>
              </label>
            </div>
          </FormSection>

          <FormSection
            v-for="group in invitation.fields_schema.groups"
            :id="`section-${group.key}`"
            :key="group.key"
            :title="group.label.en"
            :done="!missingGroups.has(group.key)"
          >
            <TemplateFields :fields="group.fields" :data="form.data" :languages="languages" :errors="fieldErrors" @update="change" />
          </FormSection>

          <FormSection id="section-photos" title="Photos" :note="`${invitation.photos.length}`">
            <ul v-if="invitation.photos.length" class="photos">
              <li v-for="photo in invitation.photos" :key="photo.id">
                <img :src="photo.url" alt="" loading="lazy" />
                <button type="button" class="link-button small" @click="removing = photo">Remove</button>
              </li>
            </ul>
            <p v-else class="muted small">No photos.</p>
          </FormSection>

          <div class="save-bar">
            <span class="muted small">{{ changes.length ? `${changes.length} change${changes.length > 1 ? 's' : ''} not saved` : 'No unsaved changes' }}</span>
            <button type="submit" class="button" :disabled="!changes.length || saving">{{ saving ? 'Saving…' : 'Save' }}</button>
          </div>
          <p v-if="saveMessage" class="small" :class="saveFailed ? 'notice' : 'muted'" role="status">{{ saveMessage }}</p>
        </form>

        <aside class="side stack">
          <DevicePreview :url="invitation.preview_url" :reload-key="reloadKey" :max-height="620" />

          <section class="card">
            <h2>Orders</h2>
            <ul v-if="invitation.orders.length" class="orders">
              <li v-for="order in invitation.orders" :key="order.id">
                <span>#{{ order.id }} · {{ rupees(order.amount_paise) }}</span>
                <span class="badge" :class="`badge--${order.status}`">{{ STATUS_LABELS[order.status] }}</span>
                <span class="muted small">
                  {{ dateTime(order.paid_at ?? order.created_at) }}
                  <template v-if="order.paid_by"> · by hand, {{ order.paid_by }}</template>
                  <template v-else-if="order.razorpay_payment_id"> · {{ order.razorpay_payment_id }}</template>
                </span>
                <button v-if="order.can_mark_paid" type="button" class="link-button small" @click="markingOrder = order">Mark paid</button>
              </li>
            </ul>
            <p v-else class="muted small">No orders.</p>
          </section>

          <section class="card">
            <h2>History</h2>
            <HistoryList :items="invitation.history" empty="No admin changes yet." />
          </section>
        </aside>
      </div>
    </template>

    <!-- Save: what will change, and an optional note for the history. -->
    <dialog ref="saveDialog" class="dialog" @close="confirmingSave = false">
      <form class="stack" @submit.prevent="save">
        <h2>Save {{ changes.length }} change{{ changes.length > 1 ? 's' : '' }}?</h2>
        <ul class="change-list small">
          <li v-for="change in changes" :key="change.path"><code>{{ change.path }}</code>: {{ show(change.before) }} → {{ show(change.after) }}</li>
        </ul>
        <p v-if="invitation?.status === 'published'" class="muted small">This invitation is live: guests see the change straight away.</p>
        <label class="field">
          Note for the history (optional)
          <input v-model="note" type="text" maxlength="500" placeholder="e.g. Spelling fix, asked by phone" />
        </label>
        <div class="dialog-actions">
          <button type="button" class="button button--quiet" @click="saveDialog.close()">Cancel</button>
          <button type="submit" class="button">Save</button>
        </div>
      </form>
    </dialog>

    <ReasonDialog
      v-if="dialog === 'disable'"
      title="Disable this invitation?"
      description="Guests opening the link will see “this invitation has ended”. You can enable it again."
      confirm-label="Disable"
      danger
      :action="(input) => act('disable', input)"
      @close="dialog = null"
    />
    <ReasonDialog
      v-if="dialog === 'enable'"
      title="Enable this invitation?"
      confirm-label="Enable"
      :action="(input) => act('enable', input)"
      @close="dialog = null"
    />
    <ReasonDialog
      v-if="dialog === 'mark-paid'"
      title="Mark paid by hand"
      :description="`Creates a ${rupees(invitation.price_paise)} order and publishes the invitation, as a Razorpay payment would.`"
      reason-label="How was it paid?"
      confirm-label="Mark paid"
      :min-length="10"
      with-reference
      :action="(input) => act('mark-paid', input)"
      @close="dialog = null"
    />
    <ReasonDialog
      v-if="markingOrder"
      :title="`Mark order #${markingOrder.id} paid`"
      reason-label="How was it paid?"
      confirm-label="Mark paid"
      :min-length="10"
      with-reference
      :action="markOrderPaid"
      @close="markingOrder = null"
    />
    <ReasonDialog
      v-if="removing"
      title="Remove this photo?"
      description="The file is deleted; it can't be brought back."
      confirm-label="Remove"
      danger
      :action="removePhoto"
      @close="removing = null"
    />
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import FormSection from '@shared/components/FormSection.vue'
import DevicePreview from '@shared/components/DevicePreview.vue'
import TemplateFields from '@shared/components/TemplateFields.vue'
import { setPath } from '@shared/lib/templateData'
import HistoryList from '../components/HistoryList.vue'
import ReasonDialog from '../components/ReasonDialog.vue'
import { api } from '../lib/api'
import { STATUS_LABELS, date, dateTime, rupees } from '../lib/format'

const LANGUAGE_OPTIONS = [
  { value: 'ta', label: 'தமிழ்', lang: 'ta' },
  { value: 'en', label: 'English', lang: 'en' },
  { value: 'both', label: 'Both', lang: 'en' },
]

const route = useRoute()

const invitation = ref(null) // as the server last answered
const form = reactive({ language: 'both', data: {} }) // what the admin is editing
const fieldErrors = ref({})
const loadError = ref('')
const reloadKey = ref(0)

const dialog = ref(null)
const markingOrder = ref(null)
const removing = ref(null)
const copied = ref(false)

const languages = computed(() => {
  if (!invitation.value?.tamil_enabled) return ['en'] // Tamil off (TAMIL_ENABLED)
  return form.language === 'both' ? ['ta', 'en'] : [form.language]
})
const missingGroups = computed(() => new Set((invitation.value?.missing ?? []).map((item) => item.group)))

// ── What changed (same idea as the server's log: one line per field) ──

function flatten(value, prefix = '', out = {}) {
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    Object.entries(value).forEach(([key, inner]) => flatten(inner, prefix ? `${prefix}.${key}` : key, out))
  } else {
    out[prefix] = value === '' ? null : value
  }
  return out
}

const original = computed(() => (invitation.value ? flatten({ language: invitation.value.language, ...withoutAvatar(invitation.value.data) }) : {}))
const changes = computed(() => {
  const now = flatten({ language: form.language, ...withoutAvatar(form.data) })
  return [...new Set([...Object.keys(original.value), ...Object.keys(now)])]
    .filter((path) => JSON.stringify(original.value[path] ?? null) !== JSON.stringify(now[path] ?? null))
    .map((path) => ({ path, before: original.value[path], after: now[path] }))
})

function withoutAvatar(data) {
  const { avatar: _kept, ...rest } = data ?? {}
  return rest
}

const show = (value) => (value === null || value === undefined ? '(empty)' : typeof value === 'object' ? JSON.stringify(value) : String(value))

// ── Loading and saving ───────────────────────────────────────────

function loaded(data) {
  invitation.value = data
  form.language = data.language
  form.data = structuredClone(data.data)
  reloadKey.value++
}

onMounted(async () => {
  try {
    loaded((await api(`/api/admin/invitations/${route.params.id}`)).data)
  } catch (e) {
    loadError.value = e.status === 404 ? "We couldn't find that invitation." : "Couldn't load the invitation. Please refresh to try again."
  }
})

function change(path, value) {
  setPath(form.data, path, value)
  if (fieldErrors.value[path]) {
    const { [path]: _gone, ...others } = fieldErrors.value
    fieldErrors.value = others
  }
}

const saveDialog = ref(null)
const confirmingSave = ref(false)
const note = ref('')
const saving = ref(false)
const saveMessage = ref('')
const saveFailed = ref(false)

async function askToSave() {
  confirmingSave.value = true
  await nextTick()
  saveDialog.value.showModal()
}

async function save() {
  saveDialog.value.close()
  saving.value = true
  saveMessage.value = ''
  try {
    const { data, meta } = await api(`/api/admin/invitations/${invitation.value.id}`, {
      method: 'PUT',
      body: { language: form.language, data: form.data, note: note.value || undefined },
    })
    fieldErrors.value = {}
    note.value = ''
    loaded(data)
    saveFailed.value = false
    saveMessage.value = meta.changed ? `Saved ${meta.changed} change${meta.changed > 1 ? 's' : ''}.` : 'Nothing had changed.'
  } catch (e) {
    saveFailed.value = true
    if (e.status === 422) {
      fieldErrors.value = Object.fromEntries(Object.entries(e.errors).map(([key, messages]) => [key.replace(/^data\./, ''), messages[0]]))
      saveMessage.value = 'Please check the highlighted fields.'
    } else {
      saveMessage.value = e.message
    }
  } finally {
    saving.value = false
  }
}

// ── Logged actions ───────────────────────────────────────────────

async function act(action, { reason, reference }) {
  const { data } = await api(`/api/admin/invitations/${invitation.value.id}/${action}`, { method: 'POST', body: { reason, reference } })
  loaded(data)
}

async function markOrderPaid({ reason, reference }) {
  await api(`/api/admin/orders/${markingOrder.value.id}/mark-paid`, { method: 'POST', body: { reason, reference } })
  loaded((await api(`/api/admin/invitations/${invitation.value.id}`)).data)
}

async function removePhoto({ reason }) {
  const { data } = await api(`/api/admin/invitations/${invitation.value.id}/media/${removing.value.id}`, { method: 'DELETE', body: { reason } })
  loaded(data)
}

async function copyLink() {
  await navigator.clipboard?.writeText(invitation.value.public_url).catch(() => {})
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}
</script>

<style scoped>
.head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; margin: 8px 0 16px; }
.head h1 { margin: 0; }
.actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-start; }

.body { display: grid; gap: 20px; align-items: start; }
@media (min-width: 900px) { .body { grid-template-columns: minmax(0, 1fr) 340px; } }

.side h2 { margin: 0 0 10px; font-size: 1rem; }

.choices { display: flex; flex-wrap: wrap; gap: 8px; }
.choice { display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 6px 14px; border: 1px solid var(--color-line-strong); border-radius: var(--radius-pill); cursor: pointer; }
.choice:has(input:checked) { border-color: var(--color-accent); background: var(--color-accent-soft); }

.photos { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin: 0; padding: 0; list-style: none; }
.photos li { display: flex; flex-direction: column; gap: 4px; }
.photos img { width: 100%; aspect-ratio: 4 / 5; object-fit: cover; border-radius: var(--radius-md); }

.save-bar {
  position: sticky;
  bottom: 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding: 12px 0;
  background: var(--color-bg);
}

.orders { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 10px; }
.orders li { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; }

.dialog { width: min(92vw, 560px); padding: 20px; border: 0; border-radius: var(--radius-xl); box-shadow: var(--shadow-dialog); }
.dialog::backdrop { background: color-mix(in srgb, var(--color-shadow) 35%, transparent); }
.dialog h2 { margin: 0; font-size: 1.15rem; }
.change-list { margin: 0; padding-left: 18px; max-height: 40vh; overflow: auto; overflow-wrap: anywhere; }
.dialog-actions { display: flex; justify-content: flex-end; gap: 10px; }
</style>
