/**
 * Freemius overlay checkout for the plan picker (partials/freemius-buy.blade.php).
 * The Freemius script loads on first use, so pages without a buy button load nothing extra.
 * Docs: https://freemius.com/help/documentation/checkout/integration/overlay-checkout/
 */
import { CURRENCY, planItem, trackEcommerce } from './track.js'

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

  // GA4 view_item once per page, priced at the cheapest plan.
  const prices = [...buttons].map((b) => Number(b.dataset.price || 0)).filter((n) => n > 0)
  if (buttons.length && prices.length) {
    const cheapest = [...buttons].find((b) => Number(b.dataset.price) === Math.min(...prices))
    trackEcommerce('view_item', { currency: CURRENCY, value: Math.min(...prices), items: [planItem(root, cheapest)] })
  }

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
        const item = planItem(root, button)
        trackEcommerce('begin_checkout', { currency: CURRENCY, value: item.price, items: [item] })
        checkout.open({
          licenses: licenses === 0 ? 'unlimited' : licenses,
          billing_cycle: 'annual',
          success: (data) => {
            // The buyer's license, download, and invoices live in the account page. The purchase=complete flag
            // gives ad and analytics tags a page to count as a finished purchase. The GA4 purchase event goes out
            // first (value is the plan's list price; tax and discounts are not in the checkout response).
            const account = new URL(root.dataset.account || '/account/', window.location.origin)
            account.searchParams.set('purchase', 'complete')
            const purchase = (data && data.purchase) || {}
            const transactionId = String(purchase.subscription_id || purchase.license_id || '')
            trackEcommerce(
              'purchase',
              { transaction_id: transactionId, currency: CURRENCY, value: item.price, items: [item] },
              () => window.location.assign(account.toString())
            )
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
