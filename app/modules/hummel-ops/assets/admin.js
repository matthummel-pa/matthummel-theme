(function () {
	'use strict';
	var cfg = window.HOPS || {};

	function post(action, data) {
		var body = new URLSearchParams(Object.assign({ action: action, _wpnonce: cfg.nonce }, data));
		return fetch(cfg.ajax, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json(); });
	}

	function el(tag, attrs, text) {
		var n = document.createElement(tag);
		Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
		if (text) { n.textContent = text; }
		return n;
	}

	function link(href, text) {
		return el('a', { href: href, target: '_blank', rel: 'noopener noreferrer' }, text);
	}

	function ymd(s) {
		var p = s.split('-');
		return new Date(+p[0], +p[1] - 1, +p[2]);
	}

	function fmtDay(s) {
		return ymd(s).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
	}

	function fmtTime(iso) {
		return new Date(iso).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
	}

	function notice(parent, text, href, linkText) {
		var p = el('p', { 'class': 'hops-note' }, text + ' ');
		if (href) { p.appendChild(el('a', { href: href }, linkText)); }
		parent.appendChild(p);
	}

	function pill(text, tone) {
		return el('span', { 'class': 'hops-pill is-' + (tone || 'neutral') }, text);
	}

	function plural(n, one, many) { return n + ' ' + (n === 1 ? one : (many || one + 's')); }

	function whenShort(d) {
		return d.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
	}

	/* Empty state: a title, one sentence, and one next step. */
	function emptyState(title, body, href, label) {
		var d = el('div', { 'class': 'hops-empty' });
		d.appendChild(el('h2', {}, title));
		d.appendChild(el('p', {}, body));
		if (href) {
			var p = el('p');
			p.appendChild(el('a', { 'class': 'button', href: href }, label));
			d.appendChild(p);
		}
		return d;
	}

	/* ---------- Confirm dialog for destructive actions ---------- */
	function confirmDialog(opts) {
		var dlg = el('dialog', { 'class': 'hops-dialog', 'aria-labelledby': 'hops-dlg-title' });
		var head = el('div', { 'class': 'hops-dialog-head', id: 'hops-dlg-title' }, opts.title);
		var body = el('p', { 'class': 'hops-dialog-body' }, opts.body);
		var foot = el('div', { 'class': 'hops-dialog-foot' });
		var cancel = el('button', { type: 'button', 'class': 'button' }, 'Cancel');
		var ok = el('button', { type: 'button', 'class': 'button button-danger' }, opts.button);
		foot.append(cancel, ok);
		dlg.append(head, body, foot);
		document.body.appendChild(dlg);
		cancel.addEventListener('click', function () { dlg.close(); });
		ok.addEventListener('click', function () { dlg.close(); opts.onConfirm(); });
		dlg.addEventListener('close', function () { dlg.remove(); });
		dlg.showModal();
		cancel.focus();
	}

	document.querySelectorAll('[data-hops-confirm]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var form = document.getElementById('hops-' + btn.dataset.hopsConfirm);
			var go = function () { if (form) { form.submit(); } };
			if (typeof HTMLDialogElement === 'undefined') { go(); return; }
			confirmDialog({
				title: btn.dataset.confirmTitle,
				body: btn.dataset.confirmBody,
				button: btn.dataset.confirmButton,
				onConfirm: go
			});
		});
	});

	/* ---------- Integrations: open the section a card or link points to ---------- */
	function openSection(id) {
		var d = id && document.getElementById(id);
		if (d && d.tagName === 'DETAILS') {
			d.open = true;
			d.scrollIntoView({ block: 'start' });
			var f = d.querySelector('input:not([type=hidden]), select');
			if (f) { f.focus({ preventScroll: true }); }
		}
	}
	document.querySelectorAll('[data-hops-open]').forEach(function (a) {
		a.addEventListener('click', function (e) { e.preventDefault(); openSection(a.dataset.hopsOpen); });
	});
	if (location.hash.indexOf('#hops-sec-') === 0) { openSection(location.hash.slice(1)); }
	window.addEventListener('hashchange', function () { if (location.hash.indexOf('#hops-sec-') === 0) { openSection(location.hash.slice(1)); } });

	/* ---------- Workflow run buttons (Workflows + Today) ---------- */
	function setLastRun(card, ok, text, when) {
		var meta = card.querySelector('.hops-last');
		if (!meta) { return; }
		meta.textContent = '';
		meta.appendChild(pill(ok ? 'Succeeded' : text, ok ? 'ok' : 'bad'));
		meta.appendChild(el('span', {}, when || whenShort(new Date())));
	}

	document.querySelectorAll('.hops-card').forEach(function (card) {
		var btn = card.querySelector('.hops-run');
		var out = card.querySelector('.hops-result');
		if (!btn || !out) { return; }
		btn.addEventListener('click', function () {
			var label = btn.textContent;
			btn.disabled = true;
			btn.textContent = 'Running…';
			out.hidden = true;
			out.className = 'hops-result';
			post('hops_run_workflow', {
				index: card.dataset.index,
				payload: card.querySelector('.hops-payload').value
			}).then(function (res) {
				var d = res.data || {};
				out.hidden = false;
				out.classList.add(res.success ? 'is-ok' : 'is-err');
				var head = res.success ? 'Started. n8n answered HTTP ' + d.status + ' in ' + (d.ms / 1000).toFixed(1) + ' s.' : (d.message || 'Request failed') + '. Check the webhook URL and that the workflow is active.';
				out.textContent = head + (d.body ? '\n\n' + d.body : '');
				setLastRun(card, res.success, d.status ? 'Failed (HTTP ' + d.status + ')' : 'Could not reach n8n', d.when);
			}).catch(function () {
				out.hidden = false;
				out.classList.add('is-err');
				out.textContent = 'The request did not reach WordPress. Check your connection and try again.';
				setLastRun(card, false, 'Could not reach n8n');
			}).finally(function () {
				btn.disabled = false;
				btn.textContent = label;
			});
		});
	});

	/* ---------- Settings: workflow repeater ---------- */
	var add = document.getElementById('hops-add');
	var rows = document.querySelector('#hops-rows tbody');
	if (add && rows) {
		add.addEventListener('click', function () {
			var i = parseInt(add.dataset.next, 10);
			add.dataset.next = i + 1;
			var opt = add.dataset.opt;
			var tr = el('tr');
			var td1 = el('td'), td2 = el('td'), td3 = el('td');
			td1.appendChild(el('input', { type: 'text', 'class': 'regular-text', name: opt + '[workflows][' + i + '][name]' }));
			td2.appendChild(el('input', { type: 'url', 'class': 'large-text', name: opt + '[workflows][' + i + '][url]', placeholder: 'https://n8n.example.com/webhook/...' }));
			td3.appendChild(el('button', { type: 'button', 'class': 'button hops-remove' }, 'Remove'));
			tr.append(td1, td2, td3);
			rows.appendChild(tr);
		});
		rows.addEventListener('click', function (e) {
			if (e.target.classList.contains('hops-remove')) {
				e.target.closest('tr').remove();
			}
		});
	}

	/* ---------- Tasks ---------- */
	function dueLabel(due, today) {
		if (!due) { return null; }
		if (due < today) { return pill('Overdue · ' + fmtDay(due), 'bad'); }
		if (due === today) { return pill('Due today', 'ok'); }
		return pill(fmtDay(due), 'neutral');
	}

	function taskRow(item, today, onDone) {
		var li = el('li', { 'class': 'hops-task', 'data-provider': item.provider });
		var chk = el('button', { type: 'button', 'class': 'hops-check', 'aria-label': 'Mark done: ' + item.title });
		chk.addEventListener('click', function () {
			chk.disabled = true;
			li.classList.add('is-busy');
			post('hops_todo_done', { provider: item.provider, id: item.id }).then(function (res) {
				if (res.success) {
					li.classList.add('is-done');
					setTimeout(function () { li.remove(); if (onDone) { onDone(); } }, 300);
				} else {
					chk.disabled = false;
					li.classList.remove('is-busy');
					li.appendChild(el('span', { 'class': 'hops-inline-err' }, ((res.data && res.data.message) || 'Could not complete this task') + '. Try again.'));
				}
			}).catch(function () {
				chk.disabled = false;
				li.classList.remove('is-busy');
				li.appendChild(el('span', { 'class': 'hops-inline-err' }, 'Network error. Try again.'));
			});
		});
		var title = el('span', { 'class': 'hops-task-title' });
		if (item.url) { title.appendChild(link(item.url, item.title)); } else { title.textContent = item.title; }
		li.append(chk, title);
		var dl = dueLabel(item.due, today);
		if (dl) { li.appendChild(dl); }
		li.appendChild(pill(item.provider_label, 'info'));
		return li;
	}

	function addDays(today, n) {
		var d = new Date(ymd(today).getTime() + n * 86400000);
		var p = function (x) { return (x < 10 ? '0' : '') + x; };
		return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
	}

	function groupTasks(items, today, withLater) {
		var week = addDays(today, 7);
		var defs = [
			['Overdue', function (i) { return i.due && i.due < today; }, 'bad'],
			['Due today', function (i) { return i.due === today; }, 'ok'],
			['Next 7 days', function (i) { return i.due && i.due > today && i.due <= week; }, 'neutral']
		];
		if (withLater) {
			defs.push(['Later', function (i) { return i.due && i.due > week; }, 'neutral']);
			defs.push(['No date', function (i) { return !i.due; }, 'neutral']);
		}
		return defs.map(function (g) {
			return { title: g[0], tone: g[2], items: items.filter(g[1]) };
		}).filter(function (g) { return g.items.length; });
	}

	function drawGroups(box, groups, today, onDone) {
		groups.forEach(function (g) {
			var sec = el('div', { 'class': 'hops-group' });
			var h = el('h3', {}, g.title + ' ');
			h.appendChild(pill(String(g.items.length), g.tone));
			sec.appendChild(h);
			var ul = el('ul', { 'class': 'hops-tasklist' });
			g.items.forEach(function (i) { ul.appendChild(taskRow(i, today, onDone)); });
			sec.appendChild(ul);
			box.appendChild(sec);
		});
	}

	function showErrors(box, errors) {
		box.textContent = '';
		Object.keys(errors || {}).forEach(function (k) {
			var d = el('div', { 'class': 'notice notice-warning inline' });
			d.appendChild(el('p', {}, k + ' did not load: ' + String(errors[k]).replace(/\.?\s*$/, '.') + ' Check its token in Integrations.'));
			box.appendChild(d);
		});
	}

	function initTasks(root) {
		var groupsBox = root.querySelector('.hops-groups');
		var status = root.querySelector('.hops-status');
		var errBox = root.querySelector('.hops-errors');
		var form = root.querySelector('.hops-task-form');
		var chips = root.querySelectorAll('.hops-chip');
		var filter = '';
		var cache = { items: [], today: '' };

		function counts() {
			chips.forEach(function (c) {
				var n = cache.items.filter(function (i) { return !c.dataset.filter || i.provider === c.dataset.filter; }).length;
				c.querySelector('.n').textContent = n;
			});
		}

		function draw() {
			groupsBox.textContent = '';
			counts();
			var shown = cache.items.filter(function (i) { return !filter || i.provider === filter; });
			if (!shown.length) {
				groupsBox.hidden = true;
				status.textContent = filter ? 'No open tasks in this app.' : 'No open tasks. Add one above to start your list.';
				return;
			}
			status.textContent = '';
			groupsBox.hidden = false;
			drawGroups(groupsBox, groupTasks(shown, cache.today, true), cache.today, function () {
				cache.items = cache.items.filter(function (x) { return x.__gone !== true; });
				load(true);
			});
		}

		function load(quiet) {
			if (!quiet) { status.textContent = 'Loading tasks…'; }
			post('hops_todos_list').then(function (res) {
				if (!res.success) { status.textContent = 'Tasks did not load. Reload the page to try again.'; return; }
				cache = res.data;
				showErrors(errBox, res.data.errors);
				draw();
			}).catch(function () { status.textContent = 'Network error while loading tasks. Reload the page to try again.'; });
		}

		chips.forEach(function (c) {
			c.addEventListener('click', function () {
				filter = c.dataset.filter;
				chips.forEach(function (x) { x.setAttribute('aria-pressed', x === c ? 'true' : 'false'); });
				draw();
			});
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var btn = form.querySelector('button');
			btn.disabled = true;
			post('hops_todo_add', {
				title: form.title.value,
				due: form.due.value,
				provider: form.provider.value
			}).then(function (res) {
				if (res.success) {
					form.title.value = '';
					form.due.value = '';
					form.title.focus();
					load(true);
				} else {
					status.textContent = ((res.data && res.data.message) || 'The task was not added') + '.';
				}
			}).catch(function () {
				status.textContent = 'Network error. The task was not added.';
			}).finally(function () { btn.disabled = false; });
		});

		load();
	}

	/* ---------- Today ---------- */
	function initToday(root) {
		var sec = function (n) { return root.querySelector('[data-sec="' + n + '"] .hops-body'); };
		var lede = document.getElementById('hops-lede');
		var base = lede ? lede.textContent.split('. Loading')[0] : '';
		var facts = { events: null, due: null, overdue: 0 };

		function setStat(id, value, subNode) {
			var box = document.getElementById(id);
			if (!box) { return; }
			box.querySelector('.hops-stat-value').textContent = value;
			var sub = box.querySelector('.hops-stat-sub');
			sub.textContent = '';
			if (subNode) { sub.appendChild(subNode); }
		}

		function writeLede() {
			if (!lede) { return; }
			var bits = [];
			if (facts.events !== null) { bits.push(plural(facts.events, 'event') + ' today'); }
			if (facts.due !== null) { bits.push(plural(facts.due, 'task') + ' due or overdue'); }
			lede.textContent = base + '. ' + (bits.length ? bits.join(' and ') + '.' : 'Connect Google and a task app to fill in your day.');
		}

		function drawCalendar(box, block) {
			box.textContent = '';
			if (block.error) {
				if (block.code === 'hops_nc') {
					box.appendChild(emptyState('Connect Google to see your day', 'Today’s events show here after one sign-in.', cfg.urls.files, 'Connect Google'));
					setStat('hops-stat-events', '–', el('a', { href: cfg.urls.files }, 'Connect Google'));
				} else {
					notice(box, 'The calendar did not load: ' + block.error);
					setStat('hops-stat-events', '–', pill('Did not load', 'bad'));
				}
				return;
			}
			facts.events = block.data.length;
			setStat('hops-stat-events', String(block.data.length), block.data.length ? null : pill('Clear day', 'ok'));
			if (!block.data.length) { notice(box, 'Nothing on your calendar today.'); return; }
			block.data.forEach(function (e) {
				var row = el('div', { 'class': 'hops-event' });
				row.appendChild(el('span', { 'class': 'hops-time' }, e.all_day ? 'All day' : fmtTime(e.start)));
				var t = el('span');
				t.appendChild(e.link ? link(e.link, e.title) : document.createTextNode(e.title));
				if (e.meet) { t.appendChild(document.createTextNode(' ')); t.appendChild(link(e.meet, 'Join call')); }
				row.appendChild(t);
				if (e.location) { row.appendChild(el('span', { 'class': 'hops-muted' }, e.location)); }
				box.appendChild(row);
			});
		}

		function drawFiles(box, block) {
			box.textContent = '';
			if (block.error) {
				if (block.code === 'hops_nc') {
					box.appendChild(emptyState('Connect Google to see recent files', 'Your six most recently edited documents show here.', cfg.urls.files, 'Connect Google'));
				} else {
					notice(box, 'Files did not load: ' + block.error);
				}
				return;
			}
			if (!block.data.length) { notice(box, 'No recent files yet.'); return; }
			var ul = el('ul', { 'class': 'hops-rows' });
			block.data.forEach(function (f) {
				var li = el('li');
				var g = el('span', { 'class': 'grow hops-file' });
				if (f.iconLink) { g.appendChild(el('img', { src: f.iconLink, alt: '', width: 16, height: 16, 'class': 'hops-icon' })); }
				g.appendChild(f.webViewLink ? link(f.webViewLink, f.name) : document.createTextNode(f.name));
				li.appendChild(g);
				if (f.modifiedTime) { li.appendChild(el('span', { 'class': 'hops-muted' }, new Date(f.modifiedTime).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }))); }
				ul.appendChild(li);
			});
			box.appendChild(ul);
		}

		function drawTasks(box, block, today) {
			box.textContent = '';
			showErrors(box, block.errors);
			var groups = groupTasks(block.items, today, false);
			var overdue = block.items.filter(function (i) { return i.due && i.due < today; }).length;
			var dueNow = overdue + block.items.filter(function (i) { return i.due === today; }).length;
			facts.due = dueNow;
			facts.overdue = overdue;
			setStat('hops-stat-tasks', String(dueNow), overdue ? pill(overdue + ' overdue', 'bad') : pill('On track', 'ok'));
			drawGroups(box, groups, today, function () { load(); });
			var undated = block.items.filter(function (i) { return !i.due; }).length;
			if (!groups.length) { notice(box, 'Nothing is due this week.'); }
			if (undated) { notice(box, plural(undated, 'task') + ' without a date.', cfg.urls.tasks, 'Open Tasks'); }
		}

		function load() {
			post('hops_rundown').then(function (res) {
				if (!res.success) { return; }
				drawCalendar(sec('calendar'), res.data.calendar);
				drawFiles(sec('files'), res.data.files);
				drawTasks(sec('tasks'), res.data.tasks, res.data.today);
				writeLede();
			}).catch(function () {
				['calendar', 'tasks', 'files'].forEach(function (n) { sec(n).textContent = 'Network error. Reload the page to try again.'; });
				if (lede) { lede.textContent = base + '. Your day did not load.'; }
			});
		}

		var quick = root.querySelector('.hops-quick');
		quick.addEventListener('submit', function (e) {
			e.preventDefault();
			var btn = quick.querySelector('button');
			btn.disabled = true;
			post('hops_todo_add', { title: quick.title.value, due: new Date().toLocaleDateString('en-CA'), provider: '' }).then(function (res) {
				if (res.success) { quick.title.value = ''; load(); }
			}).finally(function () { btn.disabled = false; });
		});

		load();
	}

	/* ---------- Drive ---------- */
	function initDrive(root) {
		var tbody = root.querySelector('tbody');
		var crumbs = root.querySelector('.hops-crumbs');
		var status = root.querySelector('.hops-status');
		var more = root.querySelector('.hops-more');
		var searchInput = root.querySelector('.hops-search input');
		var state = { mode: 'folder', stack: [{ id: 'root', name: 'My Drive' }], q: '', next: '' };
		var FOLDER = 'application/vnd.google-apps.folder';
		var TYPES = {
			'application/vnd.google-apps.document': 'Google Doc',
			'application/vnd.google-apps.spreadsheet': 'Google Sheet',
			'application/vnd.google-apps.presentation': 'Google Slides',
			'application/vnd.google-apps.form': 'Google Form',
			'application/pdf': 'PDF'
		};
		TYPES[FOLDER] = 'Folder';

		function setTab(mode) {
			root.querySelectorAll('.hops-tab').forEach(function (t) {
				t.setAttribute('aria-selected', t.dataset.mode === mode ? 'true' : 'false');
			});
		}

		function renderCrumbs() {
			crumbs.textContent = '';
			if (state.mode !== 'folder' || state.q) { return; }
			state.stack.forEach(function (c, i) {
				if (i) { crumbs.appendChild(document.createTextNode(' / ')); }
				if (i === state.stack.length - 1) {
					crumbs.appendChild(el('strong', {}, c.name));
				} else {
					var a = el('a', { href: '#' }, c.name);
					a.addEventListener('click', function (e) {
						e.preventDefault();
						state.stack = state.stack.slice(0, i + 1);
						load(true);
					});
					crumbs.appendChild(a);
				}
			});
		}

		function addRow(f) {
			var tr = el('tr');
			var name = el('td');
			var wrap = el('span', { 'class': 'hops-file' });
			name.appendChild(wrap);
			if (f.iconLink) { wrap.appendChild(el('img', { src: f.iconLink, alt: '', width: 16, height: 16, 'class': 'hops-icon' })); }
			if (f.mimeType === FOLDER) {
				var b = el('a', { href: '#' }, f.name);
				b.addEventListener('click', function (e) {
					e.preventDefault();
					state.mode = 'folder';
					state.q = '';
					searchInput.value = '';
					setTab('folder');
					state.stack.push({ id: f.id, name: f.name });
					load(true);
				});
				wrap.appendChild(b);
			} else if (f.webViewLink) {
				wrap.appendChild(link(f.webViewLink, f.name));
			} else {
				wrap.appendChild(document.createTextNode(f.name));
			}
			tr.append(
				name,
				el('td', {}, TYPES[f.mimeType] || (f.mimeType || '').split('/').pop()),
				el('td', {}, f.modifiedTime ? new Date(f.modifiedTime).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '')
			);
			tbody.appendChild(tr);
		}

		function load(reset) {
			if (reset) { tbody.textContent = ''; state.next = ''; }
			renderCrumbs();
			status.textContent = 'Loading files…';
			more.hidden = true;
			post('hops_drive_list', {
				mode: state.mode,
				folder: state.stack[state.stack.length - 1].id,
				q: state.q,
				pageToken: state.next
			}).then(function (res) {
				if (!res.success) {
					status.textContent = ((res.data && res.data.message) || 'Drive did not load') + '. Reload the page to try again.';
					return;
				}
				res.data.files.forEach(addRow);
				state.next = res.data.next;
				status.textContent = tbody.children.length ? '' : (state.q ? 'No files match “' + state.q + '”. Try a shorter search.' : 'This folder is empty.');
				more.hidden = !state.next;
			}).catch(function () { status.textContent = 'Network error while loading Drive. Reload the page to try again.'; });
		}

		root.querySelectorAll('.hops-tab').forEach(function (t) {
			t.addEventListener('click', function () {
				state.mode = t.dataset.mode;
				state.q = '';
				searchInput.value = '';
				state.stack = [{ id: 'root', name: 'My Drive' }];
				setTab(state.mode);
				load(true);
			});
		});
		root.querySelector('.hops-search').addEventListener('submit', function (e) {
			e.preventDefault();
			state.q = searchInput.value.trim();
			load(true);
		});
		more.addEventListener('click', function () { load(false); });
		load(true);
	}

	/* ---------- WordPress Releases ---------- */
	function initReleases(root) {
		var status = root.querySelector('.hops-status');
		var buttons = root.querySelectorAll('[data-hops-wp]');
		buttons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var label = btn.textContent;
				buttons.forEach(function (b) { b.disabled = true; });
				btn.textContent = 'Working…';
				status.className = 'hops-status';
				status.textContent = 'Reading WordPress.org and updating your documents. This takes a few seconds.';
				post('hops_wprel_run', { mode: btn.dataset.hopsWp }).then(function (res) {
					var msg = (res.data && res.data.message) || (res.success ? 'Done.' : 'Request failed.');
					status.classList.add(res.success ? 'is-ok' : 'is-err');
					status.textContent = msg;
					if (res.success) { setTimeout(function () { window.location.reload(); }, 1200); return; }
					buttons.forEach(function (b) { b.disabled = false; });
					btn.textContent = label;
				}).catch(function () {
					status.classList.add('is-err');
					status.textContent = 'Network error. Try again in a moment.';
					buttons.forEach(function (b) { b.disabled = false; });
					btn.textContent = label;
				});
			});
		});
	}

	var t = document.getElementById('hops-tasks');
	if (t) { initTasks(t); }
	var d = document.getElementById('hops-today');
	if (d) { initToday(d); }
	var wp = document.getElementById('hops-wp');
	if (wp) { initReleases(wp); }
	var dr = document.getElementById('hops-drive');
	if (dr) { initDrive(dr); }
})();
