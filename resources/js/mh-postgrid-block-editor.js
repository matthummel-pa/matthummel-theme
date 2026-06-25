(function (wp) {
  if (!wp || !wp.blocks) return;
  var el = wp.element.createElement, Fragment = wp.element.Fragment, __ = wp.i18n.__;
  var be = wp.blockEditor || wp.editor, IC = be.InspectorControls, ubp = be.useBlockProps;
  var c = wp.components, SSR = wp.serverSideRender || wp.components.ServerSideRender;

  wp.blocks.registerBlockType('mh/post-grid', {
    apiVersion: 2,
    title: __('Post Grid', 'matthummel'),
    description: __('Query posts or projects into a responsive card grid.', 'matthummel'),
    icon: 'grid-view',
    category: 'widgets',
    keywords: ['posts', 'projects', 'grid', 'cards'],
    supports: { align: ['wide', 'full'] },
    attributes: {
      postType:    { type: 'string',  default: 'post' },
      count:       { type: 'number',  default: 6 },
      columns:     { type: 'number',  default: 3 },
      orderby:     { type: 'string',  default: 'date' },
      order:       { type: 'string',  default: 'DESC' },
      showImage:   { type: 'boolean', default: true },
      showExcerpt: { type: 'boolean', default: true },
      showDate:    { type: 'boolean', default: true },
      showCategory:{ type: 'boolean', default: false }
    },
    edit: function (props) {
      var a = props.attributes, set = function (k){ return function (v){ var o={}; o[k]=v; props.setAttributes(o); }; };
      var controls = el(IC, {},
        el(c.PanelBody, { title: __('Query', 'matthummel'), initialOpen: true },
          el(c.SelectControl, { label: __('Post type', 'matthummel'), value: a.postType,
            options: [{label:'Posts',value:'post'},{label:'Projects',value:'projects'},{label:'Pages',value:'page'}], onChange: set('postType') }),
          el(c.RangeControl, { label: __('Number of items', 'matthummel'), value: a.count, min: 1, max: 24, onChange: set('count') }),
          el(c.RangeControl, { label: __('Columns', 'matthummel'), value: a.columns, min: 1, max: 4, onChange: set('columns') }),
          el(c.SelectControl, { label: __('Order by', 'matthummel'), value: a.orderby,
            options: [{label:'Date',value:'date'},{label:'Title',value:'title'},{label:'Random',value:'rand'},{label:'Menu order',value:'menu_order'}], onChange: set('orderby') }),
          el(c.SelectControl, { label: __('Order', 'matthummel'), value: a.order,
            options: [{label:'Descending',value:'DESC'},{label:'Ascending',value:'ASC'}], onChange: set('order') })
        ),
        el(c.PanelBody, { title: __('Card content', 'matthummel'), initialOpen: false },
          el(c.ToggleControl, { label: __('Featured image', 'matthummel'), checked: !!a.showImage, onChange: set('showImage') }),
          el(c.ToggleControl, { label: __('Excerpt', 'matthummel'), checked: !!a.showExcerpt, onChange: set('showExcerpt') }),
          el(c.ToggleControl, { label: __('Date', 'matthummel'), checked: !!a.showDate, onChange: set('showDate') }),
          el(c.ToggleControl, { label: __('Category', 'matthummel'), checked: !!a.showCategory, onChange: set('showCategory') })
        )
      );
      return el(Fragment, {}, controls, el('div', ubp ? ubp() : {}, el(SSR, { block: 'mh/post-grid', attributes: a })));
    },
    save: function () { return null; }
  });
})(window.wp);
