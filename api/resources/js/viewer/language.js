/**
 * The தமிழ் / English switch on "both" invitations. The page holds every text
 * in both languages and shows the one in <html lang> (base.css), so switching
 * is just changing that attribute. Listeners get a `viewer:language` event.
 *
 * @param {() => void} onChange  Called after a switch; text length changes, so
 *        scroll positions need re-measuring.
 */
export function setupLanguageSwitch(onChange) {
  const buttons = [...document.querySelectorAll('.lang-switch [data-lang]')]
  if (!buttons.length) return

  for (const button of buttons) {
    button.addEventListener('click', () => {
      const lang = button.dataset.lang
      if (document.documentElement.lang === lang) return

      document.documentElement.lang = lang
      buttons.forEach((other) => other.setAttribute('aria-pressed', String(other === button)))
      document.dispatchEvent(new CustomEvent('viewer:language', { detail: { lang } }))
      onChange()
    })
  }
}
