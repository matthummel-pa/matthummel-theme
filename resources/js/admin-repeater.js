/**
 * Repeater controls for Page content (theme).
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    var box = document.getElementById('mh_page_content');
    if (!box) return;

    function reindex(rep) {
      var name = rep.getAttribute('data-rep-name');
      var rows = rep.querySelectorAll(':scope > .mh-rep-rows > .mh-rep-row');
      rows.forEach(function (row, i) {
        row.querySelectorAll('[name]').forEach(function (input) {
          input.name = input.name.replace(/^(.*?)\[\d+\]/, name + '[' + i + ']');
        });
      });
    }

    function addRow(rep) {
      var tpl = rep.querySelector(':scope > .mh-rep-tpl');
      var rowsWrap = rep.querySelector(':scope > .mh-rep-rows');
      if (!tpl || !rowsWrap) return;
      var html = tpl.innerHTML.replace(/__i__/g, String(rowsWrap.children.length));
      var tmp = document.createElement('div');
      tmp.innerHTML = html.trim();
      var row = tmp.firstElementChild;
      if (!row) return;
      rowsWrap.appendChild(row);
      reindex(rep);
      var firstInput = row.querySelector('input, textarea');
      if (firstInput) {
        try { firstInput.focus(); } catch (e) {}
      }
    }

    function removeRow(row) {
      var rep = row.closest('.mh-rep');
      row.remove();
      if (rep) reindex(rep);
    }

    function moveRow(row, dir) {
      var rep = row.closest('.mh-rep');
      if (dir < 0 && row.previousElementSibling) {
        row.parentNode.insertBefore(row, row.previousElementSibling);
      } else if (dir > 0 && row.nextElementSibling) {
        row.parentNode.insertBefore(row.nextElementSibling, row);
      } else {
        return;
      }
      if (rep) reindex(rep);
    }

    box.addEventListener('click', function (e) {
      var t = e.target;
      if (t.closest('.mh-rep-add')) {
        e.preventDefault();
        addRow(t.closest('.mh-rep'));
      } else if (t.closest('.mh-rep-del')) {
        e.preventDefault();
        removeRow(t.closest('.mh-rep-row'));
      } else if (t.closest('.mh-rep-up')) {
        e.preventDefault();
        moveRow(t.closest('.mh-rep-row'), -1);
      } else if (t.closest('.mh-rep-down')) {
        e.preventDefault();
        moveRow(t.closest('.mh-rep-row'), 1);
      }
    });
  });
})();
