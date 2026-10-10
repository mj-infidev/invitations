/** 2 · The couple. The thoranam drops in, the banana trees slide in, then the names, line by line. */
export default function couple(tl, q) {
  tl.from(q('.couple__arch'), { scale: 1.15, opacity: 0, duration: 0.5 }, 0)
    .from(q('.thoranam'), { yPercent: -100, duration: 0.5 }, 0.1)
    .from(q('.banana--left'), { xPercent: -100, duration: 0.6 }, 0.15)
    .from(q('.banana--right'), { xPercent: 100, duration: 0.6 }, 0.15)
    // Whole lines only: Tamil letters join with their vowel signs and break if split.
    .from(q('.couple__role, .couple__name .bi, .couple__and'), { y: 28, opacity: 0, duration: 0.35, stagger: 0.12 }, 0.5)
    .to(q('.banana'), { y: '2vh', duration: 0.5, ease: 'none' }, '>')
    .to(q('.thoranam'), { y: '-1vh', duration: 0.5, ease: 'none' }, '<')
}
