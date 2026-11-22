/**
 * Analytics consent banner. The head script in app/setup.php sets every consent type to denied and re-applies a saved
 * "granted" before the container loads; this file asks once, saves the answer in localStorage (key mh-consent, no cookie),
 * and sends the update to Tag Manager. Ads stay denied: the site runs none.
 */
const KEY = 'mh-consent'

function readChoice() {
  try {
    return window.localStorage.getItem(KEY)
  } catch (e) {
    return null
  }
}

function saveChoice(value) {
  try {
    window.localStorage.setItem(KEY, value)
  } catch (e) {
    // Storage blocked: the answer applies to this page only.
  }
}

function applyChoice(value) {
  if (typeof window.gtag !== 'function') return
  window.gtag('consent', 'update', { analytics_storage: value === 'granted' ? 'granted' : 'denied' })
}

export function initConsent() {
  const banner = document.querySelector('#mh-consent')
  if (!banner) return

  let opener = null

  const show = () => {
    banner.hidden = false
  }

  const hide = () => {
    banner.hidden = true
    if (opener) opener.focus()
    opener = null
  }

  banner.addEventListener('click', (event) => {
    const button = event.target.closest('[data-consent]')
    if (!button) return
    const value = button.dataset.consent
    saveChoice(value)
    applyChoice(value)
    hide()
  })

  document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-consent-open]')
    if (!link) return
    opener = link
    show()
    banner.focus()
  })

  if (!readChoice()) show()
}
