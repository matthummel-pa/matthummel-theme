function setView(list, view) {
  const isList = view === 'list';
  list.classList.toggle('is-list', isList);
  document.querySelectorAll('[data-write-view]').forEach((button) => {
    const on = button.getAttribute('data-write-view') === view;
    button.classList.toggle('is-active', on);
    button.setAttribute('aria-pressed', on ? 'true' : 'false');
  });
}

async function copyRss(button) {
  const url = button.getAttribute('data-rss');
  if (!url) {
    return;
  }
  const label = button.textContent;
  try {
    await navigator.clipboard.writeText(url);
    button.textContent = 'Copied';
  } catch {
    window.prompt('Copy this RSS URL', url);
  }
  window.setTimeout(() => {
    button.textContent = label;
  }, 1600);
}

export function initWritingTools() {
  const list = document.querySelector('[data-post-list]');
  const search = document.querySelector('.js-mh-search');

  if (list) {
    let view = 'grid';
    try {
      const stored = window.localStorage.getItem('mh-write-view');
      if (stored === 'list' || stored === 'grid') {
        view = stored;
      }
    } catch {
      /* private mode */
    }
    setView(list, view);

    document.querySelectorAll('[data-write-view]').forEach((button) => {
      button.addEventListener('click', () => {
        const next = button.getAttribute('data-write-view') === 'list' ? 'list' : 'grid';
        setView(list, next);
        try {
          window.localStorage.setItem('mh-write-view', next);
        } catch {
          /* private mode */
        }
      });
    });
  }

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
