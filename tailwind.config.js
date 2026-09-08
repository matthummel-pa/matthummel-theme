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
        cream: '#f3f6fb',
        blue: '#1e4f8c',
        'blue-ink': '#173e70',
        'blue-soft': '#e4edf7',
        navy: '#173e70',
        slate: '#243041',
        ink: '#0b1220',
        paper: '#f3f6fb',
        body: '#141c28',
        muted: '#3a4554',
        line: '#c5d0de',
        focus: '#1e4f8c',
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
