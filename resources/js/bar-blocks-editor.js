(function (wp) {
  if (!wp || !wp.blocks) return;
  var el = wp.element.createElement, __ = wp.i18n.__;
  var be = wp.blockEditor || wp.editor, ubp = be.useBlockProps;
  var SSR = wp.serverSideRender || wp.components.ServerSideRender;
  var defs = window.mhBarBlocks || {};

  Object.keys(defs).forEach(function (slug) {
    var d = defs[slug];
    wp.blocks.registerBlockType('mh/' + slug, {
      apiVersion: 2,
      title: d.title,
      description: __('Renders this item from your Customizer settings and stays in sync with them.', 'matthummel'),
      icon: d.icon || 'screenoptions',
      category: 'widgets',
      keywords: ['bar', 'header', 'sync'],
      supports: { html: false },
      edit: function () {
        return el('div', ubp ? ubp() : {}, el(SSR, { block: 'mh/' + slug }));
      },
      save: function () { return null; }
    });
  });
})(window.wp);
