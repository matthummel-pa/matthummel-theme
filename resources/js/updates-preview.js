/**
 * Header "Get updates" preview.
 *
 * The chevron button toggles the panel (aria-expanded) for keyboard and touch.
 * On a fine pointer, hovering the wrapper opens it and leaving closes it; a
 * click on the chevron pins it open so moving the mouse away keeps it there.
 * Escape, a click outside, or focus leaving the wrapper closes it.
 */
export function initUpdatesPreview() {
  const root = document.querySelector('[data-updates]')
  if (!root) return

  const toggle = root.querySelector('.header-updates__toggle')
  const panel = root.querySelector('.header-updates__panel')
  if (!toggle || !panel) return

  const canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches
  let closeTimer = 0
  let pinned = false

  const isOpen = () => root.classList.contains('is-open')

  const setOpen = (open) => {
    if (!open) pinned = false
    if (open === isOpen()) return
    root.classList.toggle('is-open', open)
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false')
    panel.toggleAttribute('hidden', !open)
  }

  toggle.addEventListener('click', () => {
    window.clearTimeout(closeTimer)
    // Open by hover and not yet pinned: the click pins it instead of closing it.
    if (isOpen() && !pinned) {
      pinned = true
      return
    }
    if (isOpen()) {
      setOpen(false)
      return
    }
    setOpen(true)
    pinned = true
  })

  if (canHover) {
    root.addEventListener('pointerenter', () => {
      window.clearTimeout(closeTimer)
      setOpen(true)
    })
    root.addEventListener('pointerleave', () => {
      if (pinned) return
      // Small grace period so moving from the pill to the panel does not flicker.
      closeTimer = window.setTimeout(() => {
        if (!pinned) setOpen(false)
      }, 140)
    })
  }

  root.addEventListener('focusout', (event) => {
    if (event.relatedTarget && root.contains(event.relatedTarget)) return
    setOpen(false)
  })

  document.addEventListener('click', (event) => {
    if (isOpen() && !root.contains(event.target)) setOpen(false)
  })

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || !isOpen()) return
    setOpen(false)
    toggle.focus()
  })
}
