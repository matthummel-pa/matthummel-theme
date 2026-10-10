/**
 * Brand UI (3.6.51): theme switcher, transparent-to-solid header, scroll reveals.
 * The early <script> in layouts/app.blade.php sets html.mh-dark before paint;
 * this module only reacts to clicks and scrolling.
 */
const STORAGE_KEY = 'mh-theme';

function isDark() {
  return document.documentElement.classList.contains('mh-dark');
}

function syncToggle(button) {
  // Fixed label ("Dark mode") + aria-pressed, per the ARIA APG toggle-button pattern.
  button.setAttribute('aria-pressed', isDark() ? 'true' : 'false');
}

function initThemeToggle() {
  const buttons = document.querySelectorAll('[data-theme-toggle]');
  if (!buttons.length) {
    return;
  }
  buttons.forEach((button) => {
    syncToggle(button);
    button.addEventListener('click', () => {
      const next = isDark() ? 'light' : 'dark';
      document.documentElement.classList.toggle('mh-dark', next === 'dark');
      try {
        localStorage.setItem(STORAGE_KEY, next);
      } catch (e) {
        /* private mode: the choice lasts for this page only */
      }
      buttons.forEach(syncToggle);
    });
  });
}

function initHeaderScroll() {
  const header = document.querySelector('.site-header');
  if (!header) {
    return;
  }
  let ticking = false;
  const update = () => {
    header.classList.toggle('is-scrolled', window.scrollY > 24);
    ticking = false;
  };
  update();
  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(update);
      ticking = true;
    }
  }, { passive: true });
}

/**
 * Mark section shells and card grids for the existing [data-reveal] observer
 * (initPresenceReveal in app.js). Grid children get a staggered delay.
 */
function initSectionReveal() {
  const sections = document.querySelectorAll(
    'main .pf-section > .container > *, main .h-band > .container > *, main .cta-band .cta-band-inner > *, main .h-close__inner > *, main .work-guide > .container > *'
  );
  sections.forEach((el) => {
    if (!el.hasAttribute('data-reveal') && !el.closest('[data-reveal]')) {
      el.setAttribute('data-reveal', '');
    }
  });

  const grids = document.querySelectorAll(
    '.about-services, .h-project-grid, .work-grid, .code-repos-grid, .h-process__grid, .resources-list, .uses-list, .h-journal-list, .post-grid, .about-approach, .about-openwork__types, .h-fit__grid, .h-glance__facts, .code-pulse, .resume-timeline, .ty-browse-grid, .code-skills-grid, .h-faq__list'
  );
  grids.forEach((grid) => {
    Array.from(grid.children).forEach((child, i) => {
      if (child.hasAttribute('data-reveal')) {
        return;
      }
      child.setAttribute('data-reveal', '');
      child.setAttribute('data-reveal-delay', String(Math.min(i, 6)));
    });
  });
}

export function initBrandUi() {
  initThemeToggle();
  initHeaderScroll();
  initSectionReveal();
}
