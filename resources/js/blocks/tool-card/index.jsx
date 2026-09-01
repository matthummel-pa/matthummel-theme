import { registerBlockType } from '@wordpress/blocks'
import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor'
import { PanelBody, TextControl, SelectControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'

const MARK_OPTIONS = [
  { label: 'Cu — Cursor', value: 'Cu' },
  { label: 'GPT — ChatGPT', value: 'GPT' },
  { label: 'Cl — Claude', value: 'Cl' },
  { label: 'Ge — Gemini', value: 'Ge' },
  { label: 'VS — VS Code', value: 'VS' },
  { label: 'n8 — n8n', value: 'n8' },
  { label: 'MC — MCP', value: 'MC' },
  { label: 'GH — GitHub Actions', value: 'GH' },
  { label: 'Vi — Vite', value: 'Vi' },
]

const MARK_SLUGS = {
  Cu: 'cu',
  GPT: 'gpt',
  Cl: 'cl',
  Ge: 'ge',
  VS: 'vs',
  n8: 'n8',
  MC: 'mc',
  GH: 'gh',
  Vi: 'vi',
}

function markSlug(mark) {
  const key = String(mark || '').trim()
  if (MARK_SLUGS[key]) return MARK_SLUGS[key]
  return key.toLowerCase().replace(/[^a-z0-9]+/g, '').slice(0, 4) || 'cu'
}

function Edit({ attributes, setAttributes }) {
  const { mark, name, role, bestFor, weakAt, humanRequired } = attributes
  const slug = markSlug(mark)
  const blockProps = useBlockProps({
    className: `mh-tool-card mh-tool-card--${slug}`,
  })

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Tool card', 'sage')} initialOpen={true}>
          <SelectControl
            label={__('Mark chip', 'sage')}
            value={MARK_SLUGS[mark] ? mark : 'Cu'}
            options={MARK_OPTIONS}
            onChange={(value) => setAttributes({ mark: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__('Custom mark', 'sage')}
            value={mark}
            onChange={(value) => setAttributes({ mark: value })}
            help={__('Overrides the chip letters if you need a one-off.', 'sage')}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>
      </InspectorControls>
      <div {...blockProps}>
        <RichText
          tagName="p"
          className="mh-tool-card__name"
          placeholder={__('Tool name', 'sage')}
          value={name}
          onChange={(value) => setAttributes({ name: value })}
          allowedFormats={[]}
        />
        <RichText
          tagName="p"
          className="mh-tool-card__role"
          placeholder={__('Role label', 'sage')}
          value={role}
          onChange={(value) => setAttributes({ role: value })}
          allowedFormats={[]}
        />
        <p className="mh-tool-card__dt mh-tool-card__dt--best">{__('Best for', 'sage')}</p>
        <RichText
          tagName="p"
          className="mh-tool-card__dd"
          placeholder={__('What this tool is best for…', 'sage')}
          value={bestFor}
          onChange={(value) => setAttributes({ bestFor: value })}
          allowedFormats={['core/code', 'core/link', 'core/bold', 'core/italic']}
        />
        <p className="mh-tool-card__dt mh-tool-card__dt--weak">{__('Weak at', 'sage')}</p>
        <RichText
          tagName="p"
          className="mh-tool-card__dd"
          placeholder={__('Where it falls short…', 'sage')}
          value={weakAt}
          onChange={(value) => setAttributes({ weakAt: value })}
          allowedFormats={['core/code', 'core/link', 'core/bold', 'core/italic']}
        />
        <p className="mh-tool-card__dt mh-tool-card__dt--human">{__('Human still required', 'sage')}</p>
        <RichText
          tagName="p"
          className="mh-tool-card__dd"
          placeholder={__('What you still own…', 'sage')}
          value={humanRequired}
          onChange={(value) => setAttributes({ humanRequired: value })}
          allowedFormats={['core/code', 'core/link', 'core/bold', 'core/italic']}
        />
      </div>
    </>
  )
}

function Save({ attributes }) {
  const { mark, name, role, bestFor, weakAt, humanRequired } = attributes
  const slug = markSlug(mark)
  const blockProps = useBlockProps.save({
    className: `mh-tool-card mh-tool-card--${slug}`,
  })

  return (
    <div {...blockProps}>
      <RichText.Content tagName="p" className="mh-tool-card__name" value={name} />
      <RichText.Content tagName="p" className="mh-tool-card__role" value={role} />
      <p className="mh-tool-card__dt mh-tool-card__dt--best">Best for</p>
      <RichText.Content tagName="p" className="mh-tool-card__dd" value={bestFor} />
      <p className="mh-tool-card__dt mh-tool-card__dt--weak">Weak at</p>
      <RichText.Content tagName="p" className="mh-tool-card__dd" value={weakAt} />
      <p className="mh-tool-card__dt mh-tool-card__dt--human">Human still required</p>
      <RichText.Content tagName="p" className="mh-tool-card__dd" value={humanRequired} />
    </div>
  )
}

registerBlockType('matthummel/tool-card', {
  apiVersion: 3,
  title: __('Tool card', 'sage'),
  description: __('One tool: mark, role, best for, weak at, human still required.', 'sage'),
  category: 'matthummel',
  icon: 'index-card',
  parent: ['matthummel/tool-grid'],
  supports: {
    html: false,
    reusable: false,
    className: true,
  },
  attributes: {
    mark: { type: 'string', default: 'Cu' },
    name: { type: 'string', default: '' },
    role: { type: 'string', default: '' },
    bestFor: { type: 'string', default: '' },
    weakAt: { type: 'string', default: '' },
    humanRequired: { type: 'string', default: '' },
  },
  edit: Edit,
  save: Save,
})
