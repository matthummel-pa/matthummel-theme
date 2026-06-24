import { useEffect, useState } from 'react'
import { supabase } from './supabase.js'

const DEFAULT_PAGES = ['trending', 'blog', 'profile', 'followers']

export async function fetchFriendPages() {
  const { data } = await supabase.from('app_settings').select('friend_pages').eq('id', 1).maybeSingle()
  return data?.friend_pages || DEFAULT_PAGES
}

// Owner/admin-only control shown on each page header: toggle if friends can see this page.
export function FriendToggle({ page }) {
  const [role, setRole] = useState(null)
  const [pages, setPages] = useState(null)
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    supabase.rpc('user_role').then(({ data }) => setRole(data || 'author'))
    fetchFriendPages().then(setPages)
  }, [])

  if (role !== 'administrator' || !pages) return null
  const on = pages.includes(page)

  async function toggle() {
    setBusy(true)
    const next = on ? pages.filter((p) => p !== page) : [...pages, page]
    await supabase.from('app_settings').update({ friend_pages: next, updated_at: new Date().toISOString() }).eq('id', 1)
    setPages(next)
    setBusy(false)
  }

  return (
    <button
      onClick={toggle}
      disabled={busy}
      title="Owner control: choose whether friends can see this page"
      className={`shrink-0 rounded-full px-3 py-1 text-xs font-bold transition ${on ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'}`}
    >
      {on ? '👁 Friends can see' : '🙈 Hidden from friends'}
    </button>
  )
}
