/** 6 · Photos. The frames arrive one by one, each swinging in from its own side. */
export default function photos(tl, q) {
  tl.from(q('.photos__heading'), { y: 20, opacity: 0, duration: 0.3 }, 0)
  q('.photo').forEach((photo, i) => {
    const side = i % 2 ? 1 : -1
    tl.from(photo, {
      x: () => side * window.innerWidth * 0.6,
      y: '30vh',
      rotation: side * 25,
      opacity: 0,
      duration: 0.5,
    }, i === 0 ? 0.2 : '>-0.25')
  })
}
