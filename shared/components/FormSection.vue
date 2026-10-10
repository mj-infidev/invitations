<template>
  <!--
    One card of a form. Collapsible (on phones the builder keeps one open at a
    time) or always open (desktop). `done` shows a tick: nothing required is
    missing here. `note` replaces the tick with a short text, e.g. "3 of 6".
  -->
  <section :id="id" class="form-section" :class="{ 'is-open': open || !collapsible }">
    <component
      :is="collapsible ? 'button' : 'div'"
      class="form-section__head"
      v-bind="collapsible ? { type: 'button', 'aria-expanded': String(open), 'aria-controls': `${id}-body` } : {}"
      @click="collapsible && $emit('toggle')"
    >
      <h2 class="form-section__title">{{ title }}</h2>
      <span v-if="note" class="form-section__note">{{ note }}</span>
      <span v-else-if="done" class="form-section__done" aria-label="Complete">
        <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 10.5l3.2 3.2L15 7" /></svg>
      </span>
      <svg v-if="collapsible" class="form-section__chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="M6 8l4 4 4-4" /></svg>
    </component>
    <div v-show="open || !collapsible" :id="`${id}-body`" class="form-section__body">
      <slot />
    </div>
  </section>
</template>

<script setup>
defineProps({
  id: { type: String, required: true },
  title: { type: String, required: true },
  open: { type: Boolean, default: true },
  collapsible: { type: Boolean, default: false },
  done: { type: Boolean, default: false },
  note: { type: String, default: '' },
})

defineEmits(['toggle'])
</script>

<style scoped>
.form-section {
  border: 1px solid var(--line, #e6dfdc);
  border-radius: 12px;
  background: var(--surface, #fff);
  scroll-margin-top: 80px;
}

.form-section__head {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 14px 16px;
  border: 0;
  background: none;
  font: inherit;
  color: inherit;
  text-align: left;
}

button.form-section__head { cursor: pointer; min-height: 52px; }

.form-section__title {
  flex: 1;
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
}

.form-section__note { font-size: 0.85rem; color: var(--muted, #6b5b55); }

.form-section__done {
  display: grid;
  place-items: center;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--ok, #2f7d4b);
}

.form-section__done svg,
.form-section__chevron {
  width: 16px;
  height: 16px;
  fill: none;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.form-section__done svg { stroke: #fff; }
.form-section__chevron { stroke: currentColor; transition: transform 0.15s; }
.is-open .form-section__chevron { transform: rotate(180deg); }

.form-section__body {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 0 16px 16px;
}
</style>
