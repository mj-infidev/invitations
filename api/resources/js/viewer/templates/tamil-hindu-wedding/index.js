/**
 * Tamil Hindu wedding. Vite entry loaded by the Blade page
 * resources/views/invitations/templates/tamil-hindu-wedding.blade.php.
 */
// Fonts, self-hosted. Each file declares every subset with a unicode-range, and
// the browser downloads only the Tamil and Latin pieces the page uses.
// Tamil fonts are reviewed at Step 7.
import '@fontsource/tiro-tamil/400.css'
import '@fontsource/anek-tamil/400.css'
import '@fontsource/anek-tamil/600.css'
import '@fontsource/great-vibes/400.css'
import '@fontsource/cormorant-garamond/500.css'
import '@fontsource/cormorant-garamond/600.css'
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
