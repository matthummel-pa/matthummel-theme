import.meta.glob([
  '../images/**',
  '../fonts/**',
]);

function preferredTheme() {
  try {
    const stored = window.localStorage.getItem('mh-theme');
    if (stored === 'dark' || stored === 'light') {
      return stored;
    }
  } catch {
    /* private mode */
  }
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function applyTheme(theme) {
  document.documentElement.classList.toggle('mh-dark', theme === 'dark');
}

function syncThemeToggle(button) {
  const dark = document.documentElement.classList.contains('mh-dark');
  button.setAttribute('aria-pressed', dark ? 'true' : 'false');
  button.setAttribute(
    'aria-label',
    dark ? 'Switch to light mode' : 'Switch to dark mode'
  );
}

function initDarkMode() {
  applyTheme(preferredTheme());

  document.querySelectorAll('.mh-theme-toggle').forEach((button) => {
    syncThemeToggle(button);
    button.addEventListener('click', () => {
      const next = document.documentElement.classList.contains('mh-dark') ? 'light' : 'dark';
      applyTheme(next);
      try {
        window.localStorage.setItem('mh-theme', next);
      } catch {
        /* private mode */
      }
      document.querySelectorAll('.mh-theme-toggle').forEach(syncThemeToggle);
    });
  });
}

function initPopoutMenu() {
  const menu = document.querySelector('#mh-popout');
  const overlay = document.querySelector('.mh-popout-overlay');
  const toggle = document.querySelector('.menu-toggle');
  const close = document.querySelector('.mh-popout-close');

  if (!menu || !toggle) {
    return;
  }

  const setOpen = (open) => {
    document.body.classList.toggle('mh-popout-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    menu.setAttribute('aria-hidden', open ? 'false' : 'true');
    overlay?.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (open) {
      close?.focus();
    } else {
      toggle.focus();
    }
  };

  menu.setAttribute('aria-hidden', 'true');
  overlay?.setAttribute('aria-hidden', 'true');

  menu.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab' || !document.body.classList.contains('mh-popout-open')) {
      return;
    }
    const focusable = [...menu.querySelectorAll('a, button')].filter((el) => !el.hasAttribute('disabled'));
    if (!focusable.length) {
      return;
    }
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  toggle.addEventListener('click', () => {
    setOpen(!document.body.classList.contains('mh-popout-open'));
  });
  close?.addEventListener('click', () => setOpen(false));
  overlay?.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      setOpen(false);
    }
  });
}

function initMotion() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  document.documentElement.classList.add('mh-motion');

  const nodes = document.querySelectorAll(
    '.page-mast, .page-header, .pf-section, .cta-band, .hero-copy, .poster, .who-card, .work-card, .lift-card, .pf-card, .contact-form-panel, .contact-aside, .error-404'
  );
  if (!nodes.length || !('IntersectionObserver' in window)) {
    nodes.forEach((el) => el.classList.add('is-in'));
    return;
  }

  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) {
        return;
      }
      entry.target.classList.add('is-in');
      io.unobserve(entry.target);
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

  nodes.forEach((el) => {
    el.classList.add('mh-reveal');
    io.observe(el);
  });
}

function initHeroBlobs() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  const names = ['mh-wander-a', 'mh-wander-b', 'mh-wander-c', 'mh-wander-d'];
  document.querySelectorAll('.hero-blob').forEach((blob, i) => {
    const name = names[(Math.floor(Math.random() * names.length) + i) % names.length];
    const duration = 20 + Math.random() * 18;
    const delay = -(Math.random() * duration);
    blob.style.animation = `${name} ${duration.toFixed(1)}s ease-in-out ${delay.toFixed(1)}s infinite`;
  });
}

function initReadingProgress() {
  const bar = document.querySelector('#mh-progress');
  const prose = document.querySelector('#post-prose');
  if (!bar || !prose) {
    return;
  }

  const onScroll = () => {
    const rect = prose.getBoundingClientRect();
    const start = window.scrollY + rect.top - 80;
    const end = start + prose.offsetHeight - window.innerHeight;
    const raw = end <= start ? 1 : (window.scrollY - start) / (end - start);
    const value = Math.max(0, Math.min(100, Math.round(raw * 100)));
    bar.style.width = `${value}%`;
    bar.setAttribute('aria-valuenow', String(value));
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

function initTocSpy() {
  const links = document.querySelectorAll('.side-toc a, .mh-toc a');
  if (!links.length) {
    return;
  }
  const map = new Map();
  links.forEach((a) => {
    const id = decodeURIComponent((a.getAttribute('href') || '').replace('#', ''));
    const el = id ? document.getElementById(id) : null;
    if (el) {
      map.set(el, a);
    }
  });
  if (!map.size || !('IntersectionObserver' in window)) {
    return;
  }
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      const a = map.get(entry.target);
      if (!a) {
        return;
      }
      a.classList.toggle('is-active', entry.isIntersecting);
    });
  }, { rootMargin: '-20% 0px -60% 0px', threshold: 0 });
  map.forEach((_, el) => io.observe(el));
}

function initContactStatus() {
  const status = document.querySelector('#contact-status');
  if (status instanceof HTMLElement) {
    status.focus({ preventScroll: false });
  }
}

document.addEventListener('DOMContentLoaded', () => {
  initDarkMode();
  initPopoutMenu();
  initMotion();
  initHeroBlobs();
  initReadingProgress();
  initTocSpy();
  initContactStatus();
});
