// Cobble & Candle landing page: Freemius checkout and click events (GA4 and, if one is added, Tag Manager). Nothing else loads here.
import { initFreemiusCheckout } from './freemius-checkout.js'
import { trackEvent } from './track.js'

function initTracking() {
  document.addEventListener('click', (event) => {
    const el = event.target.closest('[data-cc-track], [data-fs-buy]')
    if (!el) return

    if (el.hasAttribute('data-fs-buy')) {
      // begin_checkout (GA4) is sent by the checkout script; this is the plain click event for Tag Manager triggers.
      trackEvent('cc_buy_click', { cc_plan: el.dataset.planLabel || '', cc_licenses: el.dataset.licenses || '' })
      return
    }

    trackEvent('cc_cta_click', { cc_cta: el.dataset.ccTrack })
  })
}

function initStickyBar() {
  const bar = document.querySelector('[data-cc-sticky]')
  const hero = document.querySelector('.cc-hero')
  const pricing = document.querySelector('#pricing')
  if (!bar || !hero || !('IntersectionObserver' in window)) return

  const seen = new Set()
  const update = (entries) => {
    entries.forEach((entry) => (entry.isIntersecting ? seen.add(entry.target) : seen.delete(entry.target)))
    bar.classList.toggle('is-on', seen.size === 0)
  }
  const observer = new IntersectionObserver(update, { threshold: 0.05 })
  observer.observe(hero)
  if (pricing) observer.observe(pricing)
  // Before the first callback both targets are "not seen"; start hidden.
  bar.classList.remove('is-on')
}

initTracking()
initStickyBar()
initFreemiusCheckout()
