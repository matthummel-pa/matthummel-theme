import { registerBlockType, createBlock } from '@wordpress/blocks'
import {
  useBlockProps,
  InnerBlocks,
  InspectorControls,
} from '@wordpress/block-editor'
import { PanelBody, TextControl, Button } from '@wordpress/components'
import { useDispatch, useSelect } from '@wordpress/data'
import { __ } from '@wordpress/i18n'

const ALLOWED = ['matthummel/tool-card']
const TEMPLATE = [
  [
    'matthummel/tool-card',
    {
      icon: 'cursor-ai',
      mark: 'Cu',
      name: 'Cursor',
      role: 'Primary editor',
      nameHeadingLevel: 3,
    },
  ],
  [
    'matthummel/tool-card',
    {
      icon: 'chatgpt',
      mark: 'GPT',
      name: 'ChatGPT',
      role: 'Editor outside the repo',
      nameHeadingLevel: 3,
    },
  ],
]

function Edit({ clientId, attributes, setAttributes }) {
  const { ariaLabel } = attributes
  const { insertBlock } = useDispatch('core/block-editor')
  const blockCount = useSelect(
    (select) => select('core/block-editor').getBlockCount(clientId),
    [clientId]
  )

  const blockProps = useBlockProps({
    className: 'mh-ai-figure mh-tool-grid-wrap',
  })

  function addToolCard() {
    insertBlock(
      createBlock('matthummel/tool-card', {
        mark: 'Cu',
        icon: '',
        name: '',
        role: '',
        nameHeadingLevel: 3,
      }),
      blockCount,
      clientId,
      false
    )
  }

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Tool Blocks', 'sage')} initialOpen={true}>
          <TextControl
            label={__('Region aria-label', 'sage')}
            value={ariaLabel}
            onChange={(value) => setAttributes({ ariaLabel: value })}
            help={__('Spoken by screen readers for the whole comparison grid.', 'sage')}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <Button variant="primary" onClick={addToolCard} style={{ marginTop: '12px' }}>
            {__('Add tool card', 'sage')}
          </Button>
        </PanelBody>
      </InspectorControls>
      <section {...blockProps} aria-label={ariaLabel || __('Tool comparison', 'sage')}>
        <ul className="mh-tool-grid">
          <InnerBlocks
            allowedBlocks={ALLOWED}
            template={TEMPLATE}
            templateLock={false}
            orientation="horizontal"
            renderAppender={() => <InnerBlocks.ButtonBlockAppender />}
          />
        </ul>
        <div className="mh-tool-blocks-add">
          <Button variant="secondary" onClick={addToolCard}>
            {__('Add tool card', 'sage')}
          </Button>
        </div>
      </section>
    </>
  )
}

function Save({ attributes }) {
  const { ariaLabel } = attributes
  const blockProps = useBlockProps.save({
    className: 'mh-ai-figure mh-tool-grid-wrap',
  })

  return (
    <section
      {...blockProps}
      aria-label={ariaLabel || 'Comparison of tools I use to ship WordPress work'}
    >
      <ul className="mh-tool-grid">
        <InnerBlocks.Content />
      </ul>
    </section>
  )
}

registerBlockType('matthummel/tool-grid', {
  apiVersion: 3,
  title: __('Tool Blocks', 'sage'),
  description: __('Comparison grid of tool cards. Add as many cards as you need.', 'sage'),
  category: 'matthummel',
  icon: 'grid-view',
  supports: {
    html: false,
    align: false,
    className: true,
  },
  attributes: {
    ariaLabel: {
      type: 'string',
      default: 'Comparison of tools I use to ship WordPress work',
    },
  },
  edit: Edit,
  save: Save,
})
