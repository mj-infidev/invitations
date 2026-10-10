<template>
  <div class="page">
    <h1>Orders</h1>

    <div class="toolbar">
      <input v-model="q" type="search" class="search" placeholder="Order number, Razorpay id, customer name or email" aria-label="Search orders" />
      <div class="tabs" role="group" aria-label="Status">
        <button v-for="tab in TABS" :key="tab.value" type="button" :aria-pressed="String(status === tab.value)" @click="status = tab.value">{{ tab.label }}</button>
      </div>
    </div>

    <p v-if="error" class="notice" role="alert">{{ error }}</p>

    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>Date</th><th>Customer</th><th>Invitation</th><th class="num">Amount</th><th>Status</th><th>Payment</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="order in items" :key="order.id">
            <td>{{ order.id }}</td>
            <td>{{ dateTime(order.created_at) }}</td>
            <td>{{ order.customer?.name }}<br /><span class="muted small">{{ order.customer?.email }}</span></td>
            <td>
              <router-link v-if="order.invitation" :to="{ name: 'invitation', params: { id: order.invitation.id } }">{{ order.invitation.title || 'Untitled draft' }}</router-link>
            </td>
            <td class="num">{{ rupees(order.amount_paise) }}</td>
            <td><span class="badge" :class="`badge--${order.status}`">{{ STATUS_LABELS[order.status] }}</span></td>
            <td class="small">
              <template v-if="order.paid_by">By hand · {{ order.paid_by }}</template>
              <template v-else-if="order.razorpay_payment_id">Razorpay · {{ order.razorpay_payment_id }}</template>
              <span v-else class="muted">{{ order.razorpay_order_id }}</span>
            </td>
            <td>
              <button v-if="order.can_mark_paid" type="button" class="button button--quiet small" @click="marking = order">Mark paid</button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="!loading && !items.length" class="empty">No orders match.</p>
    </div>

    <Pager :meta="meta" @page="goTo" />

    <ReasonDialog
      v-if="marking"
      :title="`Mark order #${marking.id} paid`"
      :description="`${rupees(marking.amount_paise)} from ${marking.customer?.name ?? 'the customer'}. This publishes the invitation, as a Razorpay payment would.`"
      reason-label="How was it paid?"
      confirm-label="Mark paid"
      :min-length="10"
      with-reference
      :action="markPaid"
      @close="marking = null"
      @done="reload"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Pager from '../components/Pager.vue'
import ReasonDialog from '../components/ReasonDialog.vue'
import { useList } from '../composables/useList'
import { api } from '../lib/api'
import { STATUS_LABELS, dateTime, rupees } from '../lib/format'

const TABS = [
  { value: '', label: 'All' },
  { value: 'paid', label: 'Paid' },
  { value: 'created', label: 'Unpaid' },
  { value: 'failed', label: 'Failed' },
  { value: 'refunded', label: 'Refunded' },
]

const { q, status, items, meta, loading, error, goTo, reload } = useList('/api/admin/orders')
const marking = ref(null)

const markPaid = ({ reason, reference }) =>
  api(`/api/admin/orders/${marking.value.id}/mark-paid`, { method: 'POST', body: { reason, reference } })
</script>
