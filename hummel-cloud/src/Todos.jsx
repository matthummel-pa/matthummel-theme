import { useCallback, useEffect, useMemo, useState } from 'react'
import { supabase } from './supabase.js'
import { getSettings } from './settings.js'
import AppIcon from './AppIcon.jsx'

export const PRIORITIES = {
  high: { label: 'High', cls: 'bg-red-50 text-red-600', dot: '🔴' },
  medium: { label: 'Medium', cls: 'bg-amber-50 text-amber-600', dot: '🟡' },
  low: { label: 'Low', cls: 'bg-emerald-50 text-emerald-700', dot: '🟢' }
}

const FILTERS = [
  { id: 'open', label: 'Open' },
  { id: 'starred', label: '⭐ Starred' },
  { id: 'today', label: 'Due today' },
  { id: 'overdue', label: 'Overdue' },
  { id: 'done', label: 'Done' },
  { id: 'all', label: 'All' }
]

const today = () => new Date().toISOString().slice(0, 10)

export const dueState = (t) => {
  if (!t.due_date || t.done) return null
  if (t.due_date < today()) return 'overdue'
  if (t.due_date === today()) return 'today'
  return 'upcoming'
}

const fmtDue = (d) => new Date(d + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' })

const inputCls =
  'rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-violet'

export default function Todos({ session }) {
  const user = session.user
  const [todos, setTodos] = useState([])
  const [loading, setLoading] = useState(true)
  const [filter, setFilter] = useState('open')
  const [sort, setSort] = useState('smart') // smart | due | priority | created
  const [search, setSearch] = useState('')
  const [newTitle, setNewTitle] = useState('')
  const [editing, setEditing] = useState(null)
  const [msg, setMsg] = useState(null)

  const load = useCallback(async () => {
    const { data, error } = await supabase.from('todos').select('*').order('created_at', { ascending: false })
    if (!error) setTodos(data || [])
    setLoading(false)
  }, [])

  useEffect(() => { load() }, [load])

  async function add(e) {
    e.preventDefault()
    const title = newTitle.trim()
    if (!title) return
    setNewTitle('')
    const { data, error } = await supabase.from('todos').insert({ user_id: user.id, title }).select().single()
    if (!error && data) setTodos((t) => [data, ...t])
  }

  async function update(id, patch) {
    setTodos((t) => t.map((x) => (x.id === id ? { ...x, ...patch } : x)))
    await supabase.from('todos').update(patch).eq('id', id)
  }

  async function toggleDone(t) {
    update(t.id, { done: !t.done, done_at: !t.done ? new Date().toISOString() : null })
  }

  async function remove(t) {
    if (getSettings().confirmDelete && !window.confirm(`Delete "${t.title}"?`)) return
    setTodos((x) => x.filter((y) => y.id !== t.id))
    await supabase.from('todos').delete().eq('id', t.id)
  }

  async function clearDone() {
    const ids = todos.filter((t) => t.done).map((t) => t.id)
    if (!ids.length) return
    if (!window.confirm(`Remove ${ids.length} completed to-do${ids.length > 1 ? 's' : ''}?`)) return
    setTodos((t) => t.filter((x) => !x.done))
    await supabase.from('todos').delete().in('id', ids)
  }

  const visible = useMemo(() => {
    let list = todos
    if (filter === 'open') list = list.filter((t) => !t.done)
    else if (filter === 'done') list = list.filter((t) => t.done)
    else if (filter === 'starred') list = list.filter((t) => t.starred && !t.done)
    else if (filter === 'today') list = list.filter((t) => dueState(t) === 'today')
    else if (filter === 'overdue') list = list.filter((t) => dueState(t) === 'overdue')
    if (search.trim()) {
      const q = search.trim().toLowerCase()
      list = list.filter((t) => t.title.toLowerCase().includes(q) || (t.tags || []).some((g) => g.toLowerCase().includes(q)))
    }
    const pRank = { high: 0, medium: 1, low: 2 }
    const cmp = {
      smart: (a, b) => (a.done - b.done) || ((a.due_date || '9999') > (b.due_date || '9999') ? 1 : (a.due_date || '9999') < (b.due_date || '9999') ? -1 : 0) || (pRank[a.priority] - pRank[b.priority]),
      due: (a, b) => ((a.due_date || '9999') > (b.due_date || '9999') ? 1 : -1),
      priority: (a, b) => pRank[a.priority] - pRank[b.priority],
      created: (a, b) => new Date(b.created_at) - new Date(a.created_at)
    }[sort]
    return [...list].sort(cmp)
  }, [todos, filter, search, sort])

  const open = todos.filter((t) => !t.done).length
  const overdue = todos.filter((t) => dueState(t) === 'overdue').length
  const pct = todos.length ? Math.round((todos.filter((t) => t.done).length / todos.length) * 100) : 0

  return (
    <main className="mx-auto w-full max-w-3xl flex-1 px-3 py-5 sm:px-7">
      <div className="mb-4 flex flex-wrap items-end justify-between gap-2">
        <div>
          <h1 className="m-0 text-xl font-extrabold tracking-tight flex items-center gap-2"><AppIcon id="tasks" className="h-7 w-7" /> Tasks</h1>
          <p className="m-0 text-sm text-slate-500">
            {open} open{overdue > 0 && <span className="font-bold text-red-500"> · {overdue} overdue</span>} · {pct}% done overall
          </p>
        </div>
        <button className="text-xs font-bold text-slate-400 hover:text-red-500" onClick={clearDone}>Clear completed</button>
      </div>

      <div className="mb-3 h-2 overflow-hidden rounded-full bg-slate-200">
        <div className="h-full rounded-full bg-brand-gradient transition-all" style={{ width: `${pct}%` }} />
      </div>

      <form onSubmit={add} className="mb-3 flex gap-2">
        <input
          className={`${inputCls} min-w-0 flex-1`}
          placeholder="Add a to-do and press Enter…"
          value={newTitle}
          onChange={(e) => setNewTitle(e.target.value)}
          maxLength={200}
        />
        <button className="rounded-lg bg-brand-gradient px-5 py-2 text-sm font-bold text-white transition hover:brightness-110">Add</button>
      </form>

      <div className="mb-4 flex flex-wrap items-center gap-2">
        {FILTERS.map((f) => (
          <button
            key={f.id}
            onClick={() => setFilter(f.id)}
            className={
              filter === f.id
                ? 'rounded-full bg-brand-gradient px-3.5 py-1.5 text-xs font-bold text-white'
                : 'rounded-full border-2 border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet'
            }
          >
            {f.label}
          </button>
        ))}
        <input type="search" className={`${inputCls} ml-auto w-32 px-2 py-1.5 text-xs sm:w-40`} placeholder="Search…" value={search} onChange={(e) => setSearch(e.target.value)} />
        <select className={`${inputCls} px-2 py-1.5 text-xs`} value={sort} onChange={(e) => setSort(e.target.value)} aria-label="Sort">
          <option value="smart">Smart sort</option>
          <option value="due">By due date</option>
          <option value="priority">By priority</option>
          <option value="created">Newest</option>
        </select>
      </div>

      {msg && <p className={`mb-3 rounded-xl px-4 py-2.5 text-sm ${msg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{msg.text}</p>}

      {loading ? (
        <div className="grid place-items-center py-20"><div className="h-9 w-9 animate-spin rounded-full border-4 border-slate-200 border-t-brand-violet" /></div>
      ) : visible.length === 0 ? (
        <div className="grid justify-items-center gap-2 py-16 text-center text-slate-500">
          <p className="m-0 text-4xl">🎉</p>
          <p className="m-0">{todos.length === 0 ? 'No to-dos yet. Add your first one above.' : 'Nothing matches this filter.'}</p>
        </div>
      ) : (
        <ul className="m-0 grid list-none gap-2 p-0">
          {visible.map((t) => {
            const ds = dueState(t)
            return (
              <li key={t.id} className={`flex items-start gap-3 rounded-2xl border bg-white p-3.5 shadow-sm ${ds === 'overdue' ? 'border-red-300' : 'border-slate-200'}`}>
                <input type="checkbox" checked={t.done} onChange={() => toggleDone(t)} className="mt-1 h-5 w-5 shrink-0 accent-violet-600" aria-label="Done" />
                <button className="min-w-0 flex-1 text-left" onClick={() => setEditing({ ...t, tagsText: (t.tags || []).join(', ') })}>
                  <p className={`m-0 text-sm font-bold ${t.done ? 'text-slate-400 line-through' : ''}`}>{t.title}</p>
                  <p className="m-0 mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-slate-500">
                    <span className={`rounded-full px-2 py-0.5 font-bold ${PRIORITIES[t.priority].cls}`}>{PRIORITIES[t.priority].dot} {PRIORITIES[t.priority].label}</span>
                    {t.due_date && (
                      <span className={ds === 'overdue' ? 'font-bold text-red-500' : ds === 'today' ? 'font-bold text-amber-600' : ''}>
                        📅 {fmtDue(t.due_date)}{ds === 'overdue' ? ' (overdue)' : ds === 'today' ? ' (today)' : ''}
                      </span>
                    )}
                    {(t.tags || []).map((g) => <span key={g} className="rounded bg-slate-100 px-1.5 py-0.5">#{g}</span>)}
                    {t.notes && <span title={t.notes}>🗒</span>}
                  </p>
                </button>
                <div className="flex shrink-0 gap-1">
                  <button
                    className={`rounded-lg border px-2 py-1 text-sm ${t.starred ? 'border-amber-300 bg-amber-50' : 'border-slate-200 hover:border-amber-300'}`}
                    onClick={() => update(t.id, { starred: !t.starred })}
                    title={t.starred ? 'Unstar (removes from Home)' : 'Star (shows on Home)'}
                  >
                    {t.starred ? '⭐' : '☆'}
                  </button>
                  <button className="rounded-lg border border-slate-200 px-2 py-1 text-sm hover:border-red-400 hover:bg-red-50" onClick={() => remove(t)} title="Delete">🗑</button>
                </div>
              </li>
            )
          })}
        </ul>
      )}

      {/* edit modal */}
      {editing && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-indigo-950/70 p-3.5" onClick={() => setEditing(null)} role="dialog" aria-modal="true">
          <div className="grid w-full max-w-md gap-3 rounded-2xl bg-white p-5" onClick={(e) => e.stopPropagation()}>
            <h3 className="m-0 text-base font-extrabold">Edit to-do</h3>
            <label className="grid gap-1 text-sm font-bold">
              Title
              <input className={inputCls} value={editing.title} onChange={(e) => setEditing({ ...editing, title: e.target.value })} maxLength={200} />
            </label>
            <label className="grid gap-1 text-sm font-bold">
              Notes
              <textarea className={`${inputCls} resize-none`} rows={3} value={editing.notes || ''} onChange={(e) => setEditing({ ...editing, notes: e.target.value })} />
            </label>
            <div className="grid grid-cols-2 gap-3">
              <label className="grid gap-1 text-sm font-bold">
                Due date
                <input type="date" className={inputCls} value={editing.due_date || ''} onChange={(e) => setEditing({ ...editing, due_date: e.target.value || null })} />
              </label>
              <label className="grid gap-1 text-sm font-bold">
                Priority
                <select className={inputCls} value={editing.priority} onChange={(e) => setEditing({ ...editing, priority: e.target.value })}>
                  <option value="high">🔴 High</option>
                  <option value="medium">🟡 Medium</option>
                  <option value="low">🟢 Low</option>
                </select>
              </label>
            </div>
            <label className="grid gap-1 text-sm font-bold">
              Tags (comma-separated)
              <input className={inputCls} value={editing.tagsText} onChange={(e) => setEditing({ ...editing, tagsText: e.target.value })} placeholder="home, blog, urgent" />
            </label>
            <div className="flex justify-end gap-2">
              <button className="rounded-lg border-2 border-slate-200 px-4 py-2 text-sm font-bold text-slate-600" onClick={() => setEditing(null)}>Cancel</button>
              <button
                className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110"
                onClick={() => {
                  const tags = editing.tagsText.split(',').map((s) => s.trim().replace(/^#/, '')).filter(Boolean).slice(0, 8)
                  update(editing.id, { title: editing.title.trim() || 'Untitled', notes: editing.notes || null, due_date: editing.due_date || null, priority: editing.priority, tags })
                  setEditing(null)
                }}
              >
                Save
              </button>
            </div>
          </div>
        </div>
      )}
    </main>
  )
}
