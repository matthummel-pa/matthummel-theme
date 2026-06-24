import { useEffect, useState } from 'react'
import { supabase } from './supabase.js'
import { getSettings, applyTheme, applyA11y } from './settings.js'
import Login from './Login.jsx'
import MfaGate from './MfaGate.jsx'
import Home from './Home.jsx'
import FileManager from './FileManager.jsx'
import Trending from './Trending.jsx'
import Blog from './Blog.jsx'
import Notes from './Notes.jsx'
import Todos from './Todos.jsx'
import Calendar from './Calendar.jsx'
import Profile from './Profile.jsx'
import Settings from './Settings.jsx'
import Admin from './Admin.jsx'
import Followers from './Followers.jsx'
import Chat from './Chat.jsx'
import AppIcon from './AppIcon.jsx'
import { fetchFriendPages, FriendToggle } from './friendAccess.jsx'

const OWNER_EMAIL = 'matthew.r.hummel@gmail.com'

// Live experience = the private "social, but only us" loop + a few supporting utilities.
// Pending (hidden) projects live in HIDDEN_NAV — flip them into NAV to re-enable.
const NAV = [
  { id: 'home', icon: 'hub', name: 'Hub', tip: 'Hub — your dashboard' },
  { id: 'blog', icon: 'feed', name: 'Feed', tip: 'Feed — your family feed' },
  { id: 'chat', icon: 'chat', name: 'Chat', tip: 'Chat — message your circle' },
  { id: 'profile', icon: 'card', name: 'MyCard', tip: 'MyCard — your profile' },
  { id: 'followers', icon: 'followers', name: 'Followers', tip: 'Followers — your circle' },
  { id: 'files', icon: 'vault', name: 'Vault', tip: 'Vault — documents & files' },
  { id: 'notes', icon: 'scribe', name: 'Scribe', tip: 'Scribe — notes & notebooks' },
  { id: 'calendar', icon: 'agenda', name: 'Agenda', tip: 'Agenda — calendar' },
  { id: 'settings', icon: 'settings', name: 'Settings', tip: 'Settings' }
]

// Hidden, not live — kept for re-enabling later (Pulse trending, Studio blog/sites, Tasks).
// eslint-disable-next-line no-unused-vars
const HIDDEN_NAV = [
  { id: 'trending', icon: 'pulse', name: 'Pulse', tip: 'Pulse — trending news' },
  { id: 'studio', icon: 'studio', name: 'Studio', tip: 'Studio — blog & sharing' },
  { id: 'todos', icon: 'tasks', name: 'Tasks', tip: 'Tasks — your to-dos' }
]

export default function App() {
  const [session, setSession] = useState(null)
  const [ready, setReady] = useState(false)
  const [view, setView] = useState(() => getSettings().startView)
  const [mfaRequired, setMfaRequired] = useState(false)
  const [role, setRole] = useState(null)
  const [friendPages, setFriendPages] = useState(null)
  const [deactivated, setDeactivated] = useState(false)
  const [reactivating, setReactivating] = useState(false)

  // load this user's role (for admin access + read-only awareness)
  useEffect(() => {
    if (!session) { setRole(null); return }
    supabase.rpc('user_role').then(({ data }) => setRole(data || 'author'))
    fetchFriendPages().then(setFriendPages)
    supabase.from('profiles').select('deactivated_at').eq('id', session.user.id).maybeSingle().then(({ data }) => setDeactivated(!!data?.deactivated_at))
  }, [session])

  // 2FA: if the account has a verified authenticator, require the code each sign-in
  useEffect(() => {
    if (!session) { setMfaRequired(false); return }
    supabase.auth.mfa.getAuthenticatorAssuranceLevel().then(({ data }) => {
      setMfaRequired(data?.currentLevel === 'aal1' && data?.nextLevel === 'aal2')
    })
  }, [session])

  // auto-lock: sign out after configured inactivity
  useEffect(() => {
    if (!session) return
    let timer
    const reset = () => {
      clearTimeout(timer)
      const mins = getSettings().autoLock
      if (!mins) return
      timer = setTimeout(() => supabase.auth.signOut(), mins * 60000)
    }
    const evs = ['mousedown', 'keydown', 'touchstart', 'scroll']
    evs.forEach((e) => window.addEventListener(e, reset, { passive: true }))
    reset()
    return () => {
      clearTimeout(timer)
      evs.forEach((e) => window.removeEventListener(e, reset))
    }
  }, [session])

  useEffect(() => {
    supabase.auth.getSession().then(({ data }) => {
      setSession(data.session)
      setReady(true)
    })
    const { data: sub } = supabase.auth.onAuthStateChange((_e, s) => setSession(s))
    return () => sub.subscription.unsubscribe()
  }, [])

  // theme: apply on load, follow system changes and settings updates
  useEffect(() => {
    applyTheme(getSettings().theme)
    applyA11y(getSettings())
    const mql = window.matchMedia?.('(prefers-color-scheme: dark)')
    const onSystem = () => applyTheme(getSettings().theme)
    mql?.addEventListener?.('change', onSystem)
    const onSettings = (e) => { applyTheme(e.detail.theme); applyA11y(e.detail) }
    window.addEventListener('hc-settings-changed', onSettings)
    return () => {
      mql?.removeEventListener?.('change', onSystem)
      window.removeEventListener('hc-settings-changed', onSettings)
    }
  }, [])

  if (!ready) {
    return (
      <div className="grid h-screen place-items-center bg-brand-gradient">
        <div className="h-10 w-10 animate-spin rounded-full border-4 border-white/35 border-t-white" aria-label="Loading" />
      </div>
    )
  }

  if (!session) return <Login />

  async function reactivate() {
    setReactivating(true)
    await supabase.from('profiles').upsert({ id: session.user.id, deactivated_at: null, updated_at: new Date().toISOString() })
    setReactivating(false)
    setDeactivated(false)
  }

  if (deactivated) {
    return (
      <div className="grid min-h-screen place-items-center bg-brand-gradient p-4">
        <div className="w-full max-w-sm overflow-hidden rounded-3xl bg-white text-center shadow-2xl shadow-indigo-950/40">
          <div className="bg-brand-gradient px-6 py-7 text-white">
            <span className="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-white/20 text-3xl">👋</span>
            <h1 className="mb-1 mt-3 text-2xl font-extrabold tracking-tight">Welcome back</h1>
            <p className="m-0 text-sm text-white/90">Your account is deactivated.</p>
          </div>
          <div className="grid gap-3 p-6">
            <p className="m-0 text-sm text-slate-600">Reactivate to restore your profile, posts and everything else exactly as you left it. If you do nothing, your account is permanently deleted 30 days after deactivation.</p>
            <button className="rounded-xl bg-brand-gradient px-4 py-3 font-bold text-white transition hover:brightness-110 disabled:opacity-60" disabled={reactivating} onClick={reactivate}>{reactivating ? 'Reactivating…' : 'Reactivate my account'}</button>
            <button className="text-sm font-semibold text-slate-500 hover:text-brand-violet" onClick={() => supabase.auth.signOut()}>Stay deactivated & sign out</button>
          </div>
        </div>
      </div>
    )
  }

  if (mfaRequired) return <MfaGate onDone={() => setMfaRequired(false)} />

  const isOwner = session.user.email?.toLowerCase() === OWNER_EMAIL
  const isAdmin = isOwner || role === 'administrator'
  let nav = isAdmin ? [...NAV, { id: 'admin', icon: 'console', name: 'Console', tip: 'Console — admin' }] : NAV
  if (role === 'friend') {
    const allowed = friendPages || ['blog', 'profile', 'followers', 'chat']
    nav = NAV.filter((n) => allowed.includes(n.id))
  }
  const allowedView = nav.some((n) => n.id === view) ? view : (nav[0]?.id || 'profile')
  if (allowedView !== view) setView(allowedView)

  return (
    <div className="flex min-h-screen flex-col bg-slate-100 text-ink">
      <header className="flex flex-wrap items-center justify-between gap-3 bg-brand-gradient px-4 py-4 text-white sm:px-9">
        <div className="flex items-center gap-3">
          <span className="grid h-10 w-10 place-items-center rounded-xl bg-white/20" aria-hidden="true">
            <svg viewBox="0 0 120 120" className="h-6 w-6">
              <g fill="none" stroke="white" strokeWidth="13" strokeLinecap="round" strokeLinejoin="round">
                <path d="M45 36 V84" /><path d="M45 61 L80 36" /><path d="M45 61 L82 85" />
              </g>
            </svg>
          </span>
          <div>
            <h1 className="text-xl font-extrabold tracking-tight">Keepary</h1>
            <p className="hidden text-xs text-white/85 sm:block">Your documents, kept safe</p>
          </div>
        </div>
        <div className="flex items-center gap-3">
          <nav className="flex gap-1 rounded-xl bg-white/15 p-1" aria-label="Main">
            {nav.map((n) => (
              <button
                key={n.id}
                onClick={() => setView(n.id)}
                title={n.tip}
                className={`flex items-center gap-1.5 rounded-lg px-1.5 py-1 text-sm font-bold transition sm:px-2 ${view === n.id ? 'bg-white text-brand-violet' : 'text-white hover:bg-white/10'}`}
              >
                <AppIcon id={n.icon} className="h-6 w-6" />
                <span className="hidden sm:inline">{n.name}</span>
              </button>
            ))}
          </nav>
          <button
            className="hidden rounded-lg border-2 border-white/45 px-4 py-2 text-sm font-bold transition hover:bg-white/10 md:block"
            onClick={() => supabase.auth.signOut()}
          >
            Sign out
          </button>
        </div>
      </header>

      {isAdmin && ['home','files','blog','notes','calendar','profile','followers','chat'].includes(view) && (
        <div className="mx-auto mt-3 flex w-full max-w-5xl items-center justify-end gap-2 px-3 sm:px-7">
          <span className="text-[11px] text-slate-400">Friend access for this page:</span>
          <FriendToggle page={view} />
        </div>
      )}
      {view === 'home' && <Home session={session} go={setView} />}
      {view === 'files' && <FileManager />}
      {view === 'trending' && <Trending session={session} go={setView} />}
      {view === 'blog' && <Blog session={session} feedOnly />}
      {view === 'studio' && <Blog session={session} />}
      {view === 'notes' && <Notes session={session} />}
      {view === 'todos' && <Todos session={session} />}
      {view === 'calendar' && <Calendar session={session} />}
      {view === 'profile' && <Profile session={session} />}
      {view === 'settings' && <Settings session={session} />}
      {view === 'followers' && <Followers session={session} />}
      {view === 'chat' && <Chat session={session} />}
      {view === 'admin' && isAdmin && <Admin />}

      <footer className="py-4 text-center text-xs text-slate-500">
        © {new Date().getFullYear()} Matt Hummel · matthummel.com ·{' '}
        <button className="underline hover:text-brand-violet" onClick={() => supabase.auth.signOut()}>Sign out</button>
      </footer>
    </div>
  )
}
