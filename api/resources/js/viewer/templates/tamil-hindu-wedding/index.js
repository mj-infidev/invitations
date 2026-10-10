/**
 * Tamil Hindu wedding. Vite entry loaded by the Blade page
 * resources/views/invitations/templates/tamil-hindu-wedding.blade.php.
 */
import '../../base.css'
import './style.css'
import { startViewer } from '../../core'
import opening from './scenes/opening'
import couple from './scenes/couple'
import families from './scenes/families'
import muhurtham from './scenes/muhurtham'
import venue from './scenes/venue'
import photos from './scenes/photos'
import welcome from './scenes/welcome'

startViewer({
  scenes: { opening, couple, families, muhurtham, venue, photos, welcome },
  lotties: {
    flame: () => import('./lottie/flame.json'),
    garland: () => import('./lottie/garland.json'),
  },
})
