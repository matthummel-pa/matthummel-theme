(function (wp) {
  if (!wp || !wp.hooks) return;
  var el = wp.element.createElement, Fragment = wp.element.Fragment, __ = wp.i18n.__;
  var IC = (wp.blockEditor || wp.editor).InspectorControls, c = wp.components;
  var compose = wp.compose.createHigherOrderComponent;

  var LANGS = ['', 'bash','css','diff','docker','go','graphql','html','ini','java','javascript','json','jsx',
    'markdown','markup','php','python','ruby','rust','scss','shell','sql','tsx','typescript','yaml'];

  // 1) add attributes to core/code
  wp.hooks.addFilter('blocks.registerBlockType', 'mh/code-attrs', function (settings, name) {
    if (name !== 'core/code') return settings;
    settings.attributes = Object.assign({}, settings.attributes, {
      mhLang:  { type: 'string',  default: '' },
      mhFile:  { type: 'string',  default: '' },
      mhLines: { type: 'boolean', default: true }
    });
    return settings;
  });

  // 2) inspector controls
  wp.hooks.addFilter('editor.BlockEdit', 'mh/code-controls', compose(function (BlockEdit) {
    return function (props) {
      if (props.name !== 'core/code') return el(BlockEdit, props);
      var a = props.attributes, set = function (k){ return function (v){ var o={}; o[k]=v; props.setAttributes(o); }; };
      return el(Fragment, {},
        el(BlockEdit, props),
        el(IC, {}, el(c.PanelBody, { title: __('Code highlighting', 'matthummel'), initialOpen: true },
          el(c.SelectControl, { label: __('Language', 'matthummel'), value: a.mhLang,
            options: LANGS.map(function(l){ return { label: l || '— none —', value: l }; }), onChange: set('mhLang') }),
          el(c.TextControl, { label: __('Filename (optional)', 'matthummel'), value: a.mhFile, onChange: set('mhFile'), placeholder: 'app.js' }),
          el(c.ToggleControl, { label: __('Line numbers', 'matthummel'), checked: !!a.mhLines, onChange: set('mhLines') })
        ))
      );
    };
  }, 'withMhCodeControls'));

  // 3) write classes + data-filename into the saved <pre>
  wp.hooks.addFilter('blocks.getSaveContent.extraProps', 'mh/code-save', function (extra, blockType, attrs) {
    if (blockType.name !== 'core/code') return extra;
    var cls = (extra.className || '').replace(/\blanguage-\S+\b/g, '').replace(/\bline-numbers\b/g, '').trim();
    if (attrs.mhLang)  cls += ' language-' + attrs.mhLang;
    if (attrs.mhLines) cls += ' line-numbers';
    extra.className = cls.trim();
    if (attrs.mhFile) extra['data-filename'] = attrs.mhFile; else delete extra['data-filename'];
    return extra;
  });
})(window.wp);
