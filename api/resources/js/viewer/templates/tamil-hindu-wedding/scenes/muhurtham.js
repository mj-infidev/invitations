/** 4 · Muhurtham. The frame and bells settle, the date and time rise in, then the countdown. */
export default function muhurtham(tl, q) {
  tl.from(q('.muhurtham__frame'), { scale: 0.9, opacity: 0, duration: 0.4 }, 0)
    .from(q('.bell'), { y: '-30vh', duration: 0.5, stagger: 0.1 }, 0)
    .from(q('.muhurtham__line'), { y: 24, opacity: 0, duration: 0.35, stagger: 0.15 }, 0.25)
    .from(q('.countdown__unit'), { scale: 0.6, opacity: 0, duration: 0.25, stagger: 0.06 }, '>-0.1')
    .to(q('.bell--left'), { rotation: 8, transformOrigin: '50% 0%', duration: 0.3, ease: 'sine.inOut' }, '>')
    .to(q('.bell--right'), { rotation: -8, transformOrigin: '50% 0%', duration: 0.3, ease: 'sine.inOut' }, '<')
}
