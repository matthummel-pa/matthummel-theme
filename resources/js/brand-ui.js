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

function initMotionToggle() {
  // WCAG 2.2.2: looping animation can be stopped. html.mh-still turns the loops off (studio.css);
  // the early <script> in layouts/app.blade.php re-applies the saved choice before paint.
  const buttons = document.querySelectorAll('[data-motion-toggle]');
  if (!buttons.length) {
    return;
  }
  const sync = () => {
    const still = document.documentElement.classList.contains('mh-still');
    buttons.forEach((button) => button.setAttribute('aria-pressed', still ? 'true' : 'false'));
  };
  sync();
  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      const still = document.documentElement.classList.toggle('mh-still');
      try {
        if (still) {
          localStorage.setItem('mh-motion', 'still');
        } else {
          localStorage.removeItem('mh-motion');
        }
      } catch (e) {
        /* private mode: the choice lasts for this page only */
      }
      sync();
    });
  });
}

function initHeaderScroll() {
  const header = document.querySelector('.site-header');
  if (!header) {
    return;
  }
  let ticking = false;
  // Publish the real header height so the sticky On-this-page bar sits exactly
  // under it. Re-measured whenever the header's box changes (fonts, wrap, zoom).
  // The header's own height must never depend on --header-h (see studio.css), or this
  // feeds back on itself. Write only when the value changes.
  let lastHeaderH = 0;
  const measure = () => {
    const h = Math.ceil(header.getBoundingClientRect().height);
    if (h > 0 && h !== lastHeaderH) {
      lastHeaderH = h;
      document.documentElement.style.setProperty('--header-h', `${h}px`);
    }
  };
  // A page with no dark hero under the header (Thank you, a bare block page) paints the
  // scrolled state at once; otherwise the white nav sits on the light canvas in light mode.
  // Only a hero that starts under the header counts: one lower on the page (after a light
  // section) says nothing about what the header is sitting on. Both edges are read in
  // viewport coordinates, so the admin bar (which pushes the fixed header down 32/46px)
  // and a non-zero scroll position at load do not skew the comparison.
  const main = document.getElementById('main');
  const hero = main ? main.querySelector('.h-hero, .page-header, .post-hero, .mh-block-hero') : null;
  const hasHero = Boolean(hero)
    && hero.getBoundingClientRect().top <= header.getBoundingClientRect().bottom + 4;
  const update = () => {
    header.classList.toggle('is-scrolled', ! hasHero || window.scrollY > 12);
    ticking = false;
  };
  measure();
  update();
  if ('ResizeObserver' in window) {
    new ResizeObserver(measure).observe(header);
  } else {
    window.addEventListener('resize', measure, { passive: true });
  }
  window.addEventListener('load', measure);
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(measure);
  }
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

/**
 * Safety net for the reveal observer: anything already above or inside the
 * viewport (anchor jumps, back/forward cache, hidden tabs) is shown without
 * waiting to intersect, and nothing stays hidden longer than a moment.
 */
function initRevealFallback() {
  const show = () => {
    document.querySelectorAll('[data-reveal]:not(.is-in)').forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.bottom < 0 || r.top < window.innerHeight * 1.15) {
        el.classList.add('is-in');
      }
    });
  };
  window.setTimeout(show, 900);
  window.addEventListener('hashchange', () => window.setTimeout(show, 250));
  window.addEventListener('pageshow', (e) => {
    if (e.persisted) {
      window.setTimeout(show, 250);
    }
  });
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      window.setTimeout(show, 250);
    }
  });
}

export function initBrandUi() {
  initThemeToggle();
  initMotionToggle();
  initHeaderScroll();
  initSectionReveal();
  initRevealFallback();
}
