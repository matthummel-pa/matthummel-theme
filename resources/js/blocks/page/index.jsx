/**
 * Page blocks: hero, section, card grid, card, questions, CTA band, page nav.
 *
 * Metadata lives in resources/blocks/<name>/block.json and the front end is rendered
 * in PHP (resources/views/blocks). Each block saves only its inner blocks, so the
 * stored content stays valid when the markup around it changes.
 */
import { registerBlockType } from '@wordpress/blocks'
import {
  useBlockProps,
  useInnerBlocksProps,
  InnerBlocks,
  InspectorControls,
  RichText,
} from '@wordpress/block-editor'
import {
  PanelBody,
  TextControl,
  ToggleControl,
  SelectControl,
  RangeControl,
  Notice,
  Disabled,
} from '@wordpress/components'
import { useSelect } from '@wordpress/data'
import ServerSideRender from '@wordpress/server-side-render'
import { __ } from '@wordpress/i18n'

import heroMeta from '../../../blocks/hero/block.json'
import sectionMeta from '../../../blocks/section/block.json'
import cardsMeta from '../../../blocks/cards/block.json'
import cardMeta from '../../../blocks/card/block.json'
import faqMeta from '../../../blocks/faq/block.json'
import ctaMeta from '../../../blocks/cta-band/block.json'
import navMeta from '../../../blocks/page-nav/block.json'

const ICONS = window.mhPageBlocks?.icons || []
const saveInner = () => <InnerBlocks.Content />
const controlProps = { __next40pxDefaultSize: true, __nextHasNoMarginBottom: true }

/** Kicker line above a heading, edited in place. */
function Eyebrow({ value, onChange, onDark = false }) {
  return (
    <RichText
      tagName="p"
      className={onDark ? 'eyebrow eyebrow--on-dark' : 'eyebrow'}
      value={value}
      onChange={onChange}
      allowedFormats={[]}
      placeholder={__('Kicker (optional)', 'sage')}
    />
  )
}

/* ── Page hero ─────────────────────────────────────────────────────────── */
const HERO_TEMPLATE = [
  ['core/heading', { level: 1, placeholder: __('Page heading', 'sage') }],
  ['core/paragraph', { className: 'is-style-lead', placeholder: __('One or two sentences about this page.', 'sage') }],
  ['core/buttons', {}, [['core/button', { text: __('Say hello', 'sage'), url: '/contact/' }]]],
]

function HeroEdit({ attributes, setAttributes }) {
  const { eyebrow, background, showPortrait, portraitCaption, layout } = attributes
  const blockProps = useBlockProps({ className: 'page-header page-header--photo mh-block-hero' })
  const inner = useInnerBlocksProps(
    { className: 'page-header__panel-inner' },
    { template: HERO_TEMPLATE, allowedBlocks: ['core/heading', 'core/paragraph', 'core/buttons'] }
  )

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Hero', 'sage')}>
          <SelectControl
            {...controlProps}
            label={__('Layout', 'sage')}
            value={layout}
            options={[
              { value: 'page', label: __('Page hero', 'sage') },
              { value: 'home', label: __('Home hero (full height)', 'sage') },
            ]}
            onChange={(value) => setAttributes({ layout: value })}
            help={__('The Home hero is sized in Customize → Home hero. The editor shows the page hero for both.', 'sage')}
          />
          <SelectControl
            {...controlProps}
            label={__('Background', 'sage')}
            value={background}
            options={[
              { value: 'photo', label: __('Featured image', 'sage') },
              { value: 'scene', label: __('Project scene', 'sage') },
            ]}
            onChange={(value) => setAttributes({ background: value })}
            help={__('The hero uses the page’s featured image. Set it in the Page sidebar.', 'sage')}
          />
          <ToggleControl
            __nextHasNoMarginBottom
            label={__('Show profile photo', 'sage')}
            help={__('On the About page the photo shows under the Story drawing instead of in the hero.', 'sage')}
            checked={showPortrait}
            onChange={(value) => setAttributes({ showPortrait: value })}
          />
          {showPortrait && (
            <TextControl
              {...controlProps}
              label={__('Photo caption', 'sage')}
              value={portraitCaption}
              onChange={(value) => setAttributes({ portraitCaption: value })}
            />
          )}
        </PanelBody>
      </InspectorControls>
      <header {...blockProps}>
        <div className="container wide page-header-inner">
          <div className="page-header__panel">
            <Eyebrow value={eyebrow} onChange={(value) => setAttributes({ eyebrow: value })} />
            <div {...inner} />
          </div>
        </div>
      </header>
    </>
  )
}

registerBlockType(heroMeta, { edit: HeroEdit, save: saveInner })

/* ── Section ───────────────────────────────────────────────────────────── */
const SECTION_TEMPLATE = [
  ['core/heading', { level: 2, placeholder: __('Section heading', 'sage') }],
  ['core/paragraph', { placeholder: __('Write this section, or add blocks.', 'sage') }],
]

function slugify(text) {
  return String(text || '')
    .toLowerCase()
    .replace(/<[^>]+>/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 40)
}

function SectionEdit({ attributes, setAttributes }) {
  const { eyebrow, tone, navLabel, anchor } = attributes
  const blockProps = useBlockProps({
    className: `pf-section mh-block-section${tone === 'alt' ? ' pf-section--alt' : ''}`,
  })
  const inner = useInnerBlocksProps({ className: 'container wide' }, { template: SECTION_TEMPLATE })

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Section', 'sage')}>
          <SelectControl
            {...controlProps}
            label={__('Background', 'sage')}
            value={tone}
            options={[
              { value: 'default', label: __('Page', 'sage') },
              { value: 'alt', label: __('Tinted', 'sage') },
            ]}
            onChange={(value) => setAttributes({ tone: value })}
          />
          <TextControl
            {...controlProps}
            label={__('Link label in “On this page”', 'sage')}
            value={navLabel}
            onChange={(value) =>
              setAttributes({ navLabel: value, anchor: anchor || slugify(value) })
            }
            help={__('Leave empty to keep this section out of the jump links.', 'sage')}
          />
          <TextControl
            {...controlProps}
            label={__('Anchor', 'sage')}
            value={anchor}
            onChange={(value) => setAttributes({ anchor: slugify(value) })}
            help={__('The #name in links to this section. Lowercase letters, numbers, and dashes.', 'sage')}
          />
        </PanelBody>
      </InspectorControls>
      <section {...blockProps}>
        <div className="container wide">
          <Eyebrow value={eyebrow} onChange={(value) => setAttributes({ eyebrow: value })} />
        </div>
        <div {...inner} />
      </section>
    </>
  )
}

registerBlockType(sectionMeta, { edit: SectionEdit, save: saveInner })

/* ── Card grid + card ──────────────────────────────────────────────────── */
const CARDS_TEMPLATE = [['matthummel/card'], ['matthummel/card'], ['matthummel/card']]

function CardsEdit({ attributes, setAttributes }) {
  const { columns, numbered } = attributes
  const blockProps = useBlockProps({
    className: `mh-cards mh-cards--${columns}${numbered ? ' mh-cards--numbered' : ''}`,
  })
  const inner = useInnerBlocksProps(blockProps, {
    template: CARDS_TEMPLATE,
    allowedBlocks: ['matthummel/card'],
    orientation: 'horizontal',
  })

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Card grid', 'sage')}>
          <RangeControl
            {...controlProps}
            label={__('Columns on wide screens', 'sage')}
            value={columns}
            min={2}
            max={4}
            onChange={(value) => setAttributes({ columns: value || 3 })}
          />
          <ToggleControl
            __nextHasNoMarginBottom
            label={__('Number the cards', 'sage')}
            checked={numbered}
            onChange={(value) => setAttributes({ numbered: value })}
            help={__('Shows 01, 02, 03 in the corner of each card.', 'sage')}
          />
        </PanelBody>
      </InspectorControls>
      <div {...inner} />
    </>
  )
}

registerBlockType(cardsMeta, { edit: CardsEdit, save: saveInner })

const CARD_TEMPLATE = [
  ['core/heading', { level: 3, placeholder: __('Card title', 'sage') }],
  ['core/paragraph', { placeholder: __('A sentence or two.', 'sage') }],
]

function CardEdit({ attributes, setAttributes }) {
  const { icon, number } = attributes
  const current = ICONS.find((item) => item.value === icon)
  const blockProps = useBlockProps({ className: 'about-svc-card mh-card' })
  const inner = useInnerBlocksProps(
    {},
    { template: CARD_TEMPLATE, allowedBlocks: ['core/heading', 'core/paragraph', 'core/list', 'core/buttons'] }
  )

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Card', 'sage')}>
          <SelectControl
            {...controlProps}
            label={__('Icon', 'sage')}
            value={icon}
            options={[{ value: '', label: __('No icon', 'sage') }, ...ICONS.map(({ value, label }) => ({ value, label }))]}
            onChange={(value) => setAttributes({ icon: value })}
          />
          <TextControl
            {...controlProps}
            label={__('Corner label', 'sage')}
            value={number}
            onChange={(value) => setAttributes({ number: value })}
            help={__('Optional, for example 01.', 'sage')}
          />
        </PanelBody>
      </InspectorControls>
      <article {...blockProps}>
        {number && (
          <span className="about-svc-card__n" aria-hidden="true">
            {number}
          </span>
        )}
        {current && (
          <div
            className="about-svc-card__icon"
            aria-hidden="true"
            // The SVG comes from the theme's own icon set (App\mh_svg_icon), printed by PHP.
            dangerouslySetInnerHTML={{ __html: current.svg }}
          />
        )}
        <div {...inner} />
      </article>
    </>
  )
}

registerBlockType(cardMeta, { edit: CardEdit, save: saveInner })

/* ── Questions ─────────────────────────────────────────────────────────── */
const FAQ_TEMPLATE = [
  ['core/details', { summary: __('Question', 'sage') }, [['core/paragraph', { placeholder: __('Answer in one to three sentences.', 'sage') }]]],
]

function FaqEdit() {
  // FAQPage data now comes from every Questions block on the page (MH SEO), so there is no toggle.
  const blockProps = useBlockProps({ className: 'faq-list mh-block-faq' })
  const inner = useInnerBlocksProps(blockProps, { template: FAQ_TEMPLATE, allowedBlocks: ['core/details'] })

  return <div {...inner} />
}

registerBlockType(faqMeta, { edit: FaqEdit, save: saveInner })

/* ── CTA band ──────────────────────────────────────────────────────────── */
const CTA_TEMPLATE = [
  ['core/heading', { level: 2, placeholder: __('Closing question or offer', 'sage') }],
  ['core/paragraph', { placeholder: __('One sentence on what to send.', 'sage') }],
  ['core/buttons', {}, [['core/button', { text: __('Say hello', 'sage'), url: '/contact/' }]]],
]

function CtaEdit({ attributes, setAttributes }) {
  const { eyebrow, showAvailability, note } = attributes
  const blockProps = useBlockProps({ className: 'cta-band mh-block-cta' })
  const inner = useInnerBlocksProps(
    { className: 'cta-band__copy' },
    { template: CTA_TEMPLATE, allowedBlocks: ['core/heading', 'core/paragraph', 'core/buttons'] }
  )

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Call to action', 'sage')}>
          <ToggleControl
            __nextHasNoMarginBottom
            label={__('Show the availability mark', 'sage')}
            checked={showAvailability}
            onChange={(value) => setAttributes({ showAvailability: value })}
          />
          <TextControl
            {...controlProps}
            label={__('Note under the button', 'sage')}
            value={note}
            onChange={(value) => setAttributes({ note: value })}
          />
        </PanelBody>
      </InspectorControls>
      <section {...blockProps}>
        <div className="container wide cta-band-inner">
          <div>
            <Eyebrow onDark value={eyebrow} onChange={(value) => setAttributes({ eyebrow: value })} />
            <div {...inner} />
            {note && <p className="cta-band__note">{note}</p>}
          </div>
        </div>
      </section>
    </>
  )
}

registerBlockType(ctaMeta, { edit: CtaEdit, save: saveInner })

/* ── On this page ──────────────────────────────────────────────────────── */
function NavEdit() {
  const blockProps = useBlockProps({ className: 'h-page-nav mh-block-nav' })
  const labels = useSelect(
    (select) =>
      select('core/block-editor')
        .getBlocks()
        .filter((block) => block.name === 'matthummel/section' && block.attributes.anchor && block.attributes.navLabel)
        .map((block) => block.attributes.navLabel),
    []
  )

  return (
    <nav {...blockProps} aria-label={__('On this page', 'sage')}>
      <div className="container wide h-page-nav__inner">
        <p className="h-page-nav__label">{__('On this page', 'sage')}</p>
        {labels.length >= 2 ? (
          <ul className="h-page-nav__pills">
            {labels.map((label) => (
              <li key={label}>
                <span className="h-page-nav__pill">{label}</span>
              </li>
            ))}
          </ul>
        ) : (
          <Notice status="info" isDismissible={false}>
            {__('Give at least two Sections a link label and they will be listed here.', 'sage')}
          </Notice>
        )}
      </div>
    </nav>
  )
}

registerBlockType(navMeta, { edit: NavEdit, save: () => null })

/* ── Data blocks ───────────────────────────────────────────────────────────
 * Lists the site fills in by itself (projects, posts, GitHub, the contact form…).
 * Each has a block.json and a Blade view and nothing else: the title, attributes
 * and sidebar controls come from PHP (App\mh_data_blocks_for_editor), and the
 * editor shows the real server render.
 */
function DataControl({ control, value, onChange }) {
  const { type, label, help, min, max, options } = control
  if (type === 'toggle') {
    return <ToggleControl __nextHasNoMarginBottom label={label} help={help} checked={!!value} onChange={onChange} />
  }
  if (type === 'number') {
    return <RangeControl {...controlProps} label={label} help={help} min={min} max={max} value={Number(value) || min} onChange={onChange} />
  }
  if (type === 'select') {
    return <SelectControl {...controlProps} label={label} help={help} value={value} options={options} onChange={onChange} />
  }

  return <TextControl {...controlProps} label={label} help={help} value={value || ''} onChange={onChange} />
}

function dataBlockEdit(name, controls) {
  return function DataBlockEdit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({ className: 'mh-data-block' })

    return (
      <>
        {controls.length > 0 && (
          <InspectorControls>
            <PanelBody title={__('Settings', 'sage')}>
              {controls.map((control) => (
                <DataControl
                  key={control.key}
                  control={control}
                  value={attributes[control.key]}
                  onChange={(value) => setAttributes({ [control.key]: value })}
                />
              ))}
            </PanelBody>
          </InspectorControls>
        )}
        <div {...blockProps}>
          {/* Links and forms in the preview are not clickable, so a click selects the block. */}
          <Disabled>
            <ServerSideRender block={name} attributes={attributes} />
          </Disabled>
        </div>
      </>
    )
  }
}

for (const { name, controls } of window.mhPageBlocks?.dataBlocks || []) {
  registerBlockType(name, { edit: dataBlockEdit(name, controls), save: () => null })
}
