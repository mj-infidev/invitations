/**
 * 5 · Venue. The mandapam slides in with the venue details; if there are other
 * events, the mandapam then steps aside and they take its place.
 */
export default function venue(tl, q) {
  tl.from(q('.venue__mandapam'), { xPercent: 80, opacity: 0, duration: 0.6 }, 0)
    .from(q('.venue__info > *'), { y: 24, opacity: 0, duration: 0.35, stagger: 0.12 }, 0.3)

  if (q('.venue__events').length) {
    tl.to(q('.venue__mandapam'), { xPercent: -60, opacity: 0, duration: 0.4 }, '+=0.3')
      .from(q('.venue__events > *'), { y: 30, opacity: 0, duration: 0.3, stagger: 0.1 }, '<0.15')
  }
}
