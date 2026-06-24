// Custom per-app logos for Keepary — M365-style colored tiles with white line glyphs.
const BG = {
  hub: 'bg-violet-500',
  vault: 'bg-blue-500',
  pulse: 'bg-rose-500',
  studio: 'bg-amber-500',
  scribe: 'bg-emerald-500',
  tasks: 'bg-teal-500',
  agenda: 'bg-indigo-500',
  card: 'bg-pink-500',
  settings: 'bg-slate-500',
  console: 'bg-slate-700',
  followers: 'bg-fuchsia-500',
  chat: 'bg-indigo-600',
  feed: 'bg-rose-500'
}

function Glyph({ id }) {
  const s = { fill: 'none', stroke: 'white', strokeWidth: 1.9, strokeLinecap: 'round', strokeLinejoin: 'round' }
  switch (id) {
    case 'hub':
      return (
        <g {...s}>
          <rect x="4" y="4" width="6.5" height="6.5" rx="1.6" />
          <rect x="13.5" y="4" width="6.5" height="6.5" rx="1.6" />
          <rect x="4" y="13.5" width="6.5" height="6.5" rx="1.6" />
          <rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.6" />
        </g>
      )
    case 'vault':
      return (
        <g {...s}>
          <rect x="5" y="10" width="14" height="10" rx="2.5" />
          <path d="M8 10V7a4 4 0 0 1 8 0v3" />
          <circle cx="12" cy="14.3" r="1.2" fill="white" stroke="none" />
          <path d="M12 15V17" />
        </g>
      )
    case 'pulse':
      return (
        <g {...s}>
          <path d="M4 15l5-5 4 4 7-7" />
          <path d="M16 6h4v4" />
        </g>
      )
    case 'studio':
      return (
        <g {...s}>
          <path d="M4 10v4h3l8 4V6l-8 4z" />
          <path d="M18.5 9a4 4 0 0 1 0 6" />
        </g>
      )
    case 'scribe':
      return (
        <g {...s}>
          <rect x="5" y="4" width="14" height="16" rx="2.2" />
          <path d="M8.5 9h7" />
          <path d="M8.5 12.5h7" />
          <path d="M8.5 16h4.5" />
        </g>
      )
    case 'tasks':
      return (
        <g {...s}>
          <rect x="4" y="4" width="16" height="16" rx="3" />
          <path d="M8 12l3 3 5-6" />
        </g>
      )
    case 'agenda':
      return (
        <g {...s}>
          <rect x="4" y="5.5" width="16" height="14.5" rx="2.4" />
          <path d="M4 9.5h16" />
          <path d="M8 3.5V6.5" />
          <path d="M16 3.5V6.5" />
        </g>
      )
    case 'card':
      return (
        <g {...s}>
          <circle cx="12" cy="9.2" r="3.2" />
          <path d="M5.5 19a6.5 6.5 0 0 1 13 0" />
        </g>
      )
    case 'settings':
      return (
        <g {...s}>
          <circle cx="12" cy="12" r="3.2" />
          <path d="M12 3v2.4M12 18.6V21M3 12h2.4M18.6 12H21M5.6 5.6l1.7 1.7M16.7 16.7l1.7 1.7M16.7 7.3l1.7-1.7M5.6 18.4l1.7-1.7" />
        </g>
      )
    case 'followers':
      return (
        <g {...s}>
          <circle cx="9" cy="9" r="3" />
          <path d="M3.5 19a5.5 5.5 0 0 1 11 0" />
          <circle cx="17" cy="8" r="2.4" />
          <path d="M15.5 13.5a5 5 0 0 1 5.5 4.5" />
        </g>
      )
    case 'feed':
      return (
        <g {...s}>
          <path d="M4 6h16" />
          <path d="M4 12h16" />
          <path d="M4 18h10" />
        </g>
      )
    case 'chat':
      return (
        <g {...s}>
          <path d="M5 5h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H9l-4 3.5V7a2 2 0 0 1 2-2z" />
          <path d="M9 10h7" />
          <path d="M9 13h4" />
        </g>
      )
    case 'console':
      return (
        <g {...s}>
          <path d="M12 3l7 3v5c0 4.5-3 7.6-7 9-4-1.4-7-4.5-7-9V6z" />
          <path d="M9 12l2 2 4-4" />
        </g>
      )
    default:
      return null
  }
}

export default function AppIcon({ id, className = 'h-6 w-6' }) {
  return (
    <span className={`grid ${className} shrink-0 place-items-center rounded-lg ${BG[id] || 'bg-slate-500'}`} aria-hidden="true">
      <svg viewBox="0 0 24 24" className="h-[70%] w-[70%]">
        <Glyph id={id} />
      </svg>
    </span>
  )
}
