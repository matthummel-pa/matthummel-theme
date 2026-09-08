/** @type {import('tailwindcss').Config} */
const config = {
  content: [
    './index.php',
    './app/**/*.php',
    './resources/**/*.{php,vue,js}',
    './resources/**/*.blade.php',
  ],
  theme: {
    extend: {
      colors: {
        // Gutenberg / theme.json presets (Bud-shaped agency keys)
        agencyBrand: '#1A1A1A',
        agencyAccent: '#FF3E3E',
        agencyMuted: '#7F7F7F',
        // Live matthummel.com tokens (also in resources/css/app.css @theme)
        khaki: '#dde5ef',
        cream: '#f6f8fc',
        blue: '#0d2e57',
        'blue-ink': '#0a2446',
        'blue-soft': '#dceaf8',
        baby: '#93c5fd',
        navy: '#0d2e57',
        slate: '#243041',
        ink: '#0b1220',
        paper: '#f6f8fc',
        body: '#141c28',
        muted: '#3a4554',
        line: '#c5d0de',
        focus: '#0d2e57',
        card: '#ffffff',
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        display: ['ClashDisplay', 'sans-serif'],
      },
    },
  },
  plugins: [],
}

export default config
