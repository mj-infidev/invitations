/**
 * 7 · Welcome. The couple appear holding garlands, exchange them in an arc,
 * then fold their hands; the closing line follows.
 *
 * The markup has each garland on the other's neck. Moving it back by the
 * distance to the giver's hands (SVG units, viewBox 0 0 400 420) is the start.
 */
const HANDS_TO_NECK = { x: 140, y: 95 }

export default function welcome(tl, q) {
  tl.from(q('.welcome__halo'), { scale: 0.6, opacity: 0, duration: 0.5 }, 0)
    .from(q('.couple-art'), { y: '8vh', opacity: 0, duration: 0.5 }, 0)
    .addLabel('exchange', 0.6)
    .from(q('.garland--from-bride'), { x: -HANDS_TO_NECK.x, y: HANDS_TO_NECK.y, duration: 0.9, ease: 'power1.inOut' }, 'exchange')
    .from(q('.garland--from-groom'), { x: HANDS_TO_NECK.x, y: HANDS_TO_NECK.y, duration: 0.9, ease: 'power1.inOut' }, 'exchange')
    // The arc: up and over, then down onto the neck.
    .to(q('.garland__lift'), { y: -60, duration: 0.45, ease: 'sine.out' }, 'exchange')
    .to(q('.garland__lift'), { y: 0, duration: 0.45, ease: 'sine.in' }, 'exchange+=0.45')
    .addLabel('fold', '>0.1')
    .fromTo(q('.arms--down'), { opacity: 1 }, { opacity: 0, duration: 0.25 }, 'fold')
    .from(q('.arms--folded'), { opacity: 0, duration: 0.25 }, 'fold')
    .from(q('.welcome > *'), { y: 24, opacity: 0, duration: 0.35, stagger: 0.15 }, '>')
}
