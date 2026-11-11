/**
 * Story steps: draw the line between the numbered boxes when the section scrolls into view.
 * Without JS, or with reduced motion, the line is simply shown complete.
 */
export function initProjectStory() {
  const steps = document.querySelector('.project-story__steps')
  if (!steps) return
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  if (!('IntersectionObserver' in window)) return

  steps.classList.add('js-link')
  const observer = new IntersectionObserver((entries) => {
    if (!entries.some((entry) => entry.isIntersecting)) return
    steps.classList.add('is-linked')
    observer.disconnect()
  }, { threshold: 0.35 })
  observer.observe(steps)
}
