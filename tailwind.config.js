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
        khaki: '#e8eef6',
        cream: '#f4f7fb',
        blue: '#2563eb',
        'blue-ink': '#1d4ed8',
        'blue-soft': '#dbeafe',
        navy: '#0b1220',
        slate: '#334155',
        ink: '#0b1220',
        paper: '#eef2f7',
        body: '#334155',
        muted: '#475569',
        line: '#c5d0de',
        focus: '#2563eb',
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
