// Shop cards: the info button opens a details panel on hover, keyboard focus or click.
// The panel is a popover (top layer), so it sits above everything and is never clipped; it is placed
// next to the button and kept inside the viewport. Click pins it open; Escape or a click elsewhere
// closes it and returns focus to the button.

const GAP = 8
const EDGE = 12

export function initCardDetails() {
  const wraps = document.querySelectorAll('[data-card-more]')
  if (!wraps.length) return

  const canHover = window.matchMedia('(hover: hover) and (pointer: fine)')
  const hasPopover = typeof HTMLElement !== 'undefined' && 'showPopover' in HTMLElement.prototype

  wraps.forEach((wrap) => {
    const button = wrap.querySelector('.shop-card__more-btn')
    const panel = wrap.querySelector('.shop-card__details')
    const card = wrap.closest('.shop-card')
    if (!button || !panel || !card) return

    let pinned = false
    let timer = 0
    let hideTimer = 0
    let frame = 0

    function place() {
      const vw = document.documentElement.clientWidth
      const vh = window.innerHeight
      const cardBox = card.getBoundingClientRect()
      const btnBox = button.getBoundingClientRect()
      const width = Math.min(Math.max(cardBox.width, 320), 384, vw - EDGE * 2)

      panel.style.width = `${width}px`
      panel.style.maxHeight = 'none'
      const natural = panel.offsetHeight

      const spaceBelow = vh - (btnBox.bottom + GAP) - EDGE
      const spaceAbove = btnBox.top - GAP - EDGE
      const below = spaceBelow >= Math.min(natural, 280) || spaceBelow >= spaceAbove
      const room = Math.max(120, below ? spaceBelow : spaceAbove)
      const height = Math.min(natural, room)
      const top = below ? btnBox.bottom + GAP : btnBox.top - GAP - height
      // Line the panel up with the card, then pull it back inside the viewport.
      const left = Math.min(Math.max(cardBox.left + (cardBox.width - width) / 2, EDGE), vw - width - EDGE)

      panel.style.maxHeight = `${height}px`
      panel.style.top = `${Math.round(top)}px`
      panel.style.left = `${Math.round(left)}px`
    }

    function onMove() {
      if (frame) return
      frame = window.requestAnimationFrame(() => {
        frame = 0
        place()
      })
    }

    function setOpen(open) {
      window.clearTimeout(timer)
      window.clearTimeout(hideTimer)
      if (open === wrap.classList.contains('is-open')) return

      if (open) {
        place()
        if (hasPopover) {
          try {
            panel.showPopover()
          } catch (error) {
            // Already open.
          }
        }
        wrap.classList.add('is-open')
        window.addEventListener('scroll', onMove, { passive: true })
        window.addEventListener('resize', onMove)
      } else {
        wrap.classList.remove('is-open')
        pinned = false
        window.removeEventListener('scroll', onMove)
        window.removeEventListener('resize', onMove)
        // Leave the top layer after the fade-out has played.
        hideTimer = window.setTimeout(() => {
          if (!hasPopover) return
          try {
            panel.hidePopover()
          } catch (error) {
            // Already closed.
          }
        }, 340)
      }
      button.setAttribute('aria-expanded', open ? 'true' : 'false')
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
