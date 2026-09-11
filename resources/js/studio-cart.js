/**
 * Studio slip: sitewide cart drawer. Opens from the header, the chip,
 * or ?mh-slip=1 after add-to-cart.
 */
export function initStudioCart() {
  const slip = document.querySelector('#mh-slip')
  const overlay = document.querySelector('[data-mh-slip-overlay]')
  if (!slip) return

  const openers = [...document.querySelectorAll('[data-mh-slip-open]')]
  const closer = slip.querySelector('[data-mh-slip-close]')

  function isOpen() {
    return !slip.hasAttribute('hidden')
  }

  function setOpen(open) {
    if (open === isOpen()) return
    slip.toggleAttribute('hidden', !open)
    overlay?.toggleAttribute('hidden', !open)
    slip.setAttribute('aria-hidden', open ? 'false' : 'true')
    document.body.classList.toggle('mh-slip-open', open)
    if (open) {
      closer?.focus()
      return
    }
    openers[0]?.focus()
  }

  openers.forEach((btn) => {
    btn.addEventListener('click', (event) => {
      event.preventDefault()
      setOpen(true)
    })
  })
  closer?.addEventListener('click', () => setOpen(false))
  overlay?.addEventListener('click', () => setOpen(false))
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen()) setOpen(false)
  })

  const params = new URLSearchParams(window.location.search)
  if (params.get('mh-slip') === '1') {
    setOpen(true)
    params.delete('mh-slip')
    const next = params.toString()
    const clean = window.location.pathname + (next ? '?' + next : '') + window.location.hash
    window.history.replaceState({}, '', clean)
  }
}
