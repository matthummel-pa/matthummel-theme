// Theme scripts
import './reading-progress.js';

// Dark mode is handled entirely by dark-mode.php (inline head + footer scripts).
// mh-theme key in localStorage, 'dark'/'light' values.
// Do NOT duplicate the toggle logic here — two listeners cancel each other out.

document.addEventListener('DOMContentLoaded', () => {
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
