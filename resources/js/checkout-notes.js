/**
 * Checkout install-want chips. Writes selected keys into a hidden input.
 * Without JS the textarea still submits.
 */
export function initCheckoutInstallNotes() {
  const root = document.querySelector('[data-mh-install-notes]')
  if (!root) return

  const input = root.querySelector('input[name="mh_install_wants"]')
  const chips = [...root.querySelectorAll('[data-mh-want]')]
  if (!input || !chips.length) return

  function selectedKeys() {
    return chips
      .filter((chip) => chip.getAttribute('aria-pressed') === 'true')
      .map((chip) => chip.getAttribute('data-mh-want') || '')
      .filter(Boolean)
  }

  function sync() {
    input.value = selectedKeys().join(',')
  }

  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      const on = chip.getAttribute('aria-pressed') === 'true'
      chip.setAttribute('aria-pressed', on ? 'false' : 'true')
      sync()
    })
  })

  sync()
}
