import { riseIn, riseOut } from '../../../core'

/** 6 · Photos. The heading, then the photos one by one, each settling from a little larger. */
export default function photos(tl, q) {
  riseIn(tl, q('.rise'))
  tl.from(q('.photo'), { scale: 1.08, autoAlpha: 0, duration: 1.2, stagger: 0.5, ease: 'power2.out' }, 1.8)
  riseOut(tl, q('.scene__content'))
}
