import { useCallback, useEffect, useRef, useState } from 'react'
import { supabase, BUCKET } from './supabase.js'
import AppIcon from './AppIcon.jsx'

const AVATAR_PREFIX = '_avatars'
const COVER_PREFIX = '_covers'

const inputCls =
  'w-full rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-violet'

const SOCIALS = [
  { key: 'bluesky', label: 'Bluesky', ph: 'handle.bsky.social', base: 'https://bsky.app/profile/' },
  { key: 'linkedin', label: 'LinkedIn', ph: 'in/username', base: 'https://linkedin.com/' },
  { key: 'facebook', label: 'Facebook', ph: 'username', base: 'https://facebook.com/' },
  { key: 'instagram', label: 'Instagram', ph: 'username', base: 'https://instagram.com/' },
  { key: 'x', label: 'X', ph: 'username', base: 'https://x.com/' },
  { key: 'github', label: 'GitHub', ph: 'username', base: 'https://github.com/' }
]

const fmtBytes = (b) => {
  if (!b) return '0 B'
  if (b < 1024 ** 2) return `${(b / 1024).toFixed(1)} KB`
  if (b < 1024 ** 3) return `${(b / 1024 ** 2).toFixed(1)} MB`
  return `${(b / 1024 ** 3).toFixed(2)} GB`
}

const normUrl = (u) => (/^https?:/.test(u) ? u : `https://${u}`)

function Avatar({ url, name, className = 'h-20 w-20 text-3xl' }) {
  if (url) return <img src={url} alt="" className={`${className} rounded-full object-cover`} />
  return (
    <span className={`grid ${className} shrink-0 place-items-center rounded-full bg-brand-gradient font-extrabold text-white`}>
      {(name || '?')[0].toUpperCase()}
    </span>
  )
}

function Section({ title, children, action }) {
  return (
    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-center justify-between">
        <h2 className="m-0 text-base font-extrabold tracking-tight">{title}</h2>
        {action}
      </div>
      <div className="mt-3">{children}</div>
    </section>
  )
}

export default function Profile({ session }) {
  const user = session.user
  const blank = {
    display_name: '', headline: '', handle: '', pronouns: '', bio: '', location: '', website: '',
    birthday: '', avatar_path: null, cover_path: null,
    cover_video_path: null, avatar_video_path: null, cover_layout: 'image', cover_message: '',
    work: [], education: [], skills: [], interests: [], socials: {}, featured: []
  }
  const [profile, setProfile] = useState(blank)
  const [draft, setDraft] = useState(blank)
  const [editing, setEditing] = useState(false)
  const [family, setFamily] = useState([])
  const [avatarUrls, setAvatarUrls] = useState({})
  const [saving, setSaving] = useState(false)
  const [msg, setMsg] = useState(null)
  const [stats, setStats] = useState(null)
  const [postCount, setPostCount] = useState(null)
  const fileRef = useRef(null)
  const coverRef = useRef(null)
  const coverVideoRef = useRef(null)
  const avatarVideoRef = useRef(null)

  const signAvatar = useCallback(async (path) => {
    if (!path) return null
    const { data } = await supabase.storage.from(BUCKET).createSignedUrl(path, 3600)
    return data?.signedUrl || null
  }, [])

  const hydrate = (r) => ({
    ...blank, ...r,
    work: Array.isArray(r.work) ? r.work : [],
    education: Array.isArray(r.education) ? r.education : [],
    skills: Array.isArray(r.skills) ? r.skills : [],
    interests: Array.isArray(r.interests) ? r.interests : [],
    socials: r.socials && typeof r.socials === 'object' ? r.socials : {},
    featured: Array.isArray(r.featured) ? r.featured : [],
    birthday: r.birthday || '',
    cover_layout: r.cover_layout || 'image',
    cover_message: r.cover_message || ''
  })

  useEffect(() => {
    ;(async () => {
      const { data } = await supabase.from('profiles').select('*')
      const rows = data || []
      setFamily(rows)
      const mine = rows.find((r) => r.id === user.id)
      if (mine) { const h = hydrate(mine); setProfile(h); setDraft(h) }
      const urls = {}
      for (const r of rows) {
        if (r.avatar_path) urls[r.avatar_path] = await signAvatar(r.avatar_path)
        if (r.cover_path) urls[r.cover_path] = await signAvatar(r.cover_path)
        if (r.cover_video_path) urls[r.cover_video_path] = await signAvatar(r.cover_video_path)
        if (r.avatar_video_path) urls[r.avatar_video_path] = await signAvatar(r.avatar_video_path)
      }
      setAvatarUrls(urls)
    })()
  }, [user.id, signAvatar]) // eslint-disable-line

  useEffect(() => {
    supabase.from('feed_posts').select('id', { count: 'exact', head: true }).eq('user_id', user.id)
      .then(({ count }) => setPostCount(count ?? 0))
  }, [user.id])

  useEffect(() => {
    ;(async () => {
      let files = 0, bytes = 0, scanned = 0
      const walk = async (p) => {
        if (scanned > 1500) return
        const { data } = await supabase.storage.from(BUCKET).list(p, { limit: 1000 })
        for (const it of data || []) {
          scanned++
          if (it.id) { if (it.name !== '.keep') { files++; bytes += it.metadata?.size || 0 } }
          else if (!it.name.startsWith('_')) await walk(p ? `${p}/${it.name}` : it.name)
        }
      }
      await walk('')
      setStats({ files, bytes })
    })()
  }, [])

  function startEdit() { setDraft(profile); setMsg(null); setEditing(true) }
  function cancelEdit() { setDraft(profile); setEditing(false); setMsg(null) }

  async function save(e) {
    e.preventDefault()
    setSaving(true); setMsg(null)
    const row = {
      id: user.id,
      display_name: draft.display_name || null,
      headline: draft.headline || null,
      handle: draft.handle ? draft.handle.replace(/^@/, '') : null,
      pronouns: draft.pronouns || null,
      bio: draft.bio || null,
      location: draft.location || null,
      website: draft.website || null,
      birthday: draft.birthday || null,
      avatar_path: profile.avatar_path || null,
      cover_path: profile.cover_path || null,
      cover_video_path: profile.cover_video_path || null,
      avatar_video_path: profile.avatar_video_path || null,
      cover_layout: draft.cover_layout || 'image',
      cover_message: draft.cover_message || null,
      work: (draft.work || []).filter((w) => w.title || w.org),
      education: (draft.education || []).filter((ed) => ed.school),
      skills: draft.skills || [],
      interests: draft.interests || [],
      socials: draft.socials || {},
      featured: (draft.featured || []).filter((f) => f.url),
      updated_at: new Date().toISOString()
    }
    const { error } = await supabase.from('profiles').upsert(row)
    setSaving(false)
    if (error) return setMsg({ ok: false, text: error.message })
    const h = hydrate(row)
    setProfile(h); setDraft(h); setEditing(false)
    setMsg({ ok: true, text: 'Profile saved.' })
    setFamily((f) => [...f.filter((r) => r.id !== user.id), { ...h, id: user.id }])
  }

  async function uploadMedia(e, target) {
    const f = e.target.files?.[0]; e.target.value = ''
    if (!f) return
    const wantsVideo = target === 'cover_video_path' || target === 'avatar_video_path'
    const isVid = /^video\//.test(f.type)
    const isImg = /^image\//.test(f.type)
    if (wantsVideo && !isVid) return setMsg({ ok: false, text: 'Please choose a video file (MP4 or WebM).' })
    if (!wantsVideo && !isImg) return setMsg({ ok: false, text: 'Please choose an image file (GIF works for an animated photo).' })
    const limits = { avatar_path: 5, cover_path: 8, avatar_video_path: 12, cover_video_path: 25 }
    const max = limits[target] || 8
    if (f.size > max * 1024 * 1024) return setMsg({ ok: false, text: `File must be under ${max} MB.` })
    setMsg(null)
    const ext = (f.name.split('.').pop() || 'bin').toLowerCase()
    const prefix = target.startsWith('cover') ? COVER_PREFIX : AVATAR_PREFIX
    const path = `${prefix}/${user.id}-${target}-${Date.now()}.${ext}`
    const { error } = await supabase.storage.from(BUCKET).upload(path, f, { upsert: true })
    if (error) return setMsg({ ok: false, text: error.message })
    const { error: e2 } = await supabase.from('profiles').upsert({ id: user.id, [target]: path, updated_at: new Date().toISOString() })
    if (e2) return setMsg({ ok: false, text: e2.message })
    const url = await signAvatar(path)
    setProfile((p) => ({ ...p, [target]: path }))
    setDraft((d) => ({ ...d, [target]: path }))
    setAvatarUrls((u) => ({ ...u, [path]: url }))
    setMsg({ ok: true, text: 'Media updated.' })
  }

  async function clearMedia(target) {
    await supabase.from('profiles').upsert({ id: user.id, [target]: null, updated_at: new Date().toISOString() })
    setProfile((p) => ({ ...p, [target]: null }))
    setDraft((d) => ({ ...d, [target]: null }))
    setMsg({ ok: true, text: 'Removed.' })
  }

  // draft array helpers
  const setD = (patch) => setDraft((d) => ({ ...d, ...patch }))
  const addRow = (key, row) => setD({ [key]: [...(draft[key] || []), row] })
  const editRow = (key, i, patch) => setD({ [key]: draft[key].map((r, j) => (j === i ? { ...r, ...patch } : r)) })
  const delRow = (key, i) => setD({ [key]: draft[key].filter((_, j) => j !== i) })

  const joined = new Date(user.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long' })
  const myName = profile.display_name || user.email.split('@')[0]
  const socialEntries = SOCIALS.filter((s) => (profile.socials || {})[s.key])

  return (
    <main className="mx-auto w-full max-w-2xl flex-1 px-3 py-5 sm:px-7">
      <h1 className="mb-4 mt-0 flex items-center gap-2 text-xl font-extrabold tracking-tight">
        <AppIcon id="card" className="h-7 w-7" /> MyCard
      </h1>
      <div className="grid gap-4">
        {/* Header */}
        <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div className="relative h-32">
            {(() => {
              const layout = profile.cover_layout || 'image'
              const img = profile.cover_path && avatarUrls[profile.cover_path]
              const vid = profile.cover_video_path && avatarUrls[profile.cover_video_path]
              if (layout === 'split' && (img || vid)) {
                return (
                  <div className="grid h-32 w-full grid-cols-2">
                    {img ? <img src={img} alt="" className="h-32 w-full object-cover" /> : <div className="h-32 w-full bg-brand-gradient" />}
                    <div className="relative h-32">
                      {vid ? <video src={vid} className="h-32 w-full object-cover" autoPlay muted loop playsInline /> : <div className="h-32 w-full bg-brand-gradient" />}
                      {profile.cover_message && <div className="absolute inset-0 grid place-items-center bg-black/35 p-2 text-center text-xs font-bold leading-snug text-white">{profile.cover_message}</div>}
                    </div>
                  </div>
                )
              }
              if (layout === 'video' && vid) {
                return (
                  <>
                    <video src={vid} className="h-32 w-full object-cover" autoPlay muted loop playsInline />
                    {profile.cover_message && <div className="absolute inset-0 grid place-items-center bg-black/25 p-2 text-center text-sm font-bold text-white">{profile.cover_message}</div>}
                  </>
                )
              }
              return img ? <img src={img} alt="" className="h-32 w-full object-cover" /> : <div className="h-32 w-full bg-brand-gradient" />
            })()}
            <button className="absolute right-3 top-3 rounded-lg bg-black/45 px-3 py-1.5 text-xs font-bold text-white backdrop-blur transition hover:bg-black/65" onClick={() => coverRef.current?.click()}>🖼 Cover</button>
            <input ref={coverRef} type="file" accept="image/*" hidden onChange={(e) => uploadMedia(e, 'cover_path')} />
            <input ref={coverVideoRef} type="file" accept="video/*" hidden onChange={(e) => uploadMedia(e, 'cover_video_path')} />
            <input ref={avatarVideoRef} type="file" accept="video/*,image/gif" hidden onChange={(e) => uploadMedia(e, 'avatar_video_path')} />
          </div>
          <div className="relative z-10 -mt-12 grid gap-2 px-5 pb-5">
            <div className="flex items-end justify-between gap-3">
              <div className="relative">
                {profile.avatar_video_path && avatarUrls[profile.avatar_video_path]
                  ? <video src={avatarUrls[profile.avatar_video_path]} className="h-24 w-24 rounded-full border-4 border-white object-cover" autoPlay muted loop playsInline />
                  : <Avatar url={avatarUrls[profile.avatar_path]} name={myName} className="h-24 w-24 border-4 border-white text-3xl" />}
                <button className="absolute -bottom-1 -right-1 grid h-8 w-8 place-items-center rounded-full border-2 border-white bg-brand-gradient text-sm text-white shadow" onClick={() => fileRef.current?.click()} title="Change photo">📷</button>
                <input ref={fileRef} type="file" accept="image/*" hidden onChange={(e) => uploadMedia(e, 'avatar_path')} />
              </div>
              {!editing && (
                <button className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110" onClick={startEdit}>✏️ Edit profile</button>
              )}
            </div>
            <div>
              <div className="flex flex-wrap items-center gap-2">
                <h2 className="m-0 text-xl font-extrabold">{myName}</h2>
                {profile.pronouns && <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">{profile.pronouns}</span>}
              </div>
              {profile.headline && <p className="m-0 mt-0.5 text-sm font-semibold text-slate-700">{profile.headline}</p>}
              <p className="m-0 mt-0.5 text-xs text-slate-500">
                {profile.handle ? `@${profile.handle}` : user.email} · Member since {joined}
              </p>
              <p className="mb-0 mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs font-semibold text-slate-500">
                {profile.location && <span>📍 {profile.location}</span>}
                {profile.birthday && <span>🎂 {new Date(profile.birthday).toLocaleDateString(undefined, { month: 'long', day: 'numeric' })}</span>}
                {profile.website && (
                  <a className="text-brand-blue hover:underline" href={normUrl(profile.website)} target="_blank" rel="noopener noreferrer">🔗 {profile.website.replace(/^https?:\/\//, '')}</a>
                )}
              </p>
              {socialEntries.length > 0 && (
                <div className="mt-2 flex flex-wrap gap-2">
                  {socialEntries.map((s) => (
                    <a key={s.key} href={normUrl(s.base + (profile.socials[s.key] || '').replace(/^@/, ''))} target="_blank" rel="noopener noreferrer"
                      className="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet">{s.label}</a>
                  ))}
                </div>
              )}
            </div>
          </div>
        </section>

        {/* Stats */}
        <section className="grid grid-cols-2 gap-3 sm:grid-cols-4">
          {[
            { label: 'Posts', value: postCount ?? '…' },
            { label: 'Connections', value: Math.max(family.length, 1) },
            { label: 'Documents', value: stats ? stats.files.toLocaleString() : '…' },
            { label: 'Storage', value: stats ? fmtBytes(stats.bytes) : '…' }
          ].map((s) => (
            <div key={s.label} className="rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm">
              <p className="m-0 text-lg font-extrabold">{s.value}</p>
              <p className="m-0 text-xs font-semibold text-slate-500">{s.label}</p>
            </div>
          ))}
        </section>

        {msg && (
          <p className={`m-0 rounded-xl px-4 py-2.5 text-sm ${msg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{msg.text}</p>
        )}

        {editing ? (
          /* ---------- EDIT MODE ---------- */
          <form onSubmit={save} className="grid gap-4">
            <Section title="Cover & media">
              <div className="grid gap-3">
                <label className="grid gap-1 text-sm font-bold">Cover style
                  <select className={inputCls} value={draft.cover_layout || 'image'} onChange={(e) => setD({ cover_layout: e.target.value })}>
                    <option value="image">Single photo</option>
                    <option value="video">Video</option>
                    <option value="split">Split — photo + video message</option>
                  </select>
                </label>
                <div className="flex flex-wrap gap-2">
                  <button type="button" className="rounded-lg border-2 border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:border-brand-violet hover:text-brand-violet" onClick={() => coverRef.current?.click()}>🖼 Upload cover photo</button>
                  <button type="button" className="rounded-lg border-2 border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:border-brand-violet hover:text-brand-violet" onClick={() => coverVideoRef.current?.click()}>🎬 Upload cover video</button>
                  {profile.cover_video_path && <button type="button" className="rounded-lg border-2 border-slate-200 px-3 py-1.5 text-xs font-bold text-red-500 hover:border-red-300" onClick={() => clearMedia('cover_video_path')}>Remove video</button>}
                </div>
                <label className="grid gap-1 text-sm font-bold">Personal message <span className="text-xs font-normal text-slate-400">(shown over the video on Video / Split covers)</span>
                  <input className={inputCls} maxLength={120} value={draft.cover_message || ''} onChange={(e) => setD({ cover_message: e.target.value })} placeholder="Hey — welcome to my page! 👋" />
                </label>
                <div className="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                  <span className="text-sm font-bold">Animated profile photo</span>
                  <button type="button" className="rounded-lg border-2 border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:border-brand-violet hover:text-brand-violet" onClick={() => avatarVideoRef.current?.click()}>Upload video / GIF</button>
                  {profile.avatar_video_path && <button type="button" className="rounded-lg border-2 border-slate-200 px-3 py-1.5 text-xs font-bold text-red-500 hover:border-red-300" onClick={() => clearMedia('avatar_video_path')}>Use still photo</button>}
                  <span className="w-full text-xs text-slate-400">A short muted clip (or animated GIF) loops in your profile circle. Tip: a GIF set as your normal photo also animates.</span>
                </div>
              </div>
            </Section>

            <Section title="Basics">
              <div className="grid gap-3">
                <div className="grid gap-3 sm:grid-cols-2">
                  <label className="grid gap-1 text-sm font-bold">Display name
                    <input className={inputCls} maxLength={50} value={draft.display_name || ''} onChange={(e) => setD({ display_name: e.target.value })} placeholder="Matt Hummel" /></label>
                  <label className="grid gap-1 text-sm font-bold">Handle
                    <input className={inputCls} maxLength={30} value={draft.handle || ''} onChange={(e) => setD({ handle: e.target.value })} placeholder="@matt" /></label>
                </div>
                <label className="grid gap-1 text-sm font-bold">Headline
                  <input className={inputCls} maxLength={120} value={draft.headline || ''} onChange={(e) => setD({ headline: e.target.value })} placeholder="Power Platform Developer · Maker · Dad" /></label>
                <div className="grid gap-3 sm:grid-cols-3">
                  <label className="grid gap-1 text-sm font-bold">Pronouns
                    <input className={inputCls} maxLength={20} value={draft.pronouns || ''} onChange={(e) => setD({ pronouns: e.target.value })} placeholder="he/him" /></label>
                  <label className="grid gap-1 text-sm font-bold">Location
                    <input className={inputCls} maxLength={80} value={draft.location || ''} onChange={(e) => setD({ location: e.target.value })} placeholder="City, State" /></label>
                  <label className="grid gap-1 text-sm font-bold">Birthday
                    <input type="date" className={inputCls} value={draft.birthday || ''} onChange={(e) => setD({ birthday: e.target.value })} /></label>
                </div>
                <label className="grid gap-1 text-sm font-bold">Website
                  <input className={inputCls} maxLength={120} value={draft.website || ''} onChange={(e) => setD({ website: e.target.value })} placeholder="matthummel.com" /></label>
                <label className="grid gap-1 text-sm font-bold">About
                  <textarea className={`${inputCls} resize-none`} rows={4} maxLength={600} value={draft.bio || ''} onChange={(e) => setD({ bio: e.target.value })} placeholder="A little about you…" /></label>
              </div>
            </Section>

            <Section title="Experience" action={<button type="button" className="text-xs font-bold text-brand-blue hover:underline" onClick={() => addRow('work', { title: '', org: '', period: '' })}>+ Add</button>}>
              {(draft.work || []).length === 0 && <p className="m-0 text-sm text-slate-400">No experience added yet.</p>}
              <div className="grid gap-3">
                {(draft.work || []).map((w, i) => (
                  <div key={i} className="grid gap-2 rounded-xl border border-slate-200 p-3">
                    <div className="grid gap-2 sm:grid-cols-2">
                      <input className={inputCls} placeholder="Title" value={w.title || ''} onChange={(e) => editRow('work', i, { title: e.target.value })} />
                      <input className={inputCls} placeholder="Company / org" value={w.org || ''} onChange={(e) => editRow('work', i, { org: e.target.value })} />
                    </div>
                    <div className="flex gap-2">
                      <input className={inputCls} placeholder="2020 – Present" value={w.period || ''} onChange={(e) => editRow('work', i, { period: e.target.value })} />
                      <button type="button" className="rounded-lg border-2 border-slate-200 px-3 text-sm font-bold text-red-500 hover:border-red-300" onClick={() => delRow('work', i)}>Remove</button>
                    </div>
                  </div>
                ))}
              </div>
            </Section>

            <Section title="Education" action={<button type="button" className="text-xs font-bold text-brand-blue hover:underline" onClick={() => addRow('education', { school: '', detail: '', year: '' })}>+ Add</button>}>
              {(draft.education || []).length === 0 && <p className="m-0 text-sm text-slate-400">No education added yet.</p>}
              <div className="grid gap-3">
                {(draft.education || []).map((ed, i) => (
                  <div key={i} className="grid gap-2 rounded-xl border border-slate-200 p-3">
                    <div className="grid gap-2 sm:grid-cols-2">
                      <input className={inputCls} placeholder="School" value={ed.school || ''} onChange={(e) => editRow('education', i, { school: e.target.value })} />
                      <input className={inputCls} placeholder="Degree / field" value={ed.detail || ''} onChange={(e) => editRow('education', i, { detail: e.target.value })} />
                    </div>
                    <div className="flex gap-2">
                      <input className={inputCls} placeholder="Year" value={ed.year || ''} onChange={(e) => editRow('education', i, { year: e.target.value })} />
                      <button type="button" className="rounded-lg border-2 border-slate-200 px-3 text-sm font-bold text-red-500 hover:border-red-300" onClick={() => delRow('education', i)}>Remove</button>
                    </div>
                  </div>
                ))}
              </div>
            </Section>

            <Section title="Skills & interests">
              <div className="grid gap-3">
                <label className="grid gap-1 text-sm font-bold">Skills (comma separated)
                  <input className={inputCls} value={(draft.skills || []).join(', ')} onChange={(e) => setD({ skills: e.target.value.split(',').map((s) => s.trim()).filter(Boolean) })} placeholder="Power Apps, SharePoint, React" /></label>
                <label className="grid gap-1 text-sm font-bold">Interests (comma separated)
                  <input className={inputCls} value={(draft.interests || []).join(', ')} onChange={(e) => setD({ interests: e.target.value.split(',').map((s) => s.trim()).filter(Boolean) })} placeholder="Photography, Hiking, Coffee" /></label>
              </div>
            </Section>

            <Section title="Social links">
              <div className="grid gap-3 sm:grid-cols-2">
                {SOCIALS.map((s) => (
                  <label key={s.key} className="grid gap-1 text-sm font-bold">{s.label}
                    <input className={inputCls} value={(draft.socials || {})[s.key] || ''} onChange={(e) => setD({ socials: { ...(draft.socials || {}), [s.key]: e.target.value } })} placeholder={s.ph} /></label>
                ))}
              </div>
            </Section>

            <Section title="Featured links" action={<button type="button" className="text-xs font-bold text-brand-blue hover:underline" onClick={() => addRow('featured', { label: '', url: '' })}>+ Add</button>}>
              {(draft.featured || []).length === 0 && <p className="m-0 text-sm text-slate-400">Pin posts, projects, or links you want people to see first.</p>}
              <div className="grid gap-2">
                {(draft.featured || []).map((f, i) => (
                  <div key={i} className="flex gap-2">
                    <input className={inputCls} placeholder="Label" value={f.label || ''} onChange={(e) => editRow('featured', i, { label: e.target.value })} />
                    <input className={inputCls} placeholder="https://…" value={f.url || ''} onChange={(e) => editRow('featured', i, { url: e.target.value })} />
                    <button type="button" className="rounded-lg border-2 border-slate-200 px-3 text-sm font-bold text-red-500 hover:border-red-300" onClick={() => delRow('featured', i)}>✕</button>
                  </div>
                ))}
              </div>
            </Section>

            <div className="flex gap-2">
              <button className="rounded-lg bg-brand-gradient px-5 py-2 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-60" disabled={saving}>{saving ? 'Saving…' : 'Save profile'}</button>
              <button type="button" className="rounded-lg border-2 border-slate-200 px-5 py-2 text-sm font-bold text-slate-600 hover:border-slate-300" onClick={cancelEdit}>Cancel</button>
            </div>
          </form>
        ) : (
          /* ---------- VIEW MODE ---------- */
          <>
            {profile.featured && profile.featured.length > 0 && (
              <Section title="Featured">
                <div className="grid gap-2 sm:grid-cols-2">
                  {profile.featured.map((f, i) => (
                    <a key={i} href={normUrl(f.url)} target="_blank" rel="noopener noreferrer" className="rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-brand-violet transition hover:border-brand-violet">⭐ {f.label || f.url}</a>
                  ))}
                </div>
              </Section>
            )}

            {profile.bio && <Section title="About"><p className="m-0 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{profile.bio}</p></Section>}

            {profile.work && profile.work.length > 0 && (
              <Section title="Experience">
                <ul className="m-0 grid list-none gap-4 p-0">
                  {profile.work.map((w, i) => (
                    <li key={i} className="flex gap-3">
                      <span className="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-lg">💼</span>
                      <div>
                        <p className="m-0 text-sm font-bold">{w.title}{w.org && <span className="font-semibold text-slate-500"> · {w.org}</span>}</p>
                        {w.period && <p className="m-0 text-xs text-slate-400">{w.period}</p>}
                      </div>
                    </li>
                  ))}
                </ul>
              </Section>
            )}

            {profile.education && profile.education.length > 0 && (
              <Section title="Education">
                <ul className="m-0 grid list-none gap-4 p-0">
                  {profile.education.map((ed, i) => (
                    <li key={i} className="flex gap-3">
                      <span className="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-lg">🎓</span>
                      <div>
                        <p className="m-0 text-sm font-bold">{ed.school}</p>
                        <p className="m-0 text-xs text-slate-500">{[ed.detail, ed.year].filter(Boolean).join(' · ')}</p>
                      </div>
                    </li>
                  ))}
                </ul>
              </Section>
            )}

            {((profile.skills && profile.skills.length > 0) || (profile.interests && profile.interests.length > 0)) && (
              <Section title="Skills & interests">
                {profile.skills.length > 0 && (
                  <div className="mb-3">
                    <p className="m-0 mb-1 text-xs font-bold uppercase tracking-wide text-slate-400">Skills</p>
                    <div className="flex flex-wrap gap-2">{profile.skills.map((s) => <span key={s} className="rounded-full bg-brand-gradient-soft px-3 py-1 text-xs font-bold text-brand-violet">{s}</span>)}</div>
                  </div>
                )}
                {profile.interests.length > 0 && (
                  <div>
                    <p className="m-0 mb-1 text-xs font-bold uppercase tracking-wide text-slate-400">Interests</p>
                    <div className="flex flex-wrap gap-2">{profile.interests.map((s) => <span key={s} className="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{s}</span>)}</div>
                  </div>
                )}
              </Section>
            )}

            {family.length > 0 && (
              <Section title="Connections">
                <ul className="m-0 grid list-none gap-3 p-0 sm:grid-cols-2">
                  {family.map((m) => (
                    <li key={m.id} className="flex items-center gap-3">
                      <Avatar url={avatarUrls[m.avatar_path]} name={m.display_name || '?'} className="h-12 w-12 text-lg" />
                      <div className="min-w-0">
                        <p className="m-0 truncate text-sm font-bold">{m.display_name || 'Member'} {m.id === user.id && <span className="text-xs font-semibold text-brand-violet">(you)</span>}</p>
                        {m.headline ? <p className="m-0 truncate text-xs text-slate-500">{m.headline}</p> : m.bio && <p className="m-0 truncate text-xs text-slate-500">{m.bio}</p>}
                      </div>
                    </li>
                  ))}
                </ul>
              </Section>
            )}
          </>
        )}
      </div>
    </main>
  )
}
