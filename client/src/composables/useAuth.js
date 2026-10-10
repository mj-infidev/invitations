import { computed, readonly, ref } from 'vue'
import { api, API_BASE } from '../lib/api'

/**
 * useAuth — the signed-in customer, shared across the client.
 *
 * Module-scope state, so every page reads one object. The session itself is an
 * HttpOnly cookie the browser holds; nothing secret is ever kept in JavaScript.
 *
 * Signing in is not an API call: the browser leaves for Google and comes back
 * (Socialite's redirect flow), to the page it left, signed in — or with
 * `?signin=failed` if the customer cancelled or something went wrong.
 */

const user = ref(null)
const ready = ref(false) // true once we know whether anyone is signed in
let mePromise = null

export function useAuth() {
  /** Resolves once per page load. */
  function fetchMe({ force = false } = {}) {
    if (force) mePromise = null
    mePromise ||= api('/api/me')
      .then(({ data }) => { user.value = data })
      .catch(() => { user.value = null })
      .finally(() => { ready.value = true })
    return mePromise
  }

  return {
    user: readonly(user),
    ready: readonly(ready),
    isSignedIn: computed(() => !!user.value),
    fetchMe,

    /** The Google sign-in link; after signing in the customer returns to `returnTo`. */
    signInUrl: (returnTo = '/') =>
      `${API_BASE}/api/auth/google/redirect?redirect=${encodeURIComponent(safeRedirect(returnTo))}`,

    async logout() {
      try {
        await api('/api/auth/logout', { method: 'POST' })
      } finally {
        // Signed out either way: a 401 here just means the session was already gone.
        user.value = null
      }
    },
  }
}

/**
 * Only same-app paths, so a crafted link can't bounce a customer off-site.
 * The API checks again on its side.
 */
export function safeRedirect(value) {
  return typeof value === 'string' && value.startsWith('/') && !value.startsWith('//') && !value.startsWith('/\\')
    ? value
    : '/'
}
