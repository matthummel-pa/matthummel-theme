/**
 * Release watch blocks (app/release-watch.php, resources/views/blocks/release-*.blade.php).
 *
 * - Banner: keeps "N days to go" right on cached pages and remembers a close per release.
 * - Rundown: turns the feature-area jump links into filter buttons.
 */

export function initReleaseWatch() {
  document.querySelectorAll('.mhrw-banner').forEach(initBanner)
  document.querySelectorAll('.mhrw-rundown').forEach(initRundown)
}

/** Whole days from today (visitor's calendar) to a Y-m-d date. */
function daysUntil(ymd) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(ymd || '')) return null
  const [y, m, d] = ymd.split('-').map(Number)
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())

  return Math.round((new Date(y, m - 1, d) - today) / 86400000)
}

function initBanner(banner) {
  const days = daysUntil(banner.dataset.release)
  const label = banner.querySelector('[data-mhrw-days]')
  if (days !== null && days < 0) {
    banner.hidden = true
    return
  }
  if (label && days !== null) {
    label.textContent = days === 0 ? 'Release day' : `${days} ${days === 1 ? 'day' : 'days'} to go`
  }

  const close = banner.querySelector('[data-mhrw-dismiss]')
  if (!close) return

  close.addEventListener('click', () => {
    try {
      window.localStorage.setItem(banner.dataset.key, '1')
    } catch {
      // Private mode or blocked storage: it just comes back next visit.
    }
    banner.hidden = true

    // Keep keyboard users oriented once the banner is gone.
    const main = document.getElementById('main')
    if (main) main.focus({ preventScroll: true })
  })
}

function initRundown(root) {
  const pill = root.querySelector('[data-mhrw-release]')
  const days = pill ? daysUntil(pill.dataset.mhrwRelease) : null
  if (pill && days !== null && days >= 0) {
    pill.textContent = days === 0 ? 'Release day' : `${days} ${days === 1 ? 'day' : 'days'} away`
  }

  const jump = root.querySelector('[data-mhrw-jump]')
  const list = root.querySelector('[data-mhrw-areas]')
  if (!jump || !list) return

  const status = root.querySelector('[data-mhrw-status]')
  const areas = [...list.querySelectorAll('details.mhrw-area')]
  const links = [...jump.querySelectorAll('a[data-mhrw-area]')]
  if (links.length === 0) return

  const all = makeChip('All areas', String(links.reduce((n, a) => n + Number(a.querySelector('span')?.textContent || 0), 0)), '')
  const chips = [all, ...links.map((a) => makeChip(a.firstChild.textContent.trim(), a.querySelector('span')?.textContent || '', a.dataset.mhrwArea))]

  jump.replaceChildren(...chips)
  jump.classList.add('is-filter')
  jump.setAttribute('role', 'group')
  all.setAttribute('aria-pressed', 'true')

  function show(id) {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    chips.forEach((chip) => chip.setAttribute('aria-pressed', String(chip.dataset.area === id)))
    list.classList.toggle('is-filtered', id !== '')

    areas.forEach((area) => {
      const match = id === '' || area.id === `mhrw-area-${id}`
      area.hidden = !match
      if (id !== '' && match) {
        area.open = true
        if (!reduce) {
          area.classList.remove('is-entering')
          void area.offsetWidth // restart the entrance animation
          area.classList.add('is-entering')
        }
      }
    })

    if (status) {
      const chip = chips.find((c) => c.dataset.area === id)
      status.textContent = id === '' ? 'Showing all areas' : `Showing ${chip?.dataset.label || 'one area'}`
    }
  }

  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      const pressed = chip.getAttribute('aria-pressed') === 'true'
      show(pressed || chip === all ? '' : chip.dataset.area)
    })
  })

  // A link like #mhrw-area-ai (from elsewhere on the site) opens that area.
  const hash = window.location.hash.replace('#mhrw-area-', '')
  if (hash && hash !== window.location.hash && chips.some((c) => c.dataset.area === hash)) show(hash)
}

function makeChip(label, count, area) {
  const button = document.createElement('button')
  button.type = 'button'
  button.className = 'mhrw-chip'
  button.dataset.area = area
  button.dataset.label = label
  button.setAttribute('aria-pressed', 'false')
  button.append(`${label} `)
  const num = document.createElement('span')
  num.textContent = count
  button.append(num)

  return button
}
