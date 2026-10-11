import { computed, readonly, ref } from 'vue'
import { api } from '../lib/api'

/**
 * The active templates (GET /api/templates), fetched once per page load and
 * shared by the home page and the site nav.
 */
const templates = ref([])
let loading = null

export function useTemplates() {
  loading ||= api('/api/templates')
    .then(({ data }) => { templates.value = data })
    // The page still works on its defaults; Create goes to the usual template.
    .catch(() => {})

  // The first template is the one the site shows and links to.
  const template = computed(() => templates.value[0] ?? null)

  return {
    templates: readonly(templates),
    template,
    loaded: loading,
    /** "Create my invitation": a new draft of the first template. */
    createLink: computed(() => ({ name: 'create', query: { template: template.value?.slug ?? 'tamil-hindu-wedding' } })),
  }
}
