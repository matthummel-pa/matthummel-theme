import { useState } from 'react'
import { supabase } from './supabase.js'

const inputCls =
  'w-full rounded-lg border-2 border-slate-200 px-3.5 py-2.5 text-base outline-none transition focus:border-brand-violet'

export default function Login() {
  const [mode, setMode] = useState('signin') // signin | signup | reset
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [msg, setMsg] = useState(null)
  const [busy, setBusy] = useState(false)

  async function submit(e) {
    e.preventDefault()
    setBusy(true)
    setMsg(null)
    try {
      if (mode === 'signin') {
        const { error } = await supabase.auth.signInWithPassword({ email, password })
        if (error) throw error
      } else if (mode === 'signup') {
        if (password.length < 10 || !/[a-zA-Z]/.test(password) || !/\d/.test(password)) {
          throw new Error('Choose a stronger password: at least 10 characters with letters and numbers.')
        }
        const { error } = await supabase.auth.signUp({
          email,
          password,
          options: { emailRedirectTo: window.location.origin }
        })
        if (error) throw error
        setMsg({ ok: true, text: 'Check your email to confirm your account, then sign in.' })
      } else {
        const { error } = await supabase.auth.resetPasswordForEmail(email, {
          redirectTo: window.location.origin
        })
        if (error) throw error
        setMsg({ ok: true, text: 'Password reset email sent.' })
      }
    } catch (err) {
      const text = /restricted/i.test(err.message)
        ? 'Keepary is invite-only. Your email isn’t on the invite list yet.'
        : err.message
      setMsg({ ok: false, text })
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="grid min-h-screen place-items-center bg-brand-gradient p-4">
      <div className="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl shadow-indigo-950/40">
        <div className="bg-brand-gradient px-6 pb-7 pt-9 text-center text-white">
          <span className="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-white/20" aria-hidden="true">
            <svg viewBox="0 0 120 120" className="h-9 w-9">
              <g fill="none" stroke="white" strokeWidth="13" strokeLinecap="round" strokeLinejoin="round">
                <path d="M45 36 V84" /><path d="M45 61 L80 36" /><path d="M45 61 L82 85" />
              </g>
            </svg>
          </span>
          <h1 className="mb-1 mt-3 text-3xl font-extrabold tracking-tight">Keepary</h1>
          <p className="text-sm text-white/90">Your private, secure document keep</p>
        </div>

        <form onSubmit={submit} className="grid gap-4 px-7 pt-7">
          <label className="grid gap-1.5 text-sm font-semibold">
            Email
            <input
              type="email"
              className={inputCls}
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              autoComplete="email"
              required
            />
          </label>
          {mode !== 'reset' && (
            <label className="grid gap-1.5 text-sm font-semibold">
              Password
              <input
                type="password"
                className={inputCls}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                autoComplete={mode === 'signup' ? 'new-password' : 'current-password'}
                minLength={8}
                required
              />
            </label>
          )}
          {msg && (
            <p className={`rounded-lg px-3 py-2.5 text-sm ${msg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>
              {msg.text}
            </p>
          )}
          <button
            className="rounded-xl bg-brand-gradient px-4 py-3 font-bold text-white transition hover:brightness-110 disabled:cursor-default disabled:opacity-70 disabled:grayscale-50"
            disabled={busy}
          >
            {busy ? 'Working…' : mode === 'signin' ? 'Sign in' : mode === 'signup' ? 'Create account' : 'Send reset email'}
          </button>
        </form>

        <div className="flex flex-wrap justify-center gap-x-4 gap-y-1 px-5 pb-6 pt-4">
          {mode !== 'signin' && (
            <button className="p-1 text-sm text-brand-blue hover:underline" onClick={() => { setMode('signin'); setMsg(null) }}>
              Sign in
            </button>
          )}
          {mode !== 'signup' && (
            <button className="p-1 text-sm text-brand-blue hover:underline" onClick={() => { setMode('signup'); setMsg(null) }}>
              First time? Create account
            </button>
          )}
          {mode !== 'reset' && (
            <button className="p-1 text-sm text-brand-blue hover:underline" onClick={() => { setMode('reset'); setMsg(null) }}>
              Forgot password
            </button>
          )}
        </div>
      </div>
    </div>
  )
}
