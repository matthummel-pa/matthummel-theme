// Shop cards: the info button opens a details panel on hover, keyboard focus or click.
// Click pins it open; Escape or a click elsewhere closes it and returns focus to the button.

export function initCardDetails() {
  const wraps = document.querySelectorAll('[data-card-more]')
  if (!wraps.length) return

  const canHover = window.matchMedia('(hover: hover) and (pointer: fine)')

  wraps.forEach((wrap) => {
    const button = wrap.querySelector('.shop-card__more-btn')
    if (!button) return

    let pinned = false
    let timer = 0

    function setOpen(open) {
      window.clearTimeout(timer)
      wrap.classList.toggle('is-open', open)
      button.setAttribute('aria-expanded', open ? 'true' : 'false')
      if (!open) pinned = false
    }

    function closeSoon() {
      window.clearTimeout(timer)
      timer = window.setTimeout(() => {
        if (!pinned && !wrap.contains(document.activeElement)) setOpen(false)
      }, 140)
    }

    button.addEventListener('click', () => {
      if (wrap.classList.contains('is-open') && pinned) {
        setOpen(false)
        return
      }
      setOpen(true)
      pinned = true
    })

    wrap.addEventListener('pointerenter', (event) => {
      if (event.pointerType === 'mouse' && canHover.matches) setOpen(true)
    })
    wrap.addEventListener('pointerleave', (event) => {
      if (event.pointerType === 'mouse') closeSoon()
    })
    wrap.addEventListener('focusin', () => setOpen(true))
    wrap.addEventListener('focusout', (event) => {
      if (!wrap.contains(event.relatedTarget)) closeSoon()
    })
    wrap.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape' || !wrap.classList.contains('is-open')) return
      event.stopPropagation()
      setOpen(false)
      button.focus()
    })
    document.addEventListener('click', (event) => {
      if (wrap.classList.contains('is-open') && !wrap.contains(event.target)) setOpen(false)
    })
  })
}
