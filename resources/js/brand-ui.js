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
  // Publish the real header height so the sticky On-this-page bar sits exactly
  // under it. Re-measured whenever the header's box changes (fonts, wrap, zoom).
  const measure = () => {
    const h = Math.ceil(header.getBoundingClientRect().height);
    if (h > 0) {
      document.documentElement.style.setProperty('--header-h', `${h}px`);
    }
  };
  const update = () => {
    header.classList.toggle('is-scrolled', window.scrollY > 12);
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
 * Two-tone headings: wrap the last two words (three when the heading has six or
 * more) in <span class="hd-accent"> so CSS can color them. Only touches headings
 * whose last child is a plain text node, so links and icons are left alone.
 */
function initHeadingAccent() {
  const heads = document.querySelectorAll(
    '.display-title.is-hero, .display-title.is-section, .h-section__title, .h-hero__name, .h-glance__title, .post-hero h1, .cta-band .display-title'
  );
  heads.forEach((h) => {
    if (h.querySelector('.hd-accent')) {
      return;
    }
    const last = h.lastChild;
    if (!last || last.nodeType !== Node.TEXT_NODE) {
      return;
    }
    const text = last.nodeValue.replace(/\s+/g, ' ');
    const trimmed = text.replace(/\s+$/, '');
    const trailing = text.slice(trimmed.length);
    const words = trimmed.split(' ').filter(Boolean);
    const total = h.textContent.trim().split(/\s+/).filter(Boolean).length;
    if (words.length < 2 || total < 3) {
      return;
    }
    const take = total >= 6 ? Math.min(3, words.length) : 2;
    const head = words.slice(0, words.length - take).join(' ');
    const tail = words.slice(words.length - take).join(' ');
    const span = document.createElement('span');
    span.className = 'hd-accent';
    span.textContent = tail;
    last.nodeValue = head ? head + ' ' : '';
    h.appendChild(span);
    if (trailing) {
      h.appendChild(document.createTextNode(trailing));
    }
  });
}

export function initBrandUi() {
  initThemeToggle();
  initHeaderScroll();
  initHeadingAccent();
  initSectionReveal();
}
