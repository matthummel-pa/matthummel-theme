/**
 * Now page activity tabs and the note form.
 * Without JS every activity panel stays visible and the form still posts.
 */
export function initNowDesk() {
  initNowActivity()
  initNowNote()
}

function initNowActivity() {
  const root = document.querySelector('[data-now-desk]')
  if (!root) return

  const tabs = [...root.querySelectorAll('[data-now-tab]')]
  const panels = [...root.querySelectorAll('[data-now-panel]')]
  if (tabs.length < 2 || panels.length < 2) return

  const tablist = root.querySelector('.now-desk__tabs')
  if (tablist) tablist.setAttribute('role', 'tablist')

  tabs.forEach((tab, index) => {
    const id = tab.getAttribute('data-now-tab') || ''
    tab.setAttribute('role', 'tab')
    tab.setAttribute('aria-controls', id)
    if (!tab.id) tab.id = `now-tab-${index}`
  })

  panels.forEach((panel) => {
    panel.setAttribute('role', 'tabpanel')
    const tab = tabs.find((item) => item.getAttribute('data-now-tab') === panel.id)
    if (tab) panel.setAttribute('aria-labelledby', tab.id)
  })

  function select(id) {
    tabs.forEach((tab) => {
      const on = tab.getAttribute('data-now-tab') === id
      tab.setAttribute('aria-selected', on ? 'true' : 'false')
      tab.tabIndex = on ? 0 : -1
    })
    panels.forEach((panel) => {
      panel.hidden = panel.id !== id
    })
  }

  const hash = window.location.hash.replace('#', '')
  const start = panels.some((panel) => panel.id === hash) ? hash : tabs[0].getAttribute('data-now-tab')
  if (start) select(start)

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', (event) => {
      const id = tab.getAttribute('data-now-tab')
      if (!id) return
      event.preventDefault()
      select(id)
    })

    tab.addEventListener('keydown', (event) => {
      let next = null
      if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
        next = tabs[(index + 1) % tabs.length]
      } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
        next = tabs[(index - 1 + tabs.length) % tabs.length]
      } else if (event.key === 'Home') {
        next = tabs[0]
      } else if (event.key === 'End') {
        next = tabs[tabs.length - 1]
      }
      if (!next) return
      event.preventDefault()
      const id = next.getAttribute('data-now-tab')
      if (id) select(id)
      next.focus()
    })
  })
}

function initNowNote() {
  const root = document.querySelector('[data-now-note]')
  if (!root) return

  const form = root.querySelector('form')
  const alert = root.querySelector('[data-now-note-error]')
  if (!form) return

  form.addEventListener('submit', (event) => {
    const name = form.querySelector('[name="mh_name"]')
    const email = form.querySelector('[name="mh_email"]')
    const message = form.querySelector('[name="mh_message"]')
    const nameOk = name instanceof HTMLInputElement && name.value.trim() !== ''
    const emailOk = email instanceof HTMLInputElement && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())
    const messageOk = message instanceof HTMLTextAreaElement && message.value.trim() !== ''

    ;[name, email, message].forEach((field) => {
      if (field) field.removeAttribute('aria-invalid')
    })

    if (nameOk && emailOk && messageOk) return

    event.preventDefault()
    if (!nameOk && name) name.setAttribute('aria-invalid', 'true')
    if (!emailOk && email) email.setAttribute('aria-invalid', 'true')
    if (!messageOk && message) message.setAttribute('aria-invalid', 'true')
    if (!alert) return

    alert.hidden = false
    alert.textContent = root.getAttribute('data-invalid') || ''
    alert.focus()
  })
}
