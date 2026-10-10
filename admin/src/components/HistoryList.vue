<template>
  <!-- Admin actions, newest first: who, what, when, why, and what changed. -->
  <ul v-if="items.length" class="history">
    <li v-for="item in items" :key="item.id">
      <div class="history__head">
        <strong>{{ ACTION_LABELS[item.action] ?? item.action }}</strong>
        <span class="muted small">{{ item.admin ?? item.details?.email ?? 'Unknown' }} · {{ dateTime(item.created_at) }}</span>
      </div>
      <p v-if="item.reason" class="history__reason">“{{ item.reason }}”</p>
      <ul v-if="item.details?.changes" class="history__changes small">
        <li v-for="(pair, field) in item.details.changes" :key="field">
          <code>{{ field }}</code>: {{ show(pair[0]) }} → {{ show(pair[1]) }}
        </li>
      </ul>
      <p v-if="item.details?.reference" class="muted small">Reference: {{ item.details.reference }}</p>
    </li>
  </ul>
  <p v-else class="muted small">{{ empty }}</p>
</template>

<script setup>
import { ACTION_LABELS, dateTime } from '../lib/format'

defineProps({
  items: { type: Array, required: true },
  empty: { type: String, default: 'Nothing yet.' },
})

const show = (value) => (value === null || value === undefined || value === '' ? '(empty)' : typeof value === 'object' ? JSON.stringify(value) : String(value))
</script>

<style scoped>
.history { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 12px; }
.history > li { padding-bottom: 12px; border-bottom: 1px solid var(--line); }
.history > li:last-child { border-bottom: 0; }
.history__head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; }
.history__reason { margin: 4px 0 0; }
.history__changes { margin: 6px 0 0; padding-left: 18px; overflow-wrap: anywhere; }
</style>
