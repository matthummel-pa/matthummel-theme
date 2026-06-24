import { useCallback, useEffect, useMemo, useState } from 'react'
import { supabase, BUCKET } from './supabase.js'
import AppIcon from './AppIcon.jsx'
import { loadRelationships, relationTo, sendFriendRequest, follow, acceptFriend, removeRelationship } from './relationships.js'

function Avatar({ url, name, size = 'h-12 w-12 text-lg' }) {
  if (url) return <img src={url} alt="" className={`${size} shrink-0 rounded-full object-cover`} />
  return <span className={`grid ${size} shrink-0 place-items-center rounded-full bg-brand-gradient font-extrabold text-white`}>{(name || '?')[0].toUpperCase()}</span>
}

const TABS = [
  { id: 'all', label: 'Everyone' },
  { id: 'friends', label: 'Friends' },
  { id: 'followers', label: 'Followers' },
  { id: 'following', label: 'Following' },
  { id: 'requests', label: 'Requests' }
]

export default function Followers({ session }) {
  const me = session.user.id
  const [people, setPeople] = useState([])
  const [rels, setRels] = useState([])
  const [blocked, setBlocked] = useState([])
  const [loading, setLoading] = useState(true)
  const [q, setQ] = useState('')
  const [tab, setTab] = useState('all')

  const load = useCallback(async () => {
    const { data } = await supabase.from('profiles').select('id, display_name, bio, headline, location, avatar_path, privacy').is('deactivated_at', null).order('display_name')
    const rows = data || []
    for (const r of rows) {
      if (r.avatar_path) {
        const { data: s } = await supabase.storage.from(BUCKET).createSignedUrl(r.avatar_path, 3600)
        r.url = s?.signedUrl || null
      }
    }
    setPeople(rows)
    setLoading(false)
  }, [])
  const refreshRels = useCallback(async () => { setRels(await loadRelationships()) }, [])
  const refreshBlocks = useCallback(async () => { const { data } = await supabase.from('blocks').select('blocked'); setBlocked((data || []).map((b) => b.blocked)) }, [])
  useEffect(() => { load(); refreshRels(); refreshBlocks() }, [load, refreshRels, refreshBlocks])

  const memberById = (id) => people.find((p) => p.id === id) || {}
  const followRow = (id) => rels.find((r) => r.kind === 'follow' && r.requester === me && r.addressee === id)
  const friendRow = (id) => rels.find((r) => r.kind === 'friend' && (r.requester === id || r.addressee === id))
  const incoming = rels.filter((r) => r.kind === 'friend' && r.status === 'pending' && r.addressee === me)

  async function addFriend(id) { await sendFriendRequest(id); refreshRels() }
  async function toggleFollow(id) { const fr = followRow(id); if (fr) await removeRelationship(fr.id); else await follow(id); refreshRels() }
  async function accept(rowId) { await acceptFriend(rowId); refreshRels() }
  async function decline(rowId) { await removeRelationship(rowId); refreshRels() }
  async function unfriend(id) { const fr = friendRow(id); if (fr && window.confirm('Remove this friend?')) { await removeRelationship(fr.id); refreshRels() } }

  const base = useMemo(() => people.filter((p) => p.id !== me && !blocked.includes(p.id)), [people, me, blocked])

  const shown = useMemo(() => {
    let list = base
    if (tab === 'friends') list = base.filter((p) => relationTo(rels, me, p.id).friend === 'yes')
    else if (tab === 'followers') list = base.filter((p) => relationTo(rels, me, p.id).followsMe)
    else if (tab === 'following') list = base.filter((p) => relationTo(rels, me, p.id).following)
    if (tab !== 'all') {
      // keep
    } else {
      list = list.filter((p) => !p.privacy || p.privacy.searchable !== false || relationTo(rels, me, p.id).friend === 'yes')
    }
    if (q.trim()) {
      const s = q.trim().toLowerCase()
      list = list.filter((p) => (p.display_name || '').toLowerCase().includes(s) || (p.headline || '').toLowerCase().includes(s) || (p.bio || '').toLowerCase().includes(s) || (p.location || '').toLowerCase().includes(s))
    }
    return list
  }, [base, rels, me, tab, q])

  function Actions({ p }) {
    const rel = relationTo(rels, me, p.id)
    const canRequest = !p.privacy || p.privacy.allow_requests !== false
    return (
      <div className="mt-2 flex flex-wrap gap-2">
        {rel.friend === 'yes' ? <button className="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-600 hover:bg-emerald-100" onClick={() => unfriend(p.id)}>Friends ✓</button>
          : rel.friend === 'out' ? <span className="rounded-lg border-2 border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-400">Requested</span>
          : rel.friend === 'in' ? <button className="rounded-lg bg-brand-gradient px-2.5 py-1 text-xs font-bold text-white" onClick={() => { const r = rels.find((x) => x.kind === 'friend' && x.status === 'pending' && x.addressee === me && x.requester === p.id); if (r) accept(r.id) }}>Accept request</button>
          : canRequest ? <button className="rounded-lg border-2 border-brand-violet px-2.5 py-1 text-xs font-bold text-brand-violet hover:bg-brand-gradient-soft" onClick={() => addFriend(p.id)}>+ Add friend</button>
          : <span className="rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-300">Requests off</span>}
        <button className={`rounded-lg px-2.5 py-1 text-xs font-bold ${rel.following ? 'bg-brand-gradient text-white' : 'border-2 border-slate-200 text-slate-500 hover:border-brand-violet hover:text-brand-violet'}`} onClick={() => toggleFollow(p.id)}>{rel.following ? 'Following' : 'Follow'}</button>
        {rel.followsMe && <span className="self-center text-[11px] font-semibold text-slate-400">follows you</span>}
      </div>
    )
  }

  return (
    <main className="mx-auto w-full max-w-2xl flex-1 px-3 py-5 sm:px-7">
      <div className="mb-4 flex items-center gap-2">
        <AppIcon id="followers" className="h-7 w-7" />
        <div>
          <h1 className="m-0 text-xl font-extrabold tracking-tight">Followers</h1>
          <p className="m-0 text-xs text-slate-500">Find people, send requests, and manage your circle</p>
        </div>
      </div>

      <div className="mb-3 flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1">
        {TABS.map((t) => (
          <button key={t.id} onClick={() => setTab(t.id)}
            className={`rounded-lg px-3 py-1.5 text-xs font-bold transition ${tab === t.id ? 'bg-brand-gradient text-white shadow' : 'text-slate-600 hover:text-brand-violet'}`}>
            {t.label}{t.id === 'requests' && incoming.length > 0 && <span className="ml-1 rounded-full bg-red-500 px-1.5 text-[10px] text-white">{incoming.length}</span>}
          </button>
        ))}
      </div>

      {tab === 'requests' ? (
        incoming.length === 0 ? (
          <div className="grid justify-items-center gap-2 py-16 text-center text-slate-500"><p className="m-0 text-4xl">🤝</p><p className="m-0">No pending friend requests.</p></div>
        ) : (
          <ul className="m-0 grid list-none gap-3 p-0">
            {incoming.map((r) => {
              const p = memberById(r.requester)
              return (
                <li key={r.id} className="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                  <Avatar url={p.url} name={p.display_name} />
                  <div className="min-w-0 flex-1">
                    <p className="m-0 text-sm font-extrabold">{p.display_name || 'Someone'}</p>
                    {p.headline && <p className="m-0 truncate text-xs text-slate-500">{p.headline}</p>}
                  </div>
                  <button className="rounded-lg bg-brand-gradient px-3 py-1.5 text-xs font-bold text-white" onClick={() => accept(r.id)}>Accept</button>
                  <button className="rounded-lg border-2 border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-500 hover:border-red-300 hover:text-red-500" onClick={() => decline(r.id)}>Decline</button>
                </li>
              )
            })}
          </ul>
        )
      ) : (
        <>
          <input
            type="search"
            className="mb-4 w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-brand-violet"
            placeholder="Search people by name, headline, bio, or location…"
            value={q}
            onChange={(e) => setQ(e.target.value)}
          />
          {loading ? (
            <div className="grid place-items-center py-20"><div className="h-9 w-9 animate-spin rounded-full border-4 border-slate-200 border-t-brand-violet" /></div>
          ) : shown.length === 0 ? (
            <div className="grid justify-items-center gap-2 py-16 text-center text-slate-500"><p className="m-0 text-4xl">👥</p><p className="m-0">{q ? 'No one matches that search.' : 'Nobody here yet.'}</p></div>
          ) : (
            <ul className="m-0 grid list-none gap-3 p-0 sm:grid-cols-2">
              {shown.map((p) => (
                <li key={p.id} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                  <div className="flex items-start gap-3">
                    <Avatar url={p.url} name={p.display_name} />
                    <div className="min-w-0">
                      <p className="m-0 text-sm font-extrabold">{p.display_name || 'Member'}</p>
                      {p.headline && <p className="m-0 truncate text-xs font-semibold text-slate-600">{p.headline}</p>}
                      {p.location && <p className="m-0 text-xs text-slate-500">📍 {p.location}</p>}
                      {!p.headline && p.bio && <p className="m-0 mt-0.5 line-clamp-2 text-xs text-slate-500">{p.bio}</p>}
                    </div>
                  </div>
                  <Actions p={p} />
                </li>
              ))}
            </ul>
          )}
        </>
      )}
    </main>
  )
}
