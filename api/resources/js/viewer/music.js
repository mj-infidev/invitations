/**
 * Optional background music. Browsers block sound that starts by itself, so it
 * only ever starts from the guest's tap on the button.
 */
export function setupMusic(src) {
  const button = document.querySelector('[data-music]')
  if (!button || !src) return

  const audio = new Audio()
  audio.preload = 'none'
  audio.loop = true
  audio.src = src

  const show = () => {
    const playing = !audio.paused
    button.classList.toggle('is-playing', playing)
    button.setAttribute('aria-pressed', String(playing))
    button.setAttribute('aria-label', playing ? button.dataset.labelPause : button.dataset.labelPlay)
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

  // Stop when the guest switches to another app or tab.
  document.addEventListener('visibilitychange', () => {
    if (document.hidden && !audio.paused) {
      audio.pause()
      show()
    }
  })
}
