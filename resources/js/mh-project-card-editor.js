(function (wp) {
  if (!wp || !wp.blocks) return;
  var el = wp.element.createElement, Fragment = wp.element.Fragment, __ = wp.i18n.__;
  var be = wp.blockEditor || wp.editor, IC = be.InspectorControls, ubp = be.useBlockProps;
  var c = wp.components, SSR = wp.serverSideRender || wp.components.ServerSideRender;

  wp.blocks.registerBlockType('mh/project-card', {
    apiVersion: 2,
    title: __('Project Card', 'matthummel'),
    description: __('A bespoke project card with image, tags, and links. Use inside columns for a manual project grid.', 'matthummel'),
    icon: 'portfolio',
    category: 'matthummel',
    keywords: ['project', 'card', 'portfolio', 'work'],
    attributes: {
      heading:   { type: 'string', default: 'Project Title' },
      excerpt:   { type: 'string', default: 'Short description of this project.' },
      link:      { type: 'string', default: '' },
      imageUrl:  { type: 'string', default: '' },
      imageAlt:  { type: 'string', default: '' },
      tags:      { type: 'string', default: 'React, Tailwind, Supabase' },
      liveUrl:   { type: 'string', default: '' },
      githubUrl: { type: 'string', default: '' }
    },
    edit: function (props) {
      var a = props.attributes;
      var set = function (k) { return function (v) { var o = {}; o[k] = v; props.setAttributes(o); }; };

      var controls = el(IC, {},
        el(c.PanelBody, { title: __('Project details', 'matthummel'), initialOpen: true },
          el(c.TextControl, { label: __('Title', 'matthummel'), value: a.heading, onChange: set('heading') }),
          el(c.TextareaControl, { label: __('Excerpt', 'matthummel'), value: a.excerpt, rows: 3, onChange: set('excerpt') }),
          el(c.TextControl, { label: __('Tags (comma-separated)', 'matthummel'), value: a.tags, placeholder: 'React, Tailwind, PHP', onChange: set('tags') })
        ),
        el(c.PanelBody, { title: __('Image', 'matthummel'), initialOpen: false },
          el(c.TextControl, { label: __('Image URL', 'matthummel'), value: a.imageUrl, type: 'url', onChange: set('imageUrl') }),
          el(c.TextControl, { label: __('Alt text', 'matthummel'), value: a.imageAlt, onChange: set('imageAlt') })
        ),
        el(c.PanelBody, { title: __('Links', 'matthummel'), initialOpen: false },
          el(c.TextControl, { label: __('Primary link (overrides both)', 'matthummel'), value: a.link, type: 'url', onChange: set('link') }),
          el(c.TextControl, { label: __('Live site URL', 'matthummel'), value: a.liveUrl, type: 'url', onChange: set('liveUrl') }),
          el(c.TextControl, { label: __('GitHub URL', 'matthummel'), value: a.githubUrl, type: 'url', onChange: set('githubUrl') })
        )
      );
      return el(Fragment, {}, controls, el('div', ubp ? ubp() : {}, el(SSR, { block: 'mh/project-card', attributes: a })));
    },
    save: function () { return null; }
  });
})(window.wp);
