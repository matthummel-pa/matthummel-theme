import.meta.glob([
  '../images/**',
  '../fonts/**',
]);

function initDarkMode() {
  const root = document.documentElement;
  const stored = window.localStorage.getItem('mh-theme');

  if (stored === 'dark') {
    root.classList.add('mh-dark');
  }

  document.querySelectorAll('.mh-theme-toggle').forEach((button) => {
    const sync = () => {
      const dark = root.classList.contains('mh-dark');
      button.setAttribute('aria-pressed', dark ? 'true' : 'false');
    };
    sync();
    button.addEventListener('click', () => {
      const next = root.classList.toggle('mh-dark') ? 'dark' : 'light';
      window.localStorage.setItem('mh-theme', next);
      sync();
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
  };

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
    '.page-mast, .page-header, .pf-section, .cta-band, .hero-copy, .poster, .who-card, .work-card, .lift-card, .pf-card, .contact-stage, .error-404'
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

document.addEventListener('DOMContentLoaded', () => {
  initDarkMode();
  initPopoutMenu();
  initMotion();
  initHeroBlobs();
});
