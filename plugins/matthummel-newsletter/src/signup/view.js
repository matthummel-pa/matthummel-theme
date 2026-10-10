function messageFromPayload(data) {
  if (!data || typeof data.message !== 'string') return ''
  return data.message
}

function bindSignupForm(form) {
  form.addEventListener('submit', function (event) {
    if (typeof fetch !== 'function') return

    const restUrl = form.getAttribute('data-rest-url') || ''
    const nonce = form.getAttribute('data-nonce') || ''
    const emailInput = form.querySelector('input[name="mhn_email"]')
    const status = form.querySelector('[role="status"]')
    const button = form.querySelector('button[type="submit"]')
    if (restUrl === '' || nonce === '' || !emailInput || !status || !button) return

    event.preventDefault()

    const honeypot = form.querySelector('input[name="mhn_hp"]')
    const payload = {
      email: emailInput.value.trim(),
      mhn_hp: honeypot ? honeypot.value : '',
    }
    const fallback = form.getAttribute('data-error') || ''

    button.disabled = true

    fetch(restUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-WP-Nonce': nonce,
      },
      body: JSON.stringify(payload),
    }).then(function (response) {
      return response.text().then(function (text) {
        let data = {}
        if (text !== '') data = JSON.parse(text)
        return { ok: response.ok, data: data }
      })
    }).then(function (result) {
      let message = messageFromPayload(result.data)
      if (message === '' && !result.ok) message = fallback
      status.textContent = message
      status.classList.toggle('is-error', !result.ok)
      if (result.ok) emailInput.removeAttribute('aria-invalid')
      else emailInput.setAttribute('aria-invalid', 'true')
      button.disabled = false
    }).catch(function () {
      button.disabled = false
      form.submit()
    })
  })
}

function initSignupBlocks() {
  const forms = document.querySelectorAll('form.mhn-signup-block__form[data-rest-url]')
  forms.forEach(function (form) {
    bindSignupForm(form)
  })
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initSignupBlocks)
else initSignupBlocks()
