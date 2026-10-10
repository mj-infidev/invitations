import { computed, readonly, ref } from 'vue'
import { api } from '../lib/api'

/**
 * useAdminAuth — the signed-in admin, shared across the panel. The session is
 * an HttpOnly cookie of the admin panel's own; nothing secret is in JavaScript.
 */

const admin = ref(null)
let mePromise = null

export function useAdminAuth() {
  /** Resolves once per page load; the router guard awaits it. */
  function fetchMe({ force = false } = {}) {
    if (force) mePromise = null
    mePromise ||= api('/api/admin/me')
      .then(({ data }) => { admin.value = data })
      .catch(() => { admin.value = null })
    return mePromise
  }

  return {
    admin: readonly(admin),
    isSignedIn: computed(() => !!admin.value),
    fetchMe,

    async login(email, password) {
      const { data } = await api('/api/admin/login', { method: 'POST', body: { email, password } })
      admin.value = data
      mePromise = Promise.resolve()
      return data
    },

    async logout() {
      try {
        await api('/api/admin/logout', { method: 'POST' })
      } finally {
        admin.value = null
      }
    },

    /** A 401 from any call: the session ended (8 hours, or the browser closed). */
    signedOutElsewhere: () => { admin.value = null },
  }
}
