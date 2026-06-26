/* Hero image finder — Customizer control behavior.
 * Searches Openverse / Unsplash / Pexels and generates AI images (Pollinations),
 * imports the chosen image into the Media Library, and sets the linked setting.
 */
(function () {
  if (typeof wp === 'undefined' || !wp.customize) return;
  var cfg = window.mhIF || {};

  function el(tag, cls, html) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (html != null) e.innerHTML = html;
    return e;
  }

  function initFinder(root) {
    var settingId = root.getAttribute('data-setting');
    var q = root.querySelector('.mh-if-q');
    var go = root.querySelector('.mh-if-go');
    var results = root.querySelector('.mh-if-results');
    var note = root.querySelector('.mh-if-note');
    var tabs = root.querySelectorAll('.mh-if-tab');
    var source = 'openverse';

    function setNote(t) { note.textContent = t || ''; }

    tabs.forEach(function (t) {
      t.addEventListener('click', function () {
        tabs.forEach(function (x) { x.classList.remove('is-active'); });
        t.classList.add('is-active');
        source = t.getAttribute('data-src');
        results.innerHTML = '';
        setNote('');
        var searchRow = root.querySelector('.mh-if-searchrow');
        searchRow.style.display = source === 'canva' ? 'none' : 'flex';
        q.placeholder = source === 'ai' ? 'Describe an image to generate…' : 'Search images…';
        go.textContent = source === 'ai' ? 'Generate' : 'Search';
        if (source === 'unsplash' && !cfg.hasUnsplash) setNote('Add an Unsplash API key in the Hero section to use this source.');
        if (source === 'pexels' && !cfg.hasPexels) setNote('Add a Pexels API key in the Hero section to use this source.');
        if (source === 'canva') renderCanva();
      });
    });

    function renderCanva() {
      var canva = window.mhCanva || {};
      results.innerHTML = '';
      var wrap = el('div', 'mh-if-aiwrap');
      // Open the Canva editor in a new tab.
      var open = el('button', 'button mh-if-use', 'Open Canva editor ↗');
      open.type = 'button';
      open.addEventListener('click', function () { window.open(canva.editor || 'https://www.canva.com/create', '_blank', 'noopener'); });
      wrap.appendChild(open);
      // Import any image URL (e.g. a Canva export link).
      var row = el('div', 'mh-if-searchrow');
      row.style.marginTop = '6px';
      var urlIn = el('input', 'mh-if-q');
      urlIn.type = 'text';
      urlIn.placeholder = 'Paste an image URL to import…';
      var imp = el('button', 'button', 'Import');
      imp.type = 'button';
      imp.addEventListener('click', function () {
        var u = (urlIn.value || '').trim();
        if (!u) { setNote('Paste an image URL first.'); return; }
        importAndSet(u, wrap);
      });
      row.appendChild(urlIn);
      row.appendChild(imp);
      wrap.appendChild(row);
      results.appendChild(wrap);
      // Connected: list designs.
      if (canva.connected) {
        var loadBtn = el('button', 'button button-primary mh-if-use', 'Load my Canva designs');
        loadBtn.type = 'button';
        loadBtn.style.marginTop = '6px';
        loadBtn.addEventListener('click', loadCanvaDesigns);
        wrap.appendChild(loadBtn);
        setNote('Connected. Load your designs, or import an image URL.');
      } else {
        setNote('Tip: connect your Canva account in Theme Options → Canva to list designs here.');
      }
    }

    function loadCanvaDesigns() {
      setNote('Loading your Canva designs…');
      fetch(cfg.rest + 'mh/v1/canva-designs', { headers: { 'X-WP-Nonce': cfg.nonce } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d && d.error) { setNote('Canva: ' + d.error.replace('not_connected', 'not connected') + '.'); return; }
          if (!d || !d.length) { setNote('No designs found.'); return; }
          var grid = el('div', 'mh-if-results');
          d.forEach(function (it) {
            if (!it.thumb) return;
            var b = el('button', 'mh-if-tile');
            b.type = 'button';
            b.style.backgroundImage = 'url("' + it.thumb + '")';
            b.title = it.title || '';
            b.addEventListener('click', function () { exportCanva(it.id, b); });
            grid.appendChild(b);
          });
          results.appendChild(grid);
          setNote(d.length + ' designs — click one to export & use it.');
        })
        .catch(function () { setNote('Could not load designs.'); });
    }

    function exportCanva(designId, tile) {
      if (tile) tile.classList.add('is-loading');
      setNote('Exporting design from Canva…');
      fetch(cfg.rest + 'mh/v1/canva-export', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
        body: JSON.stringify({ design_id: designId })
      })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d && d.url) { importAndSet(d.url, tile); }
          else { setNote('Export failed' + (d && d.error ? ': ' + d.error : '') + '.'); if (tile) tile.classList.remove('is-loading'); }
        })
        .catch(function () { setNote('Export request failed.'); if (tile) tile.classList.remove('is-loading'); });
    }

    function setImage(url) {
      var s = wp.customize(settingId);
      if (s) { s.set(''); s.set(url); }
    }

    function importAndSet(remoteUrl, tile) {
      if (tile) tile.classList.add('is-loading');
      setNote('Saving image to your Media Library…');
      fetch(cfg.rest + 'mh/v1/img-import', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
        body: JSON.stringify({ url: remoteUrl })
      })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d && d.url) { setImage(d.url); setNote('Image set ✓ (saved to Media Library)'); }
          else { setNote('Could not import that image' + (d && d.error ? ': ' + d.error : '') + '.'); }
        })
        .catch(function () { setNote('Import failed.'); })
        .then(function () { if (tile) tile.classList.remove('is-loading'); });
    }

    function renderResults(items) {
      results.innerHTML = '';
      if (!items || !items.length) { setNote('No results.'); return; }
      setNote(items.length + ' results — click one to use it.');
      items.forEach(function (it) {
        if (!it.thumb && !it.full) return;
        var b = el('button', 'mh-if-tile');
        b.type = 'button';
        b.style.backgroundImage = 'url("' + (it.thumb || it.full) + '")';
        if (it.credit) b.title = it.credit;
        b.addEventListener('click', function () { importAndSet(it.full || it.thumb, b); });
        results.appendChild(b);
      });
    }

    function generateAI(prompt) {
      setNote('Generating…');
      results.innerHTML = '';
      var seed = Math.floor(Math.random() * 1e6);
      var url = 'https://image.pollinations.ai/prompt/' + encodeURIComponent(prompt) +
        '?width=1280&height=832&nologo=true&seed=' + seed;
      var wrap = el('div', 'mh-if-aiwrap');
      var img = el('img', 'mh-if-aiimg');
      img.alt = '';
      img.src = url;
      var use = el('button', 'button button-primary mh-if-use', 'Use this image');
      use.type = 'button';
      use.addEventListener('click', function () { importAndSet(url, wrap); });
      var regen = el('button', 'button mh-if-use', 'Regenerate');
      regen.type = 'button';
      regen.addEventListener('click', function () { generateAI(prompt); });
      img.onload = function () { setNote('Generated. Click “Use this image”, or Regenerate for a new take.'); };
      img.onerror = function () { setNote('Generation failed — try again.'); };
      wrap.appendChild(img);
      wrap.appendChild(use);
      wrap.appendChild(regen);
      results.appendChild(wrap);
    }

    function run() {
      var term = (q.value || '').trim();
      if (!term) { setNote('Type something first.'); return; }
      if (source === 'ai') { generateAI(term); return; }
      if (source === 'unsplash' && !cfg.hasUnsplash) { setNote('Unsplash API key required.'); return; }
      if (source === 'pexels' && !cfg.hasPexels) { setNote('Pexels API key required.'); return; }
      setNote('Searching ' + source + '…');
      fetch(cfg.rest + 'mh/v1/img-search?source=' + encodeURIComponent(source) + '&q=' + encodeURIComponent(term), {
        headers: { 'X-WP-Nonce': cfg.nonce }
      })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d && d.error === 'no_key') { setNote('API key required for this source.'); return; }
          renderResults(d || []);
        })
        .catch(function () { setNote('Search failed.'); });
    }

    go.addEventListener('click', run);
    q.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); run(); } });
  }

  function boot() {
    document.querySelectorAll('.mh-imgfinder').forEach(function (r) {
      if (!r.dataset.bound) { r.dataset.bound = '1'; initFinder(r); }
    });
  }

  if (document.readyState !== 'loading') boot();
  else document.addEventListener('DOMContentLoaded', boot);
  wp.customize.bind('ready', function () { setTimeout(boot, 600); });
  // Controls render lazily as sections expand; re-scan on interaction.
  document.addEventListener('click', function () { setTimeout(boot, 250); }, true);
})();
