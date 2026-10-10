<template>
  <div class="page">
    <h1>Invitations</h1>

    <div class="toolbar">
      <input v-model="q" type="search" class="search" placeholder="Search by title, link, customer name or email" aria-label="Search invitations" />
      <div class="tabs" role="group" aria-label="Status">
        <button v-for="tab in TABS" :key="tab.value" type="button" :aria-pressed="String(status === tab.value)" @click="status = tab.value">{{ tab.label }}</button>
      </div>
    </div>

    <p v-if="error" class="notice" role="alert">{{ error }}</p>

    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Title</th><th>Customer</th><th>Status</th><th>Event date</th><th class="num">Views</th><th>Updated</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id" class="clickable" @click="open(item)">
            <td><router-link :to="link(item)" @click.stop>{{ item.title || 'Untitled draft' }}</router-link><br /><span class="muted small">{{ item.slug }}</span></td>
            <td>{{ item.customer?.name }}<br /><span class="muted small">{{ item.customer?.email }}</span></td>
            <td><span class="badge" :class="`badge--${item.status}`">{{ STATUS_LABELS[item.status] }}</span></td>
            <td>{{ date(item.event_date) }}</td>
            <td class="num">{{ item.view_count }}</td>
            <td>{{ dateTime(item.updated_at) }}</td>
          </tr>
        </tbody>
      </table>
      <p v-if="!loading && !items.length" class="empty">No invitations match.</p>
    </div>

    <Pager :meta="meta" @page="goTo" />
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import Pager from '../components/Pager.vue'
import { useList } from '../composables/useList'
import { STATUS_LABELS, date, dateTime } from '../lib/format'

const TABS = [
  { value: '', label: 'All' },
  { value: 'draft', label: 'Draft' },
  { value: 'published', label: 'Published' },
  { value: 'disabled', label: 'Disabled' },
  { value: 'expired', label: 'Ended' },
]

const router = useRouter()
const { q, status, items, meta, loading, error, goTo } = useList('/api/admin/invitations')

const link = (item) => ({ name: 'invitation', params: { id: item.id } })
const open = (item) => router.push(link(item))
</script>
