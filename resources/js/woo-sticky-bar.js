/**
 * Sticky buy bar for single product pages.
 *
 * Observes the hero buy box (.pf-product-buybox). When that box scrolls fully
 * out of the viewport the sticky bar appears. Reduced-motion visitors still
 * get the bar — only the slide animation is skipped.
 */
export function initStickyBar() {
  const bar = document.getElementById('pf-sticky-bar')
  if (!bar) return

  const triggerSelector = bar.dataset.trigger || '.pf-product-buybox'
  const trigger = document.querySelector(triggerSelector)
  if (!trigger) return

  // html/body use overflow-x: clip, which makes position:fixed resolve against
  // that box instead of the viewport. Pin the bar to <body> so bottom: 0 is
  // the screen edge, not just below the fold.
  if (bar.parentElement !== document.body) {
    document.body.appendChild(bar)
  }

  const show = () => {
    bar.classList.add('is-visible')
    bar.setAttribute('aria-hidden', 'false')
    bar.removeAttribute('inert')
  }

  const hide = () => {
    bar.classList.remove('is-visible')
    bar.setAttribute('aria-hidden', 'true')
    bar.setAttribute('inert', '')
  }

  if (!('IntersectionObserver' in window)) {
    show()
    return
  }

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          hide()
        } else {
          show()
        }
      })
    },
    { threshold: 0, rootMargin: '0px 0px 0px 0px' }
  )

  io.observe(trigger)
}
