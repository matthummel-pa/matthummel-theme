/**
 * Sticky "On this page" section pills (home, product, etc.).
 * Marks the active section while scrolling; respects prefers-reduced-motion.
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

    const mobile = nav.querySelector('.h-page-nav__mobile')
    if (mobile instanceof HTMLDetailsElement && mobile.open) {
      mobile.open = false
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
