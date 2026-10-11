/**
 * Tamil Hindu wedding. Vite entry loaded by the Blade page
 * resources/views/invitations/templates/tamil-hindu-wedding.blade.php.
 * Scene pictures are in public/scenes/tamil-hindu-wedding (scripts/scene-art.mjs).
 */
// This template's colours and fonts (Tamil fonts are reviewed at Step 8).
import './theme.css'
import '../../base.css'
import './style.css'
import { startViewer } from '../../core'
import opening from './scenes/opening'
import venue from './scenes/venue'
import photos from './scenes/photos'
import welcome from './scenes/welcome'

// The couple, families and muhurtham scenes are the standard: lines in, hold, out.
startViewer({ scenes: { opening, venue, photos, welcome } })
