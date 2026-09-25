function initEmulator() {
  initEditorMode()
  document.querySelectorAll('[data-mhn-emulator]').forEach((root) => {
    bindEmulator(root)
  })
}

function initEditorMode() {
  const form = document.getElementById('mhn-wizard')
  if (!form) return

  const hidden = form.querySelector('[name="mhn_editor"]')
  form.querySelectorAll('[data-mhn-editor]').forEach((button) => {
    button.addEventListener('click', () => {
      const mode = button.getAttribute('data-mhn-editor') === 'advanced' ? 'advanced' : 'simple'
      if (hidden && 'value' in hidden) hidden.value = mode
      setEditorMode(form, mode)
      if (hidden) hidden.dispatchEvent(new Event('change', { bubbles: true }))
    })
  })
}

function setEditorMode(form, mode) {
  form.querySelectorAll('[data-mhn-editor]').forEach((button) => {
    const pressed = button.getAttribute('data-mhn-editor') === mode
    button.setAttribute('aria-pressed', pressed ? 'true' : 'false')
  })
  const simple = form.querySelector('[data-mhn-simple]')
  const advanced = form.querySelector('[data-mhn-advanced]')
  if (simple) simple.hidden = mode === 'advanced'
  if (advanced) advanced.hidden = mode !== 'advanced'
}

function bindEmulator(root) {
  if (!root) return

  bindEmulatorDevices(root)
  if (root.getAttribute('data-mhn-live') === '1') bindEmulatorLive(root)
}

function bindEmulatorDevices(root) {
  const buttons = root.querySelectorAll('[data-mhn-device]')
  if (buttons.length === 0) return

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      const device = button.getAttribute('data-mhn-device') === 'mobile' ? 'mobile' : 'desktop'
      setEmulatorDevice(root, device)
    })
  })
}

function setEmulatorDevice(root, device) {
  const stage = root.querySelector('[data-mhn-stage]')
  if (!stage) return

  stage.classList.toggle('is-mobile', device === 'mobile')
  stage.classList.toggle('is-desktop', device !== 'mobile')
  root.querySelectorAll('[data-mhn-device]').forEach((button) => {
    const pressed = button.getAttribute('data-mhn-device') === device
    button.setAttribute('aria-pressed', pressed ? 'true' : 'false')
  })
}

function bindEmulatorLive(root) {
  const form = document.getElementById('mhn-wizard')
  if (!form || typeof mhnEmulator === 'undefined') return

  let timer = 0
  const queue = () => {
    window.clearTimeout(timer)
    timer = window.setTimeout(() => {
      refreshEmulator(root, form)
    }, 300)
  }
  const onEdit = () => {
    paintEmulatorChrome(root, form)
    queue()
  }

  form.addEventListener('input', onEdit)
  form.addEventListener('change', onEdit)
  bindEmulatorEditor(queue)
}

function bindEmulatorEditor(queue) {
  const attach = () => {
    if (!window.tinymce || !window.tinymce.editors) return

    window.tinymce.editors.forEach((editor) => {
      if (!editor || editor.mhnEmulatorBound) return

      editor.mhnEmulatorBound = true
      editor.on('input keyup change', queue)
    })
  }

  attach()
  window.setTimeout(attach, 600)
}

function paintEmulatorChrome(root, form) {
  const subjectLine = root.querySelector('[data-mhn-subject-line]')
  const subject = form.querySelector('[name="mhn_subject"]')
  const layout = form.querySelector('[name="mhn_layout"]:checked')
  const typed = subject && 'value' in subject ? String(subject.value).trim() : ''
  const heading = advancedField(form, 'mhn_block_heading')

  if (subjectLine && typed !== '') {
    subjectLine.textContent = typed
  } else if (subjectLine && heading !== '') {
    subjectLine.textContent = heading
  } else if (subjectLine && layout && layout.value === 'welcome' && mhnEmulator.welcomeSubject) {
    subjectLine.textContent = mhnEmulator.welcomeSubject
  }

  const preheader = root.querySelector('[data-mhn-preheader-line]')
  const intro = advancedField(form, 'mhn_block_intro')
  if (preheader && intro !== '') preheader.textContent = intro
  else if (preheader && mhnEmulator.intro) preheader.textContent = mhnEmulator.intro

  const fromName = root.querySelector('[data-mhn-from-name]')
  const fromEmail = root.querySelector('[data-mhn-from-email]')
  if (fromName && mhnEmulator.fromName) fromName.textContent = mhnEmulator.fromName
  if (fromEmail && mhnEmulator.fromEmail) fromEmail.textContent = mhnEmulator.fromEmail
}

function advancedField(form, name) {
  const mode = form.querySelector('[name="mhn_editor"]')
  if (!mode || mode.value !== 'advanced') return ''

  const field = form.querySelector('[name="' + name + '"]')
  return field && 'value' in field ? String(field.value).trim() : ''
}

function refreshEmulator(root, form) {
  if (window.tinymce) window.tinymce.triggerSave()

  const data = new FormData(form)
  data.set('action', mhnEmulator.action)
  data.set('nonce', mhnEmulator.nonce)

  fetch(mhnEmulator.ajaxUrl, {
    method: 'POST',
    body: data,
    credentials: 'same-origin',
  })
    .then((response) => response.json())
    .then((payload) => {
      if (!payload || !payload.success || !payload.data) return

      applyEmulatorPayload(root, payload.data)
    })
    .catch(() => {})
}

function applyEmulatorPayload(root, data) {
  const frame = root.querySelector('[data-mhn-emulator-frame]')
  if (frame && typeof data.html === 'string') frame.setAttribute('srcdoc', data.html)

  const typingSubject = document.activeElement && (document.activeElement.name === 'mhn_subject' || document.activeElement.name === 'mhn_block_heading')
  const typingIntro = document.activeElement && document.activeElement.name === 'mhn_block_intro'
  const subjectLine = root.querySelector('[data-mhn-subject-line]')
  if (subjectLine && data.subject && !typingSubject) subjectLine.textContent = data.subject

  const preheader = root.querySelector('[data-mhn-preheader-line]')
  if (preheader && data.preheader && !typingIntro) preheader.textContent = data.preheader
}

document.addEventListener('DOMContentLoaded', initEmulator)
