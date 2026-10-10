<template>
  <div v-if="loadError" class="page">
    <p class="notice" role="alert">{{ loadError }}</p>
    <router-link to="/invitations">← My invitations</router-link>
  </div>

  <p v-else-if="!invitation" class="page muted">Loading…</p>

  <div v-else class="builder">
    <div class="builder__bar">
      <router-link to="/invitations" class="back">← My invitations</router-link>
      <span class="save-status" :class="`is-${status}`" role="status">
        {{ statusText }}
        <!-- A field to fix needs the customer, not another try. -->
        <button v-if="status === 'error' && !hasFieldErrors" type="button" class="link-button" @click="retry">Retry</button>
      </span>
    </div>

    <!-- Phone: the form and the preview take turns. -->
    <div v-if="isPhone" class="tabs" role="tablist" aria-label="Builder">
      <button type="button" role="tab" :aria-selected="tab === 'details'" @click="tab = 'details'">Details</button>
      <button type="button" role="tab" :aria-selected="tab === 'preview'" @click="openPreviewTab">Preview</button>
    </div>

    <div class="builder__body">
      <form v-show="!isPhone || tab === 'details'" class="builder__form" @submit.prevent>
        <FormSection id="section-language" title="Language guests see">
          <div class="choices" role="radiogroup" aria-label="Language guests see">
            <label v-for="option in LANGUAGE_OPTIONS" :key="option.value" class="choice">
              <input v-model="form.language" type="radio" name="language" :value="option.value" @change="schedule" />
              <span :lang="option.lang">{{ option.label }}</span>
            </label>
          </div>
          <p class="hint">Both: guests switch between Tamil and English on the invitation.</p>
        </FormSection>

        <FormSection
          v-for="group in schema.groups"
          :id="`section-${group.key}`"
          :key="group.key"
          :title="group.label.en"
          :collapsible="isPhone"
          :open="openSection === group.key"
          :done="!missingGroups.has(group.key)"
          @toggle="toggle(group.key)"
        >
          <TemplateFields
            :fields="group.fields"
            :data="form.data"
            :languages="languages"
            :errors="fieldErrors"
            @update="change"
          />
        </FormSection>

        <FormSection
          id="section-photos"
          title="Photos"
          :collapsible="isPhone"
          :open="openSection === 'photos'"
          :note="`${invitation.photos.length} of ${invitation.template.max_photos}`"
          @toggle="toggle('photos')"
        >
          <PhotoManager
            :invitation-id="invitation.id"
            :photos="invitation.photos"
            :max="invitation.template.max_photos"
            @changed="photosChanged"
          />
        </FormSection>

        <section class="still-needed" aria-labelledby="still-needed-title">
          <h2 id="still-needed-title">{{ invitation.missing.length ? 'Still needed before paying' : 'Everything needed is filled in' }}</h2>
          <ul v-if="invitation.missing.length">
            <li v-for="item in invitation.missing" :key="`${item.key}.${item.lang}`">
              <button type="button" class="link-button" @click="jumpTo(item)">
                {{ groupTitle(item.group) }}: {{ item.label.en }}<template v-if="item.lang"> ({{ LANGUAGE_NAMES[item.lang] }})</template>
              </button>
            </li>
          </ul>
        </section>
      </form>

      <aside v-if="!isPhone" class="builder__preview" aria-label="Preview">
        <PhonePreview :url="invitation.preview_url" :reload-key="reloadKey" :max-height="previewMaxHeight" />
        <a :href="invitation.preview_url" target="_blank" rel="noopener" class="hint">Open the preview full size</a>
      </aside>

      <div v-if="isPhone && tab === 'preview'" class="builder__preview builder__preview--tab">
        <PhonePreview :url="invitation.preview_url" :max-height="previewMaxHeight" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { onBeforeRouteLeave, useRoute } from 'vue-router'
import FormSection from '@shared/components/FormSection.vue'
import TemplateFields from '@shared/components/TemplateFields.vue'
import { inputId, setPath } from '@shared/lib/templateData'
import PhonePreview from '../components/PhonePreview.vue'
import PhotoManager from '../components/PhotoManager.vue'
import { api } from '../lib/api'
import { useAutosave } from '../composables/useAutosave'
import { PHONE, useMediaQuery } from '../composables/useMediaQuery'

const LANGUAGE_NAMES = { ta: 'தமிழ்', en: 'English' }
const LANGUAGE_OPTIONS = [
  { value: 'ta', label: 'தமிழ்', lang: 'ta' },
  { value: 'en', label: 'English', lang: 'en' },
  { value: 'both', label: 'Both', lang: 'en' },
]
// Desktop: the preview reloads this long after the last successful save.
const PREVIEW_RELOAD_DELAY = 3000

const route = useRoute()
const isPhone = useMediaQuery(PHONE)

const invitation = ref(null) // as the server last answered: status, missing, photos, preview link
const schema = ref(null)
const form = reactive({ language: 'both', data: {} }) // what the customer is typing
const fieldErrors = ref({})
const loadError = ref('')

const tab = ref('details')
const openSection = ref(null)
const reloadKey = ref(0)
// Room left for the preview under the page header (and, on phones, the tabs).
const availableHeight = () => window.innerHeight - (window.matchMedia(PHONE).matches ? 190 : 140)
const previewMaxHeight = ref(availableHeight())

const languages = computed(() => (form.language === 'both' ? ['ta', 'en'] : [form.language]))
const hasFieldErrors = computed(() => Object.keys(fieldErrors.value).length > 0)
const missingGroups = computed(() => new Set((invitation.value?.missing ?? []).map((item) => item.group)))
const groupTitle = (key) => schema.value.groups.find((group) => group.key === key)?.label.en ?? key

// ── Saving ───────────────────────────────────────────────────────

const { status, schedule, flush, retry } = useAutosave(save)
const saveError = ref('')

const statusText = computed(() => ({
  saved: 'Saved ✓',
  pending: 'Saving…',
  saving: 'Saving…',
  error: saveError.value || "Couldn't save",
})[status.value])

async function save() {
  try {
    const { data } = await api(`/api/invitations/${invitation.value.id}`, {
      method: 'PUT',
      body: { language: form.language, data: form.data },
    })
    fieldErrors.value = {}
    saveError.value = ''
    serverAnswered(data)
  } catch (error) {
    if (error.status === 422) {
      // `data.bride.name.ta` -> `bride.name.ta`, the paths the fields use.
      fieldErrors.value = Object.fromEntries(
        Object.entries(error.errors).map(([key, messages]) => [key.replace(/^data\./, ''), messages[0]]),
      )
      saveError.value = 'Please check the highlighted fields'
    } else {
      saveError.value = error.status ? error.message : "Couldn't save. Check your connection"
    }
    throw error
  }
}

/**
 * Keeps what the server worked out (still needed, photos, title) but never
 * replaces what the customer is typing: their newer keystrokes would be lost.
 */
function serverAnswered(data) {
  const { data: _answers, ...rest } = data
  invitation.value = { ...invitation.value, ...rest }
  queuePreviewReload()
}

function change(path, value) {
  setPath(form.data, path, value)
  if (fieldErrors.value[path]) {
    const { [path]: _gone, ...others } = fieldErrors.value
    fieldErrors.value = others
  }
  schedule()
}

function photosChanged(data) {
  serverAnswered(data)
}

// ── Preview ──────────────────────────────────────────────────────

let reloadTimer = null

/** Desktop: reload once things have been quiet for a moment, not on every save. */
function queuePreviewReload() {
  if (isPhone.value) return
  clearTimeout(reloadTimer)
  reloadTimer = setTimeout(() => { reloadKey.value++ }, PREVIEW_RELOAD_DELAY)
}

/** Phone: the preview loads fresh each time the tab opens, after saving what's waiting. */
async function openPreviewTab() {
  await flush()
  tab.value = 'preview'
}

// ── Sections ─────────────────────────────────────────────────────

/** Phone: one section open at a time; tapping the open one closes it. */
function toggle(key) {
  openSection.value = openSection.value === key ? null : key
}

async function jumpTo(item) {
  tab.value = 'details'
  openSection.value = item.group
  await nextTick()
  const input = document.getElementById(inputId(item.key, item.lang))
  input?.scrollIntoView({ block: 'center', behavior: 'smooth' })
  input?.focus({ preventScroll: true })
}

// ── Loading and leaving ──────────────────────────────────────────

onMounted(async () => {
  try {
    const { data } = await api(`/api/invitations/${route.params.id}`)
    const { data: template } = await api(`/api/templates/${data.template.slug}`)
    schema.value = template.fields_schema
    form.language = data.language
    form.data = structuredClone(data.data)
    invitation.value = data
    // Phone: start on the first section with something missing.
    openSection.value = data.missing[0]?.group ?? schema.value.groups[0]?.key
  } catch (error) {
    loadError.value = error.status === 404 ? "We couldn't find that invitation." : "Couldn't load the invitation. Please refresh to try again."
  }
})

function warnBeforeUnload(event) {
  if (status.value === 'saved') return
  flush()
  event.preventDefault()
  event.returnValue = ''
}

window.addEventListener('beforeunload', warnBeforeUnload)
const onResize = () => { previewMaxHeight.value = availableHeight() }
window.addEventListener('resize', onResize)

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', warnBeforeUnload)
  window.removeEventListener('resize', onResize)
  clearTimeout(reloadTimer)
})

onBeforeRouteLeave(async () => {
  if (!invitation.value || (await flush())) return true
  return window.confirm("Your latest changes couldn't be saved. Leave anyway?")
})
</script>

<style scoped>
.builder { max-width: 1100px; margin: 0 auto; padding: 12px 16px 48px; }

.builder__bar {
  position: sticky;
  top: 0;
  z-index: 5;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
  background: var(--bg);
}

.back { color: inherit; text-decoration: none; }

.save-status { font-size: 0.9rem; color: var(--muted); }
.save-status.is-saved { color: var(--ok); }
.save-status.is-error { color: var(--danger); }

.tabs {
  position: sticky;
  top: 52px;
  z-index: 5;
  display: grid;
  grid-template-columns: 1fr 1fr;
  margin-bottom: 12px;
  padding: 4px;
  border-radius: 999px;
  background: var(--line);
}

.tabs button {
  min-height: 40px;
  border: 0;
  border-radius: 999px;
  background: transparent;
  font: inherit;
  cursor: pointer;
}

.tabs button[aria-selected='true'] { background: var(--surface); font-weight: 600; box-shadow: 0 1px 3px rgb(0 0 0 / 0.1); }

.builder__body {
  display: grid;
  gap: 24px;
  align-items: start;
}

@media (min-width: 768px) {
  .builder__body { grid-template-columns: minmax(0, 1fr) 340px; }
}

.builder__form { display: flex; flex-direction: column; gap: 12px; }

.builder__preview {
  position: sticky;
  top: 64px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.builder__preview--tab { position: static; }

.choices { display: flex; flex-wrap: wrap; gap: 8px; }

.choice {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 44px;
  padding: 8px 14px;
  border: 1px solid var(--line-strong);
  border-radius: 999px;
  cursor: pointer;
}

.choice:has(input:checked) { border-color: var(--accent); background: var(--accent-soft); }
.choice input { accent-color: var(--accent); }

.hint { margin: 0; font-size: 0.85rem; color: var(--muted); }

.still-needed {
  padding: 14px 16px;
  border-radius: 12px;
  background: var(--accent-soft);
}

.still-needed h2 { margin: 0 0 8px; font-size: 1rem; }
.still-needed ul { margin: 0; padding-left: 18px; display: flex; flex-direction: column; gap: 4px; }

.link-button {
  padding: 0;
  border: 0;
  background: none;
  font: inherit;
  color: var(--accent);
  text-decoration: underline;
  text-align: left;
  cursor: pointer;
}
</style>
