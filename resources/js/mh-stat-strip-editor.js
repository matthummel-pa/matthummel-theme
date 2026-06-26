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
      el(c.Button, { variant: 'secondary', style: { marginTop: 6 }, onClick: function () { onChange(items.concat([defaultItem])); } }, '+ Add stat')
    );
  }

  wp.blocks.registerBlockType('mh/stat-strip', {
    apiVersion: 2,
    title: __('Stat Strip', 'matthummel'),
    description: __('A row of highlighted stats/numbers (About page, hero sections).', 'matthummel'),
    icon: 'chart-bar',
    category: 'widgets',
    keywords: ['stats', 'numbers', 'counter', 'about'],
    attributes: {
      stats:   { type: 'string', default: '[{"value":"15+","label":"Years experience"},{"value":"100+","label":"Projects"},{"value":"2","label":"Platforms"},{"value":"100%","label":"Remote"}]' },
      columns: { type: 'number', default: 4 }
    },
    edit: function (props) {
      var a = props.attributes;
      var items = [];
      try { items = JSON.parse(a.stats); } catch(e) { items = []; }
      var set = function (k) { return function (v) { var o = {}; o[k] = v; props.setAttributes(o); }; };

      var controls = el(IC, {},
        el(c.PanelBody, { title: __('Stats', 'matthummel'), initialOpen: true },
          Repeater(items, function(next){ set('stats')(JSON.stringify(next)); },
            { value: '', label: '' },
            function(item, i, update, remove) {
              return el(Fragment, {},
                el(c.TextControl, { label: __('Value', 'matthummel'), value: item.value || '', onChange: function(v){ update({value:v}); } }),
                el(c.TextControl, { label: __('Label', 'matthummel'), value: item.label || '', onChange: function(v){ update({label:v}); } }),
                el(c.Button, { isDestructive: true, variant: 'link', onClick: remove }, __('Remove', 'matthummel'))
              );
            }
          ),
          el(c.RangeControl, { label: __('Columns', 'matthummel'), value: a.columns, min: 2, max: 4, onChange: set('columns') })
        )
      );
      return el(Fragment, {}, controls, el('div', ubp ? ubp() : {}, el(SSR, { block: 'mh/stat-strip', attributes: a })));
    },
    save: function () { return null; }
  });
})(window.wp);
