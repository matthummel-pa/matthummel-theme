/**
 * Checkout install-want chips, site URL, and live kickoff brief.
 * Without JS the textarea, URL field, and hidden suggested keys still submit.
 */
export function initCheckoutInstallNotes() {
  const root = document.querySelector('[data-mh-install-notes]')
  if (!root) return

  const input = root.querySelector('input[name="mh_install_wants"]')
  const chips = [...root.querySelectorAll('[data-mh-want]')]
  const site = root.querySelector('#mh_install_site')
  const note = document.querySelector('#order_comments')
  const brief = document.querySelector('[data-mh-live-brief]')
  const siteOut = document.querySelector('[data-mh-live-site]')
  const config = readConfig()

  function selectedKeys() {
    return chips
      .filter((chip) => chip.getAttribute('aria-pressed') === 'true')
      .map((chip) => chip.getAttribute('data-mh-want') || '')
      .filter(Boolean)
  }

  function hostFrom(value) {
    const raw = (value || '').trim()
    if (!raw) return ''
    try {
      const url = new URL(raw.includes('://') ? raw : `https://${raw}`)
      if (url.protocol !== 'http:' && url.protocol !== 'https:') return ''
      return url.hostname.replace(/^www\./i, '')
    } catch {
      return ''
    }
  }

  function joinAnd(items) {
    const clean = items.filter(Boolean)
    if (!clean.length) return ''
    if (clean.length === 1) return clean[0]
    if (clean.length === 2) return `${clean[0]} ${config.and} ${clean[1]}`
    return `${clean.slice(0, -1).join(', ')}, ${config.and} ${clean[clean.length - 1]}`
  }

  function applyTemplate(template, ...values) {
    let i = 0
    return template.replace(/%(?:\d+\$)?s/g, () => values[i++] ?? '')
  }

  function writeBrief() {
    if (input) input.value = selectedKeys().join(',')
    const labels = selectedKeys().map((key) => config.labels[key] || '').filter(Boolean)
    const host = hostFrom(site?.value || '')
    const list = joinAnd(labels)
    let sentence = config.empty
    if (host && list) sentence = applyTemplate(config.fromWith, host, list)
    else if (host) sentence = applyTemplate(config.from, host)
    else if (list) sentence = applyTemplate(config.with, list)
    if (brief && brief.textContent !== sentence) {
      brief.textContent = sentence
      brief.classList.remove('is-rewriting')
      void brief.offsetWidth
      brief.classList.add('is-rewriting')
    }
    if (siteOut) {
      siteOut.hidden = !host
      siteOut.textContent = host
    }
  }

  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      const on = chip.getAttribute('aria-pressed') === 'true'
      chip.setAttribute('aria-pressed', on ? 'false' : 'true')
      chip.classList.toggle('is-suggested', false)
      writeBrief()
    })
  })

  site?.addEventListener('input', writeBrief)
  site?.addEventListener('change', writeBrief)
  note?.addEventListener('input', () => {
    if (!brief) return
    const extra = (note.value || '').trim()
    writeBrief()
    if (extra) {
      brief.textContent = `${brief.textContent} ${extra}`
    }
  })

  writeBrief()
}

export function initCopyBrief() {
  const button = document.querySelector('[data-mh-copy-brief]')
  if (!button) return
  const src = document.querySelector('.mh-install-echo__copy-src')
  button.addEventListener('click', async () => {
    const text = src?.value || ''
    if (!text) return
    try {
      await navigator.clipboard.writeText(text)
      button.textContent = 'Copied'
    } catch {
      button.textContent = 'Copy failed'
    }
    window.setTimeout(() => {
      button.textContent = 'Copy brief'
    }, 1600)
  })
}

function readConfig() {
  const el = document.querySelector('[data-mh-brief-config]')
  const fallback = {
    labels: {},
    empty: 'Tap what applies. This sentence becomes your kickoff.',
    and: 'and',
    with: 'I will start with %s.',
    from: 'I will start from %s.',
    fromWith: 'I will start from %1$s — %2$s.',
  }
  if (!el) return fallback
  try {
    return { ...fallback, ...JSON.parse(el.getAttribute('data-mh-brief-config') || '{}') }
  } catch {
    return fallback
  }
}
