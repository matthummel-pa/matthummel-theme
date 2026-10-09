/**
 * Sticky "On this page" section pills (home, product, etc.).
 * Marks the active section while scrolling; respects prefers-reduced-motion.
 * Overflow rows use prev/next arrows instead of a visible scrollbar.
 */
export function initSectionNav() {
  const nav = document.querySelector('[data-section-nav], [data-product-section-nav]')
  if (!nav) return

  const links = [...nav.querySelectorAll('a[href^="#"]')]
  if (links.length === 0) return

  const seen = new Set()
  const sections = []
  for (const link of links) {
    const id = link.getAttribute('href')?.slice(1)
    if (!id || seen.has(id)) continue
    const el = document.getElementById(id)
    if (!el) continue
    seen.add(id)
    sections.push({ id, link, el })
  }

  if (sections.length === 0) return

  const track = initPageNavTrack(nav)

  function setActive(id) {
    links.forEach((link) => {
      const isActive = link.getAttribute('href') === `#${id}`
      link.classList.toggle('is-active', isActive)
      if (isActive) {
        link.setAttribute('aria-current', 'true')
      } else {
        link.removeAttribute('aria-current')
      }
    })

    const current = nav.querySelector('[data-section-nav-current]')
    const activeLink = links.find((link) => link.getAttribute('href') === `#${id}`)
    if (current && activeLink) {
      current.textContent = activeLink.textContent?.trim() || id
    }

    if (activeLink && track) {
      scrollChildIntoTrack(track.scroller, activeLink)
    }
  }

  links.forEach((link) => {
    link.addEventListener('click', (event) => {
      const id = link.getAttribute('href')?.slice(1)
      const target = id ? document.getElementById(id) : null
      if (!target) return
      event.preventDefault()
      const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches
      target.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' })
      setActive(id)
      history.replaceState(null, '', `#${id}`)
    })
  })

  if (!('IntersectionObserver' in window)) {
    setActive(sections[0].id)
    return
  }

  const io = new IntersectionObserver(
    (entries) => {
      const visible = entries
        .filter((entry) => entry.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)
      if (visible[0]?.target?.id) {
        setActive(visible[0].target.id)
      }
    },
    {
      rootMargin: '-20% 0px -55% 0px',
      threshold: [0, 0.15, 0.4],
    }
  )

  sections.forEach(({ el }) => io.observe(el))
}

function prefersReducedMotion() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

function initPageNavTrack(nav) {
  const scroller = nav.querySelector('[data-page-nav-scroller]')
  const prev = nav.querySelector('[data-page-nav-prev]')
  const next = nav.querySelector('[data-page-nav-next]')
  if (!scroller || !prev || !next) return null

  function maxScroll() {
    return Math.max(0, scroller.scrollWidth - scroller.clientWidth)
  }

  function update() {
    const max = maxScroll()
    const overflow = max > 4
    nav.classList.toggle('has-page-nav-overflow', overflow)
    prev.hidden = !overflow
    next.hidden = !overflow
    if (!overflow) return
    const x = scroller.scrollLeft
    prev.disabled = x <= 2
    next.disabled = x >= max - 2
  }

  function step(dir) {
    const amount = Math.max(Math.round(scroller.clientWidth * 0.7), 140)
    scroller.scrollBy({
      left: dir * amount,
      behavior: prefersReducedMotion() ? 'auto' : 'smooth',
    })
  }

  prev.addEventListener('click', () => step(-1))
  next.addEventListener('click', () => step(1))
  scroller.addEventListener('scroll', update, { passive: true })
  window.addEventListener('resize', update)
  if ('ResizeObserver' in window) {
    new ResizeObserver(update).observe(scroller)
  }
  update()

  return { scroller, update }
}

function scrollChildIntoTrack(scroller, child) {
  const pad = 12
  const left = child.offsetLeft
  const right = left + child.offsetWidth
  const viewLeft = scroller.scrollLeft
  const viewRight = viewLeft + scroller.clientWidth
  let next = viewLeft
  if (left < viewLeft + pad) next = Math.max(0, left - pad)
  else if (right > viewRight - pad) next = right - scroller.clientWidth + pad
  else return
  scroller.scrollTo({
    left: next,
    behavior: prefersReducedMotion() ? 'auto' : 'smooth',
  })
}
