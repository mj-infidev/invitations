/**
 * In the builder's preview (?preview=… in an iframe) the page is reloaded after
 * the customer's edits. It keeps its scroll position across those reloads, so
 * the customer stays on the scene they were looking at.
 *
 * sessionStorage belongs to this tab (and to the iframe's own origin); it can
 * be unavailable, so every use is guarded and the page works without it.
 */
const KEY = `preview-scroll:${location.pathname}`

export function rememberPreviewScroll() {
  if (!new URLSearchParams(location.search).has('preview')) return

  let saved = null
  try {
    saved = sessionStorage.getItem(KEY)
  } catch {
    return
  }

  // Back to where the customer was, once every scene's pin has been measured.
  if (saved !== null) window.scrollTo(0, Number(saved) || 0)

  let timer = null
  window.addEventListener('scroll', () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
      try {
        sessionStorage.setItem(KEY, String(Math.round(window.scrollY)))
      } catch {
        // Storage full or blocked: the next reload starts at the top.
      }
    }, 150)
  }, { passive: true })
}
