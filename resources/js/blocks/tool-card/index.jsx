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

const MARK_TO_ICON = {
  Cu: 'cursor-ai',
  GPT: 'chatgpt',
  Cl: 'claude',
  Ge: 'gemini',
  VS: 'vscode',
  n8: 'n8n',
  MC: 'code',
  GH: 'github',
  Vi: 'vite',
}

function markSlug(mark) {
  const key = String(mark || '').trim()
  if (MARK_SLUGS[key]) return MARK_SLUGS[key]
  return key.toLowerCase().replace(/[^a-z0-9]+/g, '').slice(0, 4) || 'cu'
}

function iconChoices() {
  const fromPhp = window.mhToolBlocks?.icons
  if (Array.isArray(fromPhp) && fromPhp.length) return fromPhp
  return [
    { value: '', label: __('Letter mark only', 'sage') },
    { value: 'cursor-ai', label: 'Cursor' },
    { value: 'chatgpt', label: 'ChatGPT' },
    { value: 'claude', label: 'Claude' },
    { value: 'gemini', label: 'Gemini' },
    { value: 'vscode', label: 'VS Code' },
    { value: 'n8n', label: 'n8n' },
    { value: 'code', label: 'MCP / code' },
    { value: 'github', label: 'GitHub' },
    { value: 'vite', label: 'Vite' },
  ]
}

function iconSvg(icon) {
  if (!icon) return ''
  const hit = iconChoices().find((row) => row.value === icon)
  return hit?.svg || ''
}

function Edit({ attributes, setAttributes }) {
  const {
    icon,
    mark,
    name,
    role,
    bestForLabel,
    weakAtLabel,
    humanRequiredLabel,
    bestFor,
    weakAt,
    humanRequired,
  } = attributes
  const slug = markSlug(mark)
  const hasIcon = Boolean(icon)
  const blockProps = useBlockProps({
    className: [
      'mh-tool-card',
      `mh-tool-card--${slug}`,
      hasIcon ? 'mh-tool-card--has-icon' : '',
    ]
      .filter(Boolean)
      .join(' '),
    'data-mark': mark || '',
  })
  const svg = iconSvg(icon)

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Icon & mark', 'sage')} initialOpen={true}>
          <SelectControl
            label={__('Icon', 'sage')}
            value={icon || ''}
            options={iconChoices().map(({ value, label }) => ({ value, label }))}
            onChange={(value) => setAttributes({ icon: value })}
            help={__('Theme SVG icons. Leave empty to show the letter mark only.', 'sage')}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <SelectControl
            label={__('Mark preset', 'sage')}
            value={MARK_SLUGS[mark] ? mark : 'Cu'}
            options={MARK_OPTIONS}
            onChange={(value) => {
              const next = { mark: value }
              if (!icon && MARK_TO_ICON[value]) next.icon = MARK_TO_ICON[value]
              setAttributes(next)
            }}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__('Custom mark letters', 'sage')}
            value={mark}
            onChange={(value) => setAttributes({ mark: value })}
            help={__('Shown when no icon is selected, and used for the card color slug.', 'sage')}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>
        <PanelBody title={__('Section labels', 'sage')} initialOpen={false}>
          <TextControl
            label={__('Best-for label', 'sage')}
            value={bestForLabel}
            onChange={(value) => setAttributes({ bestForLabel: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__('Weak-at label', 'sage')}
            value={weakAtLabel}
            onChange={(value) => setAttributes({ weakAtLabel: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__('Human-required label', 'sage')}
            value={humanRequiredLabel}
            onChange={(value) => setAttributes({ humanRequiredLabel: value })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </PanelBody>
      </InspectorControls>
      <div {...blockProps}>
        <span className="mh-tool-card__mark" aria-hidden="true">
          {svg ? (
            <span
              className="mh-tool-card__icon"
              dangerouslySetInnerHTML={{ __html: svg }}
            />
          ) : (
            mark || '·'
          )}
        </span>
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
        <RichText
          tagName="p"
          className="mh-tool-card__dt mh-tool-card__dt--best"
          placeholder={__('Best for', 'sage')}
          value={bestForLabel}
          onChange={(value) => setAttributes({ bestForLabel: value })}
          allowedFormats={[]}
        />
        <RichText
          tagName="p"
          className="mh-tool-card__dd"
          placeholder={__('What this tool is best for…', 'sage')}
          value={bestFor}
          onChange={(value) => setAttributes({ bestFor: value })}
          allowedFormats={['core/code', 'core/link', 'core/bold', 'core/italic']}
        />
        <RichText
          tagName="p"
          className="mh-tool-card__dt mh-tool-card__dt--weak"
          placeholder={__('Weak at', 'sage')}
          value={weakAtLabel}
          onChange={(value) => setAttributes({ weakAtLabel: value })}
          allowedFormats={[]}
        />
        <RichText
          tagName="p"
          className="mh-tool-card__dd"
          placeholder={__('Where it falls short…', 'sage')}
          value={weakAt}
          onChange={(value) => setAttributes({ weakAt: value })}
          allowedFormats={['core/code', 'core/link', 'core/bold', 'core/italic']}
        />
        <RichText
          tagName="p"
          className="mh-tool-card__dt mh-tool-card__dt--human"
          placeholder={__('Human still required', 'sage')}
          value={humanRequiredLabel}
          onChange={(value) => setAttributes({ humanRequiredLabel: value })}
          allowedFormats={[]}
        />
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

registerBlockType('matthummel/tool-card', {
  apiVersion: 3,
  title: __('Tool card', 'sage'),
  description: __('One tool: icon or mark, name, role, and comparison fields.', 'sage'),
  category: 'matthummel',
  icon: 'index-card',
  parent: ['matthummel/tool-grid'],
  supports: {
    html: false,
    reusable: false,
    className: true,
  },
  attributes: {
    icon: { type: 'string', default: '' },
    mark: { type: 'string', default: 'Cu' },
    name: { type: 'string', default: '' },
    role: { type: 'string', default: '' },
    bestForLabel: { type: 'string', default: 'Best for' },
    weakAtLabel: { type: 'string', default: 'Weak at' },
    humanRequiredLabel: { type: 'string', default: 'Human still required' },
    bestFor: { type: 'string', default: '' },
    weakAt: { type: 'string', default: '' },
    humanRequired: { type: 'string', default: '' },
  },
  edit: Edit,
  save: () => null,
})
