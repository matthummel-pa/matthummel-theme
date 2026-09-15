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
        khaki: '#efe8d8',
        cream: '#f6f1e4',
        blue: '#c4271a',
        'blue-ink': '#9e1f14',
        'blue-soft': '#ffd4c2',
        baby: '#ff9b7a',
        navy: '#161412',
        slate: '#2a2622',
        ink: '#161412',
        paper: '#f6f1e4',
        body: '#1a1714',
        muted: '#3f3a34',
        line: '#d2c8b0',
        focus: '#c4271a',
        card: '#ffffff',
        tomato: '#FF3E3E',
        amber: '#F5B700',
        lime: '#7DCE3A',
        violet: '#6D4AFF',
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
