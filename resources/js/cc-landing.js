// Cobble & Candle landing page: Freemius checkout and click events for Google Tag Manager. Nothing else loads here.
import { initFreemiusCheckout } from './freemius-checkout.js'

function pushEvent(data) {
  window.dataLayer = window.dataLayer || []
  window.dataLayer.push(data)
}

function initTracking() {
  document.addEventListener('click', (event) => {
    const el = event.target.closest('[data-cc-track], [data-fs-buy]')
    if (!el) return

    if (el.hasAttribute('data-fs-buy')) {
      pushEvent({
        event: 'cc_buy_click',
        cc_plan: el.dataset.ccPlan || '',
        cc_licenses: el.dataset.licenses || '',
      })
      return
    }

    pushEvent({ event: 'cc_cta_click', cc_cta: el.dataset.ccTrack })
  })
}

initTracking()
initFreemiusCheckout()
