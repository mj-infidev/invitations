/**
 * Reading and writing an invitation's `data` by the dot paths its template's
 * fields_schema uses (PLAN.md > Template form fields): `bride.name.ta`,
 * `events.0.date`. Shared by the customer builder and the admin edit screen.
 */

export function getPath(object, path) {
  return path.split('.').reduce((value, key) => (value == null ? undefined : value[key]), object)
}

/** Sets a value, creating the objects (or arrays, before a numeric key) on the way. */
export function setPath(object, path, value) {
  const keys = path.split('.')
  let target = object
  keys.slice(0, -1).forEach((key, i) => {
    if (target[key] == null || typeof target[key] !== 'object') {
      target[key] = /^\d+$/.test(keys[i + 1]) ? [] : {}
    }
    target = target[key]
  })
  target[keys[keys.length - 1]] = value
}

/** A new, empty repeater item: each of its fields blank. */
export function emptyItem(fields) {
  return Object.fromEntries(fields.map((field) => [field.key, field.bilingual ? { ta: null, en: null } : null]))
}

/** The id of a field's input, so the "still needed" list can jump to it. */
export function inputId(path, lang = null) {
  return `field-${path.replaceAll('.', '-')}${lang ? `-${lang}` : ''}`
}
