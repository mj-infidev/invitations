/**
 * Tamil Hindu wedding. Vite entry loaded by the Blade page
 * resources/views/invitations/templates/tamil-hindu-wedding.blade.php.
 * Scene pictures are in public/scenes/tamil-hindu-wedding (scripts/scene-art.mjs).
 */
// Fonts, self-hosted. Each file declares every subset with a unicode-range, and
// the browser downloads only the Tamil and Latin pieces the page uses.
// Tamil fonts are reviewed at Step 8.
import '@fontsource/tiro-tamil/400.css'
import '@fontsource/anek-tamil/400.css'
import '@fontsource/anek-tamil/600.css'
import '@fontsource/great-vibes/400.css'
import '@fontsource/cormorant-garamond/500.css'
import '@fontsource/cormorant-garamond/500-italic.css'
import '@fontsource/cormorant-garamond/600.css'
import '../../base.css'
import './style.css'
import { startViewer } from '../../core'
import opening from './scenes/opening'
import venue from './scenes/venue'
import photos from './scenes/photos'
import welcome from './scenes/welcome'

// The couple, families and muhurtham scenes are the standard: lines in, hold, out.
startViewer({ scenes: { opening, venue, photos, welcome } })
