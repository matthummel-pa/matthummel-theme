// Lightweight client for the Anthropic API. The key is stored only in this
// browser's localStorage (set it under Settings → Claude AI).

export const CLAUDE_MODELS = [
  { id: 'claude-haiku-4-5-20251001', label: 'Claude Haiku (fast & cheap)' },
  { id: 'claude-sonnet-4-6', label: 'Claude Sonnet (best quality)' }
]

export function getClaudeConfig() {
  return {
    key: localStorage.getItem('hc-claude-key') || '',
    model: localStorage.getItem('hc-claude-model') || CLAUDE_MODELS[0].id
  }
}

export function saveClaudeConfig({ key, model }) {
  if (key != null) localStorage.setItem('hc-claude-key', key)
  if (model != null) localStorage.setItem('hc-claude-model', model)
}

export function clearClaudeKey() {
  localStorage.removeItem('hc-claude-key')
}

export async function askClaude(prompt, maxTokens = 1024) {
  const { key, model } = getClaudeConfig()
  if (!key) {
    const err = new Error('No Claude API key set. Add one under Settings → Claude AI.')
    err.code = 'NO_KEY'
    throw err
  }
  const res = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: {
      'content-type': 'application/json',
      'x-api-key': key,
      'anthropic-version': '2023-06-01',
      'anthropic-dangerous-direct-browser-access': 'true'
    },
    body: JSON.stringify({ model, max_tokens: maxTokens, messages: [{ role: 'user', content: prompt }] })
  })
  if (!res.ok) {
    const body = await res.json().catch(() => ({}))
    throw new Error(body?.error?.message || `Claude API error (HTTP ${res.status})`)
  }
  const json = await res.json()
  return (json.content || []).map((c) => c.text || '').join('').trim()
}
