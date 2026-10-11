<template>
  <!--
    Inputs for a list of fields from a template's fields_schema (PLAN.md >
    Template form fields), reading values from `data` by dot path. Changes are
    emitted as `update(path, value)`; the parent owns `data` and the saving.

    Bilingual fields get one box per language the invitation shows (Tamil
    first); a language not shown keeps its value, it's just not displayed.
    Repeaters render their items with this same component.
  -->
  <div class="fields">
    <template v-for="field in shownFields" :key="field.key">
      <!-- Repeater: a card per item, with add and remove -->
      <fieldset v-if="field.type === 'repeater'" class="repeater">
        <!-- The section card already shows the group title; this names the list for screen readers. -->
        <legend class="visually-hidden">{{ field.label.en }}</legend>

        <div v-for="(item, i) in itemsOf(field)" :key="i" class="repeater__item">
          <div class="repeater__head">
            <span class="repeater__title">{{ field.label.en }} {{ i + 1 }}</span>
            <button type="button" class="link-button" @click="removeItem(field, i)">Remove</button>
          </div>
          <TemplateFields
            :fields="field.fields"
            :data="data"
            :base="`${pathOf(field)}.${i}`"
            :languages="languages"
            :errors="errors"
            @update="(path, value) => $emit('update', path, value)"
          />
        </div>

        <button
          v-if="itemsOf(field).length < (field.max_items ?? 10)"
          type="button"
          class="add-button"
          @click="addItem(field)"
        >+ Add {{ field.label.en.toLowerCase() }}</button>
        <p v-else class="hint">You can add up to {{ field.max_items }}.</p>
      </fieldset>

      <!-- Bilingual: one box per language shown -->
      <div v-else-if="field.bilingual" class="field">
        <p class="label">{{ field.label.en }}<span v-if="field.required" class="required" aria-hidden="true"> *</span></p>
        <p v-if="field.help" class="hint">{{ field.help.en }}</p>
        <div class="field__langs" :class="{ 'field__langs--two': languages.length === 2 }">
          <div v-for="lang in languages" :key="lang" class="field__lang">
            <label :for="inputId(pathOf(field), lang)" class="lang-label">{{ LANGUAGE_NAMES[lang] }}</label>
            <component
              :is="field.type === 'textarea' ? 'textarea' : 'input'"
              :id="inputId(pathOf(field), lang)"
              :lang="lang"
              :type="field.type === 'textarea' ? undefined : 'text'"
              :rows="field.type === 'textarea' ? 3 : undefined"
              :maxlength="field.max"
              :value="getPath(data, `${pathOf(field)}.${lang}`) ?? ''"
              :aria-invalid="!!errorFor(`${pathOf(field)}.${lang}`)"
              @input="$emit('update', `${pathOf(field)}.${lang}`, $event.target.value)"
            />
            <p v-if="errorFor(`${pathOf(field)}.${lang}`)" class="error">{{ errorFor(`${pathOf(field)}.${lang}`) }}</p>
          </div>
        </div>
      </div>

      <!-- One value: date, time, link, or text in one language -->
      <div v-else class="field">
        <label :for="inputId(pathOf(field))" class="label">
          {{ field.label.en }}<span v-if="field.required" class="required" aria-hidden="true"> *</span>
        </label>
        <p v-if="field.help" class="hint">{{ field.help.en }}</p>
        <component
          :is="field.type === 'textarea' ? 'textarea' : 'input'"
          :id="inputId(pathOf(field))"
          :type="INPUT_TYPES[field.type] ?? 'text'"
          :inputmode="field.type === 'url' ? 'url' : undefined"
          :placeholder="field.type === 'url' ? 'https://maps.app.goo.gl/…' : undefined"
          :maxlength="field.max"
          :value="getPath(data, pathOf(field)) ?? ''"
          :aria-invalid="!!errorFor(pathOf(field))"
          @input="$emit('update', pathOf(field), $event.target.value)"
        />
        <p v-if="errorFor(pathOf(field))" class="error">{{ errorFor(pathOf(field)) }}</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { emptyItem, getPath, inputId } from '../lib/templateData'

const props = defineProps({
  /** Fields of one schema group (or of one repeater item). */
  fields: { type: Array, required: true },
  /** The invitation's whole `data`; read only here. */
  data: { type: Object, required: true },
  /** Path these fields sit under: '' for a group, `events.0` for a repeater item. */
  base: { type: String, default: '' },
  /** Languages the invitation shows: ['ta'], ['en'] or ['ta', 'en']. */
  languages: { type: Array, required: true },
  /** Server validation messages by path (`bride.name.ta`). */
  errors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update'])

const LANGUAGE_NAMES = { ta: 'தமிழ்', en: 'English' }
const INPUT_TYPES = { date: 'date', time: 'time', url: 'url' }

// A field marked with a language (the Tamil calendar date) only when that language is shown.
const shownFields = computed(() => props.fields.filter((field) => !field.lang || props.languages.includes(field.lang)))

const pathOf = (field) => (props.base ? `${props.base}.${field.key}` : field.key)
const errorFor = (path) => props.errors[path] ?? null
const itemsOf = (field) => getPath(props.data, pathOf(field)) ?? []

function addItem(field) {
  emit('update', pathOf(field), [...itemsOf(field), emptyItem(field.fields)])
}

function removeItem(field, index) {
  emit('update', pathOf(field), itemsOf(field).filter((_, i) => i !== index))
}
</script>

<style scoped>
.fields { display: flex; flex-direction: column; gap: 16px; }

.field { display: flex; flex-direction: column; gap: 6px; }

.label { margin: 0; font-weight: 600; font-size: 0.95rem; }
.required { color: var(--color-accent); }
.hint { margin: 0; font-size: 0.85rem; color: var(--color-text-muted); }
.error { margin: 2px 0 0; font-size: 0.85rem; color: var(--color-danger); }

.field__langs { display: grid; gap: 10px; }

@media (min-width: 768px) {
  .field__langs--two { grid-template-columns: 1fr 1fr; }
}

.field__lang { display: flex; flex-direction: column; gap: 4px; }
.lang-label { font-size: 0.8rem; color: var(--color-text-muted); }

input,
textarea {
  width: 100%;
  min-height: 44px;
  padding: 10px 12px;
  border: 1px solid var(--color-line-strong);
  border-radius: var(--radius-md);
  background: var(--color-surface);
  font: inherit;
  color: inherit;
}

textarea { resize: vertical; line-height: 1.5; }

input:lang(ta),
textarea:lang(ta) { line-height: 1.6; }

input:focus,
textarea:focus {
  outline: 2px solid var(--color-accent);
  outline-offset: 1px;
  border-color: transparent;
}

[aria-invalid='true'] { border-color: var(--color-danger); }

.repeater {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin: 0;
  padding: 0;
  border: 0;
}

.repeater__item {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 12px;
  border: 1px dashed var(--color-line-strong);
  border-radius: var(--radius-lg);
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip-path: inset(50%);
  white-space: nowrap;
}

.repeater__head { display: flex; justify-content: space-between; align-items: center; }
.repeater__title { font-weight: 600; font-size: 0.9rem; }

.link-button,
.add-button {
  border: 0;
  background: none;
  font: inherit;
  color: var(--color-accent);
  cursor: pointer;
}

.link-button { font-size: 0.85rem; text-decoration: underline; }

.add-button {
  align-self: flex-start;
  min-height: 44px;
  padding: 8px 14px;
  border: 1px solid var(--color-accent);
  border-radius: var(--radius-pill);
}
</style>
