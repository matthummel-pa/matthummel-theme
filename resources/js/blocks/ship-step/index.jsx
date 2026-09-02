import { registerBlockType } from '@wordpress/blocks'
import { useBlockProps, RichText } from '@wordpress/block-editor'
import { __ } from '@wordpress/i18n'

function Edit({ attributes, setAttributes }) {
  const { title, body } = attributes
  const blockProps = useBlockProps({
    className: 'mh-ship-step',
  })

  return (
    <li {...blockProps}>
      <RichText
        tagName="h3"
        className="mh-ship-pipe__name"
        placeholder={__('Step title', 'sage')}
        value={title}
        onChange={(value) => setAttributes({ title: value })}
        allowedFormats={[]}
      />
      <RichText
        tagName="p"
        className="mh-ship-pipe__body"
        placeholder={__('What happens in this step…', 'sage')}
        value={body}
        onChange={(value) => setAttributes({ body: value })}
        allowedFormats={['core/code', 'core/link', 'core/bold', 'core/italic']}
      />
    </li>
  )
}

function Save({ attributes }) {
  const { title, body } = attributes
  const blockProps = useBlockProps.save({
    className: 'mh-ship-step',
  })

  return (
    <li {...blockProps}>
      <RichText.Content tagName="h3" className="mh-ship-pipe__name" value={title} />
      <RichText.Content tagName="p" className="mh-ship-pipe__body" value={body} />
    </li>
  )
}

registerBlockType('matthummel/ship-step', {
  apiVersion: 3,
  title: __('Ship step', 'sage'),
  description: __('One numbered step in the ship pipeline.', 'sage'),
  category: 'matthummel',
  icon: 'marker',
  parent: ['matthummel/ship-pipe'],
  supports: {
    html: false,
    reusable: false,
    className: true,
  },
  attributes: {
    title: { type: 'string', default: '' },
    body: { type: 'string', default: '' },
  },
  edit: Edit,
  save: Save,
})
