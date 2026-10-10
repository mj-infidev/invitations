/**
 * The muhurtham countdown: `[data-countdown]` with `[data-unit]` cells. When the
 * time has come the box gets .is-done, which shows its message instead.
 */
const UNITS = { days: 86400, hours: 3600, minutes: 60, seconds: 1 }

export function startCountdown(isoTarget) {
  const box = document.querySelector('[data-countdown]')
  if (!box || !isoTarget) return

  const target = new Date(isoTarget).getTime()
  const cells = Object.keys(UNITS).map((unit) => [unit, box.querySelector(`[data-unit="${unit}"]`)])
  let timer = null

  const tick = () => {
    let left = Math.max(0, Math.floor((target - Date.now()) / 1000))
    if (left === 0) {
      box.classList.add('is-done')
      clearInterval(timer)
      return
    }
    for (const [unit, cell] of cells) {
      const value = Math.floor(left / UNITS[unit])
      left -= value * UNITS[unit]
      cell.textContent = unit === 'days' ? String(value) : String(value).padStart(2, '0')
    }
  }

  tick()
  timer = setInterval(tick, 1000)
}
