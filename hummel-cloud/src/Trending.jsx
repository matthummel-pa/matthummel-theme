import { useCallback, useEffect, useRef, useState } from 'react'
import { supabase } from './supabase.js'
import { getSettings } from './settings.js'

const LS_KEY = 'hc-trending-categories'

const DEFAULT_CATEGORIES = [
  { name: 'Tech', emoji: '💻', query: 'technology power platform' },
  { name: 'Finance', emoji: '💰', query: 'finance fintech' },
  { name: 'Pop Culture', emoji: '🎬', query: 'pop culture entertainment' },
  { name: 'Health', emoji: '🩺', query: 'health wellness' }
]

const loadCategories = () => {
  try {
    const raw = JSON.parse(localStorage.getItem(LS_KEY))
    if (Array.isArray(raw) && raw.length && raw.every((c) => c?.name && c?.query)) return raw
  } catch { /* fall through */ }
  return DEFAULT_CATEGORIES
}

const PICK_EMOJI = ['🔥', '⭐', '🚀', '💡', '🌍', '🎯', '📊', '🎮', '⚽', '🍿', '🏠', '✈️']

const shuffle = (arr) => {
  const a = [...arr]
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[a[i], a[j]] = [a[j], a[i]]
  }
  return a
}

const fmtWhen = (d) => {
  if (!d) return ''
  const dt = new Date(d)
  if (isNaN(dt)) return ''
  const hrs = Math.round((Date.now() - dt.getTime()) / 3600000)
  if (hrs < 1) return 'just now'
  if (hrs < 24) return `${hrs}h ago`
  return `${Math.round(hrs / 24)}d ago`
}

function ItemLinks({ item, onSummarize, onFavorite, className = '' }) {
  return (
    <div className={`flex flex-wrap items-center gap-2 ${className}`}>
      <a
        href={item.link}
        target="_blank"
        rel="noopener noreferrer"
        className="rounded-full border-2 border-slate-200 px-3 py-1 text-xs font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet"
      >
        Full article ↗
      </a>
      <button
        onClick={() => onSummarize(item)}
        className="rounded-full bg-brand-gradient px-3 py-1 text-xs font-bold text-white transition hover:brightness-110"
      >
        ✨ Summarized version
      </button>
      <button
        onClick={() => onFavorite(item)}
        title="Pin to Home favorites"
        className="rounded-full border-2 border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600 transition hover:border-amber-400 hover:text-amber-500"
      >
        ☆
      </button>
    </div>
  )
}

export default function Trending({ session, go }) {
  const [cats, setCats] = useState(loadCategories)
  const [active, setActive] = useState(() => loadCategories()[0])
  const [editing, setEditing] = useState(false)
  const [searchText, setSearchText] = useState('')
  const [articles, setArticles] = useState([])
  const [posts, setPosts] = useState([])
  const [page, setPage] = useState(0)
  const [hasMore, setHasMore] = useState(false)
  const [loadingMore, setLoadingMore] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [summary, setSummary] = useState(null)
  const [busyNote, setBusyNote] = useState(null)
  const [myPosts, setMyPosts] = useState([])
  const [readerCount, setReaderCount] = useState(0)
  useEffect(() => {
    if (!session) return
    supabase.from('posts').select('id,title,updated_at').eq('user_id', session.user.id).eq('published', true).order('updated_at', { ascending: false }).limit(5).then(({ data }) => setMyPosts(data || []))
    supabase.from('profiles').select('id', { count: 'exact', head: true }).then(({ count }) => setReaderCount(count || 0))
  }, [session])
  const TREND_TERMS = ['Power Platform', 'AI tools', 'Cybersecurity', 'Productivity', 'New gadgets', 'Remote work']
  const runTerm = (term) => setActive({ name: term, emoji: '🔎', query: term, adhoc: true })
  const [voiceState, setVoiceState] = useState('idle') // idle | playing | paused
  const [voices, setVoices] = useState([])
  const [voiceURI, setVoiceURI] = useState('')
  const [rate, setRate] = useState(() => Number(localStorage.getItem('hc-voice-rate')) || 1)
  const queueRef = useRef(null)
  const sentinelRef = useRef(null)

  const saveCats = (next) => {
    setCats(next)
    try { localStorage.setItem(LS_KEY, JSON.stringify(next)) } catch { /* ignore */ }
  }

  const fetchTrending = useCallback(async (query, { randomize = false } = {}) => {
    setLoading(true)
    setError(null)
    setArticles([])
    setPosts([])
    setPage(0)
    setHasMore(false)
    const { data: res, error: err } = await supabase.functions.invoke('trending', { body: { q: query, page: 0, mkt: getSettings().market } })
    setLoading(false)
    if (err || res?.error) {
      setError('Could not load live trends right now. Try again in a minute.')
      return
    }
    const arts = randomize ? shuffle(res.articles || []) : res.articles || []
    setArticles(arts)
    setPosts(res.posts || [])
    setHasMore(!!res.hasMore)
  }, [])

  useEffect(() => {
    if (active) fetchTrending(active.query)
  }, [active, fetchTrending])

  const loadMore = useCallback(async () => {
    if (loadingMore || !hasMore || !active) return
    setLoadingMore(true)
    const next = page + 1
    const { data: res, error: err } = await supabase.functions.invoke('trending', { body: { q: active.query, page: next, mkt: getSettings().market } })
    setLoadingMore(false)
    if (err || res?.error) {
      setHasMore(false)
      return
    }
    setPage(next)
    const seen = new Set(articles.map((a) => a.link))
    const fresh = (res.articles || []).filter((a) => !seen.has(a.link))
    if (fresh.length) setArticles([...articles, ...fresh])
    setHasMore(!!res.hasMore && fresh.length > 0)
  }, [active, page, hasMore, loadingMore, articles])

  useEffect(() => {
    const el = sentinelRef.current
    if (!el || !hasMore || loading) return
    const obs = new IntersectionObserver(
      (entries) => { if (entries[0].isIntersecting) loadMore() },
      { rootMargin: '700px' }
    )
    obs.observe(el)
    return () => obs.disconnect()
  }, [hasMore, loading, loadMore])

  // Load available voices, best-sounding first (Natural/Neural voices rank top)
  useEffect(() => {
    const synth = window.speechSynthesis
    if (!synth) return
    const score = (v) => {
      const n = v.name.toLowerCase()
      let s = 0
      if (/natural|neural|online|premium|enhanced/.test(n)) s += 4
      if (/google|microsoft|siri|samantha|aria|jenny|guy|ryan|sonia/.test(n)) s += 2
      if (v.localService) s += 1
      return s
    }
    const load = () => {
      const en = synth.getVoices().filter((v) => v.lang?.toLowerCase().startsWith('en'))
      en.sort((a, b) => score(b) - score(a))
      setVoices(en.slice(0, 15))
    }
    load()
    synth.addEventListener?.('voiceschanged', load)
    return () => synth.removeEventListener?.('voiceschanged', load)
  }, [])

  useEffect(() => {
    if (!voices.length) return
    const saved = localStorage.getItem('hc-voice')
    setVoiceURI(saved && voices.some((v) => v.voiceURI === saved) ? saved : voices[0].voiceURI)
  }, [voices])

  const stopVoice = useCallback(() => {
    queueRef.current = null
    try { window.speechSynthesis?.cancel() } catch { /* ignore */ }
    setVoiceState('idle')
  }, [])

  const playVoice = useCallback(() => {
    const synth = window.speechSynthesis
    if (!synth || !summary?.data) return
    if (voiceState === 'paused') {
      synth.resume()
      setVoiceState('playing')
      return
    }
    synth.cancel()
    const text = [summary.data.title, ...(summary.data.points || [])].filter(Boolean).join('. ')
    if (!text) return
    // speak sentence-by-sentence so long summaries never cut off mid-read
    const chunks = (text.match(/[^.!?]+[.!?]+\s*|[^.!?]+$/g) || [text]).map((c) => c.trim()).filter(Boolean)
    const voice = voices.find((v) => v.voiceURI === voiceURI)
    const token = {}
    queueRef.current = token
    let i = 0
    const next = () => {
      if (queueRef.current !== token) return
      if (i >= chunks.length) {
        queueRef.current = null
        setVoiceState('idle')
        return
      }
      const u = new SpeechSynthesisUtterance(chunks[i++])
      if (voice) u.voice = voice
      u.rate = rate
      u.onend = next
      u.onerror = next
      synth.speak(u)
    }
    next()
    setVoiceState('playing')
  }, [summary, voiceState, voices, voiceURI, rate])

  const pauseVoice = useCallback(() => {
    try { window.speechSynthesis?.pause() } catch { /* ignore */ }
    setVoiceState('paused')
  }, [])

  useEffect(() => () => { try { window.speechSynthesis?.cancel() } catch { /* ignore */ } }, [])

  const favoriteItem = useCallback(async (item) => {
    const { data: { user } } = await supabase.auth.getUser()
    if (!user) return
    const { error: err } = await supabase.from('favorites').insert({
      user_id: user.id,
      kind: 'article',
      payload: { title: item.title, link: item.link, source: item.source || '' }
    })
    if (!err || /duplicate/i.test(err.message)) {
      setError(null)
      setBusyNote('⭐ Saved to your Home page favorites')
      setTimeout(() => setBusyNote(null), 2500)
    }
  }, [])

  const summarizeItem = useCallback(async (item) => {
    stopVoice()
    setSummary({ item, loading: true })
    const { data: res, error: err } = await supabase.functions.invoke('summarize', { body: { url: item.link } })
    if (err || res?.error) {
      setSummary({ item, loading: false, error: res?.error || 'Could not summarize this article. It may be behind a paywall — try the full article link instead.' })
      return
    }
    setSummary({ item, loading: false, data: res })
  }, [])

  function addCategory() {
    const name = window.prompt('New category name (this is also what gets searched):')
    if (!name?.trim()) return
    const clean = name.trim().slice(0, 40)
    if (cats.some((c) => c.name.toLowerCase() === clean.toLowerCase())) return
    const emoji = PICK_EMOJI[cats.length % PICK_EMOJI.length]
    const next = [...cats, { name: clean, emoji, query: clean }]
    saveCats(next)
    setActive(next[next.length - 1])
  }

  function removeCategory(name) {
    const next = cats.filter((c) => c.name !== name)
    if (!next.length) return
    saveCats(next)
    if (active?.name === name) setActive(next[0])
  }

  function resetCategories() {
    saveCats(DEFAULT_CATEGORIES)
    setActive(DEFAULT_CATEGORIES[0])
    setEditing(false)
  }

  function runSearch(e) {
    e.preventDefault()
    const q = searchText.trim()
    if (!q) return
    setActive({ name: q, emoji: '🔎', query: q, adhoc: true })
  }

  const hero = articles[0]
  const grid = articles.slice(1)

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-3 py-5 sm:px-7">
      <form onSubmit={runSearch} className="mb-4 flex gap-2">
        <input
          type="search"
          className="min-w-0 flex-1 rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-brand-violet"
          placeholder="Search trending on any topic — e.g. “SharePoint”, “college football”, “AI agents”…"
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
          maxLength={100}
        />
        <button className="rounded-xl bg-brand-gradient px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110">
          Search
        </button>
      </form>

      <nav className="mb-2 flex flex-wrap items-center gap-2" aria-label="Trending categories">
        {getSettings().location?.city && (
          <button
            onClick={() => {
              const loc = getSettings().location
              setActive({ name: `${loc.city} local`, emoji: '📍', query: `${loc.city} ${loc.region || loc.country || ''}`.trim(), adhoc: true })
            }}
            aria-pressed={active?.name === `${getSettings().location.city} local`}
            title="Local news for your saved location"
            className={
              active?.name === `${getSettings().location.city} local`
                ? 'rounded-full bg-brand-gradient px-4 py-2 text-sm font-bold text-white'
                : 'rounded-full border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet'
            }
          >
            📍 {getSettings().location.city}
          </button>
        )}
        {cats.map((c) => (
          <span key={c.name} className="relative inline-flex">
            <button
              onClick={() => setActive(c)}
              aria-pressed={active?.name === c.name && !active?.adhoc}
              className={
                active?.name === c.name && !active?.adhoc
                  ? 'rounded-full bg-brand-gradient px-4 py-2 text-sm font-bold text-white'
                  : 'rounded-full border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet'
              }
            >
              {c.emoji} {c.name}
            </button>
            {editing && cats.length > 1 && (
              <button
                onClick={() => removeCategory(c.name)}
                title={`Remove ${c.name}`}
                aria-label={`Remove ${c.name}`}
                className="absolute -right-1.5 -top-1.5 grid h-5 w-5 place-items-center rounded-full bg-red-500 text-[11px] font-bold text-white"
              >
                ✕
              </button>
            )}
          </span>
        ))}
        <button
          onClick={addCategory}
          className="rounded-full border-2 border-dashed border-slate-300 px-4 py-2 text-sm font-bold text-slate-500 transition hover:border-brand-violet hover:text-brand-violet"
        >
          + Add
        </button>
        <button
          onClick={() => setEditing(!editing)}
          className={`rounded-full px-3 py-2 text-sm font-bold transition ${editing ? 'bg-slate-700 text-white' : 'text-slate-500 hover:text-brand-violet'}`}
        >
          {editing ? 'Done' : 'Edit'}
        </button>
        {editing && (
          <button onClick={resetCategories} className="px-2 py-2 text-xs font-semibold text-slate-400 hover:text-red-500">
            Reset defaults
          </button>
        )}
        <span className="ml-auto">
          <button
            onClick={() => active && fetchTrending(active.query, { randomize: true })}
            disabled={loading}
            title="Refresh and shuffle the results"
            className="rounded-full border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet disabled:opacity-50"
          >
            🔀 Refresh
          </button>
        </span>
      </nav>

      <div className="lg:grid lg:grid-cols-[1fr_300px] lg:items-start lg:gap-6">
        <div className="min-w-0">
      {active?.adhoc && (
        <p className="mb-2 flex items-center gap-2 text-sm text-slate-500">
          Showing live trends for <strong className="text-ink">“{active.name}”</strong>
          <button
            onClick={() => {
              if (cats.some((c) => c.name.toLowerCase() === active.name.toLowerCase())) return
              const next = [...cats, { name: active.name, emoji: '🔎', query: active.query }]
              saveCats(next)
            }}
            className="rounded-full bg-brand-gradient-soft px-3 py-1 text-xs font-bold text-brand-violet hover:brightness-95"
          >
            + Save as category
          </button>
        </p>
      )}

      {loading && (
        <div className="grid place-items-center py-24">
          <div className="h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-brand-violet" />
          <p className="mt-3 text-sm text-slate-500">Searching the internet for “{active?.name}”…</p>
        </div>
      )}

      {busyNote && <div className="mb-3 rounded-xl bg-emerald-50 px-4 py-2.5 text-sm text-emerald-700">{busyNote}</div>}

      {error && !loading && (
        <div className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">
          {error}{' '}
          <button className="font-bold underline" onClick={() => fetchTrending(active.query)}>Retry</button>
        </div>
      )}

      {!loading && !error && articles.length > 0 && (
        <>
          {hero && (
            <section aria-labelledby="top-article" className="mb-7">
              <h2 id="top-article" className="mb-3 text-lg font-extrabold tracking-tight">Top Article</h2>
              <article className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-lg">
                <div className="grid gap-2 p-5 md:p-7">
                  <span className="w-fit rounded-full bg-brand-gradient px-3 py-1 text-xs font-bold text-white">
                    {hero.source || 'News'}
                  </span>
                  <h3 className="m-0 text-xl font-extrabold leading-snug tracking-tight md:text-2xl">{hero.title}</h3>
                  {hero.summary && <p className="m-0 text-sm leading-relaxed text-slate-600">{hero.summary}</p>}
                  <p className="m-0 text-xs font-semibold text-slate-500">{fmtWhen(hero.published)}</p>
                  <ItemLinks item={hero} onSummarize={summarizeItem} onFavorite={favoriteItem} className="mt-1" />
                </div>
              </article>
            </section>
          )}

          {posts.length > 0 && (
            <section aria-labelledby="social-buzz" className="mb-7">
              <h2 id="social-buzz" className="mb-3 text-lg font-extrabold tracking-tight">Social Buzz</h2>
              <ul className="m-0 grid list-none gap-3 p-0 sm:grid-cols-2">
                {posts.map((b, i) => (
                  <li key={i}>
                    <a
                      href={b.link}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex h-full items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-lg"
                    >
                      <span className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-gradient text-lg text-white" aria-hidden="true">
                        {b.handle?.replace('r/', '')[0]?.toUpperCase() || '#'}
                      </span>
                      <div className="grid gap-1">
                        <span className="text-sm font-bold text-brand-blue">{b.handle}</span>
                        <p className="m-0 text-sm leading-relaxed">{b.text}</p>
                        <span className="text-xs font-semibold text-slate-500">🔥 {b.stat}</span>
                      </div>
                    </a>
                  </li>
                ))}
              </ul>
            </section>
          )}

          {grid.length > 0 && (
            <section aria-labelledby="trending-now">
              <h2 id="trending-now" className="mb-3 text-lg font-extrabold tracking-tight">Trending Now</h2>
              <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {grid.map((t, i) => (
                  <article
                    key={t.link || i}
                    className="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg"
                  >
                    <div className="grid flex-1 content-start gap-1.5 p-4">
                      <span className="w-fit rounded-full bg-brand-gradient-soft px-2.5 py-0.5 text-[11px] font-bold text-brand-violet">
                        {t.source || 'News'}
                      </span>
                      <h3 className="m-0 text-sm font-bold leading-snug">{t.title}</h3>
                      <p className="m-0 text-xs text-slate-500">{fmtWhen(t.published)}</p>
                      <ItemLinks item={t} onSummarize={summarizeItem} onFavorite={favoriteItem} className="mt-1" />
                    </div>
                  </article>
                ))}
              </div>

              {/* endless-loading sentinel */}
              <div ref={sentinelRef} aria-hidden="true" />
              {loadingMore && (
                <div className="grid place-items-center py-8">
                  <div className="h-8 w-8 animate-spin rounded-full border-4 border-slate-200 border-t-brand-violet" />
                </div>
              )}
              {!hasMore && !loadingMore && (
                <p className="py-8 text-center text-sm text-slate-400">
                  You’ve reached the end for “{active?.name}” — hit 🔀 Refresh for a reshuffle or try another topic.
                </p>
              )}
            </section>
          )}
        </>
      )}

      {!loading && !error && articles.length === 0 && posts.length === 0 && active && (
        <div className="grid justify-items-center gap-2 py-20 text-center text-slate-500">
          <p className="m-0 text-4xl">🔎</p>
          <p className="m-0">No trending results found for “{active?.name}”. Try different wording.</p>
        </div>
      )}

        </div>
        <aside className="mt-5 grid content-start gap-4 lg:mt-0">
          <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="mb-2 flex items-center justify-between gap-2">
              <h3 className="m-0 text-sm font-extrabold tracking-tight">✍️ From your Studio</h3>
              <button className="text-xs font-bold text-brand-blue hover:underline" onClick={() => go && go('blog')}>Open →</button>
            </div>
            {myPosts.length === 0 ? (
              <p className="m-0 text-xs text-slate-500">Publish posts in Studio → Blog and your latest 5 show here.</p>
            ) : (
              <ul className="m-0 grid list-none gap-2 p-0">
                {myPosts.map((mp) => (
                  <li key={mp.id}>
                    <button className="w-full text-left" onClick={() => go && go('blog')}>
                      <span className="block truncate text-sm font-bold hover:text-brand-violet">{mp.title}</span>
                      <span className="block text-[11px] text-slate-400">{new Date(mp.updated_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}</span>
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 className="m-0 mb-2 text-sm font-extrabold tracking-tight">🔥 Trending searches</h3>
            <div className="flex flex-wrap gap-1.5">
              {[...new Set([...TREND_TERMS, ...cats.map((c) => c.name)])].slice(0, 10).map((t) => (
                <button key={t} onClick={() => runTerm(t)} className="rounded-full border-2 border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet">{t}</button>
              ))}
            </div>
          </section>

          <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 className="m-0 mb-1 text-sm font-extrabold tracking-tight">👥 Your readers</h3>
            <p className="m-0 text-xs text-slate-500"><strong className="text-ink">{readerCount}</strong> {readerCount === 1 ? 'person' : 'people'} in your Keepary circle can read your published posts.</p>
          </section>
        </aside>
      </div>

      {summary && (
        <div
          className="fixed inset-0 z-50 grid place-items-center bg-indigo-950/70 p-3.5"
          onClick={() => { stopVoice(); setSummary(null) }}
          role="dialog"
          aria-modal="true"
          aria-label="Article summary"
        >
          <div className="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4">
              <div>
                <span className="mb-1 inline-block rounded-full bg-brand-gradient px-3 py-0.5 text-[11px] font-bold text-white">
                  ✨ Summarized version
                </span>
                <h3 className="m-0 text-base font-extrabold leading-snug">
                  {summary.data?.title || summary.item.title}
                </h3>
              </div>
              <button
                className="shrink-0 rounded-lg border-2 border-slate-200 px-3 py-1.5 text-sm font-bold transition hover:border-brand-violet hover:text-brand-violet"
                onClick={() => { stopVoice(); setSummary(null) }}
              >
                ✕
              </button>
            </div>

            <div className="overflow-auto px-5 py-4">
              {summary.loading && (
                <div className="grid place-items-center gap-3 py-14">
                  <div className="h-9 w-9 animate-spin rounded-full border-4 border-slate-200 border-t-brand-violet" />
                  <p className="m-0 text-sm text-slate-500">Reading the article and writing your summary…</p>
                </div>
              )}

              {summary.error && (
                <p className="m-0 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-600">{summary.error}</p>
              )}

              {summary.data && (
                <>
                  <p className="mb-3 mt-0 text-xs font-semibold text-slate-500">
                    ⏱ ~1 min summary · full article is ~{summary.data.readingTime} min ({summary.data.wordCount.toLocaleString()} words)
                  </p>

                  {/* Listen player */}
                  <div className="mb-3 flex flex-wrap items-center gap-2 rounded-xl bg-brand-gradient-soft px-3 py-2.5">
                    {voiceState !== 'playing' ? (
                      <button
                        onClick={playVoice}
                        className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110"
                      >
                        🔊 {voiceState === 'paused' ? 'Resume' : 'Listen'}
                      </button>
                    ) : (
                      <button
                        onClick={pauseVoice}
                        className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110"
                      >
                        ⏸ Pause
                      </button>
                    )}
                    {voiceState !== 'idle' && (
                      <button
                        onClick={stopVoice}
                        className="rounded-lg border-2 border-slate-200 bg-white px-3 py-1.5 text-sm font-bold text-slate-600 transition hover:border-red-400 hover:text-red-500"
                      >
                        ⏹ Stop
                      </button>
                    )}
                    <select
                      value={voiceURI}
                      onChange={(e) => {
                        localStorage.setItem('hc-voice', e.target.value)
                        setVoiceURI(e.target.value)
                        if (voiceState !== 'idle') stopVoice()
                      }}
                      className="min-w-0 max-w-[230px] flex-1 rounded-lg border-2 border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold outline-none focus:border-brand-violet"
                      aria-label="Voice"
                      title="Choose a voice — ★ marks the most natural ones"
                    >
                      {voices.map((v) => (
                        <option key={v.voiceURI} value={v.voiceURI}>
                          {/natural|neural|online/i.test(v.name) ? '★ ' : ''}{v.name.replace(/^(Microsoft|Google)\s*/, '').replace(/\s*-\s*English.*$/i, '')}
                        </option>
                      ))}
                    </select>
                    <select
                      value={rate}
                      onChange={(e) => {
                        const r = Number(e.target.value)
                        localStorage.setItem('hc-voice-rate', String(r))
                        setRate(r)
                        if (voiceState !== 'idle') stopVoice()
                      }}
                      className="rounded-lg border-2 border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold outline-none focus:border-brand-violet"
                      aria-label="Reading speed"
                    >
                      <option value="0.8">0.8×</option>
                      <option value="1">1×</option>
                      <option value="1.2">1.2×</option>
                      <option value="1.5">1.5×</option>
                    </select>
                  </div>
                  <ul className="m-0 grid list-none gap-2.5 p-0">
                    {summary.data.points.map((p, i) => (
                      <li key={i} className="flex gap-2.5 rounded-xl bg-slate-50 px-3.5 py-2.5 text-sm leading-relaxed">
                        <span className="select-none font-bold text-brand-violet">•</span>
                        <span>{p}</span>
                      </li>
                    ))}
                  </ul>
                  <p className="mb-0 mt-3 text-[11px] text-slate-400">
                    Auto-generated extract of the key sentences — check the full article before quoting.
                  </p>
                </>
              )}
            </div>

            <div className="flex items-center justify-between gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3">
              <a
                href={summary.item.link}
                target="_blank"
                rel="noopener noreferrer"
                className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110"
              >
                Read full article ↗
              </a>
              <button className="text-sm font-semibold text-slate-500 hover:text-brand-violet" onClick={() => { stopVoice(); setSummary(null) }}>
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </main>
  )
}
