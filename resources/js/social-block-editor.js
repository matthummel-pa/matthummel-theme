/* Social Icons block editor (no build step — uses global wp.* packages). */
(function (wp) {
  if (!wp || !wp.blocks) return;

  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var __ = wp.i18n.__;
  var be = wp.blockEditor || wp.editor;
  var InspectorControls = be.InspectorControls;
  var useBlockProps = be.useBlockProps;
  var c = wp.components;
  var ServerSideRender = wp.serverSideRender || wp.components.ServerSideRender;

  var cfg = window.mhSocialBlock || { attrs: {}, defaults: {}, networks: {} };

  // Build attributes from PHP-provided schema.
  var attributes = {};
  Object.keys(cfg.attrs).forEach(function (k) {
    attributes[k] = { type: cfg.attrs[k].type, default: cfg.attrs[k].default };
  });

  function PanelColor(label, value, onChange) {
    return el(c.BaseControl, { label: label },
      el(c.ColorPalette, { value: value, onChange: onChange, clearable: true })
    );
  }

  wp.blocks.registerBlockType('mh/social-links', {
    apiVersion: 2,
    title: __('Social Icons', 'matthummel'),
    description: __('Inline SVG social icons (Blade Icons). Pulls from your site social links by default.', 'matthummel'),
    icon: 'share',
    category: 'widgets',
    keywords: ['social', 'icons', 'links', 'share'],
    supports: { align: ['wide', 'full'], html: false },
    attributes: attributes,

    edit: function (props) {
      var a = props.attributes;
      var set = function (k) { return function (v) { var o = {}; o[k] = v; props.setAttributes(o); }; };
      var blockProps = useBlockProps ? useBlockProps() : {};

      var controls = el(InspectorControls, {},
        el(c.PanelBody, { title: __('Content', 'matthummel'), initialOpen: true },
          el(c.SelectControl, {
            label: __('Source', 'matthummel'),
            value: a.source,
            options: [
              { label: __('Site social links', 'matthummel'), value: 'site' },
              { label: __('Custom links', 'matthummel'), value: 'custom' }
            ],
            onChange: set('source')
          }),
          a.source === 'custom' ? el(c.TextareaControl, {
            label: __('Custom links', 'matthummel'),
            help: __('One per line, as: network|https://url  (e.g. github|https://github.com/you)', 'matthummel'),
            value: a.customLinks,
            onChange: set('customLinks')
          }) : el('p', { style: { fontSize: '12px', opacity: 0.7 } },
            __('Showing the URLs from Customizer → Theme Options → Menu & Popout.', 'matthummel'))
        ),

        el(c.PanelBody, { title: __('Layout', 'matthummel'), initialOpen: false },
          el(c.RangeControl, { label: __('Icon size (px)', 'matthummel'), value: a.size, min: 12, max: 64, onChange: set('size') }),
          el(c.RangeControl, { label: __('Gap (px)', 'matthummel'), value: a.gap, min: 0, max: 48, onChange: set('gap') }),
          el(c.SelectControl, {
            label: __('Shape', 'matthummel'), value: a.shape,
            options: [
              { label: __('None (plain)', 'matthummel'), value: 'none' },
              { label: __('Circle', 'matthummel'), value: 'circle' },
              { label: __('Rounded', 'matthummel'), value: 'rounded' },
              { label: __('Square', 'matthummel'), value: 'square' }
            ], onChange: set('shape')
          }),
          el(c.SelectControl, {
            label: __('Alignment', 'matthummel'), value: a.align,
            options: [
              { label: __('Left', 'matthummel'), value: 'left' },
              { label: __('Center', 'matthummel'), value: 'center' },
              { label: __('Right', 'matthummel'), value: 'right' }
            ], onChange: set('align')
          }),
          el(c.ToggleControl, { label: __('Open links in new tab', 'matthummel'), checked: !!a.newTab, onChange: set('newTab') })
        ),

        el(c.PanelBody, { title: __('Style & color', 'matthummel'), initialOpen: false },
          el(c.SelectControl, {
            label: __('Icon style', 'matthummel'), value: a.iconStyle,
            options: [
              { label: __('Mono (theme color)', 'matthummel'), value: 'mono' },
              { label: __('Brand colors', 'matthummel'), value: 'brand' }
            ], onChange: set('iconStyle'),
            help: a.iconStyle === 'brand' ? __('Each icon uses its official brand color.', 'matthummel') : ''
          }),
          a.iconStyle === 'mono' ? PanelColor(__('Icon color', 'matthummel'), a.color, set('color')) : null,
          a.shape !== 'none' ? PanelColor(__('Chip background', 'matthummel'), a.bg, set('bg')) : null,
          a.iconStyle === 'mono' ? PanelColor(__('Hover icon color', 'matthummel'), a.hoverColor, set('hoverColor')) : null,
          (a.iconStyle === 'mono' && a.shape !== 'none') ? PanelColor(__('Hover chip background', 'matthummel'), a.hoverBg, set('hoverBg')) : null
        )
      );

      var preview = el(ServerSideRender, {
        block: 'mh/social-links',
        attributes: a
      });

      return el(Fragment, {}, controls, el('div', blockProps, preview));
    },

    save: function () { return null; } // dynamic — rendered in PHP
  });
})(window.wp);
