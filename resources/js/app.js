import.meta.glob([
  '../images/**',
  '../fonts/**',
]);

import { initCodeBlocks } from './code-blocks.js';
import { initWritingTools } from './writing-tools.js';
import { initWorkTools } from './work-tools.js';
import { initDiscoveryForm } from './discovery-form.js';

function initPopoutMenu() {
  const menu = document.querySelector('#mh-popout');
  const overlay = document.querySelector('.mh-popout-overlay');
  const toggle = document.querySelector('.menu-toggle');
  const close = document.querySelector('.mh-popout-close');

  if (!menu || !toggle) {
    return;
  }

  const labelOpen = toggle.dataset.labelOpen || 'Open menu';
  const labelClose = toggle.dataset.labelClose || 'Close menu';

  const isOpen = () => document.body.classList.contains('mh-popout-open');

  const setOpen = (open) => {
    if (open === isOpen()) {
      return;
    }

    document.body.classList.toggle('mh-popout-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? labelClose : labelOpen);
    menu.setAttribute('aria-hidden', open ? 'false' : 'true');
    menu.toggleAttribute('inert', !open);
    overlay?.setAttribute('aria-hidden', open ? 'false' : 'true');

    if (open) {
      close?.focus();
      return;
    }

    toggle.focus();
  };

  menu.setAttribute('aria-hidden', 'true');
  menu.setAttribute('inert', '');
  overlay?.setAttribute('aria-hidden', 'true');

  menu.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab' || !isOpen()) {
      return;
    }
    const focusable = [...menu.querySelectorAll('a, button')].filter((el) => !el.hasAttribute('disabled'));
    if (!focusable.length) {
      return;
    }
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  toggle.addEventListener('click', () => setOpen(!isOpen()));
  close?.addEventListener('click', () => setOpen(false));
  overlay?.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isOpen()) {
      setOpen(false);
    }
  });
}

function initReadingProgress() {
  const bar = document.querySelector('#mh-progress');
  const prose = document.querySelector('#post-prose');
  if (!bar || !prose) {
    return;
  }

  const onScroll = () => {
    const rect = prose.getBoundingClientRect();
    const start = window.scrollY + rect.top - 80;
    const end = start + prose.offsetHeight - window.innerHeight;
    const raw = end <= start ? 1 : (window.scrollY - start) / (end - start);
    const value = Math.max(0, Math.min(100, Math.round(raw * 100)));
    bar.style.width = `${value}%`;
    bar.setAttribute('aria-valuenow', String(value));
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

function initTocSpy() {
  const links = document.querySelectorAll('.side-toc a, .mh-toc a');
  if (!links.length) {
    return;
  }
  const map = new Map();
  links.forEach((a) => {
    const id = decodeURIComponent((a.getAttribute('href') || '').replace('#', ''));
    const el = id ? document.getElementById(id) : null;
    if (el) {
      map.set(el, a);
    }
  });
  if (!map.size || !('IntersectionObserver' in window)) {
    return;
  }
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      const a = map.get(entry.target);
      if (!a) {
        return;
      }
      a.classList.toggle('is-active', entry.isIntersecting);
    });
  }, { rootMargin: '-20% 0px -60% 0px', threshold: 0 });
  map.forEach((_, el) => io.observe(el));
}

function initContactStatus() {
  const status = document.querySelector('#contact-status');
  if (status instanceof HTMLElement) {
    status.focus({ preventScroll: false });
  }
}

function asciiComment(text) {
  return text
    .replace(/[\u2018\u2019]/g, "'")
    .replace(/[\u201C\u201D\u00AB\u00BB]/g, '"')
    .replace(/\u2014/g, '--')
    .replace(/[\u2013\u2212]/g, '-')
    .replace(/\u2026/g, '...')
    .replace(/\u00A0/g, ' ');
}

function escapeHtml(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// Client-side Markdown preview. Must stay in sync with any server-side
// comment rendering logic if added to app/comments.php in future.
function renderCommentMarkdown(raw) {
  let text = asciiComment(String(raw || '').replace(/\r\n?/g, '\n'));
  const slots = [];
  const stash = (html) => {
    const key = `%%MH${slots.length}%%`;
    slots.push([key, html]);
    return key;
  };
  text = text.replace(/```(?:[a-zA-Z0-9_-]+)?\n([\s\S]*?)```/g, (_, code) => (
    stash(`<pre><code>${escapeHtml(code.replace(/\n$/, ''))}</code></pre>`)
  ));
  text = text.replace(/`([^`\n]+)`/g, (_, code) => stash(`<code>${escapeHtml(code)}</code>`));
  text = escapeHtml(text);
  text = text.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" rel="nofollow ugc">$1</a>');
  text = text.replace(/\*\*(.+?)\*\*/gs, '<strong>$1</strong>');
  text = text.replace(/(?<![A-Za-z0-9*])\*(?!\*)(.+?)(?<!\*)\*(?![A-Za-z0-9*])/gs, '<em>$1</em>');
  text = text.replace(/(?<![A-Za-z0-9])_([^_\n]+)_(?![A-Za-z0-9])/g, '<em>$1</em>');

  const lines = text.split('\n');
  const out = [];
  let inList = false;
  let inQuote = false;
  lines.forEach((line) => {
    const quote = line.match(/^&gt;\s?(.*)$/);
    if (quote) {
      if (inList) {
        out.push('</ul>');
        inList = false;
      }
      if (!inQuote) {
        out.push('<blockquote>');
        inQuote = true;
      }
      out.push(quote[1]);
      return;
    }
    if (inQuote) {
      out.push('</blockquote>');
      inQuote = false;
    }
    const item = line.match(/^[-*]\s+(.+)$/);
    if (item) {
      if (!inList) {
        out.push('<ul>');
        inList = true;
      }
      out.push(`<li>${item[1]}</li>`);
      return;
    }
    if (inList) {
      out.push('</ul>');
      inList = false;
    }
    out.push(line);
  });
  if (inQuote) {
    out.push('</blockquote>');
  }
  if (inList) {
    out.push('</ul>');
  }
  text = out.join('\n');
  text = text
    .replace(/(<\/(?:blockquote|ul|pre)>)\n/g, '$1\n\n')
    .replace(/\n(<(?:blockquote|ul|pre)>)/g, '\n\n$1');
  slots.forEach(([key, html]) => {
    text = text.replaceAll(key, html);
  });
  text = text
    .split(/\n{2,}/)
    .map((block) => {
      const trimmed = block.trim();
      if (!trimmed) {
        return '';
      }
      if (/^<(pre|ul|blockquote|ol)\b/i.test(trimmed)) {
        return trimmed.replace(/\n/g, '');
      }
      return `<p>${trimmed.replace(/\n/g, '<br>')}</p>`;
    })
    .join('');
  return text || '<p></p>';
}

function wrapCommentSelection(textarea, before, after, fallback) {
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const value = textarea.value;
  const selected = value.slice(start, end) || fallback;
  const next = value.slice(0, start) + before + selected + after + value.slice(end);
  textarea.value = asciiComment(next);
  const caret = start + before.length + selected.length;
  textarea.setSelectionRange(start + before.length, caret);
  textarea.focus();
  textarea.dispatchEvent(new Event('input', { bubbles: true }));
}

function initComments() {
  const list = document.querySelector('.comment-list');
  document.querySelectorAll('[data-comment-sort]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-comment-sort]').forEach((other) => {
        other.classList.toggle('is-active', other === button);
      });
      if (!list) {
        return;
      }
      const items = [...list.children];
      if (button.getAttribute('data-comment-sort') === 'newest') {
        items.reverse();
      }
      items.forEach((item) => list.appendChild(item));
    });
  });

  document.querySelectorAll('.comment-copy-link').forEach((button) => {
    button.addEventListener('click', async () => {
      const url = button.getAttribute('data-copy') || '';
      try {
        await navigator.clipboard.writeText(url);
        button.textContent = 'Copied';
      } catch {
        button.textContent = 'Copy failed';
      }
      setTimeout(() => {
        button.textContent = 'Copy link';
      }, 1600);
    });
  });

  const textarea = document.querySelector('#comment');
  const preview = document.querySelector('#comment-preview');
  const count = document.querySelector('#comment-count');
  if (!(textarea instanceof HTMLTextAreaElement)) {
    return;
  }

  const sync = () => {
    textarea.value = asciiComment(textarea.value);
    if (count) {
      count.textContent = `${textarea.value.length} / 8000`;
    }
    if (preview) {
      preview.innerHTML = textarea.value.trim()
        ? renderCommentMarkdown(textarea.value)
        : '';
    }
  };
  textarea.addEventListener('input', sync);
  textarea.addEventListener('paste', () => {
    setTimeout(sync, 0);
  });
  sync();

  document.querySelectorAll('[data-comment-tool]').forEach((button) => {
    button.addEventListener('click', () => {
      const tool = button.getAttribute('data-comment-tool');
      const map = {
        bold: ['**', '**', 'bold'],
        italic: ['_', '_', 'italic'],
        code: ['`', '`', 'code'],
        quote: ['> ', '', 'quote'],
        ul: ['- ', '', 'item'],
        link: ['[', '](https://)', 'text'],
      };
      const spec = map[tool];
      if (spec) {
        wrapCommentSelection(textarea, spec[0], spec[1], spec[2]);
      }
    });
  });

  document.querySelectorAll('.comment-tab').forEach((tab) => {
    tab.addEventListener('click', () => {
      const write = tab.id === 'comment-tab-write';
      document.querySelector('#comment-tab-write')?.setAttribute('aria-selected', write ? 'true' : 'false');
      document.querySelector('#comment-tab-preview')?.setAttribute('aria-selected', write ? 'false' : 'true');
      document.querySelector('#comment-tab-write')?.classList.toggle('is-active', write);
      document.querySelector('#comment-tab-preview')?.classList.toggle('is-active', !write);
      document.querySelector('#comment-panel-write')?.toggleAttribute('hidden', !write);
      document.querySelector('#comment-panel-preview')?.toggleAttribute('hidden', write);
      if (!write) {
        sync();
      } else {
        textarea.focus();
      }
    });
  });

  textarea.addEventListener('keydown', (event) => {
    const meta = event.metaKey || event.ctrlKey;
    if (meta && event.key.toLowerCase() === 'b') {
      event.preventDefault();
      wrapCommentSelection(textarea, '**', '**', 'bold');
    }
    if (meta && event.key.toLowerCase() === 'i') {
      event.preventDefault();
      wrapCommentSelection(textarea, '_', '_', 'italic');
    }
    if (meta && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      wrapCommentSelection(textarea, '[', '](https://)', 'text');
    }
    if (meta && event.key === 'Enter') {
      event.preventDefault();
      textarea.form?.requestSubmit();
    }
  });
}

function initShareButtons() {
  document.querySelectorAll('.post-copy-link').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const url = btn.getAttribute('data-copy') || window.location.href;
      const span = btn.querySelector('span');
      const orig = span ? span.textContent : 'Copy link';
      try {
        await navigator.clipboard.writeText(url);
        if (span) span.textContent = 'Copied!';
        btn.classList.add('is-copied');
      } catch {
        if (span) span.textContent = 'Copy failed';
      }
      setTimeout(() => {
        if (span) span.textContent = orig;
        btn.classList.remove('is-copied');
      }, 2000);
    });
  });
}

function initPresenceReveal() {
  // Mark shared section shells without editing every template.
  document.querySelectorAll('.pf-section > .container > .about-shell, .pf-section > .container > .code-repos-shell, .work-card, .who-card, .cta-band, .h-cta, .lift-card').forEach((el) => {
    if (! el.hasAttribute('data-reveal')) {
      el.setAttribute('data-reveal', '');
    }
  });

  const nodes = document.querySelectorAll('[data-reveal]');
  if (!nodes.length) {
    return;
  }

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    nodes.forEach((el) => el.classList.add('is-in'));
    return;
  }

  if (!('IntersectionObserver' in window)) {
    nodes.forEach((el) => el.classList.add('is-in'));
    return;
  }

  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) {
        return;
      }
      entry.target.classList.add('is-in');
      io.unobserve(entry.target);
    });
  }, {
    rootMargin: '0px 0px -8% 0px',
    threshold: 0.12,
  });

  nodes.forEach((el) => io.observe(el));
}

document.addEventListener('DOMContentLoaded', () => {
  initPopoutMenu();
  initShareButtons();
  initReadingProgress();
  initTocSpy();
  initContactStatus();
  initComments();
  initCodeBlocks();
  initWritingTools();
  initWorkTools();
  initDiscoveryForm();
  initPresenceReveal();
});
