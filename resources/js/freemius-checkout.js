/**
 * Freemius overlay checkout for the plan picker (partials/freemius-buy.blade.php).
 * The Freemius script loads on first use, so pages without a buy button load nothing extra.
 * Docs: https://freemius.com/help/documentation/checkout/integration/overlay-checkout/
 */
const CHECKOUT_SRC = 'https://checkout.freemius.com/js/v1/'

let scriptPromise = null

function loadCheckoutScript() {
  if (window.FS && window.FS.Checkout) {
    return Promise.resolve()
  }
  if (scriptPromise) {
    return scriptPromise
  }
  scriptPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = CHECKOUT_SRC
    script.async = true
    script.onload = () => resolve()
    script.onerror = () => {
      scriptPromise = null
      reject(new Error('Freemius checkout failed to load'))
    }
    document.head.appendChild(script)
  })
  return scriptPromise
}

export function initFreemiusCheckout() {
  const root = document.querySelector('[data-fs-checkout]')
  if (!root) {
    return
  }

  const status = root.querySelector('[data-fs-status]')
  const buttons = root.querySelectorAll('[data-fs-buy]')
  const productId = root.dataset.product || ''
  const planId = root.dataset.plan || ''
  const publicKey = root.dataset.key || ''
  if (!productId || !planId || !publicKey) {
    return
  }

  function say(message) {
    if (!status) {
      return
    }
    status.textContent = message
    status.hidden = message === ''
  }

  function buildCheckout() {
    const config = {
      product_id: productId,
      plan_id: planId,
      public_key: publicKey,
      title: root.dataset.title || undefined,
    }
    if (root.dataset.sandboxToken && root.dataset.sandboxCtx) {
      config.sandbox = { token: root.dataset.sandboxToken, ctx: root.dataset.sandboxCtx }
    }
    return new window.FS.Checkout(config)
  }

  let checkout = null

  // Warm the script on first hover or focus so the click opens instantly.
  buttons.forEach((button) => {
    button.addEventListener('pointerenter', () => loadCheckoutScript().catch(() => {}), { once: true })
    button.addEventListener('focus', () => loadCheckoutScript().catch(() => {}), { once: true })

    button.addEventListener('click', async () => {
      say('')
      button.setAttribute('aria-busy', 'true')
      try {
        await loadCheckoutScript()
        checkout = checkout || buildCheckout()
        const licenses = Number(button.dataset.licenses || '1')
        checkout.open({
          licenses: licenses === 0 ? 'unlimited' : licenses,
          billing_cycle: 'annual',
          success: () => {
            // The buyer's license, download, and invoices live in the account page.
            window.location.assign(root.dataset.account || '/account/')
          },
        })
      } catch (error) {
        say('Checkout could not load. Check your connection and try again, or use “Ask about this” to write to me.')
      } finally {
        button.removeAttribute('aria-busy')
      }
    })
  })
}
