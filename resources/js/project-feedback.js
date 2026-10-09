/**
 * Project page like / star toggles (admin-ajax, nonce, cookie-backed).
 */
export function initProjectFeedback() {
  const root = document.querySelector('[data-project-react]')
  if (!root) {
    return
  }

  const ajax = root.getAttribute('data-ajax') || ''
  const nonce = root.getAttribute('data-nonce') || ''
  const postId = root.getAttribute('data-post') || ''
  const status = root.querySelector('[data-react-status]')
  if (!ajax || !nonce || !postId) {
    return
  }

  function setBusy(busy) {
    root.querySelectorAll('[data-react]').forEach((button) => {
      button.toggleAttribute('disabled', busy)
    })
  }

  function apply(kind, count, pressed) {
    const button = root.querySelector(`[data-react="${kind}"]`)
    const countEl = root.querySelector(`[data-react-count="${kind}"]`)
    if (button) {
      button.classList.toggle('is-on', pressed)
      button.setAttribute('aria-pressed', pressed ? 'true' : 'false')
    }
    if (countEl) {
      countEl.textContent = String(count)
    }
  }

  root.querySelectorAll('[data-react]').forEach((button) => {
    button.addEventListener('click', async () => {
      const kind = button.getAttribute('data-react')
      if (!kind) {
        return
      }

      setBusy(true)
      if (status) {
        status.hidden = true
        status.textContent = ''
      }

      try {
        const body = new FormData()
        body.set('action', 'mh_project_react')
        body.set('nonce', nonce)
        body.set('post_id', postId)
        body.set('kind', kind)
        const res = await fetch(ajax, {
          method: 'POST',
          credentials: 'same-origin',
          body,
        })
        const json = await res.json()
        if (!json?.success || !json.data) {
          throw new Error(json?.data?.message || 'Reaction failed')
        }
        apply(json.data.kind, json.data.count, json.data.pressed)
      } catch (error) {
        if (status) {
          status.hidden = false
          status.textContent = error instanceof Error
            ? error.message
            : 'Could not save that reaction.'
        }
      } finally {
        setBusy(false)
      }
    })
  })
}
