import { riseIn, riseOut } from '../../../core'

/**
 * 5 · Venue. The name, address and map button rise in; if there are other
 * events, they then take the venue's place.
 */
export default function venue(tl, q) {
  riseIn(tl, q('.venue__info .rise'))
  if (!q('.venue__events').length) {
    riseOut(tl, q('.venue__info'))
    return
  }
  riseOut(tl, q('.venue__info'), 4.6)
  riseIn(tl, q('.event-in'), 5.6, 0.35)
  riseOut(tl, q('.venue__events'))
}
