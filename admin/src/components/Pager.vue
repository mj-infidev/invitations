<template>
  <!-- Laravel's paginator: "Page 2 of 7 · 160 results", with previous and next. -->
  <nav v-if="meta && meta.last_page > 1" class="pager" aria-label="Pages">
    <button type="button" class="button button--quiet" :disabled="meta.current_page <= 1" @click="$emit('page', meta.current_page - 1)">← Previous</button>
    <span class="muted small">Page {{ meta.current_page }} of {{ meta.last_page }} · {{ meta.total }} results</span>
    <button type="button" class="button button--quiet" :disabled="meta.current_page >= meta.last_page" @click="$emit('page', meta.current_page + 1)">Next →</button>
  </nav>
</template>

<script setup>
defineProps({ meta: { type: Object, default: null } })
defineEmits(['page'])
</script>

<style scoped>
.pager { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 12px; }
</style>
