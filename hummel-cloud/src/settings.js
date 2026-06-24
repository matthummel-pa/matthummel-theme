export const SETTINGS_KEY = 'hc-settings'

export const DEFAULT_SETTINGS = {
  theme: 'system', // light | dark | system
  startView: 'home', // home | files | trending
  fileSort: 'name', // name | date | size
  shareDays: 7, // share link expiry in days
  confirmDelete: true,
  autoLock: 0, // minutes of inactivity before auto sign-out (0 = never)
  market: 'en-US', // news market & language (BCP47-ish ll-CC)
  location: null, // { city, region, country, countryCode }
  notesService: 'notion', // notion | goodnotes
  notionToken: '', // Notion internal integration token (stored on this device only)
  notionParent: '', // Notion page link/ID shared with the integration
  // accessibility (per-device)
  textScale: 'normal', // normal | large | xlarge
  highContrast: false,
  reduceMotion: false,
  dyslexiaFont: false,
  underlineLinks: false
}

export const NOTES_SERVICES = [
  { id: 'standalone', label: '📓 Standalone (this app only)' },
  { id: 'notion', label: '⬜ Notion' },
  { id: 'goodnotes', label: '📔 GoodNotes' }
]

export const MARKETS = [
  { id: 'en-US', label: '🇺🇸 English (United States)' },
  { id: 'en-GB', label: '🇬🇧 English (United Kingdom)' },
  { id: 'en-CA', label: '🇨🇦 English (Canada)' },
  { id: 'en-AU', label: '🇦🇺 English (Australia)' },
  { id: 'en-IN', label: '🇮🇳 English (India)' },
  { id: 'es-ES', label: '🇪🇸 Español (España)' },
  { id: 'es-MX', label: '🇲🇽 Español (México)' },
  { id: 'fr-FR', label: '🇫🇷 Français (France)' },
  { id: 'fr-CA', label: '🇨🇦 Français (Canada)' },
  { id: 'de-DE', label: '🇩🇪 Deutsch (Deutschland)' },
  { id: 'it-IT', label: '🇮🇹 Italiano (Italia)' },
  { id: 'pt-BR', label: '🇧🇷 Português (Brasil)' },
  { id: 'nl-NL', label: '🇳🇱 Nederlands (Nederland)' },
  { id: 'ja-JP', label: '🇯🇵 日本語 (日本)' }
]

export const MARKET_BY_COUNTRY = {
  US: 'en-US', GB: 'en-GB', UK: 'en-GB', CA: 'en-CA', AU: 'en-AU', NZ: 'en-AU', IE: 'en-GB', IN: 'en-IN',
  ES: 'es-ES', MX: 'es-MX', AR: 'es-MX', CO: 'es-MX', FR: 'fr-FR', BE: 'fr-FR', DE: 'de-DE', AT: 'de-DE',
  CH: 'de-DE', IT: 'it-IT', BR: 'pt-BR', PT: 'pt-BR', NL: 'nl-NL', JP: 'ja-JP'
}

export function getSettings() {
  try {
    return { ...DEFAULT_SETTINGS, ...(JSON.parse(localStorage.getItem(SETTINGS_KEY)) || {}) }
  } catch {
    return { ...DEFAULT_SETTINGS }
  }
}

export function saveSettings(next) {
  try { localStorage.setItem(SETTINGS_KEY, JSON.stringify(next)) } catch { /* ignore */ }
  window.dispatchEvent(new CustomEvent('hc-settings-changed', { detail: next }))
}

export function applyTheme(theme) {
  const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches
  const dark = theme === 'dark' || (theme === 'system' && prefersDark)
  document.documentElement.classList.toggle('dark', dark)
}

export function applyA11y(s = getSettings()) {
  const r = document.documentElement
  r.classList.toggle('a11y-large', s.textScale === 'large')
  r.classList.toggle('a11y-xlarge', s.textScale === 'xlarge')
  r.classList.toggle('a11y-contrast', !!s.highContrast)
  r.classList.toggle('a11y-reduce-motion', !!s.reduceMotion)
  r.classList.toggle('a11y-dyslexic', !!s.dyslexiaFont)
  r.classList.toggle('a11y-underline', !!s.underlineLinks)
}
