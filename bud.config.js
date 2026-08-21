/**
 * Bud-shaped entry and theme.json spec.
 * Sage 11 compiles with Vite (`vite.config.js`, `npm run build`). Keep this
 * file in sync with `wordpressThemeJson()` and the root `theme.json` source.
 *
 * @typedef {import('@roots/bud').Bud} Bud
 *
 * @param {Bud} app
 */
export default async (app) => {
  app
    /**
     * Application entrypoints
     */
    .entry('app', ['@scripts/app', '@styles/app'])
    .entry('editor', ['@scripts/editor', '@styles/editor'])

    /**
     * Handle Asset Copying
     */
    .assets(['images'])

    /**
     * Bridge Tailwind System Variables directly to WordPress theme.json
     */
    .wpjson
    .enable()
    .useTailwindColors()
    .useTailwindFontSizes()
    .settings({
      color: {
        custom: false,
        link: true,
      },
      spacing: {
        blockGap: true,
        units: ['px', 'em', 'rem', 'vh', 'vw'],
      },
    })
    .setup()
}
