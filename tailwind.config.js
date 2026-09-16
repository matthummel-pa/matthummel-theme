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
        agencyBrand: '#0B1220',
        agencyAccent: '#0D2E57',
        agencyMuted: '#4F5C6E',
        // Live matthummel.com tokens (also in resources/css/app.css @theme)
        khaki: '#eef3f9',
        cream: '#f7f9fc',
        blue: '#0d2e57',
        'blue-ink': '#0a2446',
        'blue-soft': '#dceaf8',
        baby: '#93c5fd',
        navy: '#0d2e57',
        slate: '#243041',
        ink: '#0b1220',
        paper: '#f7f9fc',
        body: '#141c28',
        muted: '#3a4554',
        line: '#cfd9e6',
        focus: '#0d2e57',
        card: '#ffffff',
        tomato: '#0D2E57',
        amber: '#4F8FD4',
        lime: '#3D8B6E',
        violet: '#3B5B9C',
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        display: ['Inter', 'sans-serif'],
      },
    },
  },
  plugins: [],
}

export default config
