import { copyText } from './writing-tools.js';

function flashLabel(button, next) {
  const node = button.childNodes[0]
  if (!node) {
    return
  }
  const label = node.textContent?.trim() || button.textContent.trim()
  const hidden = button.querySelector('.visually-hidden')
  node.textContent = `${next} `
  window.setTimeout(() => {
    node.textContent = `${label} `
    if (hidden) {
      button.appendChild(hidden)
    }
  }, 1600)
}

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
  const totalLabel = count?.textContent || '';

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

  const applyFilter = () => {
    const q = (filter?.value || '').trim().toLowerCase();
    let visible = 0;
    cards.forEach((card) => {
      const hay = card.getAttribute('data-search') || '';
      const show = q === '' || hay.includes(q);
      card.hidden = !show;
      if (show) {
        visible += 1;
      }
    });
    if (count) {
      count.textContent = q === '' ? totalLabel : `${visible} match${visible === 1 ? '' : 'es'}`;
    }
    if (empty) {
      empty.hidden = visible !== 0;
      empty.setAttribute('role', visible === 0 ? 'status' : '');
    }
  };

  filter?.addEventListener('input', applyFilter);

  hub.querySelectorAll('[data-copy-url]').forEach((button) => {
    button.addEventListener('click', async () => {
      const url = button.getAttribute('data-copy-url');
      if (!url) {
        return;
      }
      const ok = await copyText(url);
      const label = button.childNodes[0];
      if (!label) {
        return;
      }
      const was = label.textContent;
      label.textContent = ok ? 'Copied ' : 'Copy failed ';
      window.setTimeout(() => {
        label.textContent = was;
      }, 1600);
    });
  });

  hub.querySelectorAll('[data-share-project]').forEach((button) => {
    button.addEventListener('click', async () => {
      const url = button.getAttribute('data-share-url') || window.location.href;
      const title = button.getAttribute('data-share-title') || document.title;
      const text = button.getAttribute('data-share-text') || title;
      if (typeof navigator.share === 'function') {
        try {
          await navigator.share({ title, text, url });
          return;
        } catch (error) {
          if (error && error.name === 'AbortError') {
            return;
          }
        }
      }
      const ok = await copyText(url);
      flashLabel(button, ok ? 'Copied' : 'Share failed');
    });
  });

  const hash = window.location.hash.replace('#', '');
  if (hash) {
    const target = hub.querySelector(`#${CSS.escape(hash)}`);
    target?.scrollIntoView({ block: 'start' });
  }
}
