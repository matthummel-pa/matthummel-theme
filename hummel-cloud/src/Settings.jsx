import { useEffect, useState } from 'react'
import { supabase } from './supabase.js'
import { getSettings, saveSettings, applyTheme, applyA11y, MARKETS, MARKET_BY_COUNTRY, NOTES_SERVICES } from './settings.js'
import { supabase as sb } from './supabase.js'
import { CLAUDE_MODELS, getClaudeConfig, saveClaudeConfig, clearClaudeKey, askClaude } from './claude.js'
import AppIcon from './AppIcon.jsx'

const selectCls =
  'rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm font-semibold outline-none transition focus:border-brand-violet'

function Card({ title, desc, children }) {
  return (
    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 className="m-0 text-base font-extrabold tracking-tight">{title}</h2>
      {desc && <p className="mb-0 mt-1 text-xs text-slate-500">{desc}</p>}
      <div className="mt-4 grid gap-4">{children}</div>
    </section>
  )
}

function Row({ label, hint, children }) {
  return (
    <label className="flex flex-wrap items-center justify-between gap-3">
      <span className="grid gap-0.5">
        <span className="text-sm font-bold">{label}</span>
        {hint && <span className="text-xs text-slate-500">{hint}</span>}
      </span>
      {children}
    </label>
  )
}

export default function Settings({ session }) {
  const [settings, setSettings] = useState(getSettings)
  const DEFAULT_PRIVACY = { searchable: true, allow_requests: true, show_email: false, show_birthday: true, visibility: 'circle' }
  const [privacy, setPrivacy] = useState(DEFAULT_PRIVACY)
  const [privacyMsg, setPrivacyMsg] = useState(null)
  const [members, setMembers] = useState([])
  const [blocks, setBlocks] = useState([])
  const [blockQ, setBlockQ] = useState('')
  const [newEmail, setNewEmail] = useState('')
  const [emailMsg, setEmailMsg] = useState(null)
  const [dataMsg, setDataMsg] = useState(null)
  const [delText, setDelText] = useState('')
  const [delBusy, setDelBusy] = useState(false)
  const [pw, setPw] = useState('')
  const [pw2, setPw2] = useState('')
  const [pwBusy, setPwBusy] = useState(false)
  const [msg, setMsg] = useState(null)
  const [claude, setClaude] = useState(getClaudeConfig)
  const [claudeBusy, setClaudeBusy] = useState(false)
  const [claudeMsg, setClaudeMsg] = useState(null)
  const [locBusy, setLocBusy] = useState(false)
  const [locMsg, setLocMsg] = useState(null)
  const [factors, setFactors] = useState([])
  const [enroll, setEnroll] = useState(null) // { id, qr, secret, code }
  const [mfaMsg, setMfaMsg] = useState(null)

  useEffect(() => {
    sb.auth.mfa.listFactors().then(({ data }) => setFactors((data?.totp || []).filter((f) => f.status === 'verified')))
  }, [])

  useEffect(() => {
    sb.from('profiles').select('privacy').eq('id', session.user.id).maybeSingle().then(({ data }) => {
      if (data?.privacy && typeof data.privacy === 'object') setPrivacy({ ...DEFAULT_PRIVACY, ...data.privacy })
    })
  }, []) // eslint-disable-line

  useEffect(() => {
    sb.from('profiles').select('id, display_name, avatar_path').then(({ data }) => setMembers((data || []).filter((m) => m.id !== session.user.id)))
    sb.from('blocks').select('blocked').then(({ data }) => setBlocks((data || []).map((b) => b.blocked)))
  }, []) // eslint-disable-line

  function update(patch) {
    const next = { ...settings, ...patch }
    setSettings(next)
    saveSettings(next)
    if (patch.theme) applyTheme(patch.theme)
    applyA11y(next)
  }

  async function updatePrivacy(patch) {
    const next = { ...privacy, ...patch }
    setPrivacy(next); setPrivacyMsg(null)
    const { error } = await sb.from('profiles').upsert({ id: session.user.id, privacy: next, updated_at: new Date().toISOString() })
    setPrivacyMsg(error ? { ok: false, text: error.message } : { ok: true, text: 'Privacy settings saved.' })
  }

  async function blockUser(id) {
    setBlocks((b) => [...b, id])
    await sb.from('blocks').insert({ blocker: session.user.id, blocked: id })
  }
  async function unblockUser(id) {
    setBlocks((b) => b.filter((x) => x !== id))
    await sb.from('blocks').delete().eq('blocker', session.user.id).eq('blocked', id)
  }

  async function changeEmail(e) {
    e.preventDefault()
    setEmailMsg(null)
    const em = newEmail.trim()
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(em)) return setEmailMsg({ ok: false, text: 'Enter a valid email address.' })
    const { error } = await supabase.auth.updateUser({ email: em })
    if (error) return setEmailMsg({ ok: false, text: error.message })
    setNewEmail('')
    setEmailMsg({ ok: true, text: 'Check both your old and new inbox to confirm the change.' })
  }

  async function downloadData() {
    setDataMsg({ ok: true, text: 'Gathering your data…' })
    try {
      const tables = ['profiles', 'notes', 'notebooks', 'todos', 'events', 'calendars', 'favorites', 'posts', 'feed_posts']
      const out = { exported_at: new Date().toISOString(), account: { id: session.user.id, email: session.user.email, created_at: session.user.created_at } }
      for (const t of tables) { const { data } = await sb.from(t).select('*'); out[t] = data || [] }
      const blob = new Blob([JSON.stringify(out, null, 2)], { type: 'application/json' })
      const a = document.createElement('a')
      a.href = URL.createObjectURL(blob)
      a.download = `keepary-data-${new Date().toISOString().slice(0, 10)}.json`
      a.click()
      URL.revokeObjectURL(a.href)
      setDataMsg({ ok: true, text: '✓ Your data downloaded as a JSON file.' })
    } catch (err) {
      setDataMsg({ ok: false, text: 'Could not export data: ' + (err?.message || err) })
    }
  }

  async function deactivateAccount() {
    if (!window.confirm('Deactivate your account?\n\nYour profile, posts and presence are hidden right away. Sign back in any time within 30 days to reactivate exactly where you left off. After 30 days your account and all data are permanently deleted.')) return
    setDelBusy(true)
    const { error } = await sb.from('profiles').upsert({ id: session.user.id, deactivated_at: new Date().toISOString(), updated_at: new Date().toISOString() })
    if (error) { setDelBusy(false); return setMsg({ ok: false, text: error.message }) }
    await supabase.auth.signOut()
    window.location.reload()
  }

  const pwScore = (() => {
    let n = 0
    if (pw.length >= 10) n++
    if (pw.length >= 14) n++
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) n++
    if (/\d/.test(pw)) n++
    if (/[^A-Za-z0-9]/.test(pw)) n++
    return Math.min(n, 4)
  })()
  const PW_LABELS = ['Too weak', 'Weak', 'Fair', 'Good', 'Strong']
  const PW_COLORS = ['bg-red-400', 'bg-orange-400', 'bg-amber-400', 'bg-lime-500', 'bg-emerald-500']

  async function changePassword(e) {
    e.preventDefault()
    setMsg(null)
    if (pw.length < 10 || !/[a-zA-Z]/.test(pw) || !/\d/.test(pw)) {
      return setMsg({ ok: false, text: 'Use at least 10 characters with letters and numbers.' })
    }
    if (pw !== pw2) return setMsg({ ok: false, text: 'Passwords do not match.' })
    setPwBusy(true)
    const { error } = await supabase.auth.updateUser({ password: pw })
    setPwBusy(false)
    if (error) return setMsg({ ok: false, text: error.message })
    setPw(''); setPw2('')
    setMsg({ ok: true, text: 'Password updated.' })
  }

  async function loadFactors() {
    const { data } = await sb.auth.mfa.listFactors()
    setFactors((data?.totp || []).filter((f) => f.status === 'verified'))
  }

  async function startEnroll() {
    setMfaMsg(null)
    // Clear any half-finished (unverified) enrollments so re-enrolling never collides.
    const { data: existing } = await sb.auth.mfa.listFactors()
    const stale = (existing?.all || []).filter((f) => f.status === 'unverified')
    for (const f of stale) await sb.auth.mfa.unenroll({ factorId: f.id }).catch(() => {})
    const { data, error } = await sb.auth.mfa.enroll({ factorType: 'totp', friendlyName: `Authenticator ${Date.now()}` })
    if (error) return setMfaMsg({ ok: false, text: error.message })
    setEnroll({ id: data.id, qr: data.totp.qr_code, secret: data.totp.secret, code: '' })
  }

  async function cancelEnroll() {
    if (enroll?.id) await sb.auth.mfa.unenroll({ factorId: enroll.id }).catch(() => {})
    setEnroll(null)
  }

  async function confirmEnroll() {
    setMfaMsg(null)
    const code = (enroll.code || '').trim()
    if (code.length !== 6) return setMfaMsg({ ok: false, text: 'Enter the 6-digit code from your authenticator app.' })
    const { error } = await sb.auth.mfa.challengeAndVerify({ factorId: enroll.id, code })
    if (error) {
      const msg = /invalid|verif|code/i.test(error.message)
        ? 'That code didn\u2019t match \u2014 make sure your phone\u2019s clock is set automatically and enter the current 6-digit code.'
        : error.message
      return setMfaMsg({ ok: false, text: msg })
    }
    setEnroll(null)
    setMfaMsg({ ok: true, text: '\u2713 Two-factor authentication is ON. You\u2019ll be asked for a code at every sign-in.' })
    loadFactors()
  }

  async function disableMfa() {
    if (!window.confirm('Turn off two-factor authentication? Your account will be protected by password only.')) return
    const { data } = await sb.auth.mfa.listFactors()
    const all = (data?.all || []).length ? data.all : (data?.totp || [])
    let lastErr = null
    for (const fac of all) { const { error } = await sb.auth.mfa.unenroll({ factorId: fac.id }); if (error) lastErr = error }
    if (lastErr) return setMfaMsg({ ok: false, text: lastErr.message })
    setMfaMsg({ ok: true, text: 'Two-factor authentication turned off.' })
    loadFactors()
  }

  function detectLocation() {
    if (!navigator.geolocation) return setLocMsg({ ok: false, text: 'Location is not available in this browser.' })
    const consent = window.confirm(
      'Allow Keepary to use your location?\n\n' +
        'It is used only to set your local news and language preferences, and to add a local-news shortcut on the Trending page. ' +
        'Your city/region is saved on this device only — never on a server.\n\n' +
        'Your browser will also ask for permission next.'
    )
    if (!consent) {
      setLocMsg({ ok: false, text: 'Location detection cancelled — you can pick a market manually below.' })
      return
    }
    setLocBusy(true)
    setLocMsg(null)
    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        try {
          const { latitude, longitude } = pos.coords
          const res = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${latitude}&longitude=${longitude}&localityLanguage=en`)
          const j = await res.json()
          const location = {
            city: j.city || j.locality || 'Unknown',
            region: j.principalSubdivision || '',
            country: j.countryName || '',
            countryCode: j.countryCode || 'US'
          }
          const market = MARKET_BY_COUNTRY[location.countryCode] || 'en-US'
          update({ location, market })
          setLocMsg({ ok: true, text: `📍 Found you: ${location.city}${location.region ? ', ' + location.region : ''}, ${location.country}. News language set to ${market}.` })
        } catch {
          setLocMsg({ ok: false, text: 'Could not look up your location. Pick a market manually below.' })
        }
        setLocBusy(false)
      },
      (err) => {
        setLocBusy(false)
        setLocMsg({ ok: false, text: err?.message || 'Location permission denied — pick a market manually below.' })
      },
      { timeout: 15000 }
    )
  }

  function updateClaude(patch) {
    const next = { ...claude, ...patch }
    setClaude(next)
    saveClaudeConfig(next)
  }

  async function testClaude() {
    setClaudeBusy(true)
    setClaudeMsg(null)
    try {
      await askClaude('Reply with exactly: ready', 16)
      setClaudeMsg({ ok: true, text: '✓ Connected! Claude is ready to help write posts in the Blog tab.' })
    } catch (e) {
      setClaudeMsg({ ok: false, text: e.message })
    }
    setClaudeBusy(false)
  }

  async function signOutEverywhere() {
    if (!window.confirm('Sign out on all devices (including this one)?')) return
    await supabase.auth.signOut({ scope: 'global' })
  }

  return (
    <main className="mx-auto w-full max-w-2xl flex-1 px-3 py-5 sm:px-7">
      <h1 className="mb-4 mt-0 text-xl font-extrabold tracking-tight flex items-center gap-2"><AppIcon id="settings" className="h-7 w-7" /> Settings</h1>
      <div className="grid gap-4">
        <Card title="Appearance" desc="How Keepary looks on this device.">
          <Row label="Theme" hint="Switch between light and dark, or follow your device.">
            <div className="flex gap-1 rounded-lg bg-slate-100 p-1">
              {[['light', '☀️ Light'], ['dark', '🌙 Dark'], ['system', '🖥 System']].map(([v, lbl]) => (
                <button key={v} type="button" onClick={() => update({ theme: v })}
                  className={`rounded-md px-3 py-1.5 text-xs font-bold transition ${settings.theme === v ? 'bg-brand-gradient text-white shadow' : 'text-slate-600 hover:text-brand-violet'}`}>{lbl}</button>
              ))}
            </div>
          </Row>
        </Card>

        <Card title="Accessibility" desc="Make Keepary easier to read and use. Saved on this device.">
          <Row label="Text size" hint="Scales text across the whole app.">
            <select className={selectCls} value={settings.textScale} onChange={(e) => update({ textScale: e.target.value })}>
              <option value="normal">Default</option>
              <option value="large">Large</option>
              <option value="xlarge">Extra large</option>
            </select>
          </Row>
          <Row label="High contrast" hint="Stronger borders, darker text, visible focus outlines.">
            <input type="checkbox" className="h-5 w-5 accent-violet-600" checked={settings.highContrast} onChange={(e) => update({ highContrast: e.target.checked })} />
          </Row>
          <Row label="Reduce motion" hint="Turns off animations and smooth transitions.">
            <input type="checkbox" className="h-5 w-5 accent-violet-600" checked={settings.reduceMotion} onChange={(e) => update({ reduceMotion: e.target.checked })} />
          </Row>
          <Row label="Dyslexia-friendly font" hint="Switches to a more legible typeface with looser spacing.">
            <input type="checkbox" className="h-5 w-5 accent-violet-600" checked={settings.dyslexiaFont} onChange={(e) => update({ dyslexiaFont: e.target.checked })} />
          </Row>
          <Row label="Always underline links" hint="Makes links easier to spot.">
            <input type="checkbox" className="h-5 w-5 accent-violet-600" checked={settings.underlineLinks} onChange={(e) => update({ underlineLinks: e.target.checked })} />
          </Row>
        </Card>

        <Card title="Privacy" desc="Control who can find you and what's shown on your profile. Saved to your account.">
          <Row label="Discoverable in search" hint="Let others find you when searching people.">
            <input type="checkbox" className="h-5 w-5 accent-violet-600" checked={privacy.searchable} onChange={(e) => updatePrivacy({ searchable: e.target.checked })} />
          </Row>
          <Row label="Allow friend requests" hint="Others can send you a request to connect.">
            <input type="checkbox" className="h-5 w-5 accent-violet-600" checked={privacy.allow_requests} onChange={(e) => updatePrivacy({ allow_requests: e.target.checked })} />
          </Row>
          <Row label="Who can see my profile">
            <select className={selectCls} value={privacy.visibility} onChange={(e) => updatePrivacy({ visibility: e.target.value })}>
              <option value="circle">Everyone in my circle</option>
              <option value="friends">Friends only</option>
              <option value="private">Only me</option>
            </select>
          </Row>
          <Row label="Show my email on profile" hint="Off by default.">
            <input type="checkbox" className="h-5 w-5 accent-violet-600" checked={privacy.show_email} onChange={(e) => updatePrivacy({ show_email: e.target.checked })} />
          </Row>
          <Row label="Show my birthday" hint="Display your birthday on MyCard.">
            <input type="checkbox" className="h-5 w-5 accent-violet-600" checked={privacy.show_birthday} onChange={(e) => updatePrivacy({ show_birthday: e.target.checked })} />
          </Row>
          <Row label="Show my activity status" hint="Let friends see when you were last active.">
            <input type="checkbox" className="h-5 w-5 accent-violet-600" checked={privacy.activity_status !== false} onChange={(e) => updatePrivacy({ activity_status: e.target.checked })} />
          </Row>
          <Row label="Default audience for new posts" hint="Who sees posts you share by default.">
            <select className={selectCls} value={privacy.default_audience || 'circle'} onChange={(e) => updatePrivacy({ default_audience: e.target.value })}>
              <option value="circle">Everyone in my circle</option>
              <option value="friends">Friends only</option>
            </select>
          </Row>
          <div className="grid gap-2 border-t border-slate-100 pt-3">
            <span className="text-sm font-bold">Blocked people</span>
            <span className="text-xs text-slate-500">Blocked people can't find you in search or send you requests.</span>
            {blocks.length > 0 && (
              <ul className="m-0 grid list-none gap-1.5 p-0">
                {blocks.map((id) => {
                  const m = members.find((x) => x.id === id)
                  return (
                    <li key={id} className="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-1.5">
                      <span className="truncate text-sm font-semibold">{m?.display_name || 'Member'}</span>
                      <button className="text-xs font-bold text-brand-blue hover:underline" onClick={() => unblockUser(id)}>Unblock</button>
                    </li>
                  )
                })}
              </ul>
            )}
            <input type="search" className={selectCls + ' w-full'} placeholder="Search people to block…" value={blockQ} onChange={(e) => setBlockQ(e.target.value)} />
            {blockQ.trim() && (
              <ul className="m-0 grid max-h-44 list-none gap-1 overflow-auto p-0">
                {members.filter((m) => !blocks.includes(m.id) && (m.display_name || '').toLowerCase().includes(blockQ.trim().toLowerCase())).slice(0, 6).map((m) => (
                  <li key={m.id} className="flex items-center justify-between gap-2 px-1">
                    <span className="truncate text-sm">{m.display_name || 'Member'}</span>
                    <button className="rounded-lg border-2 border-slate-200 px-2.5 py-1 text-xs font-bold text-red-500 hover:border-red-300" onClick={() => blockUser(m.id)}>Block</button>
                  </li>
                ))}
              </ul>
            )}
          </div>
          {privacyMsg && <p className={`m-0 rounded-xl px-4 py-2.5 text-sm ${privacyMsg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{privacyMsg.text}</p>}
        </Card>

        <Card title="Defaults" desc="Saved on this device.">
          <Row label="Start page" hint="What you see right after signing in.">
            <select className={selectCls} value={settings.startView} onChange={(e) => update({ startView: e.target.value })}>
              <option value="home">🏠 Hub</option>
              <option value="blog">💬 Feed</option>
              <option value="chat">💬 Chat</option>
              <option value="profile">🪪 MyCard</option>
              <option value="files">📁 Vault</option>
            </select>
          </Row>
          <Row label="Default file sorting">
            <select className={selectCls} value={settings.fileSort} onChange={(e) => update({ fileSort: e.target.value })}>
              <option value="name">Name</option>
              <option value="date">Date</option>
              <option value="size">Size</option>
            </select>
          </Row>
          <Row label="Share link expiry" hint="How long file share links stay valid.">
            <select className={selectCls} value={settings.shareDays} onChange={(e) => update({ shareDays: Number(e.target.value) })}>
              <option value="1">1 day</option>
              <option value="7">7 days</option>
              <option value="30">30 days</option>
            </select>
          </Row>
          <Row label="Confirm before deleting" hint="Ask before files or folders are removed.">
            <input
              type="checkbox"
              className="h-5 w-5 accent-violet-600"
              checked={settings.confirmDelete}
              onChange={(e) => update({ confirmDelete: e.target.checked })}
            />
          </Row>
        </Card>

        <Card title="Security" desc="Protect your account the way the big apps do.">
          <div className="grid gap-2">
            <span className="text-sm font-bold">🔐 Two-factor authentication (2FA)</span>
            {factors.length > 0 ? (
              <div className="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-emerald-50 px-4 py-3">
                <span className="text-sm font-bold text-emerald-700">✓ ON — a code from your authenticator app is required at sign-in.</span>
                <button className="rounded-lg border-2 border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-500 hover:border-red-400" onClick={() => disableMfa()}>
                  Turn off
                </button>
              </div>
            ) : enroll ? (
              <div className="grid gap-3 rounded-xl border-2 border-brand-violet bg-brand-gradient-soft p-4">
                <p className="m-0 text-sm">
                  <strong>1.</strong> Scan this QR code with Google Authenticator, Microsoft Authenticator, 1Password, or any authenticator app:
                </p>
                {(() => {
                  const qr = enroll.qr || ''
                  let markup = null
                  if (qr.trim().startsWith('<')) markup = qr
                  else if (qr.startsWith('data:image/svg+xml') && !qr.includes(';base64,')) {
                    markup = qr.slice(qr.indexOf(',') + 1)
                    try { markup = decodeURIComponent(markup) } catch { /* already raw */ }
                  }
                  return (
                    <div className="flex justify-center">
                      {markup
                        ? <div className="grid h-44 w-44 place-items-center rounded-xl bg-white p-2 [&_svg]:h-full [&_svg]:w-full [&_svg]:block" dangerouslySetInnerHTML={{ __html: markup }} />
                        : <img src={qr} alt="2FA QR code" className="h-44 w-44 rounded-xl bg-white p-2 object-contain" />}
                    </div>
                  )
                })()}
                <p className="m-0 break-all text-center text-[11px] text-slate-500">Can't scan? Enter this key manually: <code className="font-bold">{enroll.secret}</code></p>
                <button type="button" className="mx-auto text-xs font-bold text-brand-blue hover:underline" onClick={startEnroll}>↻ Refresh QR code</button>
                <p className="m-0 text-sm"><strong>2.</strong> Enter the 6-digit code the app shows:</p>
                <div className="flex gap-2">
                  <input
                    inputMode="numeric"
                    maxLength={6}
                    className="w-32 rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-center text-lg font-extrabold tracking-widest outline-none focus:border-brand-violet"
                    placeholder="••••••"
                    value={enroll.code}
                    onChange={(e) => setEnroll({ ...enroll, code: e.target.value.replace(/\D/g, '') })}
                  />
                  <button className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white disabled:opacity-50" disabled={enroll.code.length !== 6} onClick={confirmEnroll}>
                    Activate 2FA
                  </button>
                  <button className="text-sm font-semibold text-slate-500" onClick={cancelEnroll}>Cancel</button>
                </div>
              </div>
            ) : (
              <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="text-xs text-slate-500">Adds a second lock: even with your password, no one gets in without your phone.</span>
                <button className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110" onClick={startEnroll}>
                  Enable 2FA
                </button>
              </div>
            )}
            {mfaMsg && <p className={`m-0 rounded-xl px-4 py-2.5 text-sm ${mfaMsg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{mfaMsg.text}</p>}
          </div>
          <Row label="Auto sign-out" hint="Automatically sign out after inactivity on this device.">
            <select className={selectCls} value={settings.autoLock} onChange={(e) => update({ autoLock: Number(e.target.value) })}>
              <option value="0">Never</option>
              <option value="15">After 15 minutes</option>
              <option value="60">After 1 hour</option>
              <option value="240">After 4 hours</option>
            </select>
          </Row>
        </Card>

        <Card title="Location & language" desc="Sets the language and region used for Trending news, and adds a local-news shortcut.">
          <Row label="Your location" hint={settings.location ? `📍 ${settings.location.city}${settings.location.region ? ', ' + settings.location.region : ''}, ${settings.location.country}` : 'Not set — detect it or just pick a market below.'}>
            <div className="flex gap-2">
              <button
                className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-60"
                onClick={detectLocation}
                disabled={locBusy}
              >
                {locBusy ? 'Locating…' : '📍 Detect my location'}
              </button>
              {settings.location && (
                <button
                  className="rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-600 transition hover:border-red-400 hover:text-red-500"
                  onClick={() => { update({ location: null }); setLocMsg(null) }}
                >
                  Clear
                </button>
              )}
            </div>
          </Row>
          <Row label="News market & language" hint="Trending articles come from this region, in its language.">
            <select className={selectCls} value={settings.market} onChange={(e) => update({ market: e.target.value })}>
              {MARKETS.map((m) => (
                <option key={m.id} value={m.id}>{m.label}</option>
              ))}
            </select>
          </Row>
          {locMsg && (
            <p className={`m-0 rounded-xl px-4 py-2.5 text-sm ${locMsg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{locMsg.text}</p>
          )}
        </Card>

        <Card title="Trending & voice" desc="Reset stored preferences for the Trending page.">
          <Row label="Trending categories" hint="Restore the default four categories.">
            <button
              className="rounded-lg border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet"
              onClick={() => { localStorage.removeItem('hc-trending-categories'); setMsg({ ok: true, text: 'Trending categories reset.' }) }}
            >
              Reset
            </button>
          </Row>
          <Row label="Reading voice & speed" hint="Forget the saved summary voice settings.">
            <button
              className="rounded-lg border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet"
              onClick={() => { localStorage.removeItem('hc-voice'); localStorage.removeItem('hc-voice-rate'); setMsg({ ok: true, text: 'Voice settings reset.' }) }}
            >
              Reset
            </button>
          </Row>
        </Card>

        <Card title="Notes publishing" desc="Choose where the 🚀 Publish button on the Notes page sends your notes.">
          <Row label="Service">
            <select className={selectCls} value={settings.notesService} onChange={(e) => update({ notesService: e.target.value })}>
              {NOTES_SERVICES.map((a) => (
                <option key={a.id} value={a.id}>{a.label}</option>
              ))}
            </select>
          </Row>
          {settings.notesService === 'notion' ? (
            <>
              <label className="grid gap-1.5">
                <span className="text-sm font-bold">Notion integration token</span>
                <input
                  type="password"
                  className="rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-violet"
                  placeholder="ntn_… or secret_…"
                  value={settings.notionToken}
                  onChange={(e) => update({ notionToken: e.target.value.trim() })}
                  autoComplete="off"
                />
                <span className="text-xs text-slate-500">
                  Create a free internal integration at{' '}
                  <a className="text-brand-blue hover:underline" href="https://www.notion.so/my-integrations" target="_blank" rel="noopener noreferrer">notion.so/my-integrations</a>, copy its token here. Stored on this device only.
                </span>
              </label>
              <label className="grid gap-1.5">
                <span className="text-sm font-bold">Parent page (link or ID)</span>
                <input
                  className="rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-violet"
                  placeholder="https://www.notion.so/My-Notes-1a2b3c…"
                  value={settings.notionParent}
                  onChange={(e) => update({ notionParent: e.target.value.trim() })}
                  autoComplete="off"
                />
                <span className="text-xs text-slate-500">
                  In Notion, open the page where published notes should land → ••• menu → <strong>Connections</strong> → add your integration. Then paste the page link here.
                </span>
              </label>
              <button
                className="w-fit rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-60"
                disabled={!settings.notionToken}
                onClick={async () => {
                  setMsg(null)
                  const { data: res, error } = await sb.functions.invoke('notion', { body: { op: 'test', token: settings.notionToken } })
                  if (error || res?.error) setMsg({ ok: false, text: res?.error || 'Could not reach Notion.' })
                  else setMsg({ ok: true, text: `✓ Connected to Notion as "${res.bot}".` })
                }}
              >
                Test Notion connection
              </button>
            </>
          ) : settings.notesService === 'goodnotes' ? (
            <p className="m-0 rounded-xl bg-brand-gradient-soft px-4 py-3 text-xs leading-relaxed text-slate-600">
              GoodNotes doesn't offer a public API, so publishing exports your note as a print-ready page — choose <strong>"Save as PDF"</strong> in the print dialog, then import that PDF into GoodNotes (it opens beautifully as annotatable pages). On iPhone/iPad you can share the PDF straight into the GoodNotes app.
            </p>
          ) : (
            <p className="m-0 rounded-xl bg-brand-gradient-soft px-4 py-3 text-xs leading-relaxed text-slate-600">
              Standalone mode — your notes and notebooks live entirely in Keepary, private to you. No external accounts needed. Switch to Notion or GoodNotes here anytime to start publishing.
            </p>
          )}
        </Card>

        <Card title="Claude AI" desc="Connect your Claude account with an Anthropic API key to generate social posts and writing prompts in the Blog tab. The key is stored only on this device.">
          <label className="grid gap-1.5">
            <span className="text-sm font-bold">Anthropic API key</span>
            <input
              type="password"
              className="rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-violet"
              placeholder="sk-ant-…"
              value={claude.key}
              onChange={(e) => updateClaude({ key: e.target.value.trim() })}
              autoComplete="off"
            />
            <span className="text-xs text-slate-500">
              Create one at{' '}
              <a className="text-brand-blue hover:underline" href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener noreferrer">
                console.anthropic.com
              </a>{' '}
              (API access is billed separately from a Claude/Cowork subscription).
            </span>
          </label>
          <Row label="Model" hint="Haiku is fast and inexpensive; Sonnet writes the best posts.">
            <select className={selectCls} value={claude.model} onChange={(e) => updateClaude({ model: e.target.value })}>
              {CLAUDE_MODELS.map((m) => (
                <option key={m.id} value={m.id}>{m.label}</option>
              ))}
            </select>
          </Row>
          <div className="flex flex-wrap gap-2">
            <button
              className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-60"
              onClick={testClaude}
              disabled={claudeBusy || !claude.key}
            >
              {claudeBusy ? 'Testing…' : 'Test connection'}
            </button>
            <button
              className="rounded-lg border-2 border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-500 transition hover:border-red-400 hover:bg-red-50"
              onClick={() => { clearClaudeKey(); setClaude((c) => ({ ...c, key: '' })); setClaudeMsg({ ok: true, text: 'Key removed from this device.' }) }}
            >
              Remove key
            </button>
          </div>
          {claudeMsg && (
            <p className={`m-0 rounded-xl px-4 py-2.5 text-sm ${claudeMsg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{claudeMsg.text}</p>
          )}
        </Card>

        <Card title="Account" desc={`Signed in as ${session.user.email}`}>
          <div className="grid gap-1 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
            <span><strong className="text-slate-600">Account created:</strong> {new Date(session.user.created_at).toLocaleString()}</span>
            {session.user.last_sign_in_at && <span><strong className="text-slate-600">Last sign-in:</strong> {new Date(session.user.last_sign_in_at).toLocaleString()}</span>}
          </div>
          <form onSubmit={changeEmail} className="grid gap-2">
            <span className="text-sm font-bold">Change email</span>
            <div className="flex flex-wrap gap-2">
              <input type="email" className="min-w-0 flex-1 rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-brand-violet" placeholder="new@email.com" value={newEmail} onChange={(e) => setNewEmail(e.target.value)} autoComplete="off" />
              <button className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white hover:brightness-110 disabled:opacity-60" disabled={!newEmail.trim()}>Update email</button>
            </div>
            {emailMsg && <p className={`m-0 rounded-xl px-4 py-2.5 text-sm ${emailMsg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{emailMsg.text}</p>}
          </form>
          <form onSubmit={changePassword} className="grid gap-3">
            <span className="text-sm font-bold">Change password</span>
            <input
              type="password"
              className="rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-violet"
              placeholder="New password (8+ characters)"
              value={pw}
              onChange={(e) => setPw(e.target.value)}
              autoComplete="new-password"
            />
            <input
              type="password"
              className="rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-violet"
              placeholder="Repeat new password"
              value={pw2}
              onChange={(e) => setPw2(e.target.value)}
              autoComplete="new-password"
            />
            {pw && (
              <div className="grid gap-1">
                <div className="flex gap-1">
                  {[0, 1, 2, 3].map((i) => <span key={i} className={`h-1.5 flex-1 rounded-full ${i < pwScore ? PW_COLORS[pwScore] : 'bg-slate-200'}`} />)}
                </div>
                <span className="text-[11px] font-semibold text-slate-500">Password strength: {PW_LABELS[pwScore]}</span>
              </div>
            )}
            <button
              className="w-fit rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-60"
              disabled={pwBusy || !pw}
            >
              {pwBusy ? 'Updating…' : 'Update password'}
            </button>
          </form>
          <Row label="Sign out everywhere" hint="Ends your session on every device.">
            <button
              className="rounded-lg border-2 border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-500 transition hover:border-red-400 hover:bg-red-50"
              onClick={signOutEverywhere}
            >
              Sign out all
            </button>
          </Row>
        </Card>

        <Card title="Your data" desc="Download a copy of everything stored in your Keepary account.">
          <Row label="Export my data" hint="Profile, notes, notebooks, to-dos, events, calendars, favorites and posts as JSON.">
            <button className="rounded-lg border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet" onClick={downloadData}>⬇ Download</button>
          </Row>
          {dataMsg && <p className={`m-0 rounded-xl px-4 py-2.5 text-sm ${dataMsg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{dataMsg.text}</p>}
        </Card>

        <section className="rounded-2xl border-2 border-amber-200 bg-amber-50/40 p-5 shadow-sm">
          <h2 className="m-0 text-base font-extrabold tracking-tight text-amber-700">Deactivate account</h2>
          <p className="mb-0 mt-1 text-xs text-slate-500">Hide your profile and content. You can come back any time within <strong>30 days</strong> just by signing in — everything is restored. After 30 days, your account and all data are permanently deleted.</p>
          <div className="mt-4">
            <button className="rounded-lg bg-amber-500 px-4 py-2 text-sm font-bold text-white transition hover:bg-amber-600 disabled:opacity-50" disabled={delBusy} onClick={deactivateAccount}>{delBusy ? 'Deactivating…' : 'Deactivate my account'}</button>
          </div>
        </section>

        {msg && (
          <p className={`m-0 rounded-xl px-4 py-3 text-sm ${msg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>
            {msg.text}
          </p>
        )}
      </div>
    </main>
  )
}
