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
  const nav = document.querySelector('.catalog-filter-nav')
  if (!nav) return

  const grid = document.querySelector('.woocommerce ul.products')
  if (!grid) return

  const links = [...nav.querySelectorAll('.catalog-filter-nav__link[data-filter-type]')]
  if (!links.length) return

  const countEl = document.getElementById('catalog-filter-count')
  const liveRegion = document.getElementById('catalog-filter-live')
  const sortSelect = document.querySelector('.catalog-toolbar__right select.orderby')
  if (sortSelect && !sortSelect.id) {
    sortSelect.id = 'mh-catalog-orderby'
  }

  function formatCount(visible) {
    const one = countEl?.dataset.labelOne || '%d product'
    const many = countEl?.dataset.labelMany || '%d products'
    const template = visible === 1 ? one : many
    return template.replace('%d', String(visible))
  }

  function applyFilter(type) {
    const items = [...grid.querySelectorAll('li.product')]
    items.forEach((item) => {
      const match = type === 'all' || item.classList.contains('mh-type-' + type)
      item.hidden = !match
      item.removeAttribute('aria-hidden')
    })

    const visible = items.filter((item) => !item.hidden).length
    const label = formatCount(visible)
    if (countEl) countEl.textContent = label
    if (liveRegion) liveRegion.textContent = label
  }

  links.forEach((link) => {
    link.addEventListener('click', () => {
      const type = link.dataset.filterType || 'all'

      links.forEach((button) => {
        button.setAttribute('aria-pressed', button === link ? 'true' : 'false')
        button.removeAttribute('aria-current')
      })

      applyFilter(type)
    })
  })
}
