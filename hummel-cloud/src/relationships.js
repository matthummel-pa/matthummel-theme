import { supabase } from './supabase.js'

// Load every relationship row that involves me (RLS already restricts to these).
export async function loadRelationships() {
  const { data } = await supabase.from('relationships').select('*')
  return data || []
}

// Derive how I relate to a given person from the relationship rows.
export function relationTo(rels, me, otherId) {
  let friend = 'none' // none | out (I requested) | in (they requested) | yes (accepted)
  let following = false // I follow them
  let followsMe = false // they follow me
  for (const r of rels) {
    if (r.kind === 'friend') {
      if (r.status === 'accepted' && (r.requester === otherId || r.addressee === otherId)) friend = 'yes'
      else if (r.status === 'pending' && r.requester === me && r.addressee === otherId && friend !== 'yes') friend = 'out'
      else if (r.status === 'pending' && r.addressee === me && r.requester === otherId && friend !== 'yes') friend = 'in'
    } else if (r.kind === 'follow') {
      if (r.requester === me && r.addressee === otherId) following = true
      if (r.requester === otherId && r.addressee === me) followsMe = true
    }
  }
  return { friend, following, followsMe }
}

export async function sendFriendRequest(addressee) {
  return supabase.from('relationships').insert({ addressee, kind: 'friend', status: 'pending', requester: (await supabase.auth.getUser()).data.user.id })
}
export async function follow(addressee) {
  return supabase.from('relationships').insert({ addressee, kind: 'follow', status: 'accepted', requester: (await supabase.auth.getUser()).data.user.id })
}
export async function acceptFriend(reqId) {
  return supabase.from('relationships').update({ status: 'accepted' }).eq('id', reqId)
}
// remove a relationship row by matching kind + the two parties (either direction for friend)
export async function removeRelationship(rowId) {
  return supabase.from('relationships').delete().eq('id', rowId)
}
