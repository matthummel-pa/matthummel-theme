/**
 * Shared-layout active indicator for nav rails.
 *
 * One absolutely positioned capsule per rail slides between items instead of each
 * item snapping its own background on and off. Rails: the desktop header nav
 * (follows hover, rests on the current page) and the sticky "On this page" pills
 * (follows `.is-active`, set by section-nav.js). Progressive: the rail gets
 * `.has-nav-indicator` only when this runs, so no-JS and reduced-motion visitors
 * keep the static styles in portfolio.css / studio.css.
 */

const RAILS = [
  { list: '.header-nav-list', item: 'a', active: '.current-menu-item > a, a[aria-current="page"]', followHover: true },
  { list: '[data-page-nav-scroller]', item: 'a', active: 'a.is-active, a[aria-current="true"]', followHover: false },
]

export function initNavIndicators() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return

  RAILS.forEach((rail) => {
    document.querySelectorAll(rail.list).forEach((list) => mountIndicator(list, rail))
  })
}

function mountIndicator(list, rail) {
  const items = [...list.querySelectorAll(rail.item)]
  if (items.length < 2) return

  // A <ul> rail gets an <li> so the markup stays valid; a <div> rail gets a <span>.
  const indicator = document.createElement(list.tagName === 'UL' ? 'li' : 'span')
  indicator.className = 'mh-nav-indicator'
  indicator.setAttribute('aria-hidden', 'true')
  indicator.setAttribute('role', 'presentation')
  list.prepend(indicator)
  list.classList.add('has-nav-indicator')

  let resting = list.querySelector(rail.active)
  let settled = false

  function moveTo(target) {
    if (!target || target.offsetWidth === 0) {
      indicator.style.opacity = '0'
      return
    }
    // Measure against the rail itself (not offsetParent, which is the <li> in a <ul>),
    // adding the rail's own scroll so the capsule rides along inside an overflow track.
    const railBox = list.getBoundingClientRect()
    const box = target.getBoundingClientRect()
    const x = box.left - railBox.left + list.scrollLeft - list.clientLeft
    const y = box.top - railBox.top + list.scrollTop - list.clientTop
    indicator.style.opacity = '1'
    indicator.style.width = `${box.width}px`
    indicator.style.height = `${box.height}px`
    indicator.style.transform = `translate3d(${x}px, ${y}px, 0)`
    if (!settled) {
      // First paint: jump, then enable the transition so nothing slides in from 0,0.
      indicator.getBoundingClientRect()
      indicator.classList.add('is-settled')
      settled = true
    }
  }

  function syncResting() {
    resting = list.querySelector(rail.active)
    list.classList.toggle('has-nav-indicator--resting', Boolean(resting))
    moveTo(resting)
  }

  if (rail.followHover) {
    items.forEach((item) => {
      item.addEventListener('pointerenter', () => moveTo(item))
      item.addEventListener('focus', () => moveTo(item))
    })
    list.addEventListener('pointerleave', () => moveTo(resting))
    list.addEventListener('focusout', (event) => {
      if (!list.contains(event.relatedTarget)) moveTo(resting)
    })
  }

  // Active item changes: section-nav.js toggles .is-active / aria-current on scroll.
  new MutationObserver(syncResting).observe(list, {
    subtree: true,
    attributes: true,
    attributeFilter: ['class', 'aria-current'],
  })

  // Re-measure when the rail wraps, the font loads, or the header nav shows/hides at 900px.
  if ('ResizeObserver' in window) new ResizeObserver(syncResting).observe(list)
  window.addEventListener('resize', syncResting)

  syncResting()
}
