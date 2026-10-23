import { nameItems, swapWithMotion } from './list-transition.js';

function setWorkView(grid, view) {
  const isList = view === 'list';
  grid.classList.toggle('is-list', isList);
  document.querySelectorAll('[data-work-view]').forEach((button) => {
    const on = button.getAttribute('data-work-view') === view;
    button.classList.toggle('is-active', on);
    button.setAttribute('aria-pressed', on ? 'true' : 'false');
  });
}

export function initWorkTools() {
  const hub = document.querySelector('[data-work-hub]');
  if (!hub) {
    return;
  }

  const grid = hub.querySelector('[data-work-grid]');
  const cards = [...hub.querySelectorAll('[data-work-card]')];
  const filter = hub.querySelector('[data-work-filter]');
  const form = hub.querySelector('[data-work-filter-form]');
  const count = hub.querySelector('[data-work-count]');
  const empty = hub.querySelector('[data-work-empty]');

  if (grid) {
    let view = 'grid';
    try {
      const stored = window.localStorage.getItem('mh-work-view');
      if (stored === 'list' || stored === 'grid') {
        view = stored;
      }
    } catch {
      /* private mode */
    }
    setWorkView(grid, view);
    hub.querySelectorAll('[data-work-view]').forEach((button) => {
      button.addEventListener('click', () => {
        const next = button.getAttribute('data-work-view') === 'list' ? 'list' : 'grid';
        setWorkView(grid, next);
        try {
          window.localStorage.setItem('mh-work-view', next);
        } catch {
          /* private mode */
        }
      });
    });
  }

  form?.addEventListener('submit', (event) => {
    event.preventDefault();
  });

  // Type pills, tool pills, sort and the search box all drive the same cards, in place.
  // State lives in the URL (?cat=&tech=&sort=&q=) so a filtered view can be shared and Back works.
  const catPills = [...hub.querySelectorAll('[data-work-cat]')];
  const techPills = [...hub.querySelectorAll('[data-work-tech]')];
  const sortLinks = [...hub.querySelectorAll('[data-work-sort]')];
  const reset = hub.querySelector('[data-work-reset]');
  const labelOne = count?.getAttribute('data-label-one') || '%d project';
  const labelMany = count?.getAttribute('data-label-many') || '%d projects';
  const SORTS = ['newest', 'oldest', 'az'];
  const byOrder = (a, b) => Number(a.dataset.order) - Number(b.dataset.order);
  const comparators = {
    newest: (a, b) => (b.dataset.date || '').localeCompare(a.dataset.date || '') || byOrder(a, b),
    oldest: (a, b) => (a.dataset.date || '').localeCompare(b.dataset.date || '') || byOrder(b, a),
    az: (a, b) => (a.dataset.title || '').localeCompare(b.dataset.title || ''),
  };

  const readUrl = () => {
    const params = new URLSearchParams(window.location.search);
    const sort = params.get('sort');
    return {
      cat: params.get('cat') || '',
      tech: (params.get('tech') || '').toLowerCase(),
      sort: SORTS.includes(sort) ? sort : 'newest',
      qRaw: (params.get('q') || '').trim(),
    };
  };
  const initial = readUrl();
  const state = { cat: initial.cat, tech: initial.tech, sort: initial.sort, q: initial.qRaw.toLowerCase() };
  if (filter && initial.qRaw) {
    filter.value = initial.qRaw;
  }
  nameItems(cards, (card) => `work-${card.id}`);

  const markActive = (items, attr, value) => {
    items.forEach((item) => {
      const on = item.getAttribute(attr) === value;
      item.classList.toggle('is-active', on);
      if (on) {
        item.setAttribute('aria-current', 'true');
      } else {
        item.removeAttribute('aria-current');
      }
    });
  };

  const render = () => {
    let visible = 0;
    cards.forEach((card) => {
      const inCat = state.cat === '' || card.getAttribute('data-cat') === state.cat;
      const inTech = state.tech === '' || (card.getAttribute('data-tech') || '').includes(`|${state.tech}|`);
      const inSearch = state.q === '' || (card.getAttribute('data-search') || '').includes(state.q);
      card.hidden = !(inCat && inTech && inSearch);
      if (!card.hidden) {
        visible += 1;
      }
    });
    if (grid) {
      [...cards].sort(comparators[state.sort]).forEach((card) => grid.appendChild(card));
    }
    markActive(catPills, 'data-work-cat', state.cat);
    markActive(techPills, 'data-work-tech', state.tech);
    markActive(sortLinks, 'data-work-sort', state.sort);
    if (count) {
      count.textContent = (visible === 1 ? labelOne : labelMany).replace('%d', String(visible));
    }
    if (empty) {
      empty.hidden = visible !== 0;
    }
  };

  // Writes the state to the address bar. Pills and sort add a history entry; typing replaces it.
  const writeUrl = (push) => {
    const url = new URL(window.location.href);
    const set = (key, value) => (value === '' ? url.searchParams.delete(key) : url.searchParams.set(key, value));
    set('cat', state.cat);
    set('tech', state.tech);
    set('sort', state.sort === 'newest' ? '' : state.sort);
    set('q', filter ? filter.value.trim() : '');
    window.history[push ? 'pushState' : 'replaceState'](null, '', url);
  };

  const change = (patch, rich) => {
    Object.assign(state, patch);
    swapWithMotion(grid, render, rich ? { rich: true } : undefined);
    writeUrl(true);
  };

  const onPlainClick = (items, attr, key) => {
    items.forEach((item) => {
      item.addEventListener('click', (event) => {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
          return;
        }
        event.preventDefault();
        const value = item.getAttribute(attr) || '';
        if (value !== state[key]) {
          change({ [key]: value }, key !== 'sort');
        }
      });
    });
  };
  onPlainClick(catPills, 'data-work-cat', 'cat');
  onPlainClick(techPills, 'data-work-tech', 'tech');
  onPlainClick(sortLinks, 'data-work-sort', 'sort');

  reset?.addEventListener('click', (event) => {
    event.preventDefault();
    if (filter) {
      filter.value = '';
    }
    Object.assign(state, { cat: '', tech: '', q: '', sort: 'newest' });
    swapWithMotion(grid, render, { rich: true });
    writeUrl(true);
  });

  filter?.addEventListener('input', () => {
    state.q = filter.value.trim().toLowerCase();
    swapWithMotion(grid, render);
    writeUrl(false);
  });

  // Back / Forward: read the address bar again and redraw.
  window.addEventListener('popstate', () => {
    const next = readUrl();
    Object.assign(state, { cat: next.cat, tech: next.tech, sort: next.sort, q: next.qRaw.toLowerCase() });
    if (filter) {
      filter.value = next.qRaw;
    }
    swapWithMotion(grid, render, { rich: true });
  });

  // The server already drew the ?cat / ?tech / ?sort view; this only applies ?q and the button states.
  render();

  const hash = window.location.hash.replace('#', '');
  if (hash) {
    const target = hub.querySelector(`#${CSS.escape(hash)}`);
    target?.scrollIntoView({ block: 'start' });
  }
}
