import { useState } from 'react'
import { supabase } from './supabase.js'

export default function MfaGate({ onDone }) {
  const [code, setCode] = useState('')
  const [busy, setBusy] = useState(false)
  const [err, setErr] = useState(null)

  async function verify(e) {
    e.preventDefault()
    setBusy(true)
    setErr(null)
    try {
      const { data: f, error: fe } = await supabase.auth.mfa.listFactors()
      if (fe) throw fe
      const factor = (f?.totp || []).find((x) => x.status === 'verified') || f?.totp?.[0]
      if (!factor) throw new Error('No authenticator found on this account.')
      const { error } = await supabase.auth.mfa.challengeAndVerify({ factorId: factor.id, code: code.trim() })
      if (error) throw error
      onDone()
    } catch (e2) {
      setErr(e2.message || 'That code didn’t work — try the current one from your app.')
    }
    setBusy(false)
  }

  return (
    <div className="grid min-h-screen place-items-center bg-brand-gradient p-4">
      <div className="w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-2xl shadow-indigo-950/40">
        <div className="bg-brand-gradient px-6 py-7 text-center text-white">
          <span className="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-white/20 text-3xl" aria-hidden="true">🔐</span>
          <h1 className="mb-1 mt-3 text-2xl font-extrabold tracking-tight">Two-factor check</h1>
          <p className="m-0 text-sm text-white/90">Enter the 6-digit code from your authenticator app.</p>
        </div>
        <form onSubmit={verify} className="grid gap-3 p-6">
          <input
            inputMode="numeric"
            autoComplete="one-time-code"
            maxLength={6}
            className="rounded-xl border-2 border-slate-200 px-4 py-3 text-center text-2xl font-extrabold tracking-[0.4em] outline-none focus:border-brand-violet"
            placeholder="••••••"
            value={code}
            onChange={(e) => setCode(e.target.value.replace(/\D/g, ''))}
            autoFocus
          />
          {err && <p className="m-0 rounded-xl bg-red-50 px-4 py-2.5 text-sm text-red-600">{err}</p>}
          <button
            className="rounded-xl bg-brand-gradient px-4 py-3 font-bold text-white transition hover:brightness-110 disabled:opacity-60"
            disabled={busy || code.length !== 6}
          >
            {busy ? 'Checking…' : 'Verify'}
          </button>
          <button
            type="button"
            className="text-sm font-semibold text-slate-500 hover:text-brand-violet"
            onClick={() => supabase.auth.signOut()}
          >
            Cancel & sign out
          </button>
          <p className="m-0 border-t border-slate-100 pt-3 text-center text-xs text-slate-400">
            Lost your device? Contact the account owner to reset two-factor authentication for you.
          </p>
        </form>
      </div>
    </div>
  )
}
