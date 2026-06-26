/**
 * reading-progress.js
 * - Drives the reading progress bar on single posts
 * - Auto-generates a Table of Contents from h2/h3 in .post-prose
 */
(function () {
  'use strict';

  // ── Reading progress bar ─────────────────────────────────────────────
  const bar = document.getElementById('mh-progress');
  if (bar) {
    const update = () => {
      const docH  = document.documentElement.scrollHeight - window.innerHeight;
      const pct   = docH > 0 ? Math.min(100, Math.round((window.scrollY / docH) * 100)) : 0;
      bar.style.width = pct + '%';
      bar.setAttribute('aria-valuenow', pct);
    };
    window.addEventListener('scroll', update, { passive: true });
    update();
  }

  // ── Table of Contents ────────────────────────────────────────────────
  const prose    = document.getElementById('post-prose');
  const tocWrap  = document.getElementById('mh-toc-placeholder');
  const tocList  = document.getElementById('mh-toc-list');

  if (prose && tocWrap && tocList) {
    const headings = prose.querySelectorAll('h2, h3');
    if (headings.length >= 3) {
      headings.forEach((h, i) => {
        if (!h.id) h.id = 'toc-' + i;
        const li   = document.createElement('li');
        const link = document.createElement('a');
        link.href        = '#' + h.id;
        link.textContent = h.textContent;
        if (h.tagName === 'H3') li.style.paddingLeft = '14px';
        li.appendChild(link);
        tocList.appendChild(li);
      });
      tocWrap.style.display = 'block';
    }
  }

  // ── Code copy buttons ────────────────────────────────────────────────
  document.querySelectorAll('.post-prose pre').forEach(pre => {
    const btn = document.createElement('button');
    btn.className   = 'mh-copy';
    btn.textContent = 'Copy';
    btn.setAttribute('aria-label', 'Copy code');
    btn.addEventListener('click', () => {
      navigator.clipboard.writeText(pre.innerText).then(() => {
        btn.textContent = 'Copied!';
        setTimeout(() => { btn.textContent = 'Copy'; }, 2000);
      });
    });
    pre.style.position = 'relative';
    pre.appendChild(btn);
  });

})();
