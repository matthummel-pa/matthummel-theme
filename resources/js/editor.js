import domReady from '@wordpress/dom-ready'

import './blocks'
import { initEditorTheme } from './editor-theme.jsx'

domReady(() => {
  // Theme blocks load via ./blocks (tool-grid, tool-card, ship-pipe, ship-step, page blocks).
  // Canvas light/dark + theme classes on core blocks: ./editor-theme.jsx.
  initEditorTheme()
})
