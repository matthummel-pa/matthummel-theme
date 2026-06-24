import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { supabase, BUCKET } from './supabase.js'
import AppIcon from './AppIcon.jsx'

const REACTIONS = ['👍', '❤️', '😂', '🎉', '🙏', '🔥']
const fmtTime = (d) => new Date(d).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
const fmtDay = (d) => new Date(d).toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' })
const sameDay = (a, b) => new Date(a).toDateString() === new Date(b).toDateString()

function Avatar({ p, size = 'h-9 w-9' }) {
  if (p?.url) return <img src={p.url} alt="" className={`${size} shrink-0 rounded-full object-cover`} />
  return <span className={`grid ${size} shrink-0 place-items-center rounded-full bg-brand-gradient text-sm font-extrabold text-white`}>{(p?.name || '?')[0].toUpperCase()}</span>
}

export default function Chat({ session }) {
  const me = session.user.id
  const [profiles, setProfiles] = useState({})
  const [channels, setChannels] = useState([])
  const [members, setMembers] = useState([]) // all people for DM start
  const [active, setActive] = useState(null) // channel object
  const [messages, setMessages] = useState([])
  const [reactions, setReactions] = useState({}) // messageId -> [{emoji,user_id}]
  const [text, setText] = useState('')
  const [loading, setLoading] = useState(true)
  const [showPeople, setShowPeople] = useState(false)
  const [reactingTo, setReactingTo] = useState(null)
  const endRef = useRef(null)

  const nameOf = (id) => profiles[id]?.name || (id === me ? 'You' : 'Member')

  const loadProfiles = useCallback(async () => {
    const { data } = await supabase.from('profiles').select('id, display_name, avatar_path')
    const map = {}
    const list = []
    for (const r of data || []) {
      let url = null
      if (r.avatar_path) { const { data: s } = await supabase.storage.from(BUCKET).createSignedUrl(r.avatar_path, 3600); url = s?.signedUrl || null }
      map[r.id] = { name: r.display_name || 'Member', url }
      list.push({ id: r.id, name: r.display_name || 'Member', url })
    }
    setProfiles(map)
    setMembers(list.filter((x) => x.id !== me))
  }, [me])

  const dmTitle = useCallback(async (ch) => {
    // for DM channels, label with the other member's name
    const { data } = await supabase.from('channel_members').select('user_id').eq('channel_id', ch.id)
    const other = (data || []).map((m) => m.user_id).find((u) => u !== me)
    return other ? (profiles[other]?.name || 'Direct message') : 'Direct message'
  }, [me, profiles])

  const loadChannels = useCallback(async () => {
    const { data } = await supabase.from('channels').select('*').order('is_dm').order('created_at')
    const chs = data || []
    // resolve DM titles
    for (const c of chs) {
      if (c.is_dm) {
        const { data: mem } = await supabase.from('channel_members').select('user_id').eq('channel_id', c.id)
        const other = (mem || []).map((m) => m.user_id).find((u) => u !== me)
        c._dmName = other
      }
    }
    setChannels(chs)
    setLoading(false)
    return chs
  }, [me])

  useEffect(() => { loadProfiles() }, [loadProfiles])
  useEffect(() => {
    loadChannels().then((chs) => { if (chs.length && !active) setActive(chs[0]) })
  }, [loadChannels]) // eslint-disable-line

  const loadMessages = useCallback(async (channelId) => {
    const { data: msgs } = await supabase.from('messages').select('*').eq('channel_id', channelId).order('created_at').limit(200)
    setMessages(msgs || [])
    const ids = (msgs || []).map((m) => m.id)
    if (ids.length) {
      const { data: rx } = await supabase.from('message_reactions').select('*').in('message_id', ids)
      const map = {}
      for (const r of rx || []) { (map[r.message_id] = map[r.message_id] || []).push(r) }
      setReactions(map)
    } else setReactions({})
  }, [])

  // load + realtime subscribe for active channel
  useEffect(() => {
    if (!active) return
    loadMessages(active.id)
    const ch = supabase
      .channel(`room-${active.id}`)
      .on('postgres_changes', { event: 'INSERT', schema: 'public', table: 'messages', filter: `channel_id=eq.${active.id}` },
        (payload) => setMessages((m) => (m.some((x) => x.id === payload.new.id) ? m : [...m, payload.new])))
      .on('postgres_changes', { event: 'INSERT', schema: 'public', table: 'message_reactions' },
        (payload) => setReactions((r) => ({ ...r, [payload.new.message_id]: [...(r[payload.new.message_id] || []).filter((x) => !(x.user_id === payload.new.user_id && x.emoji === payload.new.emoji)), payload.new] })))
      .subscribe()
    return () => { supabase.removeChannel(ch) }
  }, [active, loadMessages])

  useEffect(() => { endRef.current?.scrollIntoView({ behavior: 'smooth' }) }, [messages])

  async function send(e) {
    e?.preventDefault()
    const t = text.trim()
    if (!t || !active) return
    setText('')
    await supabase.from('messages').insert({ channel_id: active.id, user_id: me, text: t.slice(0, 2000) })
  }
  async function delMsg(m) {
    setMessages((x) => x.filter((y) => y.id !== m.id))
    await supabase.from('messages').delete().eq('id', m.id)
  }
  async function react(m, emoji) {
    setReactingTo(null)
    const mine = (reactions[m.id] || []).some((r) => r.user_id === me && r.emoji === emoji)
    if (mine) {
      setReactions((r) => ({ ...r, [m.id]: (r[m.id] || []).filter((x) => !(x.user_id === me && x.emoji === emoji)) }))
      await supabase.from('message_reactions').delete().eq('message_id', m.id).eq('user_id', me).eq('emoji', emoji)
    } else {
      await supabase.from('message_reactions').insert({ message_id: m.id, user_id: me, emoji })
    }
  }
  async function newChannel() {
    const name = window.prompt('New channel name:')
    if (!name?.trim()) return
    const clean = name.trim().toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-|-$/g, '').slice(0, 30)
    if (!clean) return
    const { data } = await supabase.from('channels').insert({ name: clean, is_dm: false, created_by: me }).select().single()
    if (data) { await loadChannels(); setActive(data) }
  }
  async function startDm(person) {
    setShowPeople(false)
    // find existing DM with this person
    const dms = channels.filter((c) => c.is_dm && c._dmName === person.id)
    if (dms[0]) { setActive(dms[0]); return }
    const { data: ch } = await supabase.from('channels').insert({ name: `dm`, is_dm: true, created_by: me }).select().single()
    if (!ch) return
    await supabase.from('channel_members').insert([{ channel_id: ch.id, user_id: me }, { channel_id: ch.id, user_id: person.id }])
    ch._dmName = person.id
    await loadChannels()
    setActive(ch)
  }

  const grouped = useMemo(() => {
    const out = []
    let last = null
    for (const m of messages) {
      const showDay = !last || !sameDay(last.created_at, m.created_at)
      const compact = last && last.user_id === m.user_id && !showDay && (new Date(m.created_at) - new Date(last.created_at) < 5 * 60000)
      out.push({ ...m, showDay, compact })
      last = m
    }
    return out
  }, [messages])

  const channelLabel = (c) => (c.is_dm ? `@ ${nameOf(c._dmName)}` : `# ${c.name}`)

  return (
    <main className="mx-auto flex w-full max-w-5xl flex-1 gap-3 px-2 py-4 sm:px-6">
      {/* sidebar */}
      <aside className="flex w-16 shrink-0 flex-col gap-3 sm:w-56">
        <div className="flex items-center gap-2">
          <AppIcon id="chat" className="h-7 w-7" />
          <h1 className="m-0 hidden text-lg font-extrabold tracking-tight sm:block">Chat</h1>
        </div>
        <div className="grid gap-1">
          <div className="flex items-center justify-between">
            <span className="hidden text-[11px] font-bold uppercase tracking-wide text-slate-400 sm:block">Channels</span>
            <button className="text-xs font-bold text-brand-blue hover:underline" onClick={newChannel} title="New channel">+</button>
          </div>
          {channels.filter((c) => !c.is_dm).map((c) => (
            <button key={c.id} onClick={() => setActive(c)} title={`# ${c.name}`}
              className={`truncate rounded-lg px-2 py-1.5 text-left text-sm font-semibold ${active?.id === c.id ? 'bg-brand-gradient text-white' : 'text-slate-600 hover:bg-slate-100'}`}>
              <span className="sm:hidden">#</span><span className="hidden sm:inline"># {c.name}</span>
            </button>
          ))}
        </div>
        <div className="grid gap-1">
          <div className="flex items-center justify-between">
            <span className="hidden text-[11px] font-bold uppercase tracking-wide text-slate-400 sm:block">Direct messages</span>
            <button className="text-xs font-bold text-brand-blue hover:underline" onClick={() => setShowPeople(!showPeople)} title="New DM">+</button>
          </div>
          {showPeople && (
            <div className="grid gap-0.5 rounded-lg border border-slate-200 bg-white p-1">
              {members.length === 0 ? <p className="m-0 p-1 text-xs text-slate-400">No one to message yet.</p> :
                members.map((p) => (
                  <button key={p.id} onClick={() => startDm(p)} className="flex items-center gap-2 truncate rounded px-1.5 py-1 text-left text-sm hover:bg-slate-100">
                    <Avatar p={p} size="h-6 w-6" /> <span className="hidden truncate sm:inline">{p.name}</span>
                  </button>
                ))}
            </div>
          )}
          {channels.filter((c) => c.is_dm).map((c) => (
            <button key={c.id} onClick={() => setActive(c)} title={nameOf(c._dmName)}
              className={`flex items-center gap-2 truncate rounded-lg px-2 py-1.5 text-left text-sm font-semibold ${active?.id === c.id ? 'bg-brand-gradient text-white' : 'text-slate-600 hover:bg-slate-100'}`}>
              <Avatar p={profiles[c._dmName]} size="h-6 w-6" /><span className="hidden truncate sm:inline">{nameOf(c._dmName)}</span>
            </button>
          ))}
        </div>
      </aside>

      {/* main pane */}
      <section className="flex min-w-0 flex-1 flex-col rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div className="border-b border-slate-200 px-4 py-3">
          <p className="m-0 text-sm font-extrabold">{active ? channelLabel(active) : 'Select a channel'}</p>
        </div>
        <div className="flex-1 overflow-auto px-4 py-3" style={{ maxHeight: '62vh' }}>
          {loading ? (
            <div className="grid place-items-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-4 border-slate-200 border-t-brand-violet" /></div>
          ) : grouped.length === 0 ? (
            <div className="grid h-full place-items-center text-center text-slate-400"><p className="m-0">No messages yet. Say something!</p></div>
          ) : (
            grouped.map((m) => (
              <div key={m.id}>
                {m.showDay && <div className="my-3 flex items-center gap-2 text-[11px] font-bold text-slate-400"><span className="h-px flex-1 bg-slate-200" />{fmtDay(m.created_at)}<span className="h-px flex-1 bg-slate-200" /></div>}
                <div className={`group relative flex gap-3 rounded-lg px-1 hover:bg-slate-50 ${m.compact ? 'mt-0.5' : 'mt-2'}`}>
                  {m.compact ? <span className="w-9 shrink-0" /> : <Avatar p={profiles[m.user_id]} />}
                  <div className="min-w-0 flex-1">
                    {!m.compact && <p className="m-0 text-sm"><span className="font-bold">{nameOf(m.user_id)}</span> <span className="text-[11px] text-slate-400">{fmtTime(m.created_at)}</span></p>}
                    <p className="m-0 whitespace-pre-wrap break-words text-sm leading-relaxed">{m.text}</p>
                    {(reactions[m.id] || []).length > 0 && (
                      <div className="mt-1 flex flex-wrap gap-1">
                        {Object.entries((reactions[m.id] || []).reduce((a, r) => { a[r.emoji] = (a[r.emoji] || 0) + 1; return a }, {})).map(([emoji, n]) => {
                          const mine = (reactions[m.id] || []).some((r) => r.user_id === me && r.emoji === emoji)
                          return <button key={emoji} onClick={() => react(m, emoji)} className={`rounded-full border px-1.5 py-0.5 text-xs ${mine ? 'border-brand-violet bg-brand-gradient-soft' : 'border-slate-200 bg-white'}`}>{emoji} {n}</button>
                        })}
                      </div>
                    )}
                  </div>
                  <div className="absolute right-2 top-0 hidden items-center gap-1 group-hover:flex">
                    <button className="rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-xs hover:border-brand-violet" onClick={() => setReactingTo(reactingTo === m.id ? null : m.id)}>😀</button>
                    {m.user_id === me && <button className="rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-xs hover:border-red-400 hover:text-red-500" onClick={() => delMsg(m)}>🗑</button>}
                  </div>
                  {reactingTo === m.id && (
                    <div className="absolute right-2 top-6 z-10 flex gap-1 rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                      {REACTIONS.map((e) => <button key={e} className="rounded px-1 text-lg hover:bg-slate-100" onClick={() => react(m, e)}>{e}</button>)}
                    </div>
                  )}
                </div>
              </div>
            ))
          )}
          <div ref={endRef} />
        </div>
        <form onSubmit={send} className="flex gap-2 border-t border-slate-200 p-3">
          <input
            className="min-w-0 flex-1 rounded-xl border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-brand-violet"
            placeholder={active ? `Message ${channelLabel(active)}` : 'Select a channel…'}
            value={text}
            onChange={(e) => setText(e.target.value)}
            maxLength={2000}
            disabled={!active}
          />
          <button className="rounded-xl bg-brand-gradient px-5 py-2 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-50" disabled={!active || !text.trim()}>Send</button>
        </form>
      </section>
    </main>
  )
}
