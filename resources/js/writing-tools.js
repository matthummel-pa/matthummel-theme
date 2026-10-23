import { nameItems, swapWithMotion } from './list-transition.js'

function resolvePostList(root) {
  if (!root) return null
  if (root.classList.contains('post-list')) return root
  return root.querySelector('.post-list') || root
}

function setView(list, view) {
  const isList = view === 'list'
  list.classList.toggle('is-list', isList)
  document.querySelectorAll('[data-write-view]').forEach((button) => {
    const on = button.getAttribute('data-write-view') === view
    button.classList.toggle('is-active', on)
    button.setAttribute('aria-pressed', on ? 'true' : 'false')
  })
}

export async function copyText(text) {
  try {
    await navigator.clipboard.writeText(text);
    return true;
  } catch {
    const field = document.createElement('textarea');
    field.value = text;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.left = '-9999px';
    document.body.appendChild(field);
    field.select();
    let ok = false;
    try {
      // execCommand is deprecated but used as a clipboard fallback for older browsers.
      ok = document.execCommand('copy');
    } catch {
      ok = false;
    }
    field.remove();
    return ok;
  }
}

async function copyRss(button) {
  const rssNode = button.closest('.write-subscribe, .footer-follow, .journal-subscribe')?.querySelector('.write-rss-url');
  const nearby = (rssNode instanceof HTMLInputElement || rssNode instanceof HTMLTextAreaElement ? rssNode.value : rssNode?.textContent)?.trim();
  const url = button.getAttribute('data-rss') || nearby;
  if (!url) {
    return;
  }
  if (!button.hasAttribute('data-idle-label')) {
    button.setAttribute('data-idle-label', button.textContent.trim());
  }
  const idle = button.getAttribute('data-idle-label');
  button.setAttribute('aria-live', 'polite');
  const ok = await copyText(url);
  button.textContent = ok ? 'Copied' : 'Copy failed';
  window.setTimeout(() => {
    button.textContent = idle;
  }, 1600);
}

/** Saved grid/list choice for the journal. */
function storedView() {
  try {
    const stored = window.localStorage.getItem('mh-write-view')
    return stored === 'list' ? 'list' : 'grid'
  } catch {
    return 'grid' // private mode
  }
}

/** Track post cards by id so a view transition can move the ones that stay. */
function namePosts(root) {
  nameItems(root.querySelectorAll('.post-card'), (card, i) => card.className.match(/\bpost-\d+\b/)?.[0] || `post-n${i}`)
}

/**
 * Journal without page reloads: topic pills, pagination, sort, and search-as-you-type
 * fetch the normal server-rendered page and swap its results into this one.
 * Every link still works as a plain link when the fetch fails or JS is off.
 */
function initJournalSwap() {
  const hub = document.querySelector('[data-journal]')
  if (!hub || typeof window.fetch !== 'function' || typeof window.DOMParser !== 'function') return

  const regionsOf = (root) => [...root.querySelectorAll('[data-journal-region]')]
  const search = document.querySelector('.js-mh-search')
  const form = search?.closest('form')
  const homeUrl = hub.getAttribute('data-journal-home') || window.location.href
  // The host page's own list classes (three-up on the journal home, one column beside the archive aside).
  const listClass = hub.getAttribute('data-journal-list') || 'post-list'
  let controller = null
  let timer = 0
  let observer = null
  let loadingMore = false

  namePosts(hub)

  const postKey = (card) => card.className.match(/\bpost-\d+\b/)?.[0] || ''

  /** Scroll-to-load: append the next page's cards when the end of the list comes into view. */
  async function loadMore(more) {
    const url = more.getAttribute('data-next')
    const list = hub.querySelector('[data-post-list]')
    const status = more.querySelector('[data-journal-more-status]')
    const button = more.querySelector('[data-journal-more-btn]')
    if (loadingMore || !url || !list) return

    loadingMore = true
    more.setAttribute('aria-busy', 'true')
    button.disabled = true
    status.textContent = more.getAttribute('data-label-loading')

    try {
      const response = await fetch(url, { credentials: 'same-origin' })
      if (!response.ok) throw new Error(`HTTP ${response.status}`)
      const doc = new DOMParser().parseFromString(await response.text(), 'text/html')
      const incoming = doc.querySelector('[data-post-list]')
      if (!incoming) throw new Error('No post list')

      const have = new Set([...list.querySelectorAll('.post-card')].map(postKey))
      let added = 0
      incoming.querySelectorAll(':scope > .post-card').forEach((card) => {
        const key = postKey(card)
        if (key && have.has(key)) return
        list.appendChild(document.importNode(card, true))
        added += 1
      })
      namePosts(hub)

      const next = doc.querySelector('[data-journal-more]')?.getAttribute('data-next')
      if (next) {
        more.setAttribute('data-next', next)
        status.textContent = (more.getAttribute('data-label-added') || '').replace('%d', String(added))
        button.disabled = false
        // Watch again: if the end is still in view, the observer fires for the next page.
        if (observer) {
          observer.unobserve(more)
          observer.observe(more)
        }
      } else {
        more.removeAttribute('data-next')
        status.textContent = more.getAttribute('data-label-end')
        button.hidden = true
        observer?.disconnect()
      }
    } catch (error) {
      // Stay quiet until the visitor presses the button; no retry loop.
      status.textContent = more.getAttribute('data-label-error')
      button.disabled = false
      observer?.unobserve(more)
    } finally {
      loadingMore = false
      more.removeAttribute('aria-busy')
    }
  }

  /** Turn the page links into scroll-to-load whenever the results have a next page. */
  function armMore() {
    observer?.disconnect()
    observer = null
    const more = hub.querySelector('[data-journal-more]')
    hub.classList.toggle('is-infinite', Boolean(more))
    if (!more) return

    more.hidden = false
    more.querySelector('[data-journal-more-btn]').addEventListener('click', () => loadMore(more))
    if (typeof window.IntersectionObserver !== 'function') return // the button still works
    observer = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) loadMore(more)
    }, { rootMargin: '0px 0px 400px 0px' })
    observer.observe(more)
  }

  armMore()

  async function load(url, { push = false, rich = false, scroll = false, refocus = '' } = {}) {
    controller?.abort()
    controller = new AbortController()
    hub.setAttribute('aria-busy', 'true')

    let doc
    try {
      const response = await fetch(url, { signal: controller.signal, credentials: 'same-origin' })
      // A search with no matches is a normal page; anything else unexpected falls back to a real visit.
      if (!response.ok && response.status !== 404) throw new Error(`HTTP ${response.status}`)
      doc = new DOMParser().parseFromString(await response.text(), 'text/html')
    } catch (error) {
      if (error?.name === 'AbortError') return
      window.location.assign(url)
      return
    }

    const next = doc.querySelector('[data-journal]')
    if (!next) {
      window.location.assign(url)
      return
    }

    const results = hub.querySelector('[data-journal-region="results"]')

    await swapWithMotion(results, () => {
      const incoming = new Map(regionsOf(next).map((region) => [region.getAttribute('data-journal-region'), region]))
      regionsOf(hub).forEach((region) => {
        const from = incoming.get(region.getAttribute('data-journal-region'))
        if (!from) return
        region.replaceChildren(...[...from.childNodes].map((node) => document.importNode(node, true)))
      })
      // The host page keeps its own layout classes and the visitor's grid/list choice.
      const fresh = hub.querySelector('[data-post-list]')
      if (fresh) {
        fresh.className = listClass
        setView(fresh, storedView())
      }
      namePosts(hub)
      armMore()
    }, { rich })

    hub.removeAttribute('aria-busy')
    if (doc.title) document.title = doc.title
    if (push) window.history.pushState({ mhJournal: true }, '', url)
    else window.history.replaceState({ mhJournal: true }, '', url)

    if (refocus) {
      const again = [...hub.querySelectorAll('.filter-pill')].find((link) => link.href === refocus)
      again?.focus({ preventScroll: true })
    }
    if (scroll) {
      const heading = hub.querySelector('h2[tabindex="-1"]') || hub
      const instant = window.matchMedia('(prefers-reduced-motion: reduce)').matches
      heading.scrollIntoView({ block: 'start', behavior: instant ? 'auto' : 'smooth' })
      if (heading !== hub) heading.focus({ preventScroll: true })
    }
  }

  hub.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return
    const link = event.target instanceof Element
      ? event.target.closest('.filter-pill, .page-numbers, .write-sort-btn, .post-cat, [data-journal-link]')
      : null
    if (!(link instanceof HTMLAnchorElement) || link.origin !== window.location.origin || link.target === '_blank') return
    event.preventDefault()
    const paging = link.classList.contains('page-numbers')
    if (search && !paging) search.value = ''
    load(link.href, { push: true, rich: true, scroll: paging, refocus: paging ? '' : link.href })
  })

  window.addEventListener('popstate', () => {
    load(window.location.href, { rich: true })
  })

  if (!search || !form) return

  const searchUrl = (term) => {
    const url = new URL(form.getAttribute('action') || '/', window.location.origin)
    url.searchParams.set('s', term)
    return url.toString()
  }

  // Search as you type: wait for a pause, and for two characters.
  search.addEventListener('input', () => {
    window.clearTimeout(timer)
    const term = search.value.trim()
    timer = window.setTimeout(() => {
      if (term === '') load(homeUrl)
      else if (term.length >= 2) load(searchUrl(term))
    }, 260)
  })

  form.addEventListener('submit', (event) => {
    const term = search.value.trim()
    if (term === '') return
    event.preventDefault()
    window.clearTimeout(timer)
    load(searchUrl(term), { push: true })
  })
}

export function initWritingTools() {
  const listRoot = document.querySelector('[data-post-list]')
  const list = resolvePostList(listRoot)
  const search = document.querySelector('.js-mh-search')

  if (list) setView(list, storedView())

  document.querySelectorAll('[data-write-view]').forEach((button) => {
    button.addEventListener('click', () => {
      const next = button.getAttribute('data-write-view') === 'list' ? 'list' : 'grid'
      // Results are swapped in place, so look the list up at click time.
      const current = resolvePostList(document.querySelector('[data-post-list]'))
      if (current) setView(current, next)
      try {
        window.localStorage.setItem('mh-write-view', next)
      } catch {
        /* private mode */
      }
    })
  })

  initJournalSwap()

  document.querySelectorAll('[data-copy-rss]').forEach((button) => {
    button.addEventListener('click', () => {
      copyRss(button);
    });
  });

  if (search) {
    document.addEventListener('keydown', (event) => {
      if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) {
        return;
      }
      const tag = event.target?.tagName;
      if (tag === 'INPUT' || tag === 'TEXTAREA' || event.target?.isContentEditable) {
        return;
      }
      event.preventDefault();
      search.focus();
      search.select?.();
    });
  }
}
