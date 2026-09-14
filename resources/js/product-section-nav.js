/**
 * Sticky section pills on single product pages.
 * Marks the active section while scrolling; respects prefers-reduced-motion.
 */
export function initProductSectionNav() {
  const nav = document.querySelector('[data-product-section-nav]')
  if (!nav) return

  const links = [...nav.querySelectorAll('a[href^="#"]')]
  if (links.length === 0) return

  const sections = links
    .map((link) => {
      const id = link.getAttribute('href')?.slice(1)
      if (!id) return null
      const el = document.getElementById(id)
      return el ? { id, link, el } : null
    })
    .filter(Boolean)

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
