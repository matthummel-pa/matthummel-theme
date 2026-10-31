// Shop cards: the info button opens the card's details in a centered modal dialog.
// Native <dialog> + showModal() supplies the top layer, the focus trap, the inert page behind it and
// Escape. This adds the open/close animation, a click on the backdrop to close, and page scroll lock.

const CLOSE_MS = 340

export function initCardDetails() {
  const wraps = document.querySelectorAll('[data-card-more]')
  if (!wraps.length) return

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)')
  const root = document.documentElement

  wraps.forEach((wrap) => {
    const button = wrap.querySelector('.shop-card__more-btn')
    const dialog = wrap.querySelector('dialog.shop-card__details')
    if (!button || !dialog || typeof dialog.showModal !== 'function') return

    let closeTimer = 0

    function lockScroll(lock) {
      if (lock) {
        const gap = window.innerWidth - root.clientWidth
        if (gap > 0) root.style.paddingRight = `${gap}px`
        root.classList.add('mh-modal-open')
        return
      }
      root.classList.remove('mh-modal-open')
      root.style.paddingRight = ''
    }

    function open() {
      window.clearTimeout(closeTimer)
      if (dialog.open) return
      dialog.showModal()
      lockScroll(true)
      button.setAttribute('aria-expanded', 'true')
      // One frame later, so the closed styles are painted and the transition runs.
      window.requestAnimationFrame(() => dialog.classList.add('is-open'))
    }

    function close() {
      if (!dialog.open) return
      dialog.classList.remove('is-open')
      window.clearTimeout(closeTimer)
      closeTimer = window.setTimeout(() => dialog.close(), reduceMotion.matches ? 0 : CLOSE_MS)
    }

    dialog.addEventListener('close', () => {
      lockScroll(false)
      button.setAttribute('aria-expanded', 'false')
      button.focus()
    })
    // Escape: play the fade-out instead of closing at once.
    dialog.addEventListener('cancel', (event) => {
      event.preventDefault()
      close()
    })
    // A click on the backdrop lands on the dialog element itself (its content sits in the body wrapper).
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) close()
    })
    dialog.querySelector('[data-card-close]')?.addEventListener('click', close)
    button.addEventListener('click', open)
    // The hover/focus hint: Escape dismisses it until the pointer or focus leaves the button.
    button.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') button.classList.add('is-tip-off')
    })
    ;['mouseleave', 'blur'].forEach((name) => button.addEventListener(name, () => button.classList.remove('is-tip-off')))
  })
}
