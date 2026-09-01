import { registerBlockType } from '@wordpress/blocks'
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor'
import { __ } from '@wordpress/i18n'

const ALLOWED = ['matthummel/ship-step']
const TEMPLATE = [
  ['matthummel/ship-step', { title: 'Brief', body: '' }],
  ['matthummel/ship-step', { title: 'Review', body: '' }],
]

function Edit() {
  const blockProps = useBlockProps({
    className: 'mh-ship-pipe',
  })

  return (
    <div {...blockProps}>
      <InnerBlocks
        allowedBlocks={ALLOWED}
        template={TEMPLATE}
        templateLock={false}
      />
    </div>
  )
}

function Save() {
  const blockProps = useBlockProps.save({
    className: 'mh-ship-pipe',
  })

  return (
    <div {...blockProps}>
      <InnerBlocks.Content />
    </div>
  )
}

registerBlockType('matthummel/ship-pipe', {
  apiVersion: 3,
  title: __('Ship pipeline', 'sage'),
  description: __('Numbered handoff steps from brief to ship.', 'sage'),
  category: 'matthummel',
  icon: 'editor-ol',
  supports: {
    html: false,
    className: true,
  },
  edit: Edit,
  save: Save,
})
