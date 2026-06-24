import { useCallback, useEffect, useRef, useState } from 'react'
import { supabase, BUCKET } from './supabase.js'
import { getSettings, NOTES_SERVICES } from './settings.js'
import { PRIORITIES, dueState } from './Todos.jsx'
import { loadRelationships, relationTo, sendFriendRequest, follow, acceptFriend, removeRelationship } from './relationships.js'

const GALLERY_PREFIX = '_gallery'

const fmtDate = (d) => new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })

const EVENT_CHIP = { violet: 'bg-violet-500', blue: 'bg-blue-500', emerald: 'bg-emerald-500', amber: 'bg-amber-400', rose: 'bg-rose-500', sky: 'bg-sky-500', slate: 'bg-slate-500' }
const startOfToday = () => { const d = new Date(); d.setHours(0, 0, 0, 0); return d }
const fmtEventWhen = (start, allDay) => {
  const d = new Date(start)
  const today = startOfToday()
  const diff = Math.round((new Date(d.getFullYear(), d.getMonth(), d.getDate()) - today) / 86400000)
  const day = diff === 0 ? 'Today' : diff === 1 ? 'Tomorrow' : d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })
  if (allDay) return `${day} · all day`
  return `${day} · ${d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })}`
}

// expand recurring events into upcoming instances within the next `days`
function upcomingEvents(events, days = 30, limit = 5) {
  const now = new Date()
  const horizon = new Date(now.getTime() + days * 86400000)
  const out = []
  for (const e of events) {
    const start = new Date(e.start_at)
    const dur = new Date(e.end_at) - start
    const until = e.recur_until ? new Date(`${e.recur_until}T23:59:59`) : null
    let cur = new Date(start)
    let guard = 0
    while (cur <= horizon && guard < 400) {
      guard++
      if (until && cur > until) break
      if (new Date(cur.getTime() + dur) >= now) out.push({ ...e, _start: new Date(cur) })
      if (e.recur === 'none') break
      if (e.recur === 'daily') cur = new Date(cur.getFullYear(), cur.getMonth(), cur.getDate() + 1, cur.getHours(), cur.getMinutes())
      else if (e.recur === 'weekly') cur = new Date(cur.getFullYear(), cur.getMonth(), cur.getDate() + 7, cur.getHours(), cur.getMinutes())
      else if (e.recur === 'monthly') cur = new Date(cur.getFullYear(), cur.getMonth() + 1, cur.getDate(), cur.getHours(), cur.getMinutes())
      else if (e.recur === 'yearly') cur = new Date(cur.getFullYear() + 1, cur.getMonth(), cur.getDate(), cur.getHours(), cur.getMinutes())
      else break
    }
  }
  return out.sort((a, b) => a._start - b._start).slice(0, limit)
}

function Card({ title, action, children, className = '', ...rest }) {
  return (
    <section {...rest} className={`rounded-2xl border border-slate-200 bg-white p-4 shadow-sm ${className}`}>
      <div className="mb-3 flex items-center justify-between gap-2">
        <h2 className="m-0 text-sm font-extrabold tracking-tight">{title}</h2>
        {action}
      </div>
      {children}
    </section>
  )
}

const linkBtn = 'text-xs font-bold text-brand-blue hover:underline'

export default function Home({ session, go }) {
  const user = session.user
  const [profile, setProfile] = useState(null)
  const [urls, setUrls] = useState({})
  const [favDocs, setFavDocs] = useState([])
  const [favArticles, setFavArticles] = useState([])
  const [gallery, setGallery] = useState([])
  const [notes, setNotes] = useState([])
  const [starred, setStarred] = useState([])
  const [events, setEvents] = useState([])
  const [lightbox, setLightbox] = useState(null)
  const CARD_IDS = ['events', 'notes', 'favdocs', 'gallery', 'm365', 'friends']
  const [order, setOrder] = useState(() => {
    try { const o = JSON.parse(localStorage.getItem('hc-home-order')); if (Array.isArray(o) && o.length === CARD_IDS.length && CARD_IDS.every((x) => o.includes(x))) return o } catch { /* */ }
    return CARD_IDS
  })
  const [dragId, setDragId] = useState(null)
  const [members, setMembers] = useState([])
  const [rels, setRels] = useState([])
  const [blocked, setBlocked] = useState([])
  const [friendQuery, setFriendQuery] = useState('')
  const loadPeople = useCallback(async () => {
    const { data } = await supabase.from('profiles').select('id, display_name, avatar_path, bio, headline, privacy').is('deactivated_at', null)
    const rows = data || []
    for (const r of rows) {
      if (r.avatar_path) { const { data: ss } = await supabase.storage.from(BUCKET).createSignedUrl(r.avatar_path, 3600); r.url = ss?.signedUrl || null }
    }
    setMembers(rows)
  }, [])
  const refreshRels = useCallback(async () => { setRels(await loadRelationships()) }, [])
  useEffect(() => { supabase.from('blocks').select('blocked').then(({ data }) => setBlocked((data || []).map((b) => b.blocked))) }, [])
  useEffect(() => { loadPeople(); refreshRels() }, [loadPeople, refreshRels])

  const memberById = (id) => members.find((m) => m.id === id) || {}
  const followRow = (id) => rels.find((r) => r.kind === 'follow' && r.requester === user.id && r.addressee === id)
  const incomingRequests = rels.filter((r) => r.kind === 'friend' && r.status === 'pending' && r.addressee === user.id)

  async function addFriend(id) { await sendFriendRequest(id); refreshRels() }
  async function toggleFollow(id) {
    const fr = followRow(id)
    if (fr) await removeRelationship(fr.id); else await follow(id)
    refreshRels()
  }
  async function acceptReq(rowId) { await acceptFriend(rowId); refreshRels() }
  async function declineReq(rowId) { await removeRelationship(rowId); refreshRels() }
  function moveCard(targetId) {
    if (!dragId || dragId === targetId) { setDragId(null); return }
    const o = [...order]
    o.splice(o.indexOf(dragId), 1)
    o.splice(o.indexOf(targetId), 0, dragId)
    setOrder(o); setDragId(null)
    try { localStorage.setItem('hc-home-order', JSON.stringify(o)) } catch { /* */ }
  }
  const cardDrag = (id) => ({
    draggable: true,
    onDragStart: () => setDragId(id),
    onDragOver: (e) => e.preventDefault(),
    onDrop: () => moveCard(id),
    style: { order: order.indexOf(id) },
    className: `cursor-move transition ${dragId === id ? 'opacity-40' : ''}`
  })
  const [msg, setMsg] = useState(null)
  const galleryRef = useRef(null)

  const sign = useCallback(async (path, secs = 3600) => {
    const { data } = await supabase.storage.from(BUCKET).createSignedUrl(path, secs)
    return data?.signedUrl || null
  }, [])

  const loadGallery = useCallback(async () => {
    const { data } = await supabase.storage.from(BUCKET).list(GALLERY_PREFIX, { limit: 60, sortBy: { column: 'created_at', order: 'desc' } })
    const files = (data || []).filter((i) => i.id && i.name !== '.keep').slice(0, 12)
    const paths = files.map((f) => `${GALLERY_PREFIX}/${f.name}`)
    let signed = {}
    if (paths.length) {
      const { data: s } = await supabase.storage.from(BUCKET).createSignedUrls(paths, 3600)
      for (const x of s || []) if (x.signedUrl) signed[x.path] = x.signedUrl
    }
    setGallery(files.map((f) => ({ name: f.name, path: `${GALLERY_PREFIX}/${f.name}`, url: signed[`${GALLERY_PREFIX}/${f.name}`] })))
  }, [])

  const loadAll = useCallback(async () => {
    const [{ data: profs }, { data: favs }, { data: nts }, { data: tds }, { data: evs }] = await Promise.all([
      supabase.from('profiles').select('*').eq('id', user.id),
      supabase.from('favorites').select('*').order('created_at', { ascending: false }),
      supabase.from('notes').select('*').order('updated_at', { ascending: false }).limit(4),
      supabase.from('todos').select('*').eq('starred', true).eq('done', false).order('created_at', { ascending: false }).limit(6),
      supabase.from('events').select('*')
    ])
    const p = profs?.[0] || null
    setProfile(p)
    setFavDocs((favs || []).filter((f) => f.kind === 'doc').slice(0, 6))
    setFavArticles((favs || []).filter((f) => f.kind === 'article').slice(0, 5))
    setNotes(nts || [])
    setStarred(tds || [])
    setEvents(evs || [])
    const u = {}
    if (p?.avatar_path) u[p.avatar_path] = await sign(p.avatar_path)
    if (p?.cover_path) u[p.cover_path] = await sign(p.cover_path)
    setUrls(u)
    loadGallery()
  }, [user.id, sign, loadGallery])

  useEffect(() => { loadAll() }, [loadAll])

  async function openDoc(fav) {
    const url = await sign(fav.payload.path, 300)
    if (url) window.open(url, '_blank', 'noopener')
    else setMsg({ ok: false, text: 'That file seems to be gone — unfavoriting it is safe.' })
  }

  async function unfav(fav) {
    setFavDocs((f) => f.filter((x) => x.id !== fav.id))
    setFavArticles((f) => f.filter((x) => x.id !== fav.id))
    await supabase.from('favorites').delete().eq('id', fav.id)
  }

  async function uploadPhotos(e) {
    const files = Array.from(e.target.files || [])
    e.target.value = ''
    for (const f of files) {
      if (!/^image\//.test(f.type)) continue
      const safe = f.name.replace(/[^\w.-]+/g, '_')
      await supabase.storage.from(BUCKET).upload(`${GALLERY_PREFIX}/${Date.now()}-${safe}`, f, { upsert: true })
    }
    loadGallery()
  }

  async function deletePhoto(ph) {
    if (getSettings().confirmDelete && !window.confirm('Remove this photo from the gallery?')) return
    setGallery((g) => g.filter((x) => x.path !== ph.path))
    setLightbox(null)
    await supabase.storage.from(BUCKET).remove([ph.path])
  }

  async function toggleTodoDone(t) {
    setStarred((s) => s.filter((x) => x.id !== t.id))
    await supabase.from('todos').update({ done: true, done_at: new Date().toISOString() }).eq('id', t.id)
  }

  const name = profile?.display_name || user.email.split('@')[0]
  const hour = new Date().getHours()
  const greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening'

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-3 py-5 sm:px-7">
      {/* profile header */}
      <section className="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div className="h-24 sm:h-32">
          {profile?.cover_path && urls[profile.cover_path] ? (
            <img src={urls[profile.cover_path]} alt="" className="h-full w-full object-cover" />
          ) : (
            <div className="h-full w-full bg-brand-gradient" />
          )}
        </div>
        <div className="relative z-10 -mt-8 flex flex-wrap items-end justify-between gap-3 px-5 pb-4">
          <div className="flex items-end gap-3">
            {profile?.avatar_path && urls[profile.avatar_path] ? (
              <img src={urls[profile.avatar_path]} alt="" className="h-16 w-16 rounded-full border-4 border-white object-cover" />
            ) : (
              <span className="grid h-16 w-16 place-items-center rounded-full border-4 border-white bg-brand-gradient text-2xl font-extrabold text-white">
                {name[0].toUpperCase()}
              </span>
            )}
            <div className="pb-1">
              <h1 className="m-0 text-lg font-extrabold leading-tight">{greeting}, {name.split(' ')[0]} 👋</h1>
              <p className="m-0 text-xs text-slate-500">
                {profile?.bio ? profile.bio.slice(0, 80) : 'Your private Keepary'}
                {profile?.location && <span> · 📍 {profile.location}</span>}
              </p>
            </div>
          </div>
          <button className={linkBtn} onClick={() => go('profile')}>Edit profile →</button>
        </div>
      </section>

      {msg && <p className={`mb-3 rounded-xl px-4 py-2.5 text-sm ${msg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{msg.text}</p>}

      <p className="mb-2 text-xs text-slate-400">Tip: drag cards to rearrange your dashboard.</p>
      <div className="grid gap-4 lg:grid-cols-2">
        {/* upcoming events */}
        <Card {...cardDrag('events')} title="📅 Upcoming events" action={<button className={linkBtn} onClick={() => go('calendar')}>Open Agenda →</button>}>
          {upcomingEvents(events).length === 0 ? (
            <p className="m-0 text-sm text-slate-500">Nothing scheduled soon. Add events on the Calendar page.</p>
          ) : (
            <ul className="m-0 grid list-none gap-2 p-0">
              {upcomingEvents(events).map((ev, i) => (
                <li key={i}>
                  <button className="flex w-full items-center gap-2.5 rounded-lg px-1.5 py-1 text-left hover:bg-brand-gradient-soft" onClick={() => go('calendar')}>
                    <span className={`h-3 w-3 shrink-0 rounded-full ${EVENT_CHIP[ev.color] || EVENT_CHIP.violet}`} />
                    <span className="min-w-0 flex-1 truncate text-sm font-bold">{ev.title}</span>
                    <span className="shrink-0 text-xs font-semibold text-slate-500">{fmtEventWhen(ev._start, ev.all_day)}</span>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </Card>

        {/* quick notes */}
        <Card
          {...cardDrag('notes')}
          title="🗒 Latest notes"
          action={<button className={linkBtn} onClick={() => go('notes')}>Open Scribe →</button>}
        >
          {notes.length === 0 ? (
            <p className="m-0 text-sm text-slate-500">
              Write rich notes with notebooks & attachments, then publish to {NOTES_SERVICES.find((a) => a.id === getSettings().notesService)?.label.replace(/^\S+\s/, '') || 'Notion'}.
            </p>
          ) : (
            <ul className="m-0 grid list-none gap-2 p-0">
              {notes.map((n) => (
                <li key={n.id}>
                  <button className="w-full text-left" onClick={() => go('notes')}>
                    <span className="block truncate text-sm font-bold">{n.title}</span>
                    <span className="block truncate text-xs text-slate-500">{(n.content || '').slice(0, 70) || '(empty)'} · {fmtDate(n.updated_at)}</span>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </Card>

        {/* favorite docs */}
        <Card {...cardDrag('favdocs')} title="📌 Favorite documents" action={<button className={linkBtn} onClick={() => go('files')}>Open Vault →</button>}>
          {favDocs.length === 0 ? (
            <p className="m-0 text-sm text-slate-500">Tap ☆ on any file in Files to pin it here.</p>
          ) : (
            <ul className="m-0 grid list-none gap-1.5 p-0">
              {favDocs.map((f) => (
                <li key={f.id} className="flex items-center gap-2">
                  <button className="min-w-0 flex-1 truncate text-left text-sm font-semibold text-brand-blue hover:underline" onClick={() => openDoc(f)}>
                    📄 {f.payload.name}
                  </button>
                  <button className="shrink-0 text-xs text-slate-400 hover:text-red-500" onClick={() => unfav(f)} title="Remove favorite">✕</button>
                </li>
              ))}
            </ul>
          )}
        </Card>

        {/* gallery */}
        <Card
          {...cardDrag('gallery')}
          title="📸 Family photo gallery"
          action={
            <span>
              <button className={linkBtn} onClick={() => galleryRef.current?.click()}>+ Add photos</button>
              <input ref={galleryRef} type="file" accept="image/*" multiple hidden onChange={uploadPhotos} />
            </span>
          }
        >
          {gallery.length === 0 ? (
            <p className="m-0 text-sm text-slate-500">Upload family photos — they're private to you two, separate from your documents.</p>
          ) : (
            <div className="grid grid-cols-3 gap-1.5 sm:grid-cols-4">
              {gallery.map((ph) => (
                <button key={ph.path} className="overflow-hidden rounded-lg" onClick={() => setLightbox(ph)}>
                  <img src={ph.url} alt="" loading="lazy" className="h-20 w-full object-cover transition hover:scale-105" />
                </button>
              ))}
            </div>
          )}
        </Card>

        {/* friends + user search + requests */}
        <Card {...cardDrag('friends')} title="👥 Friends & followers" action={<button className={linkBtn} onClick={() => go('followers')}>See all →</button>}>
          {incomingRequests.length > 0 && (
            <div className="mb-3 grid gap-2 rounded-xl bg-brand-gradient-soft p-3">
              <p className="m-0 text-xs font-extrabold uppercase tracking-wide text-brand-violet">Friend requests</p>
              {incomingRequests.map((r) => {
                const m = memberById(r.requester)
                return (
                  <div key={r.id} className="flex items-center gap-2.5">
                    {m.url ? <img src={m.url} alt="" className="h-8 w-8 shrink-0 rounded-full object-cover" /> : <span className="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-gradient text-xs font-extrabold text-white">{(m.display_name || '?')[0].toUpperCase()}</span>}
                    <span className="min-w-0 flex-1 truncate text-sm font-bold">{m.display_name || 'Someone'}</span>
                    <button className="rounded-lg bg-brand-gradient px-2.5 py-1 text-xs font-bold text-white" onClick={() => acceptReq(r.id)}>Accept</button>
                    <button className="rounded-lg border-2 border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-500 hover:border-red-300 hover:text-red-500" onClick={() => declineReq(r.id)}>Decline</button>
                  </div>
                )
              })}
            </div>
          )}
          <input
            type="search"
            className="mb-2 w-full rounded-lg border-2 border-slate-200 bg-white px-3 py-1.5 text-sm outline-none focus:border-brand-violet"
            placeholder="Search people to add…"
            value={friendQuery}
            onChange={(e) => setFriendQuery(e.target.value)}
          />
          {(() => {
            const q = friendQuery.trim().toLowerCase()
            const list = members.filter((m) => {
              if (m.id === user.id) return false
              if (blocked.includes(m.id)) return false
              const rel = relationTo(rels, user.id, m.id)
              const searchable = !m.privacy || m.privacy.searchable !== false
              if (!searchable && rel.friend !== 'yes') return false
              if (!q) return true
              return (m.display_name || '').toLowerCase().includes(q) || (m.headline || '').toLowerCase().includes(q) || (m.bio || '').toLowerCase().includes(q)
            }).slice(0, 8)
            if (members.length <= 1) return <p className="m-0 text-sm text-slate-500">No one else in your circle yet.</p>
            if (list.length === 0) return <p className="m-0 text-sm text-slate-400">No matches.</p>
            return (
              <ul className="m-0 grid list-none gap-2 p-0">
                {list.map((m) => {
                  const rel = relationTo(rels, user.id, m.id)
                  const canRequest = !m.privacy || m.privacy.allow_requests !== false
                  return (
                    <li key={m.id} className="flex items-center gap-2.5">
                      {m.url ? <img src={m.url} alt="" className="h-8 w-8 shrink-0 rounded-full object-cover" /> : <span className="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-gradient text-xs font-extrabold text-white">{(m.display_name || '?')[0].toUpperCase()}</span>}
                      <span className="min-w-0 flex-1 truncate text-sm font-semibold">{m.display_name || 'Member'}{m.headline && <span className="block truncate text-[11px] font-normal text-slate-400">{m.headline}</span>}</span>
                      {rel.friend === 'yes' ? <span className="shrink-0 rounded-lg bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-600">Friends ✓</span>
                        : rel.friend === 'out' ? <span className="shrink-0 rounded-lg border-2 border-slate-200 px-2 py-1 text-xs font-bold text-slate-400">Requested</span>
                        : rel.friend === 'in' ? <button className="shrink-0 rounded-lg bg-brand-gradient px-2.5 py-1 text-xs font-bold text-white" onClick={() => { const req = rels.find((r) => r.kind === 'friend' && r.status === 'pending' && r.addressee === user.id && r.requester === m.id); if (req) acceptReq(req.id) }}>Accept</button>
                        : canRequest ? <button className="shrink-0 rounded-lg border-2 border-brand-violet px-2.5 py-1 text-xs font-bold text-brand-violet hover:bg-brand-gradient-soft" onClick={() => addFriend(m.id)}>+ Add friend</button>
                        : <span className="shrink-0 text-[11px] text-slate-300">Private</span>}
                      <button className={`shrink-0 rounded-lg px-2.5 py-1 text-xs font-bold ${rel.following ? 'bg-brand-gradient text-white' : 'border-2 border-slate-200 text-slate-500 hover:border-brand-violet hover:text-brand-violet'}`} onClick={() => toggleFollow(m.id)}>{rel.following ? 'Following' : 'Follow'}</button>
                    </li>
                  )
                })}
              </ul>
            )
          })()}
        </Card>

        {/* M365 quick links */}
        <Card {...cardDrag('m365')} title="⚡ Microsoft 365" action={<button className={linkBtn} onClick={() => go('settings')}>Settings →</button>}>
          <p className="m-0 mb-2 text-xs text-slate-500">Quick links to your Microsoft 365 and Power Platform tools.</p>
          <div className="flex flex-wrap gap-2">
            {[
              ['OneDrive', 'https://onedrive.live.com'],
              ['OneNote', 'https://www.onenote.com/notebooks'],
              ['To Do', 'https://to-do.office.com'],
              ['Power Automate', 'https://make.powerautomate.com'],
              ['Power Apps', 'https://make.powerapps.com']
            ].map(([label, url]) => (
              <a key={label} href={url} target="_blank" rel="noopener noreferrer" className="rounded-full border-2 border-slate-200 bg-white px-3 py-1 text-xs font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet">
                {label} ↗
              </a>
            ))}
          </div>
        </Card>
      </div>

      {/* gallery lightbox */}
      {lightbox && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-indigo-950/85 p-3.5" onClick={() => setLightbox(null)} role="dialog" aria-modal="true">
          <div className="grid max-h-[92vh] w-full max-w-3xl gap-2" onClick={(e) => e.stopPropagation()}>
            <img src={lightbox.url} alt="" className="max-h-[80vh] w-full rounded-2xl object-contain" />
            <div className="flex justify-center gap-2">
              <a href={lightbox.url} download className="rounded-lg bg-white/15 px-4 py-2 text-sm font-bold text-white hover:bg-white/25">⬇ Download</a>
              <button className="rounded-lg bg-white/15 px-4 py-2 text-sm font-bold text-white hover:bg-red-500/70" onClick={() => deletePhoto(lightbox)}>🗑 Delete</button>
              <button className="rounded-lg bg-white/15 px-4 py-2 text-sm font-bold text-white hover:bg-white/25" onClick={() => setLightbox(null)}>✕ Close</button>
            </div>
          </div>
        </div>
      )}
    </main>
  )
}
