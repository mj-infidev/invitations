/**
 * Optional background music. Browsers block sound that starts by itself, so it
 * only ever starts from the guest's tap on the button.
 */
export function setupMusic(src) {
  const button = document.querySelector('[data-music]')
  if (!button || !src) return

  // { play: { ta, en }, pause: { ta, en } }: the button is named in the language shown.
  const labels = JSON.parse(button.dataset.labels)

  const audio = new Audio()
  audio.preload = 'none'
  audio.loop = true
  audio.src = src

  const show = () => {
    const playing = !audio.paused
    const names = labels[playing ? 'pause' : 'play']
    button.classList.toggle('is-playing', playing)
    button.setAttribute('aria-pressed', String(playing))
    button.setAttribute('aria-label', names[document.documentElement.lang] ?? Object.values(names)[0])
  }

  button.addEventListener('click', async () => {
    if (audio.paused) {
      try {
        await audio.play()
      } catch {
        // Blocked or failed to load: leave the button showing "play".
      }
    } else {
      audio.pause()
    }
    show()
  })

  document.addEventListener('viewer:language', show)

  // Stop when the guest switches to another app or tab.
  document.addEventListener('visibilitychange', () => {
    if (document.hidden && !audio.paused) {
      audio.pause()
      show()
    }
  })
}
