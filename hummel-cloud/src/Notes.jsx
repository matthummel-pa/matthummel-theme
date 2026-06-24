import { useCallback, useEffect, useRef, useState } from 'react'
import { supabase, BUCKET } from './supabase.js'
import { getSettings, saveSettings } from './settings.js'
import AppIcon from './AppIcon.jsx'

const ATTACH_PREFIX = '_notes'

export const NOTEBOOK_COLORS = {
  violet: 'bg-violet-100 text-violet-700 border-violet-300',
  blue: 'bg-blue-100 text-blue-700 border-blue-300',
  emerald: 'bg-emerald-100 text-emerald-700 border-emerald-300',
  amber: 'bg-amber-100 text-amber-700 border-amber-300',
  rose: 'bg-rose-100 text-rose-700 border-rose-300',
  slate: 'bg-slate-100 text-slate-700 border-slate-300'
}

const NOTEBOOK_ICONS = ['📓', '📔', '📒', '💼', '🏠', '✈️', '💡', '🧾', '🍳', '🏋️', '🎨', '⚡']

const fmtDate = (d) => new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })

const plain = (html) => (html || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()

const inputCls =
  'rounded-lg border-2 border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-brand-violet'

const toolBtn =
  'min-w-[34px] rounded-md border border-slate-200 bg-white px-2 py-1 text-sm font-bold text-slate-600 transition hover:border-brand-violet hover:text-brand-violet'

export default function Notes({ session }) {
  const user = session.user
  const [notebooks, setNotebooks] = useState([])
  const [notes, setNotes] = useState([])
  const [activeNb, setActiveNb] = useState('all')
  const [loading, setLoading] = useState(true)
  const [editing, setEditing] = useState(null) // note row being edited
  const [nbModal, setNbModal] = useState(null) // notebook being created/edited
  const [wizard, setWizard] = useState(null) // notes-service setup wizard
  const [msg, setMsg] = useState(null)
  const [publishing, setPublishing] = useState(false)
  const editorRef = useRef(null)
  const attachRef = useRef(null)
  const imageRef = useRef(null)

  const load = useCallback(async () => {
    const [{ data: nbs }, { data: nts }] = await Promise.all([
      supabase.from('notebooks').select('*').order('created_at'),
      supabase.from('notes').select('*').order('updated_at', { ascending: false })
    ])
    setNotebooks(nbs || [])
    setNotes(nts || [])
    setLoading(false)
  }, [])

  useEffect(() => { load() }, [load])

  const flash = (ok, text, ms = 4000) => {
    setMsg({ ok, text })
    setTimeout(() => setMsg(null), ms)
  }

  /* ---------- notebooks ---------- */
  async function saveNotebook() {
    const row = { user_id: user.id, name: nbModal.name.trim() || 'Notebook', icon: nbModal.icon, color: nbModal.color }
    if (nbModal.id) {
      await supabase.from('notebooks').update(row).eq('id', nbModal.id)
    } else {
      await supabase.from('notebooks').insert(row)
    }
    setNbModal(null)
    load()
  }

  async function deleteNotebook(nb) {
    if (!window.confirm(`Delete notebook "${nb.name}"? Notes inside are kept (moved to All notes).`)) return
    setNbModal(null)
    if (activeNb === nb.id) setActiveNb('all')
    await supabase.from('notebooks').delete().eq('id', nb.id)
    load()
  }

  /* ---------- notes ---------- */
  function newNote() {
    setEditing({
      id: null,
      title: '',
      content_html: '',
      notebook_id: activeNb !== 'all' && activeNb !== 'none' ? activeNb : null,
      attachments: []
    })
  }

  async function saveNote(keepOpen = false) {
    const html = editorRef.current?.innerHTML || editing.content_html || ''
    const row = {
      user_id: user.id,
      title: editing.title.trim() || 'Untitled',
      content: plain(html).slice(0, 2000),
      content_html: html,
      notebook_id: editing.notebook_id,
      attachments: editing.attachments,
      updated_at: new Date().toISOString()
    }
    let saved = editing
    if (editing.id) {
      await supabase.from('notes').update(row).eq('id', editing.id)
      saved = { ...editing, ...row }
    } else {
      const { data } = await supabase.from('notes').insert(row).select().single()
      if (data) saved = data
    }
    if (keepOpen) setEditing(saved)
    else setEditing(null)
    load()
    return saved
  }

  async function deleteNote() {
    if (getSettings().confirmDelete && !window.confirm(`Delete note "${editing.title || 'Untitled'}"?`)) return
    if (editing.id) await supabase.from('notes').delete().eq('id', editing.id)
    setEditing(null)
    load()
  }

  /* ---------- editor commands ---------- */
  const cmd = (command, value = null) => {
    editorRef.current?.focus()
    document.execCommand(command, false, value)
  }

  function addLink() {
    const url = window.prompt('Link URL:')
    if (url) cmd('createLink', /^https?:/.test(url) ? url : `https://${url}`)
  }

  async function insertImage(e) {
    const f = e.target.files?.[0]
    e.target.value = ''
    if (!f || !/^image\//.test(f.type)) return
    const path = `${ATTACH_PREFIX}/${user.id}/${Date.now()}-${f.name.replace(/[^\w.-]+/g, '_')}`
    const { error } = await supabase.storage.from(BUCKET).upload(path, f, { upsert: true })
    if (error) return flash(false, error.message)
    const { data } = await supabase.storage.from(BUCKET).createSignedUrl(path, 60 * 60 * 24 * 365)
    if (data?.signedUrl) {
      cmd('insertHTML', `<img src="${data.signedUrl}" alt="${f.name}" style="max-width:100%;border-radius:8px" />`)
      setEditing((n) => ({ ...n, attachments: [...n.attachments, { path, name: f.name, inline: true }] }))
    }
  }

  async function addAttachment(e) {
    const files = Array.from(e.target.files || [])
    e.target.value = ''
    for (const f of files) {
      const path = `${ATTACH_PREFIX}/${user.id}/${Date.now()}-${f.name.replace(/[^\w.-]+/g, '_')}`
      const { error } = await supabase.storage.from(BUCKET).upload(path, f, { upsert: true })
      if (error) { flash(false, `${f.name}: ${error.message}`); continue }
      setEditing((n) => ({ ...n, attachments: [...n.attachments, { path, name: f.name }] }))
    }
  }

  async function openAttachment(a) {
    const { data } = await supabase.storage.from(BUCKET).createSignedUrl(a.path, 300)
    if (data?.signedUrl) window.open(data.signedUrl, '_blank', 'noopener')
  }

  async function removeAttachment(a) {
    setEditing((n) => ({ ...n, attachments: n.attachments.filter((x) => x.path !== a.path) }))
    await supabase.storage.from(BUCKET).remove([a.path])
  }

  /* ---------- publishing ---------- */
  async function publish() {
    const s = getSettings()
    const saved = await saveNote(true)
    const html = saved.content_html || ''
    if (s.notesService === 'standalone') {
      return flash(true, '💾 Saved — standalone notes live right here in Keepary.')
    }
    if (s.notesService === 'notion') {
      if (!s.notionToken || !s.notionParent) {
        return flash(false, 'Set up Notion first: Settings → Notes publishing (integration token + parent page).')
      }
      setPublishing(true)
      const { data: res, error } = await supabase.functions.invoke('notion', {
        body: { op: 'publish', token: s.notionToken, parentId: s.notionParent, title: saved.title, html }
      })
      setPublishing(false)
      if (error || res?.error) return flash(false, res?.error || 'Notion publish failed.')
      flash(true, '✓ Published to Notion!')
      if (res.url) window.open(res.url, '_blank', 'noopener')
    } else {
      // GoodNotes has no public API — export a print-ready page (Save as PDF → import to GoodNotes)
      const w = window.open('', '_blank', 'noopener,width=800,height=900')
      if (!w) return flash(false, 'Popup blocked — allow popups to export for GoodNotes.')
      w.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>${saved.title}</title>
        <style>body{font-family:-apple-system,Segoe UI,Roboto,sans-serif;max-width:700px;margin:40px auto;padding:0 20px;color:#1e2235;line-height:1.6}
        h1.note-title{border-bottom:3px solid #7c3aed;padding-bottom:8px}img{max-width:100%}</style></head>
        <body><h1 class="note-title">${saved.title}</h1>${html}
        <script>window.onload=()=>setTimeout(()=>window.print(),300)<\/script></body></html>`)
      w.document.close()
      flash(true, 'Print dialog opened — choose "Save as PDF", then import the PDF into GoodNotes.', 7000)
    }
  }

  /* ---------- derived ---------- */
  const visibleNotes = notes.filter((n) => (activeNb === 'all' ? true : activeNb === 'none' ? !n.notebook_id : n.notebook_id === activeNb))
  const nbOf = (id) => notebooks.find((b) => b.id === id)
  const service = getSettings().notesService

  return (
    <main className="mx-auto w-full max-w-4xl flex-1 px-3 py-5 sm:px-7">
      <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h1 className="m-0 text-xl font-extrabold tracking-tight flex items-center gap-2"><AppIcon id="scribe" className="h-7 w-7" /> Scribe</h1>
        <p className="m-0 text-xs text-slate-500">Notes & notebooks</p>
        <button className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110" onClick={newNote}>
          + New note
        </button>
      </div>

      {/* notebook chips */}
      <div className="mb-4 flex flex-wrap items-center gap-2">
        <button
          onClick={() => setActiveNb('all')}
          className={activeNb === 'all' ? 'rounded-full bg-brand-gradient px-3.5 py-1.5 text-xs font-bold text-white' : 'rounded-full border-2 border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-600 hover:border-brand-violet'}
        >
          All notes ({notes.length})
        </button>
        {notebooks.map((nb) => (
          <span key={nb.id} className="relative inline-flex">
            <button
              onClick={() => setActiveNb(nb.id)}
              onDoubleClick={() => setNbModal(nb)}
              title="Double-click to edit notebook"
              className={`rounded-full border-2 px-3.5 py-1.5 text-xs font-bold ${activeNb === nb.id ? NOTEBOOK_COLORS[nb.color] || NOTEBOOK_COLORS.violet : 'border-slate-200 bg-white text-slate-600 hover:border-brand-violet'}`}
            >
              {nb.icon} {nb.name} ({notes.filter((n) => n.notebook_id === nb.id).length})
            </button>
          </span>
        ))}
        <button
          onClick={() => {
            const s = getSettings()
            const connected = s.notesService === 'standalone' || s.notesService === 'goodnotes' || (s.notionToken && s.notionParent)
            if (!connected) {
              setWizard({ step: 1, service: s.notesService || 'notion', token: s.notionToken || '', parent: s.notionParent || '', testing: false, err: null })
            } else {
              setNbModal({ name: '', icon: '📓', color: 'violet' })
            }
          }}
          className="rounded-full border-2 border-dashed border-slate-300 px-3.5 py-1.5 text-xs font-bold text-slate-500 hover:border-brand-violet hover:text-brand-violet"
        >
          + Notebook
        </button>
      </div>

      {msg && <p className={`mb-3 rounded-xl px-4 py-2.5 text-sm ${msg.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'}`}>{msg.text}</p>}

      {loading ? (
        <div className="grid place-items-center py-20"><div className="h-9 w-9 animate-spin rounded-full border-4 border-slate-200 border-t-brand-violet" /></div>
      ) : visibleNotes.length === 0 ? (
        <div className="grid justify-items-center gap-2 py-16 text-center text-slate-500">
          <p className="m-0 text-4xl">🗒</p>
          <p className="m-0">No notes here yet — hit "+ New note".</p>
        </div>
      ) : (
        <div className="grid gap-3 sm:grid-cols-2">
          {visibleNotes.map((n) => {
            const nb = nbOf(n.notebook_id)
            return (
              <button
                key={n.id}
                onClick={() => setEditing({ ...n, attachments: n.attachments || [] })}
                className="grid gap-1.5 rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg"
              >
                <span className="flex items-center gap-2">
                  {nb && <span className={`rounded-full border px-2 py-0.5 text-[10px] font-bold ${NOTEBOOK_COLORS[nb.color] || ''}`}>{nb.icon} {nb.name}</span>}
                  <span className="text-[11px] text-slate-400">{fmtDate(n.updated_at)}</span>
                  {(n.attachments || []).length > 0 && <span className="text-[11px] text-slate-400">📎 {(n.attachments || []).length}</span>}
                </span>
                <span className="text-sm font-extrabold">{n.title}</span>
                <span className="line-clamp-3 text-xs leading-relaxed text-slate-500">{plain(n.content_html) || n.content || '(empty)'}</span>
              </button>
            )
          })}
        </div>
      )}

      {/* ---------- note editor ---------- */}
      {editing && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-indigo-950/70 p-2 sm:p-4" role="dialog" aria-modal="true">
          <div className="flex max-h-[96vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white">
            <div className="flex items-center gap-2 border-b border-slate-200 px-4 py-3">
              <input
                className="min-w-0 flex-1 rounded-lg border-2 border-transparent px-2 py-1 text-lg font-extrabold outline-none focus:border-brand-violet"
                placeholder="Note title…"
                value={editing.title}
                onChange={(e) => setEditing({ ...editing, title: e.target.value })}
                maxLength={150}
              />
              <select
                className={`${inputCls} max-w-[150px] px-2 py-1.5 text-xs`}
                value={editing.notebook_id || ''}
                onChange={(e) => setEditing({ ...editing, notebook_id: e.target.value || null })}
                aria-label="Notebook"
              >
                <option value="">No notebook</option>
                {notebooks.map((nb) => <option key={nb.id} value={nb.id}>{nb.icon} {nb.name}</option>)}
              </select>
            </div>

            {/* toolbar */}
            <div className="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 px-3 py-2">
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); cmd('bold') }} title="Bold"><b>B</b></button>
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); cmd('italic') }} title="Italic"><i>I</i></button>
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); cmd('underline') }} title="Underline"><u>U</u></button>
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); cmd('strikeThrough') }} title="Strikethrough"><s>S</s></button>
              <span className="mx-1 h-5 w-px bg-slate-300" />
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); cmd('formatBlock', '<h1>') }} title="Heading 1">H1</button>
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); cmd('formatBlock', '<h2>') }} title="Heading 2">H2</button>
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); cmd('formatBlock', '<p>') }} title="Normal text">¶</button>
              <span className="mx-1 h-5 w-px bg-slate-300" />
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); cmd('insertUnorderedList') }} title="Bullet list">•≡</button>
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); cmd('insertOrderedList') }} title="Numbered list">1≡</button>
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); cmd('insertHTML', '<ul><li>☐ </li></ul>') }} title="Checklist">☑</button>
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); cmd('formatBlock', '<blockquote>') }} title="Quote">❝</button>
              <span className="mx-1 h-5 w-px bg-slate-300" />
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); addLink() }} title="Insert link">🔗</button>
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); imageRef.current?.click() }} title="Insert image">🖼</button>
              <button className={toolBtn} onMouseDown={(e) => { e.preventDefault(); cmd('removeFormat') }} title="Clear formatting">⌫</button>
              <input ref={imageRef} type="file" accept="image/*" hidden onChange={insertImage} />
            </div>

            {/* editor */}
            <div
              ref={editorRef}
              contentEditable
              suppressContentEditableWarning
              className="prose-sm min-h-[220px] flex-1 overflow-auto px-5 py-4 text-sm leading-relaxed outline-none [&_blockquote]:border-l-4 [&_blockquote]:border-brand-violet [&_blockquote]:pl-3 [&_blockquote]:text-slate-500 [&_h1]:text-xl [&_h1]:font-extrabold [&_h2]:text-lg [&_h2]:font-bold [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6 [&_a]:text-brand-blue [&_a]:underline"
              dangerouslySetInnerHTML={{ __html: editing.content_html || '' }}
            />

            {/* attachments */}
            <div className="border-t border-slate-200 bg-slate-50 px-4 py-2.5">
              <div className="flex flex-wrap items-center gap-2">
                <button className="rounded-lg border-2 border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 hover:border-brand-violet" onClick={() => attachRef.current?.click()}>
                  📎 Attach files
                </button>
                <input ref={attachRef} type="file" multiple hidden onChange={addAttachment} />
                {(editing.attachments || []).filter((a) => !a.inline).map((a) => (
                  <span key={a.path} className="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold">
                    <button className="hover:text-brand-violet" onClick={() => openAttachment(a)}>📄 {a.name.slice(0, 24)}</button>
                    <button className="text-slate-400 hover:text-red-500" onClick={() => removeAttachment(a)} title="Remove">✕</button>
                  </span>
                ))}
              </div>
            </div>

            {/* footer */}
            <div className="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-4 py-3">
              <div className="flex gap-2">
                {editing.id && <button className="text-sm font-bold text-red-500 hover:underline" onClick={deleteNote}>Delete</button>}
              </div>
              <div className="flex flex-wrap gap-2">
                <button className="rounded-lg border-2 border-slate-200 px-4 py-2 text-sm font-bold text-slate-600" onClick={() => setEditing(null)}>Close</button>
                <button className="rounded-lg border-2 border-brand-violet px-4 py-2 text-sm font-bold text-brand-violet transition hover:bg-brand-gradient-soft" onClick={() => saveNote(true)}>
                  💾 Save
                </button>
                {service !== 'standalone' && (
                  <button
                    className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white transition hover:brightness-110 disabled:opacity-60"
                    onClick={publish}
                    disabled={publishing}
                  >
                    {publishing ? 'Publishing…' : service === 'notion' ? '🚀 Publish to Notion' : '🚀 Export for GoodNotes'}
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ---------- notes-service setup wizard ---------- */}
      {wizard && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-indigo-950/70 p-3.5" role="dialog" aria-modal="true">
          <div className="grid w-full max-w-md gap-4 rounded-2xl bg-white p-6">
            <div className="flex items-center justify-between">
              <h3 className="m-0 text-base font-extrabold">📡 Connect your notes service</h3>
              <button className="text-sm font-bold text-slate-400 hover:text-slate-600" onClick={() => setWizard(null)}>✕</button>
            </div>
            <div className="flex gap-1.5" aria-hidden="true">
              {[1, 2, 3].map((i) => (
                <span key={i} className={`h-1.5 flex-1 rounded-full ${wizard.step >= i ? 'bg-brand-gradient' : 'bg-slate-200'}`} />
              ))}
            </div>

            {wizard.step === 1 && (
              <>
                <p className="m-0 text-sm text-slate-600">Before you build notebooks, pick where your notes should live:</p>
                <div className="grid gap-2">
                  {[
                    { id: 'standalone', icon: '📓', name: 'Standalone notebook', desc: 'Keep notes right here in Keepary — no accounts, no setup. You can connect a service later.' },
                    { id: 'notion', icon: '⬜', name: 'Notion', desc: 'Publishes real Notion pages via your own free integration.' },
                    { id: 'goodnotes', icon: '📔', name: 'GoodNotes', desc: 'Exports print-ready PDFs you import into GoodNotes (no account link needed).' }
                  ].map((svc) => (
                    <button
                      key={svc.id}
                      onClick={() => {
                        if (svc.id === 'standalone') {
                          saveSettings({ ...getSettings(), notesService: 'standalone' })
                          setWizard(null)
                          setNbModal({ name: '', icon: '📓', color: 'violet' })
                        } else {
                          setWizard({ ...wizard, service: svc.id, step: 2, err: null })
                        }
                      }}
                      className={`grid gap-0.5 rounded-xl border-2 p-3.5 text-left transition hover:border-brand-violet ${wizard.service === svc.id ? 'border-brand-violet bg-brand-gradient-soft' : 'border-slate-200'}`}
                    >
                      <span className="text-sm font-extrabold">{svc.icon} {svc.name}</span>
                      <span className="text-xs text-slate-500">{svc.desc}</span>
                    </button>
                  ))}
                </div>
              </>
            )}

            {wizard.step === 2 && wizard.service === 'goodnotes' && (
              <>
                <p className="m-0 text-sm leading-relaxed text-slate-600">
                  <strong>GoodNotes — you're all set.</strong> GoodNotes has no public API, so when you hit 🚀 Publish the app opens a print-ready
                  version of your note. Choose <strong>"Save as PDF"</strong>, then import that PDF into GoodNotes (on iPad, share it straight into the app).
                  Nothing to connect, nothing to configure.
                </p>
                <div className="flex justify-between">
                  <button className="text-sm font-bold text-slate-500" onClick={() => setWizard({ ...wizard, step: 1 })}>← Back</button>
                  <button
                    className="rounded-lg bg-brand-gradient px-5 py-2 text-sm font-bold text-white"
                    onClick={() => {
                      saveSettings({ ...getSettings(), notesService: 'goodnotes' })
                      setWizard(null)
                      setNbModal({ name: '', icon: '📓', color: 'violet' })
                    }}
                  >
                    Finish & create notebook →
                  </button>
                </div>
              </>
            )}

            {wizard.step === 2 && wizard.service === 'notion' && (
              <>
                <p className="m-0 text-sm leading-relaxed text-slate-600">
                  <strong>Step 1 of 2 — your Notion integration token.</strong><br />
                  Open <a className="text-brand-blue underline" href="https://www.notion.so/my-integrations" target="_blank" rel="noopener noreferrer">notion.so/my-integrations</a>,
                  click <strong>New integration</strong> (any name, your workspace), then copy its <strong>Internal Integration Secret</strong> and paste it here. It stays on this device.
                </p>
                <input
                  type="password"
                  className={`${inputCls} w-full`}
                  placeholder="ntn_… or secret_…"
                  value={wizard.token}
                  onChange={(e) => setWizard({ ...wizard, token: e.target.value.trim() })}
                  autoComplete="off"
                />
                <div className="flex justify-between">
                  <button className="text-sm font-bold text-slate-500" onClick={() => setWizard({ ...wizard, step: 1 })}>← Back</button>
                  <button
                    className="rounded-lg bg-brand-gradient px-5 py-2 text-sm font-bold text-white disabled:opacity-50"
                    disabled={!wizard.token}
                    onClick={() => setWizard({ ...wizard, step: 3, err: null })}
                  >
                    Next →
                  </button>
                </div>
              </>
            )}

            {wizard.step === 3 && wizard.service === 'notion' && (
              <>
                <p className="m-0 text-sm leading-relaxed text-slate-600">
                  <strong>Step 2 of 2 — where published notes land.</strong><br />
                  In Notion, open (or create) the page that should hold your published notes. Click the <strong>•••</strong> menu →
                  <strong> Connections</strong> → add the integration you just made. Then copy the page link and paste it below.
                </p>
                <input
                  className={`${inputCls} w-full`}
                  placeholder="https://www.notion.so/My-Notes-1a2b3c4d…"
                  value={wizard.parent}
                  onChange={(e) => setWizard({ ...wizard, parent: e.target.value.trim() })}
                  autoComplete="off"
                />
                {wizard.err && <p className="m-0 rounded-xl bg-red-50 px-4 py-2.5 text-sm text-red-600">{wizard.err}</p>}
                <div className="flex justify-between">
                  <button className="text-sm font-bold text-slate-500" onClick={() => setWizard({ ...wizard, step: 2 })}>← Back</button>
                  <button
                    className="rounded-lg bg-brand-gradient px-5 py-2 text-sm font-bold text-white disabled:opacity-50"
                    disabled={!wizard.parent || wizard.testing}
                    onClick={async () => {
                      setWizard((w) => ({ ...w, testing: true, err: null }))
                      const { data: res, error } = await supabase.functions.invoke('notion', { body: { op: 'test', token: wizard.token } })
                      if (error || res?.error) {
                        setWizard((w) => ({ ...w, testing: false, err: res?.error || 'Could not reach Notion — double-check the token.' }))
                        return
                      }
                      saveSettings({ ...getSettings(), notesService: 'notion', notionToken: wizard.token, notionParent: wizard.parent })
                      setWizard(null)
                      flash(true, `✓ Connected to Notion as "${res.bot}" — now design your first notebook!`)
                      setNbModal({ name: '', icon: '📓', color: 'violet' })
                    }}
                  >
                    {wizard.testing ? 'Testing…' : 'Test & finish →'}
                  </button>
                </div>
              </>
            )}
          </div>
        </div>
      )}

      {/* ---------- notebook designer ---------- */}
      {nbModal && (
        <div className="fixed inset-0 z-50 grid place-items-center bg-indigo-950/70 p-3.5" onClick={() => setNbModal(null)} role="dialog" aria-modal="true">
          <div className="grid w-full max-w-sm gap-4 rounded-2xl bg-white p-5" onClick={(e) => e.stopPropagation()}>
            <h3 className="m-0 text-base font-extrabold">{nbModal.id ? 'Edit notebook' : 'New notebook'}</h3>
            <label className="grid gap-1 text-sm font-bold">
              Name
              <input className={inputCls} value={nbModal.name} onChange={(e) => setNbModal({ ...nbModal, name: e.target.value })} placeholder="e.g. Blog ideas" maxLength={40} />
            </label>
            <div className="grid gap-1 text-sm font-bold">
              Icon
              <div className="flex flex-wrap gap-1">
                {NOTEBOOK_ICONS.map((ic) => (
                  <button key={ic} className={`rounded-lg border-2 px-2 py-1 text-lg ${nbModal.icon === ic ? 'border-brand-violet bg-brand-gradient-soft' : 'border-slate-200'}`} onClick={() => setNbModal({ ...nbModal, icon: ic })}>
                    {ic}
                  </button>
                ))}
              </div>
            </div>
            <div className="grid gap-1 text-sm font-bold">
              Color
              <div className="flex flex-wrap gap-2">
                {Object.keys(NOTEBOOK_COLORS).map((c) => (
                  <button
                    key={c}
                    className={`h-8 w-8 rounded-full border-4 ${NOTEBOOK_COLORS[c].split(' ')[0]} ${nbModal.color === c ? 'border-ink' : 'border-transparent'}`}
                    onClick={() => setNbModal({ ...nbModal, color: c })}
                    aria-label={c}
                  />
                ))}
              </div>
            </div>
            <div className="mt-1 flex items-center justify-between gap-2">
              {nbModal.id ? (
                <button className="text-sm font-bold text-red-500 hover:underline" onClick={() => deleteNotebook(nbModal)}>Delete</button>
              ) : <span />}
              <div className="flex gap-2">
                <button className="rounded-lg border-2 border-slate-200 px-4 py-2 text-sm font-bold text-slate-600" onClick={() => setNbModal(null)}>Cancel</button>
                <button className="rounded-lg bg-brand-gradient px-4 py-2 text-sm font-bold text-white" onClick={saveNotebook}>Save</button>
              </div>
            </div>
          </div>
        </div>
      )}
    </main>
  )
}
