<template>
  <div class="page">
    <h1>Customers</h1>

    <div class="toolbar">
      <input v-model="q" type="search" class="search" placeholder="Name or email" aria-label="Search customers" />
    </div>

    <p v-if="error" class="notice" role="alert">{{ error }}</p>

    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Name</th><th>Email</th><th>Joined</th><th class="num">Invitations</th><th class="num">Paid</th></tr>
        </thead>
        <tbody>
          <template v-for="customer in items" :key="customer.id">
            <tr class="clickable" :aria-expanded="String(open === customer.id)" @click="toggle(customer)">
              <td>{{ customer.name }}</td>
              <td>{{ customer.email ?? '—' }}</td>
              <td>{{ date(customer.joined_at) }}</td>
              <td class="num">{{ customer.invitations_count }}</td>
              <td class="num">{{ customer.paid_count }}</td>
            </tr>
            <tr v-if="open === customer.id">
              <td colspan="5">
                <p v-if="!details" class="muted small">Loading…</p>
                <p v-else-if="!details.invitations.length" class="muted small">No invitations.</p>
                <ul v-else class="invitations">
                  <li v-for="invitation in details.invitations" :key="invitation.id">
                    <router-link :to="{ name: 'invitation', params: { id: invitation.id } }">{{ invitation.title || 'Untitled draft' }}</router-link>
                    <span class="badge" :class="`badge--${invitation.status}`">{{ STATUS_LABELS[invitation.status] }}</span>
                    <span class="muted small">{{ date(invitation.event_date) }}</span>
                  </li>
                </ul>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
      <p v-if="!loading && !items.length" class="empty">No customers match.</p>
    </div>

    <Pager :meta="meta" @page="goTo" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Pager from '../components/Pager.vue'
import { useList } from '../composables/useList'
import { api } from '../lib/api'
import { STATUS_LABELS, date } from '../lib/format'

const { q, items, meta, loading, error, goTo } = useList('/api/admin/users')
const open = ref(null)
const details = ref(null)

async function toggle(customer) {
  if (open.value === customer.id) {
    open.value = null
    return
  }
  open.value = customer.id
  details.value = null
  details.value = (await api(`/api/admin/users/${customer.id}`)).data
}
</script>

<style scoped>
.invitations { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 8px; }
.invitations li { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
</style>
