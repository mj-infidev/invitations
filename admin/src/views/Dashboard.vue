<template>
  <div class="page">
    <h1>Dashboard</h1>
    <p v-if="error" class="notice" role="alert">{{ error }}</p>
    <p v-else-if="!stats" class="muted">Loading…</p>

    <template v-else>
      <ul class="stats">
        <li class="card"><span class="muted small">Sales today</span><strong>{{ rupees(stats.sales_today_paise) }}</strong></li>
        <li class="card"><span class="muted small">Sales this month</span><strong>{{ rupees(stats.sales_month_paise) }}</strong></li>
        <li class="card"><span class="muted small">Paid orders this month</span><strong>{{ stats.paid_orders_month }}</strong></li>
        <li class="card"><span class="muted small">Published invitations</span><strong>{{ stats.published }}</strong></li>
        <li class="card"><span class="muted small">Drafts</span><strong>{{ stats.drafts }}</strong></li>
        <li class="card"><span class="muted small">Total views</span><strong>{{ stats.total_views.toLocaleString('en-IN') }}</strong></li>
      </ul>

      <div class="columns">
        <section class="card">
          <h2>Latest paid orders</h2>
          <ul v-if="stats.recent_orders.length" class="recent">
            <li v-for="order in stats.recent_orders" :key="order.id">
              <router-link v-if="order.invitation" :to="{ name: 'invitation', params: { id: order.invitation.id } }">
                {{ order.invitation.title || 'Untitled' }}
              </router-link>
              <span class="muted small">
                {{ rupees(order.amount_paise) }} · {{ dateTime(order.paid_at) }}<template v-if="order.paid_by"> · by {{ order.paid_by }}</template>
              </span>
            </li>
          </ul>
          <p v-else class="muted small">No paid orders yet.</p>
        </section>

        <section class="card">
          <h2>Recent admin activity</h2>
          <HistoryList :items="stats.recent_actions" empty="No admin activity yet." />
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import HistoryList from '../components/HistoryList.vue'
import { api } from '../lib/api'
import { dateTime, rupees } from '../lib/format'

const stats = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    stats.value = (await api('/api/admin/stats')).data
  } catch {
    error.value = "Couldn't load the dashboard. Please refresh to try again."
  }
})
</script>

<style scoped>
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 12px;
  margin: 16px 0;
  padding: 0;
  list-style: none;
}

.stats li { display: flex; flex-direction: column; gap: 4px; }
.stats strong { font-size: 1.5rem; font-variant-numeric: tabular-nums; }

.columns { display: grid; gap: 16px; }
@media (min-width: 900px) { .columns { grid-template-columns: 1fr 1fr; } }

.columns h2 { margin: 0 0 12px; font-size: 1.05rem; }
.recent { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 10px; }
.recent li { display: flex; flex-direction: column; }
</style>
