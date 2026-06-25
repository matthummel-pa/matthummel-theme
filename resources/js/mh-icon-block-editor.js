(function (wp) {
  if (!wp || !wp.blocks) return;
  var el = wp.element.createElement, Fragment = wp.element.Fragment, __ = wp.i18n.__;
  var be = wp.blockEditor || wp.editor, IC = be.InspectorControls, ubp = be.useBlockProps;
  var c = wp.components, SSR = wp.serverSideRender || wp.components.ServerSideRender;

  wp.blocks.registerBlockType('mh/icon', {
    apiVersion: 2,
    title: __('Icon (Blade)', 'matthummel'),
    description: __('Insert any Blade icon: si- (brands), heroicon-o-/s-, lucide-, or mh- (local).', 'matthummel'),
    icon: 'star-filled',
    category: 'media',
    keywords: ['icon', 'svg', 'blade'],
    attributes: {
      name:  { type: 'string',  default: 'heroicon-o-sparkles' },
      size:  { type: 'number',  default: 32 },
      color: { type: 'string',  default: '' },
      align: { type: 'string',  default: 'left' },
      label: { type: 'string',  default: '' }
    },
    edit: function (props) {
      var a = props.attributes, set = function (k){ return function (v){ var o={}; o[k]=v; props.setAttributes(o); }; };
      var controls = el(IC, {},
        el(c.PanelBody, { title: __('Icon', 'matthummel'), initialOpen: true },
          el(c.TextControl, { label: __('Icon name', 'matthummel'), value: a.name, onChange: set('name'),
            help: __('e.g. si-github, heroicon-o-rocket, lucide-zap, mh-spark', 'matthummel') }),
          el(c.RangeControl, { label: __('Size (px)', 'matthummel'), value: a.size, min: 12, max: 160, onChange: set('size') }),
          el(c.BaseControl, { label: __('Color', 'matthummel') },
            el(c.ColorPalette, { value: a.color, onChange: set('color'), clearable: true })),
          el(c.SelectControl, { label: __('Alignment', 'matthummel'), value: a.align,
            options: [{label:'Left',value:'left'},{label:'Center',value:'center'},{label:'Right',value:'right'}], onChange: set('align') }),
          el(c.TextControl, { label: __('Accessible label (optional)', 'matthummel'), value: a.label, onChange: set('label'),
            help: __('Leave blank for decorative icons.', 'matthummel') })
        )
      );
      return el(Fragment, {}, controls, el('div', ubp ? ubp() : {}, el(SSR, { block: 'mh/icon', attributes: a })));
    },
    save: function () { return null; }
  });
})(window.wp);
