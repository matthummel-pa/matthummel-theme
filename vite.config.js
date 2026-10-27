import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin'
import { wordpressPlugin, wordpressThemeJson } from '@roots/vite-plugin';
import { register } from 'node:module'

// Windows only: @roots/vite-plugin imports tailwind.config.js by absolute path (C:\...),
// which Node's ESM loader rejects. This resolve hook turns drive-letter paths into
// file:// URLs. It runs before the plugin loads the config, and does nothing on Linux/CI.
if (process.platform === 'win32') {
  register(`data:text/javascript,${encodeURIComponent(`
    import { pathToFileURL } from 'node:url'
    export async function resolve(specifier, context, nextResolve) {
      return nextResolve(/^[a-zA-Z]:/.test(specifier) ? pathToFileURL(specifier).href : specifier, context)
    }
  `)}`)
}

// Set APP_URL if it doesn't exist for Laravel Vite plugin
if (! process.env.APP_URL) {
  process.env.APP_URL = 'http://example.test';
}

export default defineConfig({
    base: '/wp-content/themes/matthummel/public/build/',
  plugins: [
    tailwindcss(),
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/editor.css',
        'resources/js/editor.js',
      ],
      refresh: true,
      assets: ['resources/images/**', 'resources/fonts/**'],
    }),

    wordpressPlugin(),

    // Generate the theme.json file in the public/build/assets directory
    // based on the Tailwind config and the theme.json file from base theme folder
    wordpressThemeJson({
      disableTailwindColors: false,
      disableTailwindFonts: false,
      disableTailwindFontSizes: false,
      disableTailwindBorderRadius: false,
      cssFile: 'app.css',
      tailwindConfig: './tailwind.config.js',
      baseThemeJsonPath: './theme.json',
      outputPath: 'assets/theme.json',
    }),
  ],
  resolve: {
    alias: {
      '@scripts': '/resources/js',
      '@styles': '/resources/css',
      '@fonts': '/resources/fonts',
      '@images': '/resources/images',
    },
  },
})
