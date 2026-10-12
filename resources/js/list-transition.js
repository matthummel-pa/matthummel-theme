/**
 * Animate a list while its items change (filtering, paging, live search).
 *
 * With the View Transitions API, items that stay slide to their new place and
 * the rest fade. Elsewhere the list fades out, changes, and fades back in
 * (`.is-swapping` / `.is-swapped-in` in studio.css). With reduced motion the
 * change is instant.
 */

const OUT_MS = 130
const IN_MS = 220

function prefersReducedMotion() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

/**
 * Give each item a stable `view-transition-name` so it is tracked across the change.
 *
 * @param {Iterable<Element>} items
 * @param {(item: Element, index: number) => string} keyOf Unique, CSS-identifier-safe key per item.
 */
export function nameItems(items, keyOf) {
  let i = 0
  for (const item of items) {
    const key = String(keyOf(item, i)).replace(/[^a-z0-9_-]/gi, '-')
    item.style.viewTransitionName = key === '' ? '' : `mh-${key}`
    i += 1
  }
}

/**
 * Run `mutate` (a synchronous DOM change) with motion.
 *
 * @param {Element|null} list The list being changed.
 * @param {() => void} mutate
 * @param {{ rich?: boolean }} [options] `rich` allows a view transition; leave it off for per-keystroke updates.
 * @returns {Promise<void>} Resolves once the new state is on screen.
 */
export function swapWithMotion(list, mutate, { rich = false } = {}) {
  if (!list || prefersReducedMotion()) {
    mutate()
    return Promise.resolve()
  }

  if (rich && typeof document.startViewTransition === 'function') {
    try {
      const transition = document.startViewTransition(() => mutate())
      // `finished` rejects when a newer transition skips this one; the DOM is still correct.
      return transition.finished.catch(() => {})
    } catch {
      mutate()
      return Promise.resolve()
    }
  }

  list.classList.remove('is-swapped-in')
  list.classList.add('is-swapping')
  return new Promise((resolve) => {
    window.setTimeout(() => {
      mutate()
      list.classList.remove('is-swapping')
      list.classList.add('is-swapped-in')
      window.setTimeout(() => {
        list.classList.remove('is-swapped-in')
        resolve()
      }, IN_MS)
    }, OUT_MS)
  })
}
