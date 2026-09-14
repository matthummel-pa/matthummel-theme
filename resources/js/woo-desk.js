/**
 * Cart / checkout desk helpers: coupon fold, sticky mobile bar visibility.
 */
export function initWooDesk() {
  const bar = document.querySelector('[data-mh-cart-mobile-bar]')
  if (bar) {
    const aside = document.querySelector('.woocommerce-wrap--cart .woo-desk__aside')
    if (aside && 'IntersectionObserver' in window) {
      const observer = new IntersectionObserver(
        ([entry]) => {
          bar.hidden = entry.isIntersecting
        },
        { rootMargin: '0px 0px -20% 0px', threshold: 0.15 }
      )
      observer.observe(aside)
    }
  }

  const coupon = document.querySelector('.woocommerce-wrap--cart .coupon')
  if (!coupon || coupon.dataset.mhFolded === '1') return
  const input = coupon.querySelector('#coupon_code')
  const button = coupon.querySelector('button[name="apply_coupon"]')
  if (!input || !button) return

  coupon.dataset.mhFolded = '1'
  const wrap = document.createElement('details')
  wrap.className = 'woo-coupon-fold'
  const summary = document.createElement('summary')
  summary.className = 'woo-coupon-fold__summary'
  summary.textContent = 'Have a coupon?'
  wrap.appendChild(summary)

  const body = document.createElement('div')
  body.className = 'woo-coupon-fold__body'
  while (coupon.firstChild) body.appendChild(coupon.firstChild)
  wrap.appendChild(body)
  coupon.appendChild(wrap)
}
