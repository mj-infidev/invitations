/**
 * api.js — the one place the admin panel talks to the Laravel API.
 *
 * The admin panel has its own session cookie, separate from customers'
 * (App\Http\Middleware\UseAdminSession), sent only to /api/admin/*. Its CSRF
 * token comes in its own cookie too, XSRF-TOKEN-ADMIN, and goes back in the
 * usual X-XSRF-TOKEN header. Writes need both, or the API answers 419.
 *
 * Keep VITE_API_URL on `localhost`, never 127.0.0.1 — they are different cookie
 * hosts and mixing them makes every write fail.
 */

export const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000'

const CSRF_COOKIE = 'XSRF-TOKEN-ADMIN'

export class ApiError extends Error {
  constructor(status, body) {
    super(body?.message || `Request failed (${status})`)
    this.status = status
    this.errors = body?.errors || {} // Laravel validation errors: { field: [messages] }
  }

  /** First message for a field, or the general message. */
  messageFor(field) {
    return this.errors?.[field]?.[0] ?? this.message
  }
}

function readCookie(name) {
  const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'))
  return match ? decodeURIComponent(match[1]) : null
}

let csrfReady = null

/** Starts the admin session and sets its CSRF cookie, once, before the first write. */
function ensureCsrfCookie() {
  if (readCookie(CSRF_COOKIE)) return Promise.resolve()

  csrfReady ||= fetch(`${API_BASE}/api/admin/csrf-cookie`, { credentials: 'include' })
    .catch(() => {})
    .finally(() => { csrfReady = null })

  return csrfReady
}

/**
 * @param {string} path  e.g. '/api/admin/orders'
 * @param {object} opts  { method, query, body, signal }
 */
export async function api(path, { method = 'GET', query, body, signal } = {}) {
  const isWrite = method !== 'GET'

  if (isWrite) await ensureCsrfCookie()

  const url = new URL(API_BASE + path)
  Object.entries(query || {}).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) url.searchParams.set(key, value)
  })

  const headers = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'

  if (isWrite) {
    const token = readCookie(CSRF_COOKIE)
    if (token) headers['X-XSRF-TOKEN'] = token
  }

  const res = await fetch(url, {
    method,
    headers,
    credentials: 'include',
    body: body !== undefined ? JSON.stringify(body) : undefined,
    signal,
  })

  const data = res.status === 204 ? null : await res.json().catch(() => null)

  if (!res.ok) throw new ApiError(res.status, data)

  return data
}
