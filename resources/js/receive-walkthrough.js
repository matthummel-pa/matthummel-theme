/**
 * Interactive "What you receive" walkthrough on the home page.
 */
export function initReceiveWalkthrough() {
  const root = document.querySelector('[data-receive-walkthrough]')
  if (!root) return

  const tabs = [...root.querySelectorAll('[data-receive-step]')]
  const panels = [...root.querySelectorAll('[data-receive-panel]')]
  if (tabs.length === 0 || panels.length === 0) return

  function activate(id) {
    tabs.forEach((tab) => {
      const on = tab.getAttribute('data-receive-step') === id
      tab.classList.toggle('is-active', on)
      tab.setAttribute('aria-selected', on ? 'true' : 'false')
      tab.tabIndex = on ? 0 : -1
    })
    panels.forEach((panel) => {
      const on = panel.getAttribute('data-receive-panel') === id
      panel.classList.toggle('is-active', on)
      if (on) {
        panel.removeAttribute('hidden')
      } else {
        panel.setAttribute('hidden', '')
      }
    })
  }

  tabs.forEach((tab, index) => {
    tab.tabIndex = index === 0 ? 0 : -1
    tab.addEventListener('click', () => {
      const id = tab.getAttribute('data-receive-step')
      if (id) activate(id)
    })
    tab.addEventListener('keydown', (event) => {
      const key = event.key
      if (!['ArrowDown', 'ArrowRight', 'ArrowUp', 'ArrowLeft', 'Home', 'End'].includes(key)) {
        return
      }
      event.preventDefault()
      let next = index
      if (key === 'ArrowDown' || key === 'ArrowRight') next = (index + 1) % tabs.length
      if (key === 'ArrowUp' || key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length
      if (key === 'Home') next = 0
      if (key === 'End') next = tabs.length - 1
      const id = tabs[next].getAttribute('data-receive-step')
      if (!id) return
      activate(id)
      tabs[next].focus()
    })
  })
}
