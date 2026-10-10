/**
 * api.js — the one place the client talks to the Laravel API.
 *
 * Customers are signed in with an HttpOnly session cookie (Sanctum SPA mode),
 * never a token in JavaScript. The client origin is in Sanctum's stateful list,
 * so every write through /api/* is CSRF-checked. Writes must:
 *  - send cookies (credentials: 'include')
 *  - send the XSRF-TOKEN cookie back as the X-XSRF-TOKEN header
 * Miss either and the API answers 419, not 422.
 *
 * Keep VITE_API_URL on `localhost`, never 127.0.0.1 — they are different cookie
 * hosts and mixing them makes every write fail.
 */

export const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000'

export class ApiError extends Error {
  constructor(status, body) {
    super(body?.message || `Request failed (${status})`)
    this.status = status
    this.errors = body?.errors || {} // Laravel validation errors: { field: [messages] }
  }
}

function readCookie(name) {
  const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'))
  return match ? decodeURIComponent(match[1]) : null
}

let csrfReady = null

/**
 * Sets the XSRF-TOKEN cookie. Fetched once per page load, lazily, before the
 * first write — a browsing-only visit never pays for it.
 */
export function ensureCsrfCookie() {
  if (readCookie('XSRF-TOKEN')) return Promise.resolve()

  csrfReady ||= fetch(`${API_BASE}/sanctum/csrf-cookie`, { credentials: 'include' })
    .catch(() => {}) // a failed prime still lets the write attempt (and surface) a real error

  return csrfReady
}

/**
 * @param {string} path  e.g. '/api/me'
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
    const token = readCookie('XSRF-TOKEN')
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

/**
 * Uploads a file as multipart form data, reporting progress (0–1) as it goes:
 * fetch can't report upload progress, so this one uses XMLHttpRequest. Same
 * cookie and CSRF rules as api().
 *
 * @param {string} path  e.g. '/api/invitations/3/media'
 * @param {FormData} form
 * @param {(fraction: number) => void} [onProgress]
 */
export async function upload(path, form, onProgress = () => {}) {
  await ensureCsrfCookie()

  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest()
    xhr.open('POST', API_BASE + path)
    xhr.withCredentials = true
    xhr.setRequestHeader('Accept', 'application/json')
    const token = readCookie('XSRF-TOKEN')
    if (token) xhr.setRequestHeader('X-XSRF-TOKEN', token)

    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable) onProgress(event.loaded / event.total)
    }
    xhr.onload = () => {
      let data = null
      try {
        data = JSON.parse(xhr.responseText)
      } catch {
        // Not JSON: leave data null; the status says what happened.
      }
      if (xhr.status >= 200 && xhr.status < 300) resolve(data)
      else reject(new ApiError(xhr.status, data))
    }
    xhr.onerror = () => reject(new ApiError(0, { message: "Couldn't reach the server. Check your connection and try again." }))
    xhr.send(form)
  })
}
