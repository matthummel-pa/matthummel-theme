(function (wp) {
  if (!wp || !wp.blocks) return;
  var el = wp.element.createElement, Fragment = wp.element.Fragment, __ = wp.i18n.__;
  var be = wp.blockEditor || wp.editor, IC = be.InspectorControls, ubp = be.useBlockProps;
  var c = wp.components, SSR = wp.serverSideRender || wp.components.ServerSideRender;

  function Repeater(items, onChange, defaultItem, renderRow) {
    return el('div', { className: 'mh-repeater' },
      items.map(function (item, i) {
        return el('div', { key: i, className: 'mh-repeater-row', style: { border: '1px solid #ddd', borderRadius: 6, padding: '10px 12px', marginBottom: 8, background: '#fafafa' } },
          renderRow(item, i, function (updates) {
            var next = items.slice(); next[i] = Object.assign({}, item, updates); onChange(next);
          }, function () {
            var next = items.slice(); next.splice(i, 1); onChange(next);
          })
        );
      }),
      el(c.Button, { variant: 'secondary', style: { marginTop: 6 }, onClick: function () { onChange(items.concat([defaultItem])); } }, '+ Add card')
    );
  }

  wp.blocks.registerBlockType('mh/skills-grid', {
    apiVersion: 2,
    title: __('Skills Grid', 'matthummel'),
    description: __('Grid of skill or feature cards — 2 or 3 columns.', 'matthummel'),
    icon: 'columns',
    category: 'matthummel',
    keywords: ['skills', 'features', 'cards', 'grid'],
    attributes: {
      cards:   { type: 'string', default: '[{"title":"Front-End","body":"HTML, CSS, JS, React, Tailwind"},{"title":"Back-End","body":"PHP, WordPress, Node.js, APIs"},{"title":"Accessibility","body":"WCAG 2.1, Core Web Vitals"}]' },
      columns: { type: 'number', default: 3 },
      style:   { type: 'string', default: 'default' }
    },
    edit: function (props) {
      var a = props.attributes;
      var items = [];
      try { items = JSON.parse(a.cards); } catch(e) { items = []; }
      var set = function (k) { return function (v) { var o = {}; o[k] = v; props.setAttributes(o); }; };

      var controls = el(IC, {},
        el(c.PanelBody, { title: __('Cards', 'matthummel'), initialOpen: true },
          Repeater(items, function(next){ set('cards')(JSON.stringify(next)); },
            { title: '', body: '' },
            function(item, i, update, remove) {
              return el(Fragment, {},
                el(c.TextControl, { label: __('Title', 'matthummel'), value: item.title || '', onChange: function(v){ update({title:v}); } }),
                el(c.TextareaControl, { label: __('Body', 'matthummel'), value: item.body || '', rows: 2, onChange: function(v){ update({body:v}); } }),
                el(c.Button, { isDestructive: true, variant: 'link', onClick: remove }, __('Remove', 'matthummel'))
              );
            }
          )
        ),
        el(c.PanelBody, { title: __('Layout', 'matthummel'), initialOpen: false },
          el(c.RangeControl, { label: __('Columns', 'matthummel'), value: a.columns, min: 2, max: 3, onChange: set('columns') }),
          el(c.SelectControl, { label: __('Card style', 'matthummel'), value: a.style,
            options: [{ label: __('Default (white card)', 'matthummel'), value: 'default' }, { label: __('Focus (green tint)', 'matthummel'), value: 'focus' }],
            onChange: set('style') })
        )
      );
      return el(Fragment, {}, controls, el('div', ubp ? ubp() : {}, el(SSR, { block: 'mh/skills-grid', attributes: a })));
    },
    save: function () { return null; }
  });
})(window.wp);
