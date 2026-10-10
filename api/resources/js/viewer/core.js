/**
 * The invitation viewer: plays each scene's animation from the scroll position
 * while the scene is pinned. Shared by every template; a template passes its
 * scenes and Lottie files to startViewer().
 *
 * The page's markup is every scene's finished state. Scenes build their
 * timelines with from(), so with reduced motion (or if this script fails)
 * nothing is left hidden: guests just get the finished scenes with a fade.
 */
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import { startCountdown } from './countdown'
import { setupMusic } from './music'
import { setupLanguageSwitch } from './language'
import { rememberPreviewScroll } from './preview-scroll'
import { loadLotties } from './lottie'

gsap.registerPlugin(ScrollTrigger)

/**
 * @param {object} template
 * @param {Record<string, (tl: gsap.core.Timeline, q: (selector: string) => Element[], section: HTMLElement) => void>} template.scenes
 *        One builder per `data-scene` name. It adds tweens to a timeline whose
 *        progress follows the scroll through that scene.
 * @param {Record<string, () => Promise<{default: object}>>} template.lotties
 *        Lottie files by `data-lottie` name, imported when the guest gets near.
 */
export function startViewer({ scenes, lotties }) {
  const config = JSON.parse(document.getElementById('invitation-data').textContent)
  startCountdown(config.muhurthamAt)
  setupMusic(config.music)
  // Tamil runs longer than English: every pin and scene length is measured again.
  setupLanguageSwitch(() => ScrollTrigger.refresh())

  // The address bar showing and hiding on phones resizes the window; re-measuring
  // every pin on each of those makes the page jump.
  ScrollTrigger.config({ ignoreMobileResize: true })

  const sections = gsap.utils.toArray('[data-scene]')
  const mm = gsap.matchMedia()

  mm.add('(prefers-reduced-motion: no-preference)', () => {
    // Also set by the inline script in the page's <head>, before first paint.
    document.documentElement.classList.add('motion')

    // In page order: each pin pushes the scenes below it down.
    sections.forEach((section) => {
      const build = scenes[section.dataset.scene]
      if (!build) return

      // data-length: how far the guest scrolls through the scene, in % of the screen height.
      const length = Number(section.dataset.length || 150) / 100
      const tl = gsap.timeline({
        defaults: { ease: 'power1.out' },
        scrollTrigger: {
          trigger: section,
          start: 'top top',
          end: () => `+=${Math.round(window.innerHeight * length)}`,
          pin: true,
          // A short catch-up instead of `true`: on touch screens it smooths the
          // steps between scroll events, which read as stutter on cheap phones.
          scrub: 0.5,
          anticipatePin: 1,
          invalidateOnRefresh: true,
        },
      })
      build(tl, gsap.utils.selector(section), section)

      // Loops load a screen before the scene arrives, and only play while it's on screen.
      if (section.querySelector('[data-lottie]')) {
        let players = null
        ScrollTrigger.create({
          trigger: section,
          start: 'top bottom+=100%',
          end: 'bottom top',
          onToggle: async (self) => {
            players ??= loadLotties(section, lotties)
            const loaded = await players
            loaded.forEach((player) => (self.isActive ? player.play() : player.pause()))
          },
        })
      }
    })

    return () => document.documentElement.classList.remove('motion')
  })

  // After the scenes above: their pins set the page length the position refers to.
  ScrollTrigger.refresh()
  rememberPreviewScroll()

  mm.add('(prefers-reduced-motion: reduce)', () => {
    sections.forEach((section) => {
      gsap.from(section.querySelector('.scene__stage'), {
        opacity: 0,
        duration: 0.8,
        scrollTrigger: { trigger: section, start: 'top 85%', once: true },
      })
    })
  })
}
