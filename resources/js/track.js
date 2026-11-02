/**
 * Marketing and analytics events. The site sends Google Analytics 4 through Site Kit (gtag.js) and has no Tag Manager
 * container today, so events go two ways: to the dataLayer (a Google Tag Manager container, if one is added later, reads it)
 * and to gtag() when gtag.js is on the page and no container is. Nothing is stored in the browser and no cookie is set here.
 * Ad and pixel tags that need consent should be gated by the consent tool, not by this file.
 */
export const CURRENCY = 'USD'

function dataLayer() {
  window.dataLayer = window.dataLayer || []
  return window.dataLayer
}

function hasContainer() {
  const g = window.google_tag_manager
  return !!g && Object.keys(g).some((key) => key.startsWith('GTM-'))
}

// The cache plugin delays Site Kit's gtag.js until the first tap or scroll, so gtag() may not exist yet when an event happens.
// Events wait in memory (never in storage) until it does, for up to 30 seconds, then are dropped. If the visitor's tag never loads
// (an ad blocker, no consent), nothing is sent.
const waiting = []
let waitTimer = null
let waitTries = 0

function flushGtag() {
  if (typeof window.gtag === 'function') {
    while (waiting.length) {
      const [name, params] = waiting.shift()
      window.gtag('event', name, params)
    }
    window.clearInterval(waitTimer)
    waitTimer = null
  } else if (++waitTries > 120) {
    waiting.length = 0
    window.clearInterval(waitTimer)
    waitTimer = null
  }
}

function sendGtag(name, params) {
  if (hasContainer()) return
  waiting.push([name, params])
  if (!waitTimer) {
    waitTries = 0
    waitTimer = window.setInterval(flushGtag, 250)
  }
  flushGtag()
}

/** A plain event: a click on a call to action, for example. */
export function trackEvent(name, params = {}) {
  dataLayer().push({ event: name, ...params })
  sendGtag(name, params)
}

/**
 * A GA4 ecommerce event (view_item, begin_checkout, purchase). `done` runs once the tag has had time to send, or after
 * 600 ms at the latest, so a buyer is never held up by a slow or blocked tag.
 */
export function trackEcommerce(name, data, done) {
  let finished = false
  const finish = () => {
    if (finished) return
    finished = true
    if (done) done()
  }

  dataLayer().push({ ecommerce: null })
  dataLayer().push({ event: name, ecommerce: data, eventCallback: finish, eventTimeout: 1500 })
  sendGtag(name, { ...data, event_callback: finish })
  window.setTimeout(finish, 600)
}

/** The GA4 item for one plan button: data-price (a number) and data-plan-label on the button. */
export function planItem(root, button) {
  return {
    item_id: `fs-${root.dataset.product || ''}`,
    item_name: root.dataset.title || 'Theme',
    item_variant: button.dataset.planLabel || '',
    price: Number(button.dataset.price || 0),
    quantity: 1,
  }
}
