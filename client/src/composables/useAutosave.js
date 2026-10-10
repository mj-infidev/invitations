import { ref } from 'vue'

/**
 * Saves as the customer types: `schedule()` on every change, and `save()` runs
 * `delay` ms after the last one. One save at a time: changes made while a save
 * is running are sent by the next one, never in parallel, so an older save
 * can't land after a newer one.
 *
 * status: 'saved' | 'pending' (changes waiting) | 'saving' | 'error'
 *
 * @param {() => Promise<unknown>} save  Sends the whole current form.
 */
export function useAutosave(save, { delay = 800 } = {}) {
  const status = ref('saved')
  let timer = null
  let running = null // the one save loop, while it runs
  let dirty = false // changes not yet sent

  function schedule() {
    dirty = true
    status.value = 'pending'
    clearTimeout(timer)
    timer = setTimeout(run, delay)
  }

  /** Starts the save loop, or returns the one already running. */
  function run() {
    clearTimeout(timer)
    timer = null

    running ??= (async () => {
      while (dirty) {
        dirty = false
        status.value = 'saving'
        try {
          await save()
        } catch {
          dirty = true
          status.value = 'error'
          return
        }
        // Still typing: the timer will start the next save.
        if (timer) {
          status.value = 'pending'
          return
        }
      }
      status.value = 'saved'
    })().finally(() => { running = null })

    return running
  }

  /** Sends anything waiting, now. Resolves true when everything is saved. */
  async function flush() {
    await run()
    // A loop that was running when flush began may have left changes behind.
    if (dirty) await run()
    return status.value === 'saved'
  }

  return { status, schedule, flush, retry: run }
}
