import { registerBlockType } from '@wordpress/blocks'
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor'
import { __ } from '@wordpress/i18n'

const ALLOWED = ['matthummel/tool-card']
const TEMPLATE = [
  ['matthummel/tool-card', { mark: 'Cu', name: 'Cursor', role: 'Primary editor' }],
  ['matthummel/tool-card', { mark: 'GPT', name: 'ChatGPT', role: 'Editor outside the repo' }],
]

function Edit() {
  const blockProps = useBlockProps({
    className: 'mh-ai-figure mh-tool-grid-wrap',
  })

  return (
    <div {...blockProps} role="region" aria-label={__('Tool comparison', 'sage')}>
      <div className="mh-tool-grid">
        <InnerBlocks
          allowedBlocks={ALLOWED}
          template={TEMPLATE}
          templateLock={false}
          orientation="horizontal"
        />
      </div>
    </div>
  )
}

function Save() {
  const blockProps = useBlockProps.save({
    className: 'mh-ai-figure mh-tool-grid-wrap',
  })

  return (
    <div {...blockProps} role="region" aria-label="Comparison of tools I use to ship WordPress work">
      <div className="mh-tool-grid">
        <InnerBlocks.Content />
      </div>
    </div>
  )
}

registerBlockType('matthummel/tool-grid', {
  apiVersion: 3,
  title: __('Tool comparison grid', 'sage'),
  description: __('Two-column grid of AI / workflow tool cards.', 'sage'),
  category: 'matthummel',
  icon: 'grid-view',
  supports: {
    html: false,
    align: false,
    className: true,
  },
  edit: Edit,
  save: Save,
})
