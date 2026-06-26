(function (wp) {
  if (!wp || !wp.blocks) return;
  var el = wp.element.createElement, Fragment = wp.element.Fragment, __ = wp.i18n.__;
  var be = wp.blockEditor || wp.editor, IC = be.InspectorControls, ubp = be.useBlockProps;
  var c = wp.components, SSR = wp.serverSideRender || wp.components.ServerSideRender;

  function Repeater(items, onChange, defaultItem, renderRow) {
    return el('div', { className: 'mh-repeater' },
      items.map(function (item, i) {
        return el('div', { key: i, style: { border: '1px solid #ddd', borderRadius: 6, padding: '10px 12px', marginBottom: 8, background: '#fafafa' } },
          renderRow(item, i, function (updates) {
            var next = items.slice(); next[i] = Object.assign({}, item, updates); onChange(next);
          }, function () {
            var next = items.slice(); next.splice(i, 1); onChange(next);
          })
        );
      }),
      el(c.Button, { variant: 'secondary', style: { marginTop: 6 }, onClick: function () { onChange(items.concat([defaultItem])); } }, '+ Add link')
    );
  }

  wp.blocks.registerBlockType('mh/resource-group', {
    apiVersion: 2,
    title: __('Resource Group', 'matthummel'),
    description: __('A card of curated external links with a heading and emoji. Used on the Resources page.', 'matthummel'),
    icon: 'list-ul',
    category: 'widgets',
    keywords: ['resources', 'links', 'list', 'curated'],
    attributes: {
      heading: { type: 'string', default: 'Resources' },
      emoji:   { type: 'string', default: '🔗' },
      links:   { type: 'string', default: '[{"label":"MDN Web Docs","url":"https://developer.mozilla.org/"}]' }
    },
    edit: function (props) {
      var a = props.attributes;
      var items = [];
      try { items = JSON.parse(a.links); } catch(e) { items = []; }
      var set = function (k) { return function (v) { var o = {}; o[k] = v; props.setAttributes(o); }; };

      var controls = el(IC, {},
        el(c.PanelBody, { title: __('Group settings', 'matthummel'), initialOpen: true },
          el(c.TextControl, { label: __('Heading', 'matthummel'), value: a.heading, onChange: set('heading') }),
          el(c.TextControl, { label: __('Emoji / icon', 'matthummel'), value: a.emoji, onChange: set('emoji') })
        ),
        el(c.PanelBody, { title: __('Links', 'matthummel'), initialOpen: true },
          Repeater(items, function(next){ set('links')(JSON.stringify(next)); },
            { label: '', url: '' },
            function(item, i, update, remove) {
              return el(Fragment, {},
                el(c.TextControl, { label: __('Label', 'matthummel'), value: item.label || '', onChange: function(v){ update({label:v}); } }),
                el(c.TextControl, { label: __('URL', 'matthummel'), value: item.url || '', type: 'url', onChange: function(v){ update({url:v}); } }),
                el(c.Button, { isDestructive: true, variant: 'link', onClick: remove }, __('Remove', 'matthummel'))
              );
            }
          )
        )
      );
      return el(Fragment, {}, controls, el('div', ubp ? ubp() : {}, el(SSR, { block: 'mh/resource-group', attributes: a })));
    },
    save: function () { return null; }
  });
})(window.wp);
