/**
 * Editor canvas = public page (3.7.9).
 *
 * 1. Light or dark: the canvas follows the same `mh-theme` localStorage key the
 *    header toggle writes (resources/js/brand-ui.js). No key means dark, like
 *    layouts/app.blade.php. A "Dark mode" item in the editor's options menu
 *    flips it for both the canvas and the next public page load.
 * 2. Theme classes: on block pages PHP adds `.display-title`, `.lead`, `.btn`
 *    and `.mh-buttons` to core blocks (app/blocks.php). The same classes go on
 *    the matching blocks in the editor, so one set of CSS styles both.
 */
import { addFilter } from '@wordpress/hooks'
import { createHigherOrderComponent } from '@wordpress/compose'
import { useSelect } from '@wordpress/data'
import { registerPlugin } from '@wordpress/plugins'
import { PluginMoreMenuItem } from '@wordpress/editor'
import { useState, useEffect } from '@wordpress/element'
import { __ } from '@wordpress/i18n'

const STORAGE_KEY = 'mh-theme'
const DARK_CLASS = 'mh-dark'
const PAGE_BLOCKS = ['matthummel/hero', 'matthummel/section', 'matthummel/cta-band', 'matthummel/card']

/* The choice for this screen. Read from storage on load; kept here so a private
   window (storage throws) still holds the menu's pick, and so the menu item and
   the canvas always flip from the same value. */
let isDark = true
const listeners = new Set()

/* ── Dark mode ─────────────────────────────────────────────────────────── */

/** The visitor's saved choice. Dark unless they picked light, like the front end. */
export function prefersDark() {
  try {
    return localStorage.getItem(STORAGE_KEY) !== 'light'
  } catch (e) {
    return isDark
  }
}

/** Set the choice for this screen, paint the canvas, and tell the menu item. */
function setDark(dark) {
  isDark = dark
  applyTheme(dark)
  listeners.forEach((fn) => fn(dark))
}

/** Every root the canvas can have: the editor iframe's <html>, or the wrapper when there is no iframe. */
function canvasRoots() {
  const roots = []
  document.querySelectorAll('iframe[name="editor-canvas"]').forEach((frame) => {
    const doc = frame.contentDocument
    if (doc && doc.documentElement) roots.push(doc.documentElement)
  })
  document.querySelectorAll('.editor-styles-wrapper').forEach((el) => roots.push(el))
  return roots
}

function applyTheme(dark) {
  canvasRoots().forEach((root) => root.classList.toggle(DARK_CLASS, dark))
  // The admin shell only reads this for the toggle; theme CSS keys off the canvas root.
  document.documentElement.dataset.mhTheme = dark ? 'dark' : 'light'
}

function saveTheme(dark) {
  try {
    localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light')
  } catch (e) {
    /* private mode: the choice lasts for this screen only */
  }
}

/** Keep the class on the canvas as the editor mounts, re-mounts, or swaps its iframe. */
export function initEditorTheme() {
  const sync = () => setDark(prefersDark())
  sync()

  const observer = new MutationObserver(sync)
  observer.observe(document.body, { childList: true, subtree: true })

  // Iframe documents replace their <html> on load; re-apply once each one is ready.
  document.addEventListener(
    'load',
    (event) => {
      if (event.target instanceof HTMLIFrameElement && event.target.name === 'editor-canvas') sync()
    },
    true
  )

  // Another tab flipped the header toggle.
  window.addEventListener('storage', (event) => {
    if (event.key === STORAGE_KEY) sync()
  })
}

/** Options menu (⋮) → "Dark mode" check item. */
function DarkModeMenuItem() {
  const [dark, setMenuDark] = useState(isDark)

  // Follow the canvas: a storage event from another tab, or the first sync, lands here too.
  useEffect(() => {
    listeners.add(setMenuDark)
    return () => listeners.delete(setMenuDark)
  }, [])

  return (
    <PluginMoreMenuItem
      role="menuitemcheckbox"
      isSelected={dark}
      onClick={() => {
        const next = !isDark
        saveTheme(next)
        setDark(next)
      }}
      info={__('Preview the canvas the way visitors see it. Saved for the public site too.', 'sage')}
    >
      {__('Dark mode', 'sage')}
    </PluginMoreMenuItem>
  )
}

registerPlugin('matthummel-dark-mode', { render: DarkModeMenuItem })

/* ── Theme classes on core blocks inside page blocks ───────────────────── */

/**
 * Class the front end adds to this block, or '' when it adds none.
 * Mirrors the render_block filter in app/blocks.php.
 */
function themeClassFor(name, attributes, insidePageBlock) {
  if (!insidePageBlock) return ''
  const className = attributes.className || ''
  switch (name) {
    case 'core/heading':
      if (attributes.level === 1) return 'display-title is-hero'
      if (attributes.level === 2) return 'display-title is-section'
      return ''
    case 'core/paragraph':
      return className.includes('is-style-lead') ? 'lead' : ''
    case 'core/buttons':
      return 'mh-buttons'
    default:
      return ''
  }
}

const withThemeClasses = createHigherOrderComponent((BlockListBlock) => {
  return (props) => {
    const insidePageBlock = useSelect(
      (select) => {
        const { getBlockParents, getBlockName } = select('core/block-editor')
        return getBlockParents(props.clientId).some((id) => PAGE_BLOCKS.includes(getBlockName(id)))
      },
      [props.clientId]
    )
    const extra = themeClassFor(props.name, props.attributes, insidePageBlock)
    if (!extra) return <BlockListBlock {...props} />
    return <BlockListBlock {...props} className={[props.className, extra].filter(Boolean).join(' ')} />
  }
}, 'withThemeClasses')

addFilter('editor.BlockListBlock', 'matthummel/theme-classes', withThemeClasses)
