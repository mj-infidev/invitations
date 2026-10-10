import { onBeforeUnmount, ref } from 'vue'

/** Whether a media query matches, kept up to date as the window changes. */
export function useMediaQuery(query) {
  const list = window.matchMedia(query)
  const matches = ref(list.matches)
  const update = (event) => { matches.value = event.matches }

  list.addEventListener('change', update)
  onBeforeUnmount(() => list.removeEventListener('change', update))

  return matches
}

/** The builder's phone layout: one column, collapsible sections, a Preview tab. */
export const PHONE = '(max-width: 767px)'
