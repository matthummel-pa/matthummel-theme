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

  // Type pills and the search box filter the same cards, in place.
  const pills = [...hub.querySelectorAll('[data-work-cat]')];
  const reset = hub.querySelector('[data-work-reset]');
  const labelOne = count?.getAttribute('data-label-one') || '%d project';
  const labelMany = count?.getAttribute('data-label-many') || '%d projects';
  const state = {
    cat: pills.find((pill) => pill.classList.contains('is-active'))?.getAttribute('data-work-cat') || '',
    q: '',
  };
  nameItems(cards, (card) => `work-${card.id}`);

  const render = () => {
    let visible = 0;
    cards.forEach((card) => {
      const inCat = state.cat === '' || card.getAttribute('data-cat') === state.cat;
      const inSearch = state.q === '' || (card.getAttribute('data-search') || '').includes(state.q);
      card.hidden = !(inCat && inSearch);
      if (!card.hidden) {
        visible += 1;
      }
    });
    pills.forEach((pill) => {
      const on = pill.getAttribute('data-work-cat') === state.cat;
      pill.classList.toggle('is-active', on);
      if (on) {
        pill.setAttribute('aria-current', 'true');
      } else {
        pill.removeAttribute('aria-current');
      }
    });
    if (count) {
      count.textContent = (visible === 1 ? labelOne : labelMany).replace('%d', String(visible));
    }
    if (empty) {
      empty.hidden = visible !== 0;
    }
  };

  const setCat = (cat) => {
    if (cat === state.cat) {
      return;
    }
    state.cat = cat;
    swapWithMotion(grid, render, { rich: true });
    const url = new URL(window.location.href);
    if (cat === '') {
      url.searchParams.delete('cat');
    } else {
      url.searchParams.set('cat', cat);
    }
    window.history.replaceState(null, '', url);
  };

  pills.forEach((pill) => {
    pill.addEventListener('click', (event) => {
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
        return;
      }
      event.preventDefault();
      setCat(pill.getAttribute('data-work-cat') || '');
    });
  });

  reset?.addEventListener('click', (event) => {
    event.preventDefault();
    if (filter) {
      filter.value = '';
    }
    state.q = '';
    state.cat = '';
    swapWithMotion(grid, render, { rich: true });
    window.history.replaceState(null, '', reset.getAttribute('href') || window.location.pathname);
  });

  filter?.addEventListener('input', () => {
    state.q = filter.value.trim().toLowerCase();
    swapWithMotion(grid, render);
  });

  const hash = window.location.hash.replace('#', '');
  if (hash) {
    const target = hub.querySelector(`#${CSS.escape(hash)}`);
    target?.scrollIntoView({ block: 'start' });
  }
}
