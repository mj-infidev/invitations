/**
 * Converts a template's scene artwork to WebP for the invitation page.
 *
 *   node scripts/scene-art.mjs <template-slug> [scene=phone.png[,desktop.png] ...]
 *
 * Reads each scene's portrait picture and, if there is one, its landscape one, from
 * ../art/scenes/<slug>/ in either layout:
 *   <scene>.png and <scene>-desktop.png, or phone/<scene>.png and desktop/<scene>.png
 * (the flat files win where both exist). `scene=` arguments take a scene's
 * pictures from elsewhere. Writes public/scenes/<slug>/<scene>-phone-<w>.webp
 * and <scene>-desktop-<w>.webp, which the page finds by the invitation's
 * template slug (App\Support\InvitationPage::sceneArt). Needs ffmpeg on the PATH.
 *
 * art/ holds the large source PNGs and is not in git; the WebP files are.
 */
import { execFileSync } from 'node:child_process'
import { existsSync, mkdirSync, readdirSync, statSync } from 'node:fs'
import { basename, dirname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

// Widths: a phone size and the full source size, for the browser to choose between.
const SIZES = { phone: [640, 941], desktop: [960, 1672] }
const QUALITY = 72

const api = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const [slug, ...overrides] = process.argv.slice(2)
if (!slug) {
  console.error('Usage: node scripts/scene-art.mjs <template-slug> [scene=phone.png[,desktop.png] ...]')
  process.exit(1)
}

const source = resolve(api, '../art/scenes', slug)
const pngs = (shape) => {
  const dir = join(source, shape)
  if (!existsSync(dir)) return {}
  return Object.fromEntries(readdirSync(dir).filter((f) => f.endsWith('.png')).map((f) => [basename(f, '.png'), join(dir, f)]))
}

const phone = pngs('phone')
const desktop = pngs('desktop')
for (const [name, file] of Object.entries(pngs('.'))) {
  if (name.endsWith('-desktop')) desktop[name.slice(0, -'-desktop'.length)] = file
  else phone[name] = file
}
for (const arg of overrides) {
  const [scene, files] = arg.split('=')
  const [p, d] = files.split(',').map((file) => resolve(file))
  phone[scene] = p
  if (d) desktop[scene] = d
}

const out = join(api, 'public/scenes', slug)
mkdirSync(out, { recursive: true })

const kb = (file) => `${Math.round(statSync(file).size / 1024)} KB`
const missing = []

for (const [scene, file] of Object.entries(phone)) {
  const shapes = { phone: file }
  if (desktop[scene]) shapes.desktop = desktop[scene]
  else missing.push(scene)

  for (const [shape, input] of Object.entries(shapes)) {
    for (const width of SIZES[shape]) {
      const target = join(out, `${scene}-${shape}-${width}.webp`)
      execFileSync('ffmpeg', ['-v', 'error', '-y', '-i', input, '-vf', `scale=${width}:-2:flags=lanczos`,
        '-c:v', 'libwebp', '-quality', String(QUALITY), '-compression_level', '6', target])
      console.log(`${basename(target)}  ${kb(target)}`)
    }
  }
}

if (missing.length) console.log(`\nNo desktop picture (the portrait one is used): ${missing.join(', ')}`)
