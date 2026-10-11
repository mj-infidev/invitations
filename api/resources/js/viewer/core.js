/**
 * The invitation viewer: plays each scene's animation from the scroll position.
 * Shared by every template; a template passes its scenes to startViewer().
 *
 * With motion, every scene's stage is fixed to the screen, one above the other,
 * and its section is only scroll room (CSS `--length`, in % of the screen
 * height). While the guest scrolls through a section its stage cross-fades in
 * over the one before, its picture zooms slowly, and its text rises in, holds,
 * and fades away before the next scene. Only opacity and transforms animate.
 *
 * The page's markup is every scene's finished state. Scenes build their
 * timelines with from(), so with reduced motion (or if this script fails)
 * nothing is left hidden: guests just get the finished scenes, one after another.
 */
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import { startCountdown } from './countdown'
import { setupMusic } from './music'
import { setupLanguageSwitch } from './language'
import { rememberPreviewScroll } from './preview-scroll'
import { loadLotties } from './lottie'

gsap.registerPlugin(ScrollTrigger)

/** A scene's timeline runs from 0 to SCENE, whatever its scroll length. */
export const SCENE = 10
/** The cross-fade from the scene before, at the start of each scene. */
const FADE = 1.6

/** The text's lines rise and fade in, one after another. */
export function riseIn(tl, targets, at = FADE * 0.6, stagger = 0.45) {
  return tl.from(targets, { y: 36, autoAlpha: 0, duration: 1.6, stagger, ease: 'power2.out' }, at)
}

/**
 * The text drifts up and fades out before the next scene fades in. Animates the
 * container, not the lines, so it never fights riseIn() over the same elements.
 */
export function riseOut(tl, target, at = SCENE - 1.4) {
  return tl.to(target, { y: -28, autoAlpha: 0, duration: 1.2, ease: 'power1.in' }, at)
}

/** What a scene does when its template has nothing of its own: lines in, hold, out. */
function standardScene(tl, q) {
  riseIn(tl, q('.rise'))
  riseOut(tl, q('.scene__content'))
}

/**
 * @param {object} template
 * @param {Record<string, (tl: gsap.core.Timeline, q: (selector: string) => Element[], section: HTMLElement) => void>} [template.scenes]
 *        Builders by `data-scene` name, for scenes that do more than the
 *        standard; each adds tweens between 0 and SCENE.
 * @param {Record<string, () => Promise<{default: object}>>} [template.lotties]
 *        Lottie files by `data-lottie` name, imported when the guest gets near.
 */
export function startViewer({ scenes = {}, lotties = {} } = {}) {
  const config = JSON.parse(document.getElementById('invitation-data').textContent)
  startCountdown(config.muhurthamAt)
  setupMusic(config.music)
  // Text length changes with the language: re-measure.
  setupLanguageSwitch(() => ScrollTrigger.refresh())

  // The address bar showing and hiding on phones resizes the window; re-measuring
  // on each of those makes the page jump.
  ScrollTrigger.config({ ignoreMobileResize: true })

  const sections = gsap.utils.toArray('[data-scene]')
  const mm = gsap.matchMedia()

  mm.add('(prefers-reduced-motion: no-preference)', () => {
    // Also set by the inline script in the page's <head>, before first paint.
    document.documentElement.classList.add('motion')

    sections.forEach((section, i) => {
      const q = gsap.utils.selector(section)
      const stage = section.querySelector('.scene__stage')
      const last = i === sections.length - 1

      // The picture loads (the CSS renders it) from a screen and a half before
      // its scene. The first is always rendered (style.css).
      if (i > 0) {
        ScrollTrigger.create({
          trigger: section,
          start: 'top bottom+=150%',
          end: 'max',
          once: true,
          onEnter: () => section.classList.add('is-near'),
        })
      }

      const tl = gsap.timeline({
        defaults: { ease: 'power1.out' },
        scrollTrigger: {
          trigger: section,
          start: 'top top',
          // The last scene has no scene after it to scroll into.
          end: last ? 'bottom bottom' : 'bottom top',
          // A short catch-up instead of `true`: on touch screens it smooths the
          // steps between scroll events, which read as stutter on cheap phones.
          scrub: 0.5,
          // No invalidateOnRefresh: nothing is measured from the screen, and it
          // would drop the hidden start of from() tweens later in the scene.
        },
      })

      // Slow zoom on the picture for the whole scene.
      tl.fromTo(q('.scene__art img'), { scale: 1 }, { scale: 1.12, duration: SCENE, ease: 'none' }, 0)

      if (i > 0) {
        tl.fromTo(stage, { autoAlpha: 0 }, { autoAlpha: 1, duration: FADE, ease: 'none' }, 0)
        // Once covered, the scene before stops being drawn.
        tl.set(sections[i - 1].querySelector('.scene__stage'), { visibility: 'hidden' }, FADE)
      }

      ;(scenes[section.dataset.scene] ?? standardScene)(tl, q, section)

      // Pad to the full length, so every scene's tweens sit at the same scroll points.
      if (tl.duration() < SCENE) tl.set({}, {}, SCENE)

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

  ScrollTrigger.refresh()
  rememberPreviewScroll()

  mm.add('(prefers-reduced-motion: reduce)', () => {
    sections.forEach((section) => {
      gsap.from(section.querySelector('.scene__content'), {
        opacity: 0,
        duration: 0.8,
        scrollTrigger: { trigger: section, start: 'top 85%', once: true },
      })
    })
  })

  // The page's <head> takes .motion away again if this never got here.
  document.documentElement.classList.add('viewer-ready')
}
