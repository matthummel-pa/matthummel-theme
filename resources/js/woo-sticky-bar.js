/**
 * Sticky buy bar for single product pages.
 *
 * Observes the hero purchase card (.pf-product-hero__card). When that card
 * scrolls fully out of the viewport the sticky bar slides in from the bottom.
 * When it re-enters the viewport the bar hides again.
 */
export function initStickyBar() {
  const bar = document.getElementById('pf-sticky-bar');
  if (!bar) return;

  const triggerSelector = bar.dataset.trigger || '.pf-product-hero__card';
  const trigger = document.querySelector(triggerSelector);
  if (!trigger) return;

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  const show = () => {
    bar.classList.add('is-visible');
    bar.setAttribute('aria-hidden', 'false');
  };

  const hide = () => {
    bar.classList.remove('is-visible');
    bar.setAttribute('aria-hidden', 'true');
  };

  if (!('IntersectionObserver' in window)) return;

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          hide();
        } else {
          show();
        }
      });
    },
    { threshold: 0, rootMargin: '0px 0px 0px 0px' }
  );

  io.observe(trigger);
}
