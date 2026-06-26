// Theme styles — Tailwind processes this via PostCSS
import '../styles/app.css';

/**
 * Lightweight domReady helper (replaces @roots/sage/client/dom-ready).
 * @param {Function} fn
 */
const domReady = (fn) => {
  if (document.readyState !== 'loading') {
    fn();
  } else {
    document.addEventListener('DOMContentLoaded', fn);
  }
};

/**
 * Application entrypoint
 */
domReady(async () => {
  // Add your theme JS here
});

// Vite HMR
if (import.meta.hot) {
  import.meta.hot.accept();
}
