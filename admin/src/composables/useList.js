import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../lib/api'

/**
 * A searchable, filterable, paged list from an admin endpoint that answers
 * with Laravel's paginator. Search, filter and page live in the address, so
 * Back and a shared link return to the same view. Typing waits 300 ms; an
 * older request is cancelled when a newer one starts.
 *
 * @param {string} path  e.g. '/api/admin/orders'
 */
export function useList(path) {
  const route = useRoute()
  const router = useRouter()

  const q = ref(route.query.q ?? '')
  const status = ref(route.query.status ?? '')
  const page = ref(Number(route.query.page) || 1)

  const items = ref([])
  const meta = ref(null)
  const loading = ref(false)
  const error = ref('')
  let controller = null

  async function load() {
    controller?.abort()
    controller = new AbortController()
    loading.value = true
    error.value = ''
    try {
      const { data, ...rest } = await api(path, { query: { q: q.value, status: status.value, page: page.value }, signal: controller.signal })
      items.value = data
      meta.value = rest
    } catch (e) {
      if (e.name !== 'AbortError') error.value = "Couldn't load the list. Please try again."
    } finally {
      loading.value = false
    }
  }

  function syncAddress() {
    router.replace({ query: { ...(q.value && { q: q.value }), ...(status.value && { status: status.value }), ...(page.value > 1 && { page: page.value }) } })
  }

  let typing = null
  watch(q, () => {
    clearTimeout(typing)
    typing = setTimeout(() => { page.value = 1; syncAddress(); load() }, 300)
  })
  watch(status, () => { page.value = 1; syncAddress(); load() })

  function goTo(newPage) {
    page.value = newPage
    syncAddress()
    load()
  }

  load()

  return { q, status, items, meta, loading, error, goTo, reload: load }
}
