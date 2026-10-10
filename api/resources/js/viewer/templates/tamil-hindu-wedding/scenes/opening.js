/**
 * 1 · Opening. Starts at night with only the உ and the unlit lamp; scrolling
 * lights the lamp, brings the dawn, and raises the gopuram tier by tier.
 * Only position, scale, rotation and opacity are animated, in every scene.
 */
export default function opening(tl, q) {
  tl.to(q('.scroll-hint'), { opacity: 0, y: -12, duration: 0.25 }, 0)
    .from(q('.lamp__flame'), { scale: 0, opacity: 0, transformOrigin: '50% 100%', duration: 0.3, stagger: 0.12 }, 0.05)
    .from(q('.lamp__glow'), { scale: 0.3, opacity: 0, duration: 0.5 }, 0.15)
    .from(q('.opening__dawn'), { opacity: 0, duration: 1, ease: 'none' }, 0.2)
    .from(q('.opening__sun'), { y: '40vh', opacity: 0, duration: 1 }, 0.3)
    // Ground first, kalasams last. SVG units: 260 is below the ground line.
    .from(q('.gopuram__part'), { y: 260, opacity: 0, duration: 0.45, stagger: 0.14 }, 0.35)
    // Depth: once risen, the far layers drift more slowly than the near ones.
    .to(q('.opening__sun'), { y: '-3vh', duration: 0.6, ease: 'none' }, '>')
    .to(q('.opening__gopuram'), { y: '-2vh', duration: 0.6, ease: 'none' }, '<')
    .to(q('.lamp'), { y: '3vh', duration: 0.6, ease: 'none' }, '<')
}
