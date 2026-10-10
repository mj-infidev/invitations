/**
 * Small looping animations (lamp flame, swinging garlands). Each
 * `[data-lottie="name"]` box holds a still drawing for reduced motion; once its
 * loop has loaded the box gets .is-playing and the still is hidden.
 */
let lottiePlayer = null

/** @returns {Promise<Array<{play: () => void, pause: () => void}>>} */
export async function loadLotties(section, files) {
  // The light build: SVG renderer only, about half the size of the full player.
  lottiePlayer ??= import('lottie-web/build/player/lottie_light').then((module) => module.default)
  const lottie = await lottiePlayer

  const boxes = [...section.querySelectorAll('[data-lottie]')]
  return Promise.all(boxes.map(async (box) => {
    const file = files[box.dataset.lottie]
    if (!file) return { play() {}, pause() {} }

    const { default: data } = await file()
    const player = lottie.loadAnimation({
      container: box,
      renderer: 'svg',
      loop: true,
      autoplay: false,
      // Lottie writes into the data it is given; boxes sharing a file need their own copy.
      animationData: structuredClone(data),
    })
    box.classList.add('is-playing')
    return player
  }))
}
