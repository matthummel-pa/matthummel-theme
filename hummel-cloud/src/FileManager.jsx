import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { supabase, BUCKET } from './supabase.js'
import { getSettings } from './settings.js'

const PLACEHOLDER = '.keep'

const fmtSize = (b) => {
  if (b == null) return '—'
  if (b < 1024) return `${b} B`
  if (b < 1024 ** 2) return `${(b / 1024).toFixed(1)} KB`
  if (b < 1024 ** 3) return `${(b / 1024 ** 2).toFixed(1)} MB`
  return `${(b / 1024 ** 3).toFixed(2)} GB`
}

const fmtDate = (d) => (d ? new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—')

const ext = (name) => name.split('.').pop().toLowerCase()

const kindOf = (name) => {
  const e = ext(name)
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'avif'].includes(e)) return 'image'
  if (e === 'pdf') return 'pdf'
  if (['mp4', 'webm', 'mov', 'm4v', 'ogv'].includes(e)) return 'video'
  if (['mp3', 'wav', 'm4a', 'ogg', 'flac'].includes(e)) return 'audio'
  if (['doc', 'docx'].includes(e)) return 'word'
  if (['xls', 'xlsx', 'csv'].includes(e)) return 'excel'
  if (['ppt', 'pptx'].includes(e)) return 'ppt'
  if (['zip', 'rar', '7z'].includes(e)) return 'zip'
  if (['txt', 'md', 'json', 'log'].includes(e)) return 'text'
  return 'file'
}

const ICONS = {
  folder: '📁', image: '🖼️', pdf: '📕', video: '🎬', audio: '🎵',
  word: '📘', excel: '📗', ppt: '📙', zip: '🗜️', text: '📄', file: '📄'
}

const PREVIEWABLE = ['image', 'pdf', 'video', 'audio', 'text']

const miniBtn =
  'min-h-[34px] min-w-[40px] rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm transition hover:border-brand-violet'

export default function FileManager() {
  const [path, setPath] = useState([])
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [sortBy, setSortBy] = useState(() => getSettings().fileSort)
  const [sortDir, setSortDir] = useState(1)
  const [busy, setBusy] = useState(null)
  const [error, setError] = useState(null)
  const [preview, setPreview] = useState(null)
  const [dragOver, setDragOver] = useState(false)
  const inputRef = useRef(null)

  const prefix = path.join('/')
  const fullPath = (name) => (prefix ? `${prefix}/${name}` : name)

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    const { data, error } = await supabase.storage.from(BUCKET).list(prefix, { limit: 1000 })
    if (error) setError(error.message)
    else setItems((data || []).filter((i) => i.name !== PLACEHOLDER && !(prefix === '' && i.name.startsWith('_'))))
    setLoading(false)
  }, [prefix])

  useEffect(() => { load() }, [load])

  const visible = useMemo(() => {
    let list = items
    if (search.trim()) {
      const q = search.trim().toLowerCase()
      list = list.filter((i) => i.name.toLowerCase().includes(q))
    }
    const folders = list.filter((i) => !i.id)
    const files = list.filter((i) => i.id)
    const cmp = (a, b) => {
      let r = 0
      if (sortBy === 'name') r = a.name.localeCompare(b.name, undefined, { numeric: true })
      else if (sortBy === 'date') r = new Date(a.updated_at || 0) - new Date(b.updated_at || 0)
      else r = (a.metadata?.size || 0) - (b.metadata?.size || 0)
      return r * sortDir
    }
    return [...folders.sort((a, b) => a.name.localeCompare(b.name) * sortDir), ...files.sort(cmp)]
  }, [items, search, sortBy, sortDir])

  async function uploadFiles(fileList) {
    const files = Array.from(fileList)
    if (!files.length) return
    let done = 0
    for (const f of files) {
      setBusy(`Uploading ${f.name} (${++done}/${files.length})…`)
      const { error } = await supabase.storage.from(BUCKET).upload(fullPath(f.name), f, { upsert: true })
      if (error) { setError(`${f.name}: ${error.message}`); break }
    }
    setBusy(null)
    load()
  }

  async function newFolder() {
    const name = window.prompt('Folder name:')
    if (!name) return
    const clean = name.replace(/[\\/:*?"<>|]/g, '').trim()
    if (!clean) return
    setBusy('Creating folder…')
    const { error } = await supabase.storage.from(BUCKET)
      .upload(fullPath(`${clean}/${PLACEHOLDER}`), new Blob(['']), { upsert: true })
    if (error) setError(error.message)
    setBusy(null)
    load()
  }

  async function download(item) {
    const { data, error } = await supabase.storage.from(BUCKET)
      .createSignedUrl(fullPath(item.name), 120, { download: item.name })
    if (error) return setError(error.message)
    const a = document.createElement('a')
    a.href = data.signedUrl
    a.download = item.name
    a.click()
  }

  async function share(item) {
    const days = getSettings().shareDays || 7
    const { data, error } = await supabase.storage.from(BUCKET)
      .createSignedUrl(fullPath(item.name), 60 * 60 * 24 * days)
    if (error) return setError(error.message)
    try {
      if (navigator.share) {
        await navigator.share({ title: item.name, url: data.signedUrl })
      } else {
        await navigator.clipboard.writeText(data.signedUrl)
        setBusy(`Share link copied to clipboard (valid ${days} day${days > 1 ? 's' : ''})`)
        setTimeout(() => setBusy(null), 3000)
      }
    } catch { /* user cancelled */ }
  }

  async function removeFile(item) {
    if (getSettings().confirmDelete && !window.confirm(`Permanently delete "${item.name}"?`)) return
    setBusy('Deleting…')
    const { error } = await supabase.storage.from(BUCKET).remove([fullPath(item.name)])
    if (error) setError(error.message)
    setBusy(null)
    load()
  }

  async function removeFolder(item) {
    if (getSettings().confirmDelete && !window.confirm(`Delete folder "${item.name}" and everything inside it?`)) return
    setBusy('Deleting folder…')
    const base = fullPath(item.name)
    const collect = async (p) => {
      const { data } = await supabase.storage.from(BUCKET).list(p, { limit: 1000 })
      let paths = []
      for (const it of data || []) {
        if (it.id) paths.push(`${p}/${it.name}`)
        else paths = paths.concat(await collect(`${p}/${it.name}`))
      }
      return paths
    }
    const all = await collect(base)
    if (all.length) await supabase.storage.from(BUCKET).remove(all)
    setBusy(null)
    load()
  }

  async function favorite(item) {
    const { data: { user } } = await supabase.auth.getUser()
    if (!user) return
    const { error } = await supabase.from('favorites').insert({
      user_id: user.id,
      kind: 'doc',
      payload: { path: fullPath(item.name), name: item.name }
    })
    if (error && !/duplicate/i.test(error.message)) setError(error.message)
    else {
      setBusy('⭐ Pinned to your Home page')
      setTimeout(() => setBusy(null), 2500)
    }
  }

  async function openPreview(item) {
    const kind = kindOf(item.name)
    if (!PREVIEWABLE.includes(kind)) return download(item)
    setBusy('Opening preview…')
    const { data, error } = await supabase.storage.from(BUCKET).createSignedUrl(fullPath(item.name), 3600)
    setBusy(null)
    if (error) return setError(error.message)
    let text = null
    if (kind === 'text') {
      try { text = await (await fetch(data.signedUrl)).text() } catch { text = '(could not load file)' }
    }
    setPreview({ name: item.name, kind, url: data.signedUrl, text })
  }

  function onDrop(e) {
    e.preventDefault()
    setDragOver(false)
    if (e.dataTransfer.files?.length) uploadFiles(e.dataTransfer.files)
  }

  return (
    <main
      className={`mx-auto w-full max-w-5xl flex-1 px-3 py-5 sm:px-7 ${dragOver ? 'rounded-2xl bg-brand-gradient-soft outline-3 outline-dashed outline-brand-violet -outline-offset-8' : ''}`}
      onDragOver={(e) => { e.preventDefault(); setDragOver(true) }}
      onDragLeave={() => setDragOver(false)}
      onDrop={onDrop}
    >
      {/* toolbar */}
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <nav className="flex flex-wrap items-center text-sm" aria-label="Breadcrumb">
          <button className="px-0.5 py-1 font-semibold text-brand-blue hover:underline" onClick={() => setPath([])}>
            Home
          </button>
          {path.map((seg, i) => (
            <span key={i}>
              <span className="mx-1 text-slate-400">/</span>
              <button className="px-0.5 py-1 font-semibold text-brand-blue hover:underline" onClick={() => setPath(path.slice(0, i + 1))}>
                {seg}
              </button>
            </span>
          ))}
        </nav>
        <div className="flex w-full flex-wrap items-center gap-2 sm:w-auto">
          <input
            type="search"
            className="min-w-0 flex-1 rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-violet sm:min-w-[170px] sm:flex-none"
            placeholder="Search this folder…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
          <select
            className="rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-brand-violet"
            value={sortBy}
            onChange={(e) => setSortBy(e.target.value)}
            aria-label="Sort by"
          >
            <option value="name">Name</option>
            <option value="date">Date</option>
            <option value="size">Size</option>
          </select>
          <button
            className="rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm font-bold transition hover:border-brand-violet hover:text-brand-violet"
            onClick={() => setSortDir(-sortDir)}
            title="Reverse order"
          >
            {sortDir === 1 ? '↑' : '↓'}
          </button>
          <button
            className="rounded-lg border-2 border-slate-200 bg-white px-3.5 py-2 text-sm font-bold transition hover:border-brand-violet hover:text-brand-violet"
            onClick={newFolder}
          >
            + Folder
          </button>
          <button
            className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110"
            onClick={() => inputRef.current?.click()}
          >
            Upload
          </button>
          <input ref={inputRef} type="file" multiple hidden onChange={(e) => { uploadFiles(e.target.files); e.target.value = '' }} />
        </div>
      </div>

      {busy && <div className="mb-3 rounded-lg bg-brand-gradient-soft px-3.5 py-2.5 text-sm text-brand-violet">{busy}</div>}
      {error && (
        <div className="mb-3 cursor-pointer rounded-lg bg-red-50 px-3.5 py-2.5 text-sm text-red-600" onClick={() => setError(null)}>
          {error} ✕
        </div>
      )}

      {loading ? (
        <div className="grid place-items-center py-20">
          <div className="h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-brand-violet" />
        </div>
      ) : visible.length === 0 ? (
        <div className="grid justify-items-center gap-2 py-20 text-center text-slate-500">
          <p className="m-0 text-4xl">☁️</p>
          <p className="m-0">{search ? 'No matches in this folder.' : 'Nothing here yet. Drag & drop files or hit Upload.'}</p>
        </div>
      ) : (
        <ul className="m-0 grid list-none grid-cols-1 gap-3 p-0 min-[400px]:grid-cols-2 sm:grid-cols-[repeat(auto-fill,minmax(230px,1fr))]">
          {visible.map((item) => {
            const isFolder = !item.id
            const kind = isFolder ? 'folder' : kindOf(item.name)
            return (
              <li
                key={item.name}
                className="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg"
              >
                <button
                  className="grid flex-1 gap-1 px-4 pb-2.5 pt-4 text-left"
                  onClick={() => (isFolder ? setPath([...path, item.name]) : openPreview(item))}
                  title={isFolder ? 'Open folder' : 'Preview / download'}
                >
                  <span className="text-3xl leading-none" aria-hidden="true">{ICONS[kind]}</span>
                  <span className="break-words text-sm font-bold">{item.name}</span>
                  <span className="text-xs text-slate-500">
                    {isFolder ? 'Folder' : `${fmtSize(item.metadata?.size)} · ${fmtDate(item.updated_at)}`}
                  </span>
                </button>
                <div className="flex gap-1.5 border-t border-slate-200 bg-slate-50 px-3 py-2">
                  {!isFolder && (
                    <>
                      <button className={miniBtn} onClick={() => download(item)} title="Download">⬇</button>
                      <button className={miniBtn} onClick={() => share(item)} title="Share link">🔗</button>
                      <button className={miniBtn} onClick={() => favorite(item)} title="Pin to Home favorites">☆</button>
                    </>
                  )}
                  <button
                    className={`${miniBtn} hover:border-red-500 hover:bg-red-50`}
                    onClick={() => (isFolder ? removeFolder(item) : removeFile(item))}
                    title="Delete"
                  >
                    🗑
                  </button>
                </div>
              </li>
            )
          })}
        </ul>
      )}

      {preview && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-indigo-950/70 p-3.5" onClick={() => setPreview(null)} role="dialog" aria-modal="true">
          <div className="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between gap-2.5 border-b border-slate-200 px-4 py-3">
              <strong className="break-all text-sm">{preview.name}</strong>
              <button
                className="shrink-0 rounded-lg border-2 border-slate-200 px-3 py-1.5 text-sm font-bold transition hover:border-brand-violet hover:text-brand-violet"
                onClick={() => setPreview(null)}
              >
                ✕ Close
              </button>
            </div>
            <div className="grid min-h-[200px] place-items-center overflow-auto bg-indigo-950">
              {preview.kind === 'image' && <img className="max-h-[80vh] max-w-full" src={preview.url} alt={preview.name} />}
              {preview.kind === 'pdf' && <iframe className="h-[80vh] w-full border-0 bg-white" src={preview.url} title={preview.name} />}
              {preview.kind === 'video' && <video className="max-h-[80vh] max-w-full" src={preview.url} controls autoPlay playsInline />}
              {preview.kind === 'audio' && <audio className="mx-5 my-12 w-[min(480px,90%)]" src={preview.url} controls autoPlay />}
              {preview.kind === 'text' && (
                <pre className="m-0 max-h-[80vh] w-full justify-self-stretch overflow-auto whitespace-pre-wrap bg-white p-4 text-sm text-ink">{preview.text}</pre>
              )}
            </div>
          </div>
        </div>
      )}
    </main>
  )
}
