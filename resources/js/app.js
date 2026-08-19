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

document.addEventListener('DOMContentLoaded', () => {
  initDarkMode();
  initPopoutMenu();
});
