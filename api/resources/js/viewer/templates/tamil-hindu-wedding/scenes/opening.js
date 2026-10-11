import { gsap } from 'gsap'
import { riseOut } from '../../../core'

/**
 * 1 · Opening. On screen from the start, so its words rise in once on arrival
 * rather than with the scroll; scrolling fades them and the hint away.
 */
export default function opening(tl, q) {
  gsap.from(q('.rise'), { y: 30, autoAlpha: 0, duration: 1.4, stagger: 0.25, delay: 0.2, ease: 'power2.out' })
  tl.to(q('.scroll-hint'), { autoAlpha: 0, y: -12, duration: 1 }, 0)
  riseOut(tl, q('.scene__content'))
}
