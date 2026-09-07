/**
 * Client-side catalog filter for the WooCommerce shop archive.
 *
 * Intercepts clicks on `.catalog-filter-nav__link[data-filter-type]` buttons
 * and shows/hides product `<li>` items by their `mh-type-*` class — no page
 * navigation required.
 *
 * Falls back gracefully: if JS is unavailable the filter buttons simply do
 * nothing (they have no href to follow).
 */
export function initShopFilter() {
  const nav = document.querySelector('.catalog-filter-nav');
  if (!nav) return;

  const grid = document.querySelector('.woocommerce ul.products');
  if (!grid) return;

  const links = [...nav.querySelectorAll('.catalog-filter-nav__link[data-filter-type]')];
  if (!links.length) return;

  function applyFilter(type) {
    const items = [...grid.querySelectorAll('li.product')];
    items.forEach((item) => {
      if (type === 'all') {
        item.hidden = false;
        item.removeAttribute('aria-hidden');
      } else {
        const match = item.classList.contains('mh-type-' + type);
        item.hidden = !match;
        item.setAttribute('aria-hidden', match ? 'false' : 'true');
      }
    });

    // Announce the visible count to screen readers via a live region.
    const visible = items.filter((i) => !i.hidden).length;
    let liveRegion = document.getElementById('catalog-filter-live');
    if (!liveRegion) {
      liveRegion = document.createElement('span');
      liveRegion.id = 'catalog-filter-live';
      liveRegion.setAttribute('role', 'status');
      liveRegion.setAttribute('aria-live', 'polite');
      liveRegion.className = 'visually-hidden';
      document.body.appendChild(liveRegion);
    }
    liveRegion.textContent = visible + ' product' + (visible !== 1 ? 's' : '') + ' shown';
  }

  links.forEach((link) => {
    link.addEventListener('click', () => {
      const type = link.dataset.filterType || 'all';

      links.forEach((l) => {
        l.setAttribute('aria-current', l === link ? 'true' : 'false');
      });

      applyFilter(type);
    });
  });
}
