// Theme scripts
import './reading-progress.js';

// Dark mode toggle
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.mh-theme-toggle');
  const html   = document.documentElement;

  // Init from localStorage
  if (localStorage.getItem('mh-dark') === '1') {
    html.classList.add('mh-dark');
    toggle && toggle.setAttribute('aria-pressed', 'true');
  }

  if (toggle) {
    toggle.addEventListener('click', () => {
      const isDark = html.classList.toggle('mh-dark');
      localStorage.setItem('mh-dark', isDark ? '1' : '0');
      toggle.setAttribute('aria-pressed', String(isDark));
    });
  }

  // Popout menu
  const menuToggle  = document.querySelector('.menu-toggle');
  const popout      = document.getElementById('mh-popout');
  const overlay     = document.querySelector('.mh-popout-overlay');
  const closeBtn    = document.querySelector('.mh-popout-close');

  const openMenu  = () => {
    document.body.classList.add('mh-popout-open');
    menuToggle && menuToggle.setAttribute('aria-expanded', 'true');
    popout && popout.focus();
  };
  const closeMenu = () => {
    document.body.classList.remove('mh-popout-open');
    menuToggle && menuToggle.setAttribute('aria-expanded', 'false');
  };

  menuToggle && menuToggle.addEventListener('click', openMenu);
  closeBtn   && closeBtn.addEventListener('click', closeMenu);
  overlay    && overlay.addEventListener('click', closeMenu);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
  });
});
