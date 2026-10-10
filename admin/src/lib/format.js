/** Display helpers. Money is stored in paise; times are shown in Indian time. */

export const rupees = (paise) =>
  `₹${((paise ?? 0) / 100).toLocaleString('en-IN', { maximumFractionDigits: 2 })}`

export const date = (value) =>
  value ? new Date(value.length === 10 ? `${value}T00:00:00` : value).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Asia/Kolkata' }) : '—'

export const dateTime = (value) =>
  value ? new Date(value).toLocaleString('en-IN', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit', timeZone: 'Asia/Kolkata' }) : '—'

export const STATUS_LABELS = {
  draft: 'Draft',
  published: 'Published',
  expired: 'Ended',
  disabled: 'Disabled',
  created: 'Unpaid',
  paid: 'Paid',
  failed: 'Failed',
  refunded: 'Refunded',
}

/** What an admin action looks like in a history list. */
export const ACTION_LABELS = {
  'admin.login': 'Signed in',
  'admin.login_failed': 'Failed sign-in',
  'admin.logout': 'Signed out',
  'invitation.update': 'Edited details',
  'invitation.disable': 'Disabled',
  'invitation.enable': 'Enabled',
  'media.remove': 'Removed a photo',
  'order.mark_paid': 'Marked paid by hand',
  'template.update': 'Changed template',
}
