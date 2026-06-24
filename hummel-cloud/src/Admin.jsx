import { useCallback, useEffect, useMemo, useState } from 'react'
import { supabase } from './supabase.js'
import AppIcon from './AppIcon.jsx'

const OWNER = 'matthew.r.hummel@gmail.com'
const fmtBytes = (b) => {
  if (!b) return '0 B'
  if (b < 1024 ** 2) return `${(b / 1024).toFixed(1)} KB`
  if (b < 1024 ** 3) return `${(b / 1024 ** 2).toFixed(1)} MB`
  return `${(b / 1024 ** 3).toFixed(2)} GB`
}
const fmtDate = (d) => (d ? new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—')
const fmtWhen = (d) => {
  if (!d) return 'never'
  const hrs = Math.round((Date.now() - new Date(d).getTime()) / 3600000)
  if (hrs < 1) return 'just now'
  if (hrs < 24) return `${hrs}h ago`
  const days = Math.round(hrs / 24)
  return days < 30 ? `${days}d ago` : fmtDate(d)
}
const fmtActAt = (d) => new Date(d).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
const actionLabel = (a) => ({
  login: '🔑 Signed in', logout: '👋 Signed out', user_signedup: '✨ Signed up',
  token_refreshed: '🔄 Session refreshed', user_modified: '✏️ Account changed',
  user_recovery_requested: '📧 Password reset requested', factor_verified: '🔐 2FA verified',
  mfa_challenge_verified: '🔐 2FA passed', user_confirmation_requested: '📨 Confirmation requested'
}[a] || `• ${a || 'event'}`)

const ROLES = [
  { id: 'administrator', label: 'Administrator', desc: 'Full access, including this admin panel', cls: 'bg-rose-100 text-rose-700' },
  { id: 'editor', label: 'Editor', desc: 'Full use; manage shared family content', cls: 'bg-violet-100 text-violet-700' },
  { id: 'author', label: 'Author', desc: 'Full use of their own content (default)', cls: 'bg-blue-100 text-blue-700' },
  { id: 'contributor', label: 'Contributor', desc: 'Full use of their own content', cls: 'bg-sky-100 text-sky-700' },
  { id: 'subscriber', label: 'Subscriber', desc: 'Read-only — cannot add or change anything', cls: 'bg-slate-200 text-slate-600' },
  { id: 'friend', label: 'Friend', desc: 'Only sees the pages you allow (Pulse, Studio, Card…)', cls: 'bg-fuchsia-100 text-fuchsia-700' }
]
const roleMeta = (id) => ROLES.find((r) => r.id === id) || ROLES[2]

const inputCls = 'rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-violet'

function Stat({ label, value }) {
  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm">
      <p className="m-0 text-lg font-extrabold">{value}</p>
      <p className="m-0 text-xs font-semibold text-slate-500">{label}</p>
    </div>
  )
}

export default function Admin() {
  const [data, setData] = useState(null)
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(true)
  const [msg, setMsg] = useState(null)
  const [adding, setAdding] = useState(false)
  const [form, setForm] = useState({ email: '', password: '', autoConfirm: true })
  const [manage, setManage] = useState(null)
  const [tempPw, setTempPw] = useState('')
  const [working, setWorking] = useState(false)
  const [q, setQ] = useState('')

  const call = useCallback(async (op, extra = {}) => {
    const { data: res, error: err } = await supabase.functions.invoke('admin', { body: { op, ...extra } })
    if (err || res?.error) throw new Error(res?.error || 'Request failed.')
    return res
  }, [])

  const load = useCallback(async () => {
    setLoading(true); setError(null)
    try { setData(await call('stats')) } catch (e) { setError(e.message) }
    setLoading(false)
  }, [call])

  useEffect(() => { load() }, [load])
  const flash = (ok, text) => { setMsg({ ok, text }); setTimeout(() => setMsg(null), 5000) }

  async function addUser(e) {
    e.preventDefault(); setWorking(true)
    try { const r = await call('add_user', form); flash(true, r.message); setForm({ email: '', password: '', autoConfirm: true }); setAdding(false); load() }
    catch (e2) { flash(false, e2.message) }
    setWorking(false)
  }
  async function doOp(op, email, extra = {}, confirmText) {
    if (confirmText && !window.confirm(confirmText)) return
    setWorking(true)
    try { const r = await call(op, { email, ...extra }); flash(true, r.message); if (op === 'set_password') setTempPw(''); if (op === 'remove_user') setManage(null); load() }
    catch (e2) { flash(false, e2.message) }
    setWorking(false)
  }

  function exportCsv() {
    const cols = ['email', 'display_name', 'confirmed', 'banned', 'mfa_enabled', 'last_ip', 'created_at', 'last_sign_in_at', 'storage_bytes', 'storage_files', 'notes', 'todos_open', 'todos_done', 'events', 'favorites']
    const rows = [cols.join(',')].concat((data.users || []).map((u) => cols.map((c) => `"${String(u[c] ?? '').replace(/"/g, '""')}"`).join(',')))
    const blob = new Blob([rows.join('\n')], { type: 'text/csv' })
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = `keepary-users-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
  }

  const users = useMemo(() => {
    const list = data?.users || []
    if (!q.trim()) return list
    const s = q.trim().toLowerCase()
    return list.filter((u) => u.email.toLowerCase().includes(s) || (u.display_name || '').toLowerCase().includes(s))
  }, [data, q])

  return (
    <main className="mx-auto w-full max-w-4xl flex-1 px-3 py-5 sm:px-7">
      <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
        <div>
          <h1 className="m-0 text-xl font-extrabold tracking-tight flex items-center gap-2"><AppIcon id="console" className="h-7 w-7" /> Console</h1>
          <p className="m-0 text-xs text-slate-500">Owner-only — user data never leaves this view.</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <button className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110" onClick={() => setAdding(!adding)}>+ Add user</button>
          {data && <button className="rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-600 hover:border-brand-violet" onClick={exportCsv}>⬇ CSV</button>}
          <button className="rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-600 hover:border-brand-violet" onClick={load} disabled={loading}>{loading ? '…' : '🔄'}</button>
        </div>
      </div>

      {msg && <p className={`mb-3 rounded-xl px-4 py-2.5 text-sm ${msg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{msg.text}</p>}
      {error && <p className="mb-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{error}</p>}

      {adding && (
        <form onSubmit={addUser} className="mb-4 grid gap-3 rounded-2xl border-2 border-brand-violet bg-brand-gradient-soft p-4">
          <h2 className="m-0 text-sm font-extrabold">Add a user manually</h2>
          <div className="grid gap-3 sm:grid-cols-2">
            <label className="grid gap-1 text-xs font-bold">Email
              <input type="email" className={inputCls} value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} placeholder="person@example.com" required /></label>
            <label className="grid gap-1 text-xs font-bold">Temporary password
              <input className={inputCls} value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} placeholder="10+ chars, letters & numbers" required /></label>
          </div>
          <label className="flex items-center gap-2 text-xs font-bold">
            <input type="checkbox" className="h-4 w-4 accent-violet-600" checked={form.autoConfirm} onChange={(e) => setForm({ ...form, autoConfirm: e.target.checked })} />
            Mark email confirmed (skip verification email)</label>
          <p className="m-0 text-[11px] text-slate-500">They sign in with this temp password, then change it under Settings → Security. Adding a user also adds them to the invite allowlist.</p>
          <div className="flex gap-2">
            <button className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white disabled:opacity-60" disabled={working}>{working ? 'Creating…' : 'Create user'}</button>
            <button type="button" className="rounded-lg border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600" onClick={() => setAdding(false)}>Cancel</button>
          </div>
        </form>
      )}

      {loading && !data && <div className="grid place-items-center py-20"><div className="h-9 w-9 animate-spin rounded-full border-4 border-slate-200 border-t-brand-violet" /></div>}

      {data && (
        <div className="grid gap-4">
          <section className="grid grid-cols-3 gap-3">
            <Stat label="Users" value={data.totals?.users ?? '—'} />
            <Stat label="Total storage" value={fmtBytes(data.totals?.storage_bytes)} />
            <Stat label="Stored objects" value={(data.totals?.objects ?? 0).toLocaleString()} />
          </section>

          <div className="flex items-center justify-between gap-2">
            <h2 className="m-0 text-sm font-extrabold tracking-tight">Users ({users.length})</h2>
            <input type="search" className={`${inputCls} w-40 px-2 py-1.5 text-xs sm:w-56`} placeholder="Search users…" value={q} onChange={(e) => setQ(e.target.value)} />
          </div>

          <section className="grid gap-3">
            {users.map((u) => (
              <article key={u.id} className={`rounded-2xl border bg-white p-4 shadow-sm ${u.banned ? 'border-red-300' : 'border-slate-200'}`}>
                <div className="flex flex-wrap items-start justify-between gap-2">
                  <div className="flex items-center gap-3">
                    <span className={`grid h-10 w-10 place-items-center rounded-full text-lg font-extrabold text-white ${u.banned ? 'bg-slate-400' : 'bg-brand-gradient'}`}>{(u.display_name || u.email)[0].toUpperCase()}</span>
                    <div>
                      <p className="m-0 text-sm font-extrabold">{u.display_name || u.email.split('@')[0]} {u.banned && <span className="text-xs font-bold text-red-500">· SUSPENDED</span>}</p>
                      <p className="m-0 text-xs text-slate-500">{u.email}</p>
                      <p className="m-0 mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-slate-500">
                        <span className={`rounded-full px-2 py-0.5 font-bold ${roleMeta(u.role).cls}`} title={roleMeta(u.role).desc}>{roleMeta(u.role).label}</span>
                        <span className={u.confirmed ? 'font-bold text-emerald-600' : 'font-bold text-amber-600'}>{u.confirmed ? '✓ Confirmed' : '⏳ Unconfirmed'}</span>
                        <span className={u.mfa_enabled ? 'font-bold text-emerald-600' : 'text-slate-400'}>{u.mfa_enabled ? '🔐 2FA on' : '🔓 2FA off'}</span>
                        <span title="Last sign-in IP, masked for privacy">🌐 {u.last_ip || 'no record'}</span>
                      </p>
                    </div>
                  </div>
                  <div className="text-right text-[11px] text-slate-500">
                    <p className="m-0">Joined {fmtDate(u.created_at)}</p>
                    <p className="m-0">Last seen {fmtWhen(u.last_sign_in_at)}</p>
                    <button className="mt-1 rounded-lg border-2 border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 hover:border-brand-violet" onClick={() => { setManage(manage === u.email ? null : u.email); setTempPw('') }}>{manage === u.email ? 'Close' : 'Manage'}</button>
                  </div>
                </div>
                <div className="mt-3 grid grid-cols-3 gap-2 text-center sm:grid-cols-6">
                  {[['Storage', fmtBytes(u.storage_bytes)], ['Files', u.storage_files], ['Notes', `${u.notes}${u.notebooks ? ` / ${u.notebooks}📓` : ''}`], ['To-dos', `${u.todos_open}o · ${u.todos_done}d`], ['Events', `${u.events}${u.calendars ? ` / ${u.calendars}📅` : ''}`], ['Favorites', u.favorites]].map(([label, value]) => (
                    <div key={label} className="rounded-lg bg-slate-50 px-1 py-1.5"><p className="m-0 text-xs font-extrabold">{value}</p><p className="m-0 text-[10px] font-semibold text-slate-500">{label}</p></div>
                  ))}
                </div>
                {manage === u.email && (
                  <div className="mt-3 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div className="flex flex-wrap gap-2">
                      <button className="rounded-lg border-2 border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:border-brand-violet disabled:opacity-50" disabled={working} onClick={() => doOp('reset_email', u.email)}>📧 Send reset email</button>
                      <button className="rounded-lg border-2 border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:border-brand-violet disabled:opacity-50" disabled={working || !u.mfa_enabled} onClick={() => doOp('reset_mfa', u.email, {}, `Reset 2FA for ${u.email}?`)}>🔐 Reset 2FA</button>
                      {!u.confirmed && <button className="rounded-lg border-2 border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:border-brand-violet disabled:opacity-50" disabled={working} onClick={() => doOp('resend_confirm', u.email)}>✓ Confirm email</button>}
                      {u.email !== OWNER && (
                        <button className="rounded-lg border-2 border-amber-200 bg-white px-3 py-1.5 text-xs font-bold text-amber-600 hover:border-amber-400 disabled:opacity-50" disabled={working} onClick={() => doOp('suspend', u.email, { ban: !u.banned }, u.banned ? null : `Suspend ${u.email}? They won't be able to sign in.`)}>{u.banned ? '▶️ Reactivate' : '⏸ Suspend'}</button>
                      )}
                      {u.email !== OWNER && (
                        <button className="rounded-lg border-2 border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-500 hover:border-red-400 disabled:opacity-50" disabled={working} onClick={() => doOp('remove_user', u.email, {}, `Permanently remove ${u.email} and all their data? This cannot be undone.`)}>🗑 Remove</button>
                      )}
                    </div>
                    {u.email !== OWNER && (
                      <label className="grid gap-1 text-xs font-bold">Role (permissions)
                        <select className={inputCls} value={u.role} disabled={working} onChange={(e) => doOp('set_role', u.email, { role: e.target.value })}>
                          {ROLES.map((r) => <option key={r.id} value={r.id}>{r.label} — {r.desc}</option>)}
                        </select>
                      </label>
                    )}
                    <div className="flex flex-wrap items-end gap-2">
                      <label className="grid gap-1 text-xs font-bold">Set temporary password
                        <input className={inputCls} value={tempPw} onChange={(e) => setTempPw(e.target.value)} placeholder="10+ chars, letters & numbers" /></label>
                      <button className="rounded-lg bg-brand-gradient px-3 py-2 text-xs font-bold text-white disabled:opacity-50" disabled={working || !tempPw} onClick={() => doOp('set_password', u.email, { password: tempPw })}>Set password</button>
                    </div>
                    <p className="m-0 text-[11px] text-slate-400">Passwords are stored hashed and can't be shown — email a reset link or set a temporary one.</p>
                  </div>
                )}
              </article>
            ))}
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 className="m-0 mb-2 text-sm font-extrabold tracking-tight">Recent activity</h2>
            <ul className="m-0 grid list-none gap-1 p-0">
              {(data.activity || []).map((a, i) => (
                <li key={i} className="flex flex-wrap items-center gap-x-2 text-xs text-slate-500">
                  <span className="font-semibold text-ink">{actionLabel(a.action)}</span>
                  {a.actor && <span>· {a.actor}</span>}
                  {a.ip && <span>· 🌐 {a.ip}</span>}
                  <span className="ml-auto">{fmtActAt(a.at)}</span>
                </li>
              ))}
              {(!data.activity || data.activity.length === 0) && <li className="text-xs text-slate-400">No recent activity recorded.</li>}
            </ul>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 className="m-0 mb-2 text-sm font-extrabold tracking-tight">Invite allowlist</h2>
            <ul className="m-0 grid list-none gap-1 p-0">
              {(data.allowed || []).map((a) => {
                const signedUp = (data.users || []).some((u) => u.email.toLowerCase() === a.email.toLowerCase())
                return (<li key={a.email} className="flex items-center justify-between gap-2 text-sm"><span className="font-semibold">{a.email}</span><span className={`text-xs font-bold ${signedUp ? 'text-emerald-600' : 'text-slate-400'}`}>{signedUp ? 'Signed up' : 'Invited — not signed up'}</span></li>)
              })}
            </ul>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 className="m-0 mb-2 text-sm font-extrabold tracking-tight">Roles & permissions</h2>
            <ul className="m-0 grid list-none gap-1.5 p-0">
              {ROLES.map((r) => (
                <li key={r.id} className="flex items-center gap-2 text-xs">
                  <span className={`shrink-0 rounded-full px-2 py-0.5 font-bold ${r.cls}`}>{r.label}</span>
                  <span className="text-slate-500">{r.desc}</span>
                </li>
              ))}
            </ul>
            <p className="m-0 mt-2 text-[11px] text-slate-400">Subscriber is enforced at the database level — read-only users are blocked from writing even via the API.</p>
          </section>

          <p className="m-0 text-center text-[11px] text-slate-400">🔒 IP addresses are masked in the database (last two segments hidden) so full addresses never reach this page.</p>
        </div>
      )}
    </main>
  )
}
