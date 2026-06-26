/**
 * mh/section — Page-builder wrapper block (editor UI).
 *
 * No build step. Loaded directly via wp_register_script.
 * Requires: wp-blocks, wp-element, wp-block-editor, wp-components, wp-i18n
 */
(function (wp) {
  'use strict';
  if (!wp || !wp.blocks) return;

  var el          = wp.element.createElement;
  var __          = wp.i18n.__;
  var Fragment    = wp.element.Fragment;
  var InnerBlocks = wp.blockEditor.InnerBlocks;
  var ubp         = wp.blockEditor.useBlockProps;
  var IC          = wp.blockEditor.InspectorControls;
  var c           = wp.components;

  /* ── Colour options ───────────────────────────────────────────────── */
  var BG_COLOURS = [
    { label: 'None',            value: '' },
    { label: 'Paper (default)', value: 'paper' },
    { label: 'Cream',          value: 'cream' },
    { label: 'Card (white)',    value: 'card' },
    { label: 'Green tint',     value: 'tint' },
    { label: 'Green',          value: 'green' },
    { label: 'Ink (dark)',     value: 'ink' },
  ];

  var PADDING_OPTS = [
    { label: 'None',        value: 'none' },
    { label: 'Small',       value: 'sm' },
    { label: 'Medium',      value: 'md' },
    { label: 'Large',       value: 'lg' },
    { label: 'Extra large', value: 'xl' },
  ];

  var WIDTH_OPTS = [
    { label: 'Narrow (720 px)',    value: 'narrow' },
    { label: 'Contained (1180 px)',value: 'contained' },
    { label: 'Wide (1440 px)',     value: 'wide' },
    { label: 'Full width',         value: 'full' },
  ];

  var TEXT_OPTS = [
    { label: 'Auto',          value: '' },
    { label: 'Light (white)', value: 'light' },
    { label: 'Dark (ink)',    value: 'dark' },
  ];

  /* ── CSS-var map for editor live preview ─────────────────────────── */
  var BG_VAR = {
    paper: 'var(--color-paper,#fbfaf7)',
    cream: 'var(--color-cream,#f0ede6)',
    card:  '#ffffff',
    tint:  'var(--color-green-tint,#eef6f1)',
    green: 'var(--color-green,#2f6b4e)',
    ink:   'var(--color-ink,#17191e)',
  };

  /* ── Block definition ────────────────────────────────────────────── */
  wp.blocks.registerBlockType('mh/section', {
    apiVersion: 2,
    title:      __('Section', 'matthummel'),
    description: __('Page-builder wrapper — set background, padding, and container width, then drop any blocks inside.', 'matthummel'),
    icon:       'layout',
    category:   'matthummel',
    keywords:   ['section', 'wrapper', 'layout', 'builder', 'row', 'container'],

    supports: {
      align:  ['full'],
      anchor: true,
      html:   false,
    },

    attributes: {
      bgColor:        { type: 'string',  default: '' },
      bgImageUrl:     { type: 'string',  default: '' },
      bgOverlay:      { type: 'number',  default: 40 },
      paddingTop:     { type: 'string',  default: 'md' },
      paddingBottom:  { type: 'string',  default: 'md' },
      containerWidth: { type: 'string',  default: 'contained' },
      textColor:      { type: 'string',  default: '' },
      hasRule:        { type: 'boolean', default: false },
      anchor:         { type: 'string',  default: '' },
    },

    edit: function (props) {
      var a   = props.attributes;
      var set = function (key) { return function (val) { var o = {}; o[key] = val; props.setAttributes(o); }; };

      /* Live preview styles */
      var wrapStyle = {};
      if (a.bgColor && BG_VAR[a.bgColor]) wrapStyle.backgroundColor = BG_VAR[a.bgColor];
      if (a.bgImageUrl) {
        var alpha = Math.round(a.bgOverlay) / 100;
        wrapStyle.backgroundImage    = 'linear-gradient(rgba(0,0,0,' + alpha + '),rgba(0,0,0,' + alpha + ')),url(' + a.bgImageUrl + ')';
        wrapStyle.backgroundSize     = 'cover';
        wrapStyle.backgroundPosition = 'center';
      }
      if (a.textColor === 'light') wrapStyle.color = 'var(--color-paper,#fbfaf7)';
      if (a.textColor === 'dark')  wrapStyle.color = 'var(--color-ink,#17191e)';

      var pt = a.paddingTop  !== 'none' ? ({ none: 0, sm: 24, md: 48, lg: 72, xl: 96 }[a.paddingTop]  || 48) + 'px' : '0';
      var pb = a.paddingBottom !== 'none' ? ({ none: 0, sm: 24, md: 48, lg: 72, xl: 96 }[a.paddingBottom] || 48) + 'px' : '0';
      wrapStyle.paddingTop    = pt;
      wrapStyle.paddingBottom = pb;

      var blockProps = ubp({ style: wrapStyle, className: 'mh-section' });

      var controls = el(IC, {},
        /* Background */
        el(c.PanelBody, { title: __('Background', 'matthummel'), initialOpen: true },
          el(c.SelectControl, {
            label:    __('Colour', 'matthummel'),
            value:    a.bgColor,
            options:  BG_COLOURS,
            onChange: set('bgColor'),
          }),
          el(c.TextControl, {
            label:    __('Image URL', 'matthummel'),
            value:    a.bgImageUrl,
            type:     'url',
            help:     __('Enter a full URL or paste from Media Library.', 'matthummel'),
            onChange: set('bgImageUrl'),
          }),
          a.bgImageUrl
            ? el(c.RangeControl, {
                label:    __('Overlay opacity %', 'matthummel'),
                value:    a.bgOverlay,
                min:      0,
                max:      80,
                step:     5,
                onChange: set('bgOverlay'),
              })
            : null,
          el(c.SelectControl, {
            label:    __('Text colour', 'matthummel'),
            value:    a.textColor,
            options:  TEXT_OPTS,
            onChange: set('textColor'),
          })
        ),

        /* Spacing */
        el(c.PanelBody, { title: __('Spacing', 'matthummel'), initialOpen: false },
          el(c.SelectControl, {
            label:    __('Padding top', 'matthummel'),
            value:    a.paddingTop,
            options:  PADDING_OPTS,
            onChange: set('paddingTop'),
          }),
          el(c.SelectControl, {
            label:    __('Padding bottom', 'matthummel'),
            value:    a.paddingBottom,
            options:  PADDING_OPTS,
            onChange: set('paddingBottom'),
          })
        ),

        /* Layout */
        el(c.PanelBody, { title: __('Layout', 'matthummel'), initialOpen: false },
          el(c.SelectControl, {
            label:    __('Container width', 'matthummel'),
            value:    a.containerWidth,
            options:  WIDTH_OPTS,
            onChange: set('containerWidth'),
          }),
          el(c.ToggleControl, {
            label:    __('Add horizontal rule above section', 'matthummel'),
            checked:  !!a.hasRule,
            onChange: set('hasRule'),
          })
        )
      );

      return el(Fragment, {},
        controls,
        el('div', blockProps,
          el('div', { className: 'mh-section-inner' },
            el(InnerBlocks)
          )
        )
      );
    },

    save: function () {
      /* PHP render_callback handles the outer wrapper. InnerBlocks.Content
         saves the inner HTML so Gutenberg can reconstruct the tree. */
      return el(InnerBlocks.Content);
    },
  });
})(window.wp);
