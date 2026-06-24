import { useCallback, useEffect, useRef, useState } from 'react'
import { supabase, BUCKET } from './supabase.js'
import { askClaude, getClaudeConfig } from './claude.js'
import { getSettings, saveSettings } from './settings.js'
import AppIcon from './AppIcon.jsx'

const PLATFORMS = [
  { id: 'reddit', name: 'Reddit', hint: 'Conversational title + context, no hard sell' },
  { id: 'devto', name: 'dev.to', hint: 'Markdown intro post with takeaways' },
  { id: 'bluesky', name: 'Bluesky', hint: 'Max 300 characters, 2-3 hashtags' },
  { id: 'facebook', name: 'Facebook', hint: 'Friendly, 2-3 short paragraphs' }
]

const fmtDate = (d) => new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
const fmtWhen = (d) => {
  const m = Math.round((Date.now() - new Date(d).getTime()) / 60000)
  if (m < 1) return 'now'
  if (m < 60) return `${m}m`
  if (m < 1440) return `${Math.round(m / 60)}h`
  const days = Math.round(m / 1440)
  return days < 7 ? `${days}d` : fmtDate(d)
}
const plain = (html) => (html || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
const inputCls = 'rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-violet'

function Avatar({ p, size = 'h-9 w-9' }) {
  if (p?.url) return <img src={p.url} alt="" className={`${size} shrink-0 rounded-full object-cover`} />
  return <span className={`grid ${size} shrink-0 place-items-center rounded-full bg-brand-gradient text-sm font-extrabold text-white`}>{(p?.name || '?')[0].toUpperCase()}</span>
}

function templateDraft(platform, post) {
  const base = `${post.title}\n\n${post.excerpt || ''}`
  switch (platform) {
    case 'reddit': return `Title: ${post.title}\n\n${post.excerpt || ''}\n\nFull post: ${post.link}`
    case 'devto': return `# ${post.title}\n\n${post.excerpt || ''}\n\n👉 ${post.link}`
    case 'bluesky': return `${post.title} 👇\n\n${(post.excerpt || '').slice(0, 140)}…\n\n${post.link}`
    case 'facebook': return `New on the blog! 📝\n\n${base.slice(0, 280)}\n\nRead it here: ${post.link}`
    default: return base
  }
}
function claudePrompt(platform, post) {
  const p = PLATFORMS.find((x) => x.id === platform)
  return `Write a ready-to-publish ${p.name} post promoting this blog article. Style: ${p.hint}. Sound like a real person sharing something useful, not an ad. Include the link.\n\nTitle: ${post.title}\nLink: ${post.link}\nExcerpt: ${post.excerpt || ''}\n\n${platform === 'bluesky' ? 'STRICT 300 character limit. ' : ''}${platform === 'reddit' ? 'Start with "Title: ..." then the body. ' : ''}Return ONLY the post text.`
}

export default function Blog({ session, feedOnly = false }) {
  const user = session.user
  const [tab, setTab] = useState('feed')
  const [profiles, setProfiles] = useState({})

  const loadProfiles = useCallback(async () => {
    const { data } = await supabase.from('profiles').select('id, display_name, avatar_path')
    const map = {}
    for (const r of data || []) {
      let url = null
      if (r.avatar_path) {
        const { data: s } = await supabase.storage.from(BUCKET).createSignedUrl(r.avatar_path, 3600)
        url = s?.signedUrl || null
      }
      map[r.id] = { name: r.display_name || 'Member', url }
    }
    setProfiles(map)
  }, [])
  useEffect(() => { loadProfiles() }, [loadProfiles])
  const nameOf = (id) => profiles[id]?.name || (id === user.id ? 'You' : 'Member')

  return (
    <main className="mx-auto w-full max-w-3xl flex-1 px-3 py-5 sm:px-7">
      <div className="mb-4 flex items-center gap-2">
        <AppIcon id={feedOnly ? 'feed' : 'studio'} className="h-7 w-7" />
        <div>
          <h1 className="m-0 text-xl font-extrabold tracking-tight">{feedOnly ? 'Feed' : 'Studio'}</h1>
          {feedOnly && <p className="m-0 text-xs text-slate-500">Share with your circle — private to invited people only</p>}
        </div>
      </div>

      {!feedOnly && (
        <div className="mb-4 flex gap-1 rounded-xl bg-slate-200/60 p-1">
          {[['feed', '💬 Feed'], ['blog', '📝 Blog'], ['sites', '🌐 Sites']].map(([id, label]) => (
            <button
              key={id}
              onClick={() => setTab(id)}
              className={`flex-1 rounded-lg px-3 py-1.5 text-sm font-bold transition ${tab === id ? 'bg-white text-brand-violet shadow-sm' : 'text-slate-600'}`}
            >
              {label}
            </button>
          ))}
        </div>
      )}

      {(feedOnly || tab === 'feed') && <Feed user={user} profiles={profiles} nameOf={nameOf} />}
      {!feedOnly && tab === 'blog' && <BlogTab user={user} nameOf={nameOf} profiles={profiles} />}
      {!feedOnly && tab === 'sites' && <SitesTab />}
    </main>
  )
}

function Feed({ user, profiles, nameOf }) {
  const [posts, setPosts] = useState([])
  const [likes, setLikes] = useState({})
  const [commentsByPost, setCommentsByPost] = useState({})
  const [openComments, setOpenComments] = useState(null)
  const [text, setText] = useState('')
  const [link, setLink] = useState('')
  const [busy, setBusy] = useState(false)
  const [loading, setLoading] = useState(true)
  const [reply, setReply] = useState('')

  const load = useCallback(async () => {
    const [{ data: fp }, { data: fl }, { data: fc }] = await Promise.all([
      supabase.from('feed_posts').select('*').order('created_at', { ascending: false }).limit(100),
      supabase.from('feed_likes').select('post_id, user_id'),
      supabase.from('feed_comments').select('post_id')
    ])
    setPosts(fp || [])
    const lk = {}
    for (const l of fl || []) {
      lk[l.post_id] = lk[l.post_id] || { count: 0, mine: false }
      lk[l.post_id].count++
      if (l.user_id === user.id) lk[l.post_id].mine = true
    }
    setLikes(lk)
    const cc = {}
    for (const c of fc || []) cc[c.post_id] = (cc[c.post_id] || 0) + 1
    setCommentsByPost(cc)
    setLoading(false)
  }, [user.id])
  useEffect(() => { load() }, [load])

  async function post() {
    const t = text.trim()
    if (!t) return
    setBusy(true)
    const { error } = await supabase.from('feed_posts').insert({ user_id: user.id, text: t.slice(0, 500), link: link.trim() || null })
    setBusy(false)
    if (!error) { setText(''); setLink(''); load() }
  }
  async function toggleLike(p) {
    const mine = likes[p.id]?.mine
    setLikes((l) => ({ ...l, [p.id]: { count: (l[p.id]?.count || 0) + (mine ? -1 : 1), mine: !mine } }))
    if (mine) await supabase.from('feed_likes').delete().eq('post_id', p.id).eq('user_id', user.id)
    else await supabase.from('feed_likes').insert({ post_id: p.id, user_id: user.id })
  }
  async function del(p) {
    if (!window.confirm('Delete this post?')) return
    setPosts((x) => x.filter((y) => y.id !== p.id))
    await supabase.from('feed_posts').delete().eq('id', p.id)
  }
  async function openReplies(p) {
    if (openComments === p.id) { setOpenComments(null); return }
    setOpenComments(p.id); setReply('')
    const { data } = await supabase.from('feed_comments').select('*').eq('post_id', p.id).order('created_at')
    setCommentsByPost((c) => ({ ...c, [`list:${p.id}`]: data || [] }))
  }
  async function addReply(p) {
    const t = reply.trim()
    if (!t) return
    await supabase.from('feed_comments').insert({ post_id: p.id, user_id: user.id, text: t.slice(0, 400) })
    setReply('')
    const { data } = await supabase.from('feed_comments').select('*').eq('post_id', p.id).order('created_at')
    setCommentsByPost((c) => ({ ...c, [`list:${p.id}`]: data || [], [p.id]: (data || []).length }))
  }

  return (
    <div className="grid gap-3">
      <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div className="flex items-start gap-3">
          <Avatar p={profiles[user.id]} />
          <div className="min-w-0 flex-1">
            <textarea
              className="w-full resize-none rounded-lg border-2 border-slate-200 bg-white p-2.5 text-sm outline-none focus:border-brand-violet"
              rows={2}
              maxLength={500}
              placeholder="Share something with family & friends…"
              value={text}
              onChange={(e) => setText(e.target.value)}
            />
            <input className={`${inputCls} mt-2 w-full`} placeholder="Optional link (https://…)" value={link} onChange={(e) => setLink(e.target.value)} />
            <div className="mt-2 flex items-center justify-between">
              <span className="text-xs text-slate-400">{text.length}/500</span>
              <button className="rounded-lg bg-brand-gradient px-5 py-2 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-50" onClick={post} disabled={busy || !text.trim()}>Post</button>
            </div>
          </div>
        </div>
      </div>

      {loading ? (
        <div className="grid place-items-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-4 border-slate-200 border-t-brand-violet" /></div>
      ) : posts.length === 0 ? (
        <div className="grid justify-items-center gap-2 py-14 text-center text-slate-500"><p className="m-0 text-4xl">💬</p><p className="m-0">No posts yet. Say hi to the family!</p></div>
      ) : (
        posts.map((p) => (
          <article key={p.id} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex items-start gap-3">
              <Avatar p={profiles[p.user_id]} />
              <div className="min-w-0 flex-1">
                <p className="m-0 text-sm"><span className="font-bold">{nameOf(p.user_id)}</span> <span className="text-slate-400">· {fmtWhen(p.created_at)}</span></p>
                <p className="m-0 mt-0.5 whitespace-pre-wrap text-sm leading-relaxed">{p.text}</p>
                {p.link && <a href={/^https?:/.test(p.link) ? p.link : `https://${p.link}`} target="_blank" rel="noopener noreferrer" className="mt-1 block truncate text-sm font-semibold text-brand-blue hover:underline">🔗 {p.link}</a>}
                <div className="mt-2 flex items-center gap-4 text-xs font-bold text-slate-500">
                  <button className={`flex items-center gap-1 ${likes[p.id]?.mine ? 'text-rose-500' : 'hover:text-rose-500'}`} onClick={() => toggleLike(p)}>
                    {likes[p.id]?.mine ? '❤️' : '🤍'} {likes[p.id]?.count || 0}
                  </button>
                  <button className="flex items-center gap-1 hover:text-brand-violet" onClick={() => openReplies(p)}>💬 {commentsByPost[p.id] || 0}</button>
                  <button className="flex items-center gap-1 hover:text-brand-violet" onClick={() => { const t = p.text + (p.link ? ' ' + p.link : ''); if (navigator.share) navigator.share({ text: t }).catch(() => {}); else navigator.clipboard?.writeText(t) }} title="Share / copy">📤</button>
                  {p.user_id === user.id && <button className="ml-auto hover:text-red-500" onClick={() => del(p)}>🗑</button>}
                </div>

                {openComments === p.id && (
                  <div className="mt-3 grid gap-2 border-t border-slate-100 pt-3">
                    {(commentsByPost[`list:${p.id}`] || []).map((c) => (
                      <div key={c.id} className="flex items-start gap-2">
                        <Avatar p={profiles[c.user_id]} size="h-7 w-7" />
                        <p className="m-0 text-sm"><span className="font-bold">{nameOf(c.user_id)}</span> <span className="text-slate-400">· {fmtWhen(c.created_at)}</span><br />{c.text}</p>
                      </div>
                    ))}
                    <div className="flex gap-2">
                      <input className={`${inputCls} flex-1`} placeholder="Reply…" value={reply} onChange={(e) => setReply(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && addReply(p)} />
                      <button className="rounded-lg bg-brand-gradient px-3 py-2 text-xs font-bold text-white" onClick={() => addReply(p)}>Reply</button>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </article>
        ))
      )}
    </div>
  )
}

function BlogTab({ user, nameOf, profiles }) {
  const [posts, setPosts] = useState([])
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(null)
  const [reading, setReading] = useState(null)
  const [msg, setMsg] = useState(null)
  const editorRef = useRef(null)

  const load = useCallback(async () => {
    const { data } = await supabase.from('posts').select('*').order('updated_at', { ascending: false })
    setPosts(data || [])
    setLoading(false)
  }, [])
  useEffect(() => { load() }, [load])

  const cmd = (c, v = null) => { editorRef.current?.focus(); document.execCommand(c, false, v) }

  async function savePost(publish) {
    const html = editorRef.current?.innerHTML || editing.content_html || ''
    const row = {
      user_id: user.id,
      title: editing.title.trim() || 'Untitled',
      content_html: html,
      excerpt: plain(html).slice(0, 280),
      published: publish,
      updated_at: new Date().toISOString()
    }
    if (editing.id) await supabase.from('posts').update(row).eq('id', editing.id)
    else await supabase.from('posts').insert(row)
    setEditing(null); load()
    setMsg({ ok: true, text: publish ? '✓ Published to your family blog.' : 'Draft saved.' })
    setTimeout(() => setMsg(null), 3500)
  }
  async function delPost(p) {
    if (!window.confirm(`Delete "${p.title}"?`)) return
    setReading(null)
    await supabase.from('posts').delete().eq('id', p.id); load()
  }
  async function shareToFeed(p) {
    await supabase.from('feed_posts').insert({ user_id: user.id, text: `📝 New post: ${p.title}`, link: null })
    setMsg({ ok: true, text: 'Shared an announcement to the Feed.' })
    setTimeout(() => setMsg(null), 3500)
  }

  return (
    <div className="grid gap-3">
      <div className="flex items-center justify-between">
        <p className="m-0 text-sm text-slate-500">Write posts and publish them to your family & friends.</p>
        <button className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110" onClick={() => setEditing({ id: null, title: '', content_html: '', published: false })}>+ New post</button>
      </div>
      {msg && <p className={`m-0 rounded-xl px-4 py-2.5 text-sm ${msg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{msg.text}</p>}

      {loading ? (
        <div className="grid place-items-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-4 border-slate-200 border-t-brand-violet" /></div>
      ) : posts.length === 0 ? (
        <div className="grid justify-items-center gap-2 py-14 text-center text-slate-500"><p className="m-0 text-4xl">📝</p><p className="m-0">No posts yet — write your first one.</p></div>
      ) : (
        posts.map((p) => (
          <article key={p.id} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex items-center gap-2">
              <Avatar p={profiles[p.user_id]} size="h-7 w-7" />
              <span className="text-xs font-semibold text-slate-500">{nameOf(p.user_id)} · {fmtDate(p.updated_at)}</span>
              {!p.published && <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">Draft</span>}
            </div>
            <button className="mt-1 block text-left" onClick={() => setReading(p)}>
              <h2 className="m-0 text-base font-extrabold leading-snug hover:text-brand-violet">{p.title}</h2>
              <p className="m-0 mt-0.5 line-clamp-2 text-sm text-slate-500">{p.excerpt || plain(p.content_html).slice(0, 160)}</p>
            </button>
            {p.user_id === user.id && (
              <div className="mt-2 flex gap-2">
                <button className="rounded-full border-2 border-slate-200 px-3 py-1 text-xs font-bold text-slate-600 hover:border-brand-violet" onClick={() => setEditing({ ...p })}>Edit</button>
                {p.published && <button className="rounded-full border-2 border-slate-200 px-3 py-1 text-xs font-bold text-slate-600 hover:border-brand-violet" onClick={() => shareToFeed(p)}>Share to Feed</button>}
              </div>
            )}
          </article>
        ))
      )}

      {reading && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-indigo-950/70 p-3.5" onClick={() => setReading(null)} role="dialog" aria-modal="true">
          <div className="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4">
              <div>
                <h2 className="m-0 text-lg font-extrabold leading-snug">{reading.title}</h2>
                <p className="m-0 text-xs text-slate-500">{nameOf(reading.user_id)} · {fmtDate(reading.updated_at)}</p>
              </div>
              <button className="shrink-0 rounded-lg border-2 border-slate-200 px-3 py-1.5 text-sm font-bold" onClick={() => setReading(null)}>✕</button>
            </div>
            <div className="overflow-auto px-5 py-4 text-sm leading-relaxed [&_a]:text-brand-blue [&_a]:underline [&_h1]:text-xl [&_h1]:font-extrabold [&_h2]:text-lg [&_h2]:font-bold [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6" dangerouslySetInnerHTML={{ __html: reading.content_html || '<p>(empty)</p>' }} />
            {reading.user_id === user.id && (
              <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-3">
                <button className="text-sm font-bold text-red-500 hover:underline" onClick={() => delPost(reading)}>Delete</button>
                <button className="rounded-lg border-2 border-slate-200 px-4 py-2 text-sm font-bold text-slate-600" onClick={() => { setEditing({ ...reading }); setReading(null) }}>Edit</button>
              </div>
            )}
          </div>
        </div>
      )}

      {editing && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-indigo-950/70 p-2 sm:p-4" role="dialog" aria-modal="true">
          <div className="flex max-h-[96vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white">
            <input className="border-b border-slate-200 px-5 py-3 text-lg font-extrabold outline-none" placeholder="Post title…" value={editing.title} onChange={(e) => setEditing({ ...editing, title: e.target.value })} maxLength={150} autoFocus />
            <div className="flex flex-wrap gap-1 border-b border-slate-200 bg-slate-50 px-3 py-2">
              {[['bold', <b>B</b>], ['italic', <i>I</i>], ['formatBlock:<h2>', 'H2'], ['insertUnorderedList', '•≡'], ['insertOrderedList', '1≡']].map(([c, lbl]) => (
                <button key={c} className="min-w-[34px] rounded-md border border-slate-200 bg-white px-2 py-1 text-sm font-bold text-slate-600 hover:border-brand-violet" onMouseDown={(e) => { e.preventDefault(); const [cc, v] = String(c).split(':'); cmd(cc, v || null) }}>{lbl}</button>
              ))}
              <button className="min-w-[34px] rounded-md border border-slate-200 bg-white px-2 py-1 text-sm font-bold text-slate-600 hover:border-brand-violet" onMouseDown={(e) => { e.preventDefault(); const u = window.prompt('Link URL:'); if (u) cmd('createLink', /^https?:/.test(u) ? u : `https://${u}`) }}>🔗</button>
            </div>
            <div ref={editorRef} contentEditable suppressContentEditableWarning className="min-h-[220px] flex-1 overflow-auto px-5 py-4 text-sm leading-relaxed outline-none [&_h2]:text-lg [&_h2]:font-bold [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6 [&_a]:text-brand-blue [&_a]:underline" dangerouslySetInnerHTML={{ __html: editing.content_html || '' }} />
            <div className="flex items-center justify-between gap-2 border-t border-slate-200 px-4 py-3">
              <button className="text-sm font-bold text-slate-500" onClick={() => setEditing(null)}>Cancel</button>
              <div className="flex gap-2">
                <button className="rounded-lg border-2 border-brand-violet px-4 py-2 text-sm font-bold text-brand-violet hover:bg-brand-gradient-soft" onClick={() => savePost(false)}>Save draft</button>
                <button className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110" onClick={() => savePost(true)}>🚀 Publish</button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

function SitesTab() {
  const [wpUrl, setWpUrl] = useState(getSettings().wpUrl || '')
  const [connected, setConnected] = useState(getSettings().wpUrl || '')
  const [posts, setPosts] = useState([])
  const [page, setPage] = useState(1)
  const [hasMore, setHasMore] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [share, setShare] = useState(null)
  const [imgQuery, setImgQuery] = useState('')
  const [images, setImages] = useState(null)
  const [imgBusy, setImgBusy] = useState(false)
  const [copied, setCopied] = useState(null)

  const load = useCallback(async (site, p = 1) => {
    if (!site) return
    if (p === 1) { setLoading(true); setPosts([]) }
    setError(null)
    const { data: res, error: err } = await supabase.functions.invoke('blog', { body: { site, page: p } })
    setLoading(false)
    if (err || res?.error) { setError(res?.error || 'Could not load that site.'); return }
    setPosts((prev) => (p === 1 ? res.posts : [...prev, ...res.posts]))
    setPage(p); setHasMore(!!res.hasMore)
  }, [])

  useEffect(() => { if (connected) load(connected, 1) }, [connected, load])

  function connect(e) {
    e?.preventDefault()
    const u = wpUrl.trim()
    if (!u) return
    saveSettings({ ...getSettings(), wpUrl: u })
    setConnected(u)
  }

  async function copy(text, tag) { try { await navigator.clipboard.writeText(text); setCopied(tag); setTimeout(() => setCopied(null), 2000) } catch { /* */ } }
  function openShare(post) {
    const drafts = {}
    for (const p of PLATFORMS) drafts[p.id] = templateDraft(p.id, post)
    setShare({ post, platform: 'reddit', drafts, busy: false, err: null })
    setImgQuery((post.title || '').split(':')[0].slice(0, 60)); setImages(null)
  }
  async function genClaude() {
    if (!getClaudeConfig().key) { setShare((s) => ({ ...s, err: 'Add a Claude API key in Settings → Claude AI to auto-write, or use the template below.' })); return }
    setShare((s) => ({ ...s, busy: true, err: null }))
    try { const t = await askClaude(claudePrompt(share.platform, share.post), 800); setShare((s) => ({ ...s, busy: false, drafts: { ...s.drafts, [s.platform]: t } })) }
    catch (e) { setShare((s) => ({ ...s, busy: false, err: e.message })) }
  }
  function openShareUrl() {
    const { post, platform, drafts } = share
    const text = drafts[platform]
    let url = ''
    if (platform === 'reddit') { const title = text.startsWith('Title:') ? text.split('\n')[0].replace(/^Title:\s*/, '') : post.title; url = `https://www.reddit.com/submit?url=${encodeURIComponent(post.link)}&title=${encodeURIComponent(title)}` }
    else if (platform === 'bluesky') url = `https://bsky.app/intent/compose?text=${encodeURIComponent(text.slice(0, 300))}`
    else if (platform === 'facebook') url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(post.link)}`
    else if (platform === 'devto') { copy(text, 'devto-open'); url = 'https://dev.to/new' }
    window.open(url, '_blank', 'noopener')
  }
  async function searchImages(p = 1) {
    if (!imgQuery.trim()) return
    setImgBusy(true)
    const { data: res, error: err } = await supabase.functions.invoke('images', { body: { q: imgQuery.trim(), page: p } })
    setImgBusy(false)
    setImages(err || res?.error ? { error: 'Image search failed — try again.' } : res)
  }

  return (
    <div className="grid gap-3">
      <form onSubmit={connect} className="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div className="min-w-0 flex-1">
          <p className="m-0 mb-1.5 text-sm font-bold">Connect a WordPress site</p>
          <input className={`${inputCls} w-full`} placeholder="yourblog.com" value={wpUrl} onChange={(e) => setWpUrl(e.target.value)} />
          <p className="m-0 mt-1 text-[11px] text-slate-400">Pulls posts via the site's public WordPress REST API. Try <button type="button" className="font-bold text-brand-blue hover:underline" onClick={() => setWpUrl('matthummel.com')}>matthummel.com</button>.</p>
        </div>
        <button className="self-end rounded-lg bg-brand-gradient px-5 py-2 text-sm font-bold text-white transition hover:brightness-110">Connect</button>
      </form>

      {loading && <div className="grid place-items-center py-16"><div className="h-8 w-8 animate-spin rounded-full border-4 border-slate-200 border-t-brand-violet" /></div>}
      {error && !loading && <div className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{error}</div>}

      {!loading && !error && connected && posts.length > 0 && (
        <>
          <p className="m-0 text-xs text-slate-500">Showing posts from <strong>{connected.replace(/^https?:\/\//, '')}</strong></p>
          <div className="grid gap-3 sm:grid-cols-2">
            {posts.map((post) => (
              <article key={post.id} className="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-lg">
                <div className="flex flex-wrap gap-1.5">
                  {(post.categories || []).slice(0, 2).map((c) => <span key={c} className="rounded-full bg-brand-gradient-soft px-2.5 py-0.5 text-[11px] font-bold text-brand-violet">{c}</span>)}
                  <span className="text-xs font-semibold text-slate-400">{fmtDate(post.date)}</span>
                </div>
                <h2 className="m-0 text-base font-extrabold leading-snug">{post.title}</h2>
                <p className="m-0 flex-1 text-sm leading-relaxed text-slate-600">{(post.excerpt || '').slice(0, 180)}{(post.excerpt || '').length > 180 ? '…' : ''}</p>
                <div className="mt-1 flex flex-wrap gap-2">
                  <a href={post.link} target="_blank" rel="noopener noreferrer" className="rounded-full border-2 border-slate-200 px-3 py-1 text-xs font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet">View post ↗</a>
                  <button onClick={() => openShare(post)} className="rounded-full bg-brand-gradient px-3 py-1 text-xs font-bold text-white transition hover:brightness-110">📣 Share & promote</button>
                </div>
              </article>
            ))}
          </div>
          {hasMore && <div className="grid place-items-center py-4"><button className="rounded-full border-2 border-slate-200 bg-white px-5 py-2 text-sm font-bold text-slate-600 hover:border-brand-violet" onClick={() => load(connected, page + 1)}>Load more</button></div>}
        </>
      )}

      {share && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-indigo-950/70 p-3.5" onClick={() => setShare(null)} role="dialog" aria-modal="true">
          <div className="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4">
              <div><span className="mb-1 inline-block rounded-full bg-brand-gradient px-3 py-0.5 text-[11px] font-bold text-white">📣 Share & promote</span><h3 className="m-0 text-base font-extrabold leading-snug">{share.post.title}</h3></div>
              <button className="shrink-0 rounded-lg border-2 border-slate-200 px-3 py-1.5 text-sm font-bold" onClick={() => setShare(null)}>✕</button>
            </div>
            <div className="grid gap-4 overflow-auto px-5 py-4">
              <div className="flex flex-wrap gap-2">
                {PLATFORMS.map((p) => (
                  <button key={p.id} onClick={() => setShare((s) => ({ ...s, platform: p.id, err: null }))} className={share.platform === p.id ? 'rounded-full bg-brand-gradient px-4 py-2 text-sm font-bold text-white' : 'rounded-full border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 hover:border-brand-violet'}>{p.name}</button>
                ))}
              </div>
              <p className="m-0 text-xs text-slate-500">{PLATFORMS.find((p) => p.id === share.platform)?.hint}</p>
              <textarea className="min-h-[170px] w-full resize-y rounded-xl border-2 border-slate-200 bg-white p-3 text-sm leading-relaxed outline-none focus:border-brand-violet" value={share.drafts[share.platform]} onChange={(e) => setShare((s) => ({ ...s, drafts: { ...s.drafts, [s.platform]: e.target.value } }))} />
              {share.platform === 'bluesky' && <p className={`m-0 text-xs font-semibold ${share.drafts.bluesky.length > 300 ? 'text-red-500' : 'text-slate-400'}`}>{share.drafts.bluesky.length}/300</p>}
              {share.err && <p className="m-0 rounded-xl bg-red-50 px-4 py-2.5 text-sm text-red-600">{share.err}</p>}
              <div className="flex flex-wrap gap-2">
                <button onClick={genClaude} disabled={share.busy} className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-60">{share.busy ? '✨ Writing…' : '✨ Generate with Claude'}</button>
                <button onClick={() => copy(share.drafts[share.platform], 'draft')} className="rounded-lg border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 hover:border-brand-violet">{copied === 'draft' ? '✓ Copied' : '📋 Copy'}</button>
                <button onClick={openShareUrl} className="rounded-lg border-2 border-brand-violet px-4 py-2 text-sm font-bold text-brand-violet transition hover:bg-brand-gradient hover:text-white">{share.platform === 'devto' ? '🚀 Copy & open dev.to' : `🚀 Share on ${PLATFORMS.find((p) => p.id === share.platform)?.name}`}</button>
              </div>
              {copied === 'devto-open' && <p className="m-0 text-xs font-semibold text-emerald-600">Draft copied — paste it into dev.to.</p>}
              <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <p className="mb-2 mt-0 text-sm font-bold">🖼 Find an image</p>
                <div className="flex gap-2">
                  <input className={`${inputCls} min-w-0 flex-1`} value={imgQuery} onChange={(e) => setImgQuery(e.target.value)} placeholder="e.g. workflow, dashboard" onKeyDown={(e) => e.key === 'Enter' && searchImages(1)} />
                  <button onClick={() => searchImages(1)} disabled={imgBusy} className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white disabled:opacity-60">{imgBusy ? '…' : 'Search'}</button>
                </div>
                <p className="mb-0 mt-1.5 text-[11px] text-slate-400">Openly-licensed images from Openverse.</p>
                {images?.error && <p className="mb-0 mt-2 text-sm text-red-600">{images.error}</p>}
                {images?.images && (
                  <div className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    {images.images.map((im) => (
                      <figure key={im.id} className="m-0 overflow-hidden rounded-lg border border-slate-200 bg-white">
                        <a href={im.source} target="_blank" rel="noopener noreferrer"><img src={im.thumb} alt={im.title} loading="lazy" className="h-20 w-full object-cover" /></a>
                        <figcaption className="grid gap-1 p-1.5"><span className="truncate text-[10px] text-slate-500">{im.creator} · {im.license}</span><button onClick={() => copy(im.url, `img-${im.id}`)} className="rounded border border-slate-200 px-1 py-0.5 text-[10px] font-bold text-slate-600 hover:border-brand-violet">{copied === `img-${im.id}` ? '✓' : 'Copy URL'}</button></figcaption>
                      </figure>
                    ))}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
