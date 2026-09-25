function initWizard() {
  const form = document.getElementById('mhn-wizard')
  if (!form || typeof mhnWizard === 'undefined') return

  bindCounts()
  bindMedia(form)
  const save = bindAutosave(form)
  bindStepLinks(save)
}

function bindCounts() {
  document.querySelectorAll('[data-mhn-count]').forEach((input) => {
    const hint = document.getElementById(input.getAttribute('data-mhn-count') || '')
    if (!hint || !('value' in input)) return
    const update = () => {
      hint.textContent = String(input.value.length)
    }
    input.addEventListener('input', update)
    update()
  })
}

function bindMedia(form) {
  const button = document.getElementById('mhn-pick-image')
  const clear = document.getElementById('mhn-clear-image')
  const input = form.querySelector('[name="mhn_image_id"]')
  const altInput = form.querySelector('[name="mhn_image_alt"]')
  const preview = document.getElementById('mhn-image-preview')
  if (!button || !input || !window.wp || !wp.media) return

  let frame
  button.addEventListener('click', (event) => {
    event.preventDefault()
    if (!frame) {
      frame = wp.media({
        title: button.textContent || 'Image',
        multiple: false,
        library: { type: 'image' },
      })
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON()
        input.value = String(attachment.id || '')
        if (altInput && 'value' in altInput && altInput.value.trim() === '' && attachment.alt) {
          altInput.value = attachment.alt
        }
        if (preview) {
          preview.replaceChildren()
          if (attachment.url) {
            const img = document.createElement('img')
            img.src = attachment.url
            img.alt = ''
            img.width = 240
            preview.append(img)
          }
        }
        input.dispatchEvent(new Event('change', { bubbles: true }))
      })
    }
    frame.open()
  })

  if (!clear) return
  clear.addEventListener('click', (event) => {
    event.preventDefault()
    input.value = '0'
    if (altInput && 'value' in altInput) altInput.value = ''
    if (preview) preview.replaceChildren()
    input.dispatchEvent(new Event('change', { bubbles: true }))
  })
}

function bindAutosave(form) {
  let timer = 0
  let sending = false
  const status = document.getElementById('mhn-save-status')

  function save() {
    if (sending) return Promise.resolve(currentIssueId(form))
    if (window.tinymce) window.tinymce.triggerSave()
    const data = new FormData(form)
    data.set('action', 'mhn_wizard_autosave')
    data.set('mhn_action', 'stay')
    sending = true
    return fetch(mhnWizard.ajaxUrl, {
      method: 'POST',
      body: data,
      credentials: 'same-origin',
    })
      .then((response) => response.json())
      .then((payload) => {
        sending = false
        const id = payload && payload.success && payload.data ? Number(payload.data.id) : 0
        if (!id) return 0
        const hidden = form.querySelector('[name="mhn_issue"]')
        if (hidden) hidden.value = String(id)
        if (status) status.textContent = mhnWizard.saved
        const url = new URL(window.location.href)
        if (!url.searchParams.get('issue')) {
          url.searchParams.set('issue', String(id))
          window.history.replaceState({}, '', url)
        }
        return id
      })
      .catch(() => {
        sending = false
        return 0
      })
  }

  const queue = () => {
    window.clearTimeout(timer)
    timer = window.setTimeout(() => {
      save()
    }, 800)
  }
  form.addEventListener('input', queue)
  form.addEventListener('change', queue)

  return save
}

function bindStepLinks(save) {
  document.querySelectorAll('.mhn-wizard-steps a').forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault()
      save().then((id) => {
        const url = new URL(link.href, window.location.origin)
        if (id) url.searchParams.set('issue', String(id))
        window.location.assign(url)
      })
    })
  })
}

function currentIssueId(form) {
  const hidden = form.querySelector('[name="mhn_issue"]')
  return hidden ? Number(hidden.value) : 0
}

document.addEventListener('DOMContentLoaded', initWizard)
