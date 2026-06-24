import { useCallback, useEffect, useMemo, useState } from 'react'
import { supabase } from './supabase.js'
import { getSettings } from './settings.js'
import AppIcon from './AppIcon.jsx'

/* ---------------- date helpers (no libraries) ---------------- */
const sod = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate())
const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n)
const addMonths = (d, n) => new Date(d.getFullYear(), d.getMonth() + n, 1)
const startOfWeek = (d) => addDays(sod(d), -d.getDay())
const sameDay = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()
const pad = (n) => String(n).padStart(2, '0')
const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
const hm = (d) => `${pad(d.getHours())}:${pad(d.getMinutes())}`
const fmtTime = (d) => d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
const fmtDayLong = (d) => d.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' })
const MONTHS_FMT = { month: 'long', year: 'numeric' }
const DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

export const EVENT_COLORS = {
  violet: { chip: 'bg-violet-500', block: 'bg-violet-100 text-violet-800 border-violet-400' },
  blue: { chip: 'bg-blue-500', block: 'bg-blue-100 text-blue-800 border-blue-400' },
  emerald: { chip: 'bg-emerald-500', block: 'bg-emerald-100 text-emerald-800 border-emerald-400' },
  amber: { chip: 'bg-amber-400', block: 'bg-amber-100 text-amber-800 border-amber-400' },
  rose: { chip: 'bg-rose-500', block: 'bg-rose-100 text-rose-800 border-rose-400' },
  sky: { chip: 'bg-sky-500', block: 'bg-sky-100 text-sky-800 border-sky-400' },
  slate: { chip: 'bg-slate-500', block: 'bg-slate-200 text-slate-700 border-slate-400' }
}

const HOUR_PX = 44

/* Expand recurring events into concrete instances inside [from, to] */
function expand(events, from, to) {
  const out = []
  for (const e of events) {
    const start = new Date(e.start_at)
    const end = new Date(e.end_at)
    const dur = end - start
    const until = e.recur_until ? new Date(`${e.recur_until}T23:59:59`) : null
    let cur = new Date(start)
    let guard = 0
    while (cur <= to && guard < 500) {
      guard++
      if (until && cur > until) break
      const curEnd = new Date(cur.getTime() + dur)
      if (curEnd >= from) {
        out.push({ ...e, _start: new Date(cur), _end: curEnd, _key: `${e.id}@${cur.getTime()}` })
      }
      if (e.recur === 'none') break
      if (e.recur === 'daily') cur = new Date(cur.getFullYear(), cur.getMonth(), cur.getDate() + 1, cur.getHours(), cur.getMinutes())
      else if (e.recur === 'weekly') cur = new Date(cur.getFullYear(), cur.getMonth(), cur.getDate() + 7, cur.getHours(), cur.getMinutes())
      else if (e.recur === 'monthly') cur = new Date(cur.getFullYear(), cur.getMonth() + 1, cur.getDate(), cur.getHours(), cur.getMinutes())
      else if (e.recur === 'yearly') cur = new Date(cur.getFullYear() + 1, cur.getMonth(), cur.getDate(), cur.getHours(), cur.getMinutes())
      else break
    }
  }
  return out.sort((a, b) => a._start - b._start)
}

const onDay = (inst, day) => inst._start < addDays(day, 1) && inst._end > day

const inputCls =
  'rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-violet'

export default function Calendar({ session }) {
  const user = session.user
  const [events, setEvents] = useState([])
  const [cals, setCals] = useState([])
  const [calModal, setCalModal] = useState(null)
  const [hidden, setHidden] = useState(() => {
    try { return new Set(JSON.parse(localStorage.getItem('hc-cal-hidden')) || []) } catch { return new Set() }
  })
  const [cursor, setCursor] = useState(() => sod(new Date()))
  const [view, setView] = useState('month') // month | week | agenda
  const [modal, setModal] = useState(null)
  const [search, setSearch] = useState('')
  const [msg, setMsg] = useState(null)
  const today = sod(new Date())

  const load = useCallback(async () => {
    const [{ data: ev }, { data: cs }] = await Promise.all([
      supabase.from('events').select('*'),
      supabase.from('calendars').select('*').order('created_at')
    ])
    setEvents(ev || [])
    setCals(cs || [])
  }, [])

  useEffect(() => { load() }, [load])

  /* ---------- range + instances for current view ---------- */
  const range = useMemo(() => {
    if (view === 'week') {
      const from = startOfWeek(cursor)
      return { from, to: addDays(from, 7) }
    }
    if (view === 'agenda') return { from: today, to: addDays(today, 90) }
    const first = new Date(cursor.getFullYear(), cursor.getMonth(), 1)
    const from = startOfWeek(first)
    return { from, to: addDays(from, 42) }
  }, [view, cursor, today])

  const instances = useMemo(() => expand(events, range.from, range.to), [events, range])

  const colorOf = (inst) => cals.find((c) => c.id === inst.calendar_id)?.color || inst.color
  const shownList = instances.filter((i) => !hidden.has(i.calendar_id || 'personal'))

  function toggleHidden(key) {
    const next = new Set(hidden)
    if (next.has(key)) next.delete(key)
    else next.add(key)
    setHidden(next)
    try { localStorage.setItem('hc-cal-hidden', JSON.stringify([...next])) } catch { /* ignore */ }
  }

  async function saveCalendar() {
    const row = { user_id: user.id, name: calModal.name.trim() || 'Calendar', color: calModal.color, shared: calModal.shared }
    if (calModal.id) await supabase.from('calendars').update(row).eq('id', calModal.id)
    else await supabase.from('calendars').insert(row)
    setCalModal(null)
    load()
  }

  async function deleteCalendar() {
    if (!window.confirm(`Delete calendar "${calModal.name}"? All events on this calendar are deleted too.`)) return
    await supabase.from('calendars').delete().eq('id', calModal.id)
    setCalModal(null)
    load()
  }

  /* ---------- CRUD ---------- */
  function blankEvent(day, hour = 9) {
    const start = new Date(day.getFullYear(), day.getMonth(), day.getDate(), hour, 0)
    return {
      id: null, title: '', description: '', location: '', color: 'violet', recur: 'none', recur_until: '',
      all_day: false, calendar_id: null,
      startDate: ymd(start), startTime: hm(start),
      endDate: ymd(start), endTime: hm(new Date(start.getTime() + 3600000))
    }
  }

  function openEvent(inst) {
    const s = new Date(inst.start_at)
    const e = new Date(inst.end_at)
    setModal({
      id: inst.id, title: inst.title, description: inst.description || '', location: inst.location || '',
      color: inst.color, recur: inst.recur, recur_until: inst.recur_until || '', all_day: inst.all_day, calendar_id: inst.calendar_id || null,
      startDate: ymd(s), startTime: hm(s), endDate: ymd(e), endTime: hm(e)
    })
  }

  async function saveEvent() {
    const start = modal.all_day
      ? new Date(`${modal.startDate}T00:00:00`)
      : new Date(`${modal.startDate}T${modal.startTime}`)
    let end = modal.all_day
      ? new Date(`${modal.endDate}T23:59:59`)
      : new Date(`${modal.endDate}T${modal.endTime}`)
    if (!(start < end)) {
      setMsg({ ok: false, text: 'End must be after start.' })
      return
    }
    const row = {
      user_id: user.id,
      title: modal.title.trim() || '(no title)',
      description: modal.description || null,
      location: modal.location || null,
      start_at: start.toISOString(),
      end_at: end.toISOString(),
      all_day: modal.all_day,
      color: modal.color,
      calendar_id: modal.calendar_id || null,
      recur: modal.recur,
      recur_until: modal.recur !== 'none' && modal.recur_until ? modal.recur_until : null
    }
    if (modal.id) await supabase.from('events').update(row).eq('id', modal.id)
    else await supabase.from('events').insert(row)
    setModal(null)
    setMsg(null)
    load()
  }

  async function deleteEvent() {
    const isSeries = modal.recur !== 'none'
    if (getSettings().confirmDelete && !window.confirm(isSeries ? 'Delete this entire recurring series?' : `Delete "${modal.title}"?`)) return
    if (modal.id) await supabase.from('events').delete().eq('id', modal.id)
    setModal(null)
    load()
  }

  /* ---------- navigation ---------- */
  function move(dir) {
    if (view === 'week') setCursor(addDays(cursor, dir * 7))
    else setCursor(addMonths(cursor, dir))
  }

  const heading =
    view === 'week'
      ? `${startOfWeek(cursor).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })} – ${addDays(startOfWeek(cursor), 6).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}`
      : view === 'agenda'
        ? 'Next 90 days'
        : cursor.toLocaleDateString(undefined, MONTHS_FMT)

  /* ---------- month view ---------- */
  const monthCells = useMemo(() => {
    if (view !== 'month') return []
    return Array.from({ length: 42 }, (_, i) => addDays(range.from, i))
  }, [view, range])

  /* ---------- agenda ---------- */
  const agendaItems = useMemo(() => {
    if (view !== 'agenda') return []
    let list = shownList
    if (search.trim()) {
      const q = search.trim().toLowerCase()
      list = list.filter((i) => i.title.toLowerCase().includes(q) || (i.location || '').toLowerCase().includes(q) || (i.description || '').toLowerCase().includes(q))
    }
    const groups = []
    for (const inst of list) {
      const key = ymd(inst._start)
      const g = groups.find((x) => x.key === key)
      if (g) g.items.push(inst)
      else groups.push({ key, day: sod(inst._start), items: [inst] })
    }
    return groups
  }, [view, shownList, search])

  const weekDays = view === 'week' ? Array.from({ length: 7 }, (_, i) => addDays(range.from, i)) : []
  const nowOffset = (new Date().getHours() + new Date().getMinutes() / 60) * HOUR_PX

  return (
    <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col px-2 py-4 sm:px-6">
      {/* header */}
      <div className="mb-3 flex flex-wrap items-center gap-2">
        <h1 className="m-0 mr-1 text-xl font-extrabold tracking-tight flex items-center gap-2"><AppIcon id="agenda" className="h-7 w-7" /> Agenda</h1>
        <button className="rounded-lg border-2 border-slate-200 bg-white px-3 py-1.5 text-sm font-bold text-slate-600 hover:border-brand-violet" onClick={() => setCursor(sod(new Date()))}>
          Today
        </button>
        <button className="rounded-lg border-2 border-slate-200 bg-white px-2.5 py-1.5 text-sm font-bold text-slate-600 hover:border-brand-violet" onClick={() => move(-1)} aria-label="Previous">‹</button>
        <button className="rounded-lg border-2 border-slate-200 bg-white px-2.5 py-1.5 text-sm font-bold text-slate-600 hover:border-brand-violet" onClick={() => move(1)} aria-label="Next">›</button>
        <span className="text-base font-extrabold">{heading}</span>
        <span className="ml-auto flex items-center gap-1 rounded-xl bg-slate-200/60 p-1">
          {['month', 'week', 'agenda'].map((v) => (
            <button
              key={v}
              onClick={() => setView(v)}
              className={`rounded-lg px-3 py-1 text-xs font-bold capitalize ${view === v ? 'bg-white text-brand-violet shadow-sm' : 'text-slate-600'}`}
            >
              {v}
            </button>
          ))}
        </span>
        <button className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110" onClick={() => setModal(blankEvent(today))}>
          + New event
        </button>
      </div>

      {/* calendars bar */}
      <div className="mb-3 flex flex-wrap items-center gap-1.5">
        <button
          onClick={() => toggleHidden('personal')}
          className={`inline-flex items-center gap-1.5 rounded-full border-2 border-slate-200 bg-white px-3 py-1 text-xs font-bold transition ${hidden.has('personal') ? 'opacity-40' : ''}`}
          title="Show/hide events without a calendar"
        >
          <span className="h-2.5 w-2.5 rounded-full bg-violet-500" /> My events
        </button>
        {cals.map((c) => (
          <button
            key={c.id}
            onClick={() => toggleHidden(c.id)}
            onDoubleClick={() => c.user_id === user.id && setCalModal(c)}
            title={c.user_id === user.id ? 'Click to show/hide · double-click to edit' : 'Shared by family · click to show/hide'}
            className={`inline-flex items-center gap-1.5 rounded-full border-2 border-slate-200 bg-white px-3 py-1 text-xs font-bold transition ${hidden.has(c.id) ? 'opacity-40' : ''}`}
          >
            <span className={`h-2.5 w-2.5 rounded-full ${EVENT_COLORS[c.color]?.chip || EVENT_COLORS.violet.chip}`} />
            {c.name}
            {c.shared && <span title="Shared with family">👥</span>}
          </button>
        ))}
        <button
          onClick={() => setCalModal({ name: '', color: 'blue', shared: false })}
          className="rounded-full border-2 border-dashed border-slate-300 px-3 py-1 text-xs font-bold text-slate-500 hover:border-brand-violet hover:text-brand-violet"
        >
          + Calendar
        </button>
      </div>

      {msg && <p className={`mb-2 rounded-xl px-4 py-2 text-sm ${msg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{msg.text}</p>}

      {/* ---------------- MONTH ---------------- */}
      {view === 'month' && (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div className="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-[11px] font-bold text-slate-500">
            {DOW.map((d) => <div key={d} className="py-1.5">{d}</div>)}
          </div>
          <div className="grid grid-cols-7">
            {monthCells.map((day) => {
              const inMonth = day.getMonth() === cursor.getMonth()
              const dayEvents = shownList.filter((i) => onDay(i, day))
              return (
                <div
                  key={day.toISOString()}
                  className={`min-h-[92px] cursor-pointer border-b border-r border-slate-100 p-1 align-top transition hover:bg-brand-gradient-soft ${inMonth ? '' : 'bg-slate-50/60'}`}
                  onClick={() => setModal(blankEvent(day))}
                >
                  <div className="mb-0.5 flex justify-end">
                    <span className={`grid h-6 w-6 place-items-center rounded-full text-[11px] font-bold ${sameDay(day, today) ? 'bg-brand-gradient text-white' : inMonth ? 'text-slate-700' : 'text-slate-400'}`}>
                      {day.getDate()}
                    </span>
                  </div>
                  {dayEvents.slice(0, 3).map((inst) => (
                    <button
                      key={inst._key}
                      onClick={(e) => { e.stopPropagation(); openEvent(inst) }}
                      className={`mb-0.5 block w-full truncate rounded border-l-2 px-1 py-0.5 text-left text-[10px] font-semibold leading-tight ${EVENT_COLORS[colorOf(inst)]?.block || EVENT_COLORS.violet.block}`}
                      title={inst.title}
                    >
                      {!inst.all_day && <span className="opacity-70">{fmtTime(inst._start)} </span>}{inst.title}
                    </button>
                  ))}
                  {dayEvents.length > 3 && (
                    <button
                      className="block w-full text-left text-[10px] font-bold text-brand-violet"
                      onClick={(e) => { e.stopPropagation(); setCursor(day); setView('week') }}
                    >
                      +{dayEvents.length - 3} more
                    </button>
                  )}
                </div>
              )
            })}
          </div>
        </div>
      )}

      {/* ---------------- WEEK ---------------- */}
      {view === 'week' && (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          {/* day headers + all-day row */}
          <div className="grid grid-cols-[44px_repeat(7,1fr)] border-b border-slate-200 bg-slate-50">
            <div />
            {weekDays.map((d) => (
              <div key={d.toISOString()} className="border-l border-slate-100 px-1 py-1.5 text-center">
                <p className="m-0 text-[10px] font-bold text-slate-500">{DOW[d.getDay()]}</p>
                <p className={`m-0 mx-auto grid h-6 w-6 place-items-center rounded-full text-xs font-extrabold ${sameDay(d, today) ? 'bg-brand-gradient text-white' : ''}`}>{d.getDate()}</p>
                {shownList.filter((i) => i.all_day && onDay(i, d)).slice(0, 2).map((inst) => (
                  <button key={inst._key} onClick={() => openEvent(inst)} className={`mt-0.5 block w-full truncate rounded border-l-2 px-1 text-[9px] font-bold ${EVENT_COLORS[colorOf(inst)]?.block}`}>
                    {inst.title}
                  </button>
                ))}
              </div>
            ))}
          </div>
          {/* time grid */}
          <div className="max-h-[62vh] overflow-y-auto">
            <div className="relative grid grid-cols-[44px_repeat(7,1fr)]" style={{ height: 24 * HOUR_PX }}>
              {/* hour labels */}
              <div className="relative">
                {Array.from({ length: 24 }, (_, h) => (
                  <span key={h} className="absolute right-1 -translate-y-1/2 text-[9px] font-semibold text-slate-400" style={{ top: h * HOUR_PX }}>
                    {h === 0 ? '' : `${((h + 11) % 12) + 1}${h < 12 ? 'am' : 'pm'}`}
                  </span>
                ))}
              </div>
              {weekDays.map((d) => (
                <div key={d.toISOString()} className="relative border-l border-slate-100">
                  {Array.from({ length: 24 }, (_, h) => (
                    <div
                      key={h}
                      className="absolute w-full cursor-pointer border-t border-slate-100 hover:bg-brand-gradient-soft"
                      style={{ top: h * HOUR_PX, height: HOUR_PX }}
                      onClick={() => setModal(blankEvent(d, h))}
                    />
                  ))}
                  {sameDay(d, today) && (
                    <div className="absolute left-0 right-0 z-10 h-0.5 bg-red-500" style={{ top: nowOffset }}>
                      <span className="absolute -left-1 -top-[3px] h-2 w-2 rounded-full bg-red-500" />
                    </div>
                  )}
                  {shownList.filter((i) => !i.all_day && onDay(i, d)).map((inst, idx) => {
                    const dayStart = d
                    const s = inst._start < dayStart ? dayStart : inst._start
                    const dayEnd = addDays(d, 1)
                    const e = inst._end > dayEnd ? dayEnd : inst._end
                    const top = (s.getHours() + s.getMinutes() / 60) * HOUR_PX
                    const height = Math.max(((e - s) / 3600000) * HOUR_PX, 20)
                    return (
                      <button
                        key={inst._key}
                        onClick={(ev) => { ev.stopPropagation(); openEvent(inst) }}
                        className={`absolute z-[5] overflow-hidden rounded-md border-l-4 px-1 py-0.5 text-left text-[10px] font-bold leading-tight shadow-sm ${EVENT_COLORS[colorOf(inst)]?.block}`}
                        style={{ top, height, left: 2 + idx * 4, right: 2 }}
                        title={`${inst.title} · ${fmtTime(inst._start)}–${fmtTime(inst._end)}`}
                      >
                        {inst.title}
                        <span className="block font-medium opacity-75">{fmtTime(inst._start)}</span>
                      </button>
                    )
                  })}
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* ---------------- AGENDA ---------------- */}
      {view === 'agenda' && (
        <div className="grid gap-3">
          <input
            type="search"
            className={`${inputCls} w-full sm:max-w-xs`}
            placeholder="Search events…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
          {agendaItems.length === 0 ? (
            <div className="grid justify-items-center gap-2 rounded-2xl border border-slate-200 bg-white py-16 text-center text-slate-500">
              <p className="m-0 text-4xl">🗓</p>
              <p className="m-0">{search ? 'No matching events.' : 'Nothing scheduled in the next 90 days.'}</p>
            </div>
          ) : (
            agendaItems.map((g) => (
              <section key={g.key} className="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
                <h2 className={`m-0 mb-2 text-sm font-extrabold ${sameDay(g.day, today) ? 'text-brand-violet' : ''}`}>
                  {sameDay(g.day, today) ? 'Today — ' : ''}{fmtDayLong(g.day)}
                </h2>
                <ul className="m-0 grid list-none gap-1.5 p-0">
                  {g.items.map((inst) => (
                    <li key={inst._key}>
                      <button className="flex w-full items-center gap-2.5 rounded-lg px-2 py-1.5 text-left hover:bg-brand-gradient-soft" onClick={() => openEvent(inst)}>
                        <span className={`h-3 w-3 shrink-0 rounded-full ${EVENT_COLORS[colorOf(inst)]?.chip}`} />
                        <span className="w-28 shrink-0 text-xs font-semibold text-slate-500">
                          {inst.all_day ? 'All day' : `${fmtTime(inst._start)} – ${fmtTime(inst._end)}`}
                        </span>
                        <span className="min-w-0 flex-1 truncate text-sm font-bold">{inst.title}</span>
                        {inst.recur !== 'none' && <span className="shrink-0 text-xs text-slate-400" title="Repeats">🔁</span>}
                        {inst.location && <span className="hidden shrink-0 text-xs text-slate-400 sm:inline">📍 {inst.location}</span>}
                      </button>
                    </li>
                  ))}
                </ul>
              </section>
            ))
          )}
        </div>
      )}

      {/* ---------------- calendar designer ---------------- */}
      {calModal && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-indigo-950/70 p-3.5" onClick={() => setCalModal(null)} role="dialog" aria-modal="true">
          <div className="grid w-full max-w-sm gap-4 rounded-2xl bg-white p-5" onClick={(e) => e.stopPropagation()}>
            <h3 className="m-0 text-base font-extrabold">{calModal.id ? 'Edit calendar' : 'New calendar'}</h3>
            <label className="grid gap-1 text-sm font-bold">
              Name
              <input className={inputCls} value={calModal.name} onChange={(e) => setCalModal({ ...calModal, name: e.target.value })} placeholder="e.g. Work, Kids, Bills" maxLength={40} autoFocus />
            </label>
            <div className="grid gap-1 text-sm font-bold">
              Color
              <div className="flex gap-2">
                {Object.keys(EVENT_COLORS).map((c) => (
                  <button
                    key={c}
                    className={`h-8 w-8 rounded-full ${EVENT_COLORS[c].chip} ${calModal.color === c ? 'ring-2 ring-ink ring-offset-2' : ''}`}
                    onClick={() => setCalModal({ ...calModal, color: c })}
                    aria-label={c}
                  />
                ))}
              </div>
            </div>
            <label className="flex items-start gap-2 text-sm">
              <input type="checkbox" className="mt-0.5 h-4 w-4 accent-violet-600" checked={calModal.shared} onChange={(e) => setCalModal({ ...calModal, shared: e.target.checked })} />
              <span>
                <span className="font-bold">👥 Share with family</span>
                <span className="block text-xs text-slate-500">Shannon can see this calendar and add or edit events on it.</span>
              </span>
            </label>
            <div className="flex items-center justify-between gap-2">
              {calModal.id ? (
                <button className="text-sm font-bold text-red-500 hover:underline" onClick={deleteCalendar}>Delete</button>
              ) : <span />}
              <div className="flex gap-2">
                <button className="rounded-lg border-2 border-slate-200 px-4 py-2 text-sm font-bold text-slate-600" onClick={() => setCalModal(null)}>Cancel</button>
                <button className="rounded-lg bg-brand-gradient px-5 py-2 text-sm font-bold text-white" onClick={saveCalendar}>Save</button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ---------------- event modal ---------------- */}
      {modal && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-indigo-950/70 p-3.5" onClick={() => setModal(null)} role="dialog" aria-modal="true">
          <div className="grid w-full max-w-md gap-3 overflow-auto rounded-2xl bg-white p-5" style={{ maxHeight: '92vh' }} onClick={(e) => e.stopPropagation()}>
            <input
              className="rounded-lg border-2 border-slate-200 px-3 py-2 text-base font-extrabold outline-none focus:border-brand-violet"
              placeholder="Event title"
              value={modal.title}
              onChange={(e) => setModal({ ...modal, title: e.target.value })}
              maxLength={150}
              autoFocus
            />
            <label className="flex items-center gap-2 text-sm font-bold">
              <input type="checkbox" className="h-4 w-4 accent-violet-600" checked={modal.all_day} onChange={(e) => setModal({ ...modal, all_day: e.target.checked })} />
              All day
            </label>
            <div className="grid grid-cols-2 gap-2">
              <label className="grid gap-1 text-xs font-bold">
                Starts
                <input type="date" className={inputCls} value={modal.startDate} onChange={(e) => setModal({ ...modal, startDate: e.target.value, endDate: e.target.value > modal.endDate ? e.target.value : modal.endDate })} />
              </label>
              {!modal.all_day && (
                <label className="grid gap-1 text-xs font-bold">
                  Time
                  <input type="time" className={inputCls} value={modal.startTime} onChange={(e) => setModal({ ...modal, startTime: e.target.value })} />
                </label>
              )}
              <label className="grid gap-1 text-xs font-bold">
                Ends
                <input type="date" className={inputCls} value={modal.endDate} onChange={(e) => setModal({ ...modal, endDate: e.target.value })} />
              </label>
              {!modal.all_day && (
                <label className="grid gap-1 text-xs font-bold">
                  Time
                  <input type="time" className={inputCls} value={modal.endTime} onChange={(e) => setModal({ ...modal, endTime: e.target.value })} />
                </label>
              )}
            </div>
            <label className="grid gap-1 text-xs font-bold">
              Calendar
              <select
                className={inputCls}
                value={modal.calendar_id || ''}
                onChange={(e) => {
                  const id = e.target.value || null
                  const cal = cals.find((c) => c.id === id)
                  setModal({ ...modal, calendar_id: id, color: cal ? cal.color : modal.color })
                }}
              >
                <option value="">My events (no calendar)</option>
                {cals.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}{c.shared ? ' 👥' : ''}{c.user_id !== user.id ? ' (family)' : ''}
                  </option>
                ))}
              </select>
            </label>
            <label className="grid gap-1 text-xs font-bold">
              Location
              <input className={inputCls} value={modal.location} onChange={(e) => setModal({ ...modal, location: e.target.value })} placeholder="Optional" maxLength={120} />
            </label>
            <label className="grid gap-1 text-xs font-bold">
              Description
              <textarea className={`${inputCls} resize-none`} rows={2} value={modal.description} onChange={(e) => setModal({ ...modal, description: e.target.value })} placeholder="Optional" />
            </label>
            <div className="grid grid-cols-2 gap-2">
              <label className="grid gap-1 text-xs font-bold">
                Repeats
                <select className={inputCls} value={modal.recur} onChange={(e) => setModal({ ...modal, recur: e.target.value })}>
                  <option value="none">Does not repeat</option>
                  <option value="daily">Daily</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </select>
              </label>
              {modal.recur !== 'none' && (
                <label className="grid gap-1 text-xs font-bold">
                  Until (optional)
                  <input type="date" className={inputCls} value={modal.recur_until} onChange={(e) => setModal({ ...modal, recur_until: e.target.value })} />
                </label>
              )}
            </div>
            {modal.calendar_id ? (
              <p className="m-0 text-[11px] text-slate-400">
                Color comes from the calendar:{' '}
                <span className={`inline-block h-2.5 w-2.5 rounded-full align-middle ${EVENT_COLORS[cals.find((c) => c.id === modal.calendar_id)?.color]?.chip}`} />{' '}
                {cals.find((c) => c.id === modal.calendar_id)?.name}
              </p>
            ) : (
              <div className="grid gap-1 text-xs font-bold">
                Color
                <div className="flex gap-2">
                  {Object.keys(EVENT_COLORS).map((c) => (
                    <button
                      key={c}
                      className={`h-7 w-7 rounded-full ${EVENT_COLORS[c].chip} ${modal.color === c ? 'ring-2 ring-ink ring-offset-2' : ''}`}
                      onClick={() => setModal({ ...modal, color: c })}
                      aria-label={c}
                    />
                  ))}
                </div>
              </div>
            )}
            <div className="mt-1 flex items-center justify-between gap-2">
              {modal.id ? (
                <button className="text-sm font-bold text-red-500 hover:underline" onClick={deleteEvent}>Delete{modal.recur !== 'none' ? ' series' : ''}</button>
              ) : <span />}
              <div className="flex gap-2">
                <button className="rounded-lg border-2 border-slate-200 px-4 py-2 text-sm font-bold text-slate-600" onClick={() => { setModal(null); setMsg(null) }}>Cancel</button>
                <button className="rounded-lg bg-brand-gradient px-5 py-2 text-sm font-bold text-white transition hover:brightness-110" onClick={saveEvent}>Save</button>
              </div>
            </div>
            {modal.id && modal.recur !== 'none' && (
              <p className="m-0 text-[11px] text-slate-400">Editing a repeating event changes the whole series.</p>
            )}
          </div>
        </div>
      )}
    </main>
  )
}
