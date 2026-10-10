/**
 * 3 · Families. The kolam's dots appear, then its lines draw one at a time
 * around the parents' names and the family's message.
 *
 * Drawing a line animates stroke-dashoffset, the one exception to animating
 * only position, scale, rotation and opacity. The kolam is a small SVG, so the
 * repaint per frame is cheap; pathLength="1" on each line makes 1 a full line.
 */
export default function families(tl, q) {
  const lines = q('.kolam__line')
  tl.set(lines, { strokeDasharray: 1 }, 0)
    .from(q('.kolam__dot'), { scale: 0, opacity: 0, transformOrigin: '50% 50%', duration: 0.2, stagger: 0.015 }, 0)
    .fromTo(lines, { strokeDashoffset: 1 }, { strokeDashoffset: 0, duration: 0.35, stagger: 0.07, ease: 'none' }, 0.2)
    .from(q('.families__line'), { y: 24, opacity: 0, duration: 0.35, stagger: 0.18 }, 0.35)
}
