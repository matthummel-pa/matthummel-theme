// Cobble & Candle landing page: Freemius checkout and click events (through Tag Manager). Nothing else loads here.
import { initFreemiusCheckout } from './freemius-checkout.js'
import { trackEvent } from './track.js'
import { initConsent } from './consent.js'

// Screenshot viewer. Every screenshot (an image inside .cc-shot) becomes a button that opens the full image, uncropped, in a native
// dialog with previous / next, a counter, Esc to close and focus returned to the screenshot. Without JavaScript the images stay plain.
function initLightbox() {
  const images = Array.from(document.querySelectorAll('.cc-shot img'))
  if (!images.length || typeof HTMLDialogElement === 'undefined') return

  const make = (tag, className, text) => {
    const el = document.createElement(tag)
    if (className) el.className = className
    if (text) el.textContent = text
    return el
  }
  const iconButton = (className, label, glyph) => {
    const button = make('button', className)
    button.type = 'button'
    button.setAttribute('aria-label', label)
    button.append(make('span', 'cc-lightbox__glyph', glyph))
    button.firstChild.setAttribute('aria-hidden', 'true')
    return button
  }

  const dialog = make('dialog', 'cc-lightbox')
  dialog.setAttribute('aria-label', 'Screenshot viewer')
  const bar = make('div', 'cc-lightbox__bar')
  const count = make('p', 'cc-lightbox__count')
  const close = iconButton('cc-lightbox__close', 'Close', '\u00d7')
  const stage = make('div', 'cc-lightbox__stage')
  const prev = iconButton('cc-lightbox__nav cc-lightbox__nav--prev', 'Previous screenshot', '\u2039')
  const next = iconButton('cc-lightbox__nav cc-lightbox__nav--next', 'Next screenshot', '\u203a')
  const figure = make('figure', 'cc-lightbox__figure')
  const big = make('img', 'cc-lightbox__img')
  const caption = make('figcaption', 'cc-lightbox__caption')
  big.addEventListener('load', () => big.classList.remove('is-loading'))
  big.addEventListener('error', () => big.classList.remove('is-loading'))
  figure.append(big, caption)
  stage.append(prev, figure, next)
  bar.append(count, close)
  dialog.append(bar, stage)
  document.body.append(dialog)

  let index = 0
  let opener = null

  const show = (i) => {
    index = (i + images.length) % images.length
    const source = images[index]
    const src = source.currentSrc || source.src
    // Dim the picture until the new one has loaded, so the old image never shows under the new caption.
    if (big.getAttribute('src') !== src) big.classList.add('is-loading')
    big.src = src
    big.alt = source.alt || ''
    caption.textContent = source.alt || ''
    count.textContent = `${index + 1} of ${images.length}`
    prev.hidden = next.hidden = images.length < 2
  }

  const open = (i, from) => {
    opener = from
    show(i)
    if (!dialog.open) dialog.showModal()
    document.documentElement.classList.add('cc-lightbox-open')
    const name = (images[index].src.split('/').pop() || '').replace(/\.[a-z0-9]+$/i, '')
    trackEvent('cc_screenshot_open', { cc_shot: name })
  }

  images.forEach((img, i) => {
    const button = make('button', 'cc-zoom')
    button.type = 'button'
    button.setAttribute('aria-label', `Enlarge screenshot: ${img.alt || 'screenshot'}`)
    img.replaceWith(button)
    button.append(img)
    button.addEventListener('click', () => open(i, button))
  })

  prev.addEventListener('click', () => show(index - 1))
  next.addEventListener('click', () => show(index + 1))
  close.addEventListener('click', () => dialog.close())
  // A click on the dark area around the picture closes it; a click on the picture, bar or buttons does not.
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog || event.target === stage || event.target === figure) dialog.close()
  })
  dialog.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') {
      event.preventDefault()
      show(index - 1)
    } else if (event.key === 'ArrowRight') {
      event.preventDefault()
      show(index + 1)
    }
  })
  dialog.addEventListener('close', () => {
    document.documentElement.classList.remove('cc-lightbox-open')
    if (opener && typeof opener.focus === 'function') opener.focus()
    opener = null
  })
}

// Light / dark switch. The head script has already set data-cc-theme from the saved choice or the system setting; this wires the
// button (aria-pressed is true while light mode is on), saves the choice in localStorage (no cookie), and follows the system
// setting until the visitor picks one.
function initTheme() {
  const button = document.querySelector('[data-cc-theme-toggle]')
  if (!button) return

  const root = document.documentElement
  const media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: light)') : null
  const saved = () => {
    try {
      const value = window.localStorage.getItem('cc-theme')
      return value === 'light' || value === 'dark' ? value : null
    } catch (e) {
      return null
    }
  }
  const apply = (mode) => {
    root.setAttribute('data-cc-theme', mode)
    button.setAttribute('aria-pressed', mode === 'light' ? 'true' : 'false')
  }

  apply(root.getAttribute('data-cc-theme') === 'light' ? 'light' : 'dark')

  button.addEventListener('click', () => {
    const next = root.getAttribute('data-cc-theme') === 'light' ? 'dark' : 'light'
    apply(next)
    try {
      window.localStorage.setItem('cc-theme', next)
    } catch (e) {
      // Storage blocked: the choice applies to this page only.
    }
  })

  if (media && media.addEventListener) {
    media.addEventListener('change', (event) => {
      if (!saved()) apply(event.matches ? 'light' : 'dark')
    })
  }
}

function initTracking() {
  document.addEventListener('click', (event) => {
    // Header and sticky buttons carry data-cc-track; core Button blocks in the page content carry a cc-track-<name> class.
    const el = event.target.closest('[data-cc-track], [data-fs-buy], [class*="cc-track-"]')
    if (!el) return

    if (el.hasAttribute('data-fs-buy')) {
      // begin_checkout (GA4) is sent by the checkout script; this is the plain click event for Tag Manager triggers.
      trackEvent('cc_buy_click', { cc_plan: el.dataset.planLabel || '', cc_licenses: el.dataset.licenses || '' })
      return
    }

    const named = String(el.className).match(/cc-track-([a-z_]+)/)
    trackEvent('cc_cta_click', { cc_cta: el.dataset.ccTrack || (named ? named[1] : '') })
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

initTheme()
initLightbox()
initTracking()
initStickyBar()
initFreemiusCheckout()
initConsent()
