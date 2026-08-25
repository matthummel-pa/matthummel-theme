/**
 * Multi-step project discovery form (/start/).
 * Progressive enhancement: without JS, all fieldsets stay visible.
 */

export function initDiscoveryForm() {
  const form = document.querySelector('[data-discovery-form]')
  if (!form) return

  const steps = [...form.querySelectorAll('[data-discovery-step]')]
  const indicators = [...form.querySelectorAll('[data-step-indicator]')]
  const backBtn = form.querySelector('[data-discovery-back]')
  const nextBtn = form.querySelector('[data-discovery-next]')
  const nav = form.querySelector('[data-discovery-nav]')
  const review = form.querySelector('[data-discovery-review]')
  const reviewList = form.querySelector('[data-discovery-review-list]')

  if (!steps.length || !nextBtn) return

  form.classList.add('is-stepped')
  if (nav) nav.hidden = false
  let current = 1
  const total = steps.length

  function stepEl(n) {
    return form.querySelector(`[data-discovery-step="${n}"]`)
  }

  function requiredInStep(step) {
    return [...step.querySelectorAll('[required]')].filter((el) => {
      if (el.disabled || el.getAttribute('aria-hidden') === 'true') return false
      return true
    })
  }

  function validateStep(n) {
    const step = stepEl(n)
    if (!step) return true
    let ok = true
    for (const el of requiredInStep(step)) {
      if (!el.checkValidity()) {
        el.reportValidity()
        ok = false
        break
      }
    }
    return ok
  }

  function showStep(n) {
    current = Math.max(1, Math.min(total, n))
    steps.forEach((step) => {
      const id = Number(step.dataset.discoveryStep)
      const active = id === current
      step.hidden = !active
      step.classList.toggle('is-active', active)
    })
    indicators.forEach((li) => {
      const id = Number(li.dataset.stepIndicator)
      li.classList.toggle('is-active', id === current)
      li.classList.toggle('is-done', id < current)
      if (id === current) {
        li.setAttribute('aria-current', 'step')
      } else {
        li.removeAttribute('aria-current')
      }
    })
    if (backBtn) backBtn.hidden = current === 1
    if (nextBtn) nextBtn.hidden = current === total

    const actions = form.querySelector('[data-discovery-step="4"] .contact-form__actions')
    if (current === total && backBtn && actions && backBtn.parentElement !== actions) {
      actions.prepend(backBtn)
    } else if (current !== total && backBtn && nav && backBtn.parentElement !== nav) {
      nav.prepend(backBtn)
    }

    if (current === total) {
      fillReview()
      if (review) review.hidden = false
    } else if (review) {
      review.hidden = true
    }
    form.setAttribute('data-current-step', String(current))
  }

  function labelFor(name) {
    const el = form.querySelector(`[name="${name}"]`)
    if (!el) return name
    const lab = form.querySelector(`label[for="${el.id}"]`)
    if (!lab) return name
    return lab.textContent.replace(/\s*\*.*$/, '').replace(/\s*\(optional\).*$/i, '').trim()
  }

  function displayValue(el) {
    if (!el) return ''
    if (el.tagName === 'SELECT') {
      const opt = el.options[el.selectedIndex]
      return opt && opt.value ? opt.textContent.trim() : ''
    }
    return (el.value || '').trim()
  }

  function fillReview() {
    if (!reviewList) return
    const keys = [
      'mh_name',
      'mh_email',
      'mh_company',
      'mh_role',
      'mh_project_type',
      'mh_client',
      'mh_need',
      'mh_success',
      'mh_timeline',
    ]
    reviewList.innerHTML = ''
    for (const name of keys) {
      const el = form.querySelector(`[name="${name}"]`)
      const value = displayValue(el)
      if (!value) continue
      const dt = document.createElement('dt')
      dt.textContent = labelFor(name)
      const dd = document.createElement('dd')
      dd.textContent = value.length > 140 ? `${value.slice(0, 137)}…` : value
      reviewList.append(dt, dd)
    }
  }

  nextBtn.addEventListener('click', () => {
    if (!validateStep(current)) return
    showStep(current + 1)
  })

  backBtn?.addEventListener('click', () => showStep(current - 1))

  form.addEventListener('submit', (event) => {
    // Validate all steps before submit (in case someone jumps)
    for (let n = 1; n <= total; n += 1) {
      if (!validateStep(n)) {
        event.preventDefault()
        showStep(n)
        return
      }
    }
  })

  // Start on first invalid step if returning from error redirect
  if (form.classList.contains('is-error')) {
    let firstInvalid = 1
    for (let n = 1; n <= total; n += 1) {
      const step = stepEl(n)
      if (step?.querySelector('[aria-invalid="true"]')) {
        firstInvalid = n
        break
      }
    }
    showStep(firstInvalid)
  } else {
    showStep(1)
  }
}
