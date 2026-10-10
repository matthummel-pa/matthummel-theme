import assert from 'node:assert/strict'
import fs from 'node:fs'
import vm from 'node:vm'

const source = fs.readFileSync(new URL('../assets/wizard.js', import.meta.url), 'utf8')

function listenMap() {
  const map = new Map()
  return {
    map,
    addEventListener(type, fn) {
      const list = map.get(type) || []
      list.push(fn)
      map.set(type, list)
    },
  }
}

function field(name, value) {
  return {
    name,
    value,
    type: 'hidden',
    tagName: 'INPUT',
    getAttribute(attr) {
      return attr === 'name' ? name : null
    },
    dispatchEvent() {},
    addEventListener() {},
  }
}

function createHarness() {
  const listeners = listenMap()
  const textarea = field('mhn_body', '<p>Hi {first_name|there},</p>')
  const issue = field('mhn_issue', '12')
  const fields = [textarea, issue]
  const formListeners = listenMap()
  const form = {
    id: 'mhn-wizard',
    elements: fields,
    ...formListeners,
    querySelector(selector) {
      const name = /name="([^"]+)"/.exec(selector)
      if (!name) return null
      return fields.find((item) => item.name === name[1]) || null
    },
    querySelectorAll() {
      return []
    },
    append(node) {
      fields.push(node)
    },
    submitCalls: 0,
    submit() {
      this.submitCalls += 1
    },
  }
  const documentListeners = listenMap()
  const document = {
    ...documentListeners,
    getElementById(id) {
      if (id === 'mhn-wizard') return form
      return null
    },
    querySelector() {
      return null
    },
    querySelectorAll() {
      return []
    },
    createElement() {
      return field('', '')
    },
  }

  const requests = []
  let release
  const gate = new Promise((resolve) => {
    release = resolve
  })
  const fetch = (url, options) => {
    const body = options.body
    requests.push(body.get('mhn_body'))
    if (requests.length === 1) {
      return gate.then(() => ({
        json: () => Promise.resolve({ success: true, data: { id: 12 } }),
      }))
    }
    return Promise.resolve({
      json: () => Promise.resolve({ success: true, data: { id: 12 } }),
    })
  }

  const editor = {
    initialized: true,
    hidden: false,
    content: '<p>The letter I wrote.</p>',
    isHidden() {
      return this.hidden
    },
    save() {
      textarea.value = this.content
    },
  }

  const context = {
    window: {},
    document,
    FormData: class {
      constructor(target) {
        this.values = new Map(target.elements.map((item) => [item.name, item.value]))
      }
      set(key, value) {
        this.values.set(key, value)
      }
      get(key) {
        return this.values.get(key)
      }
    },
    fetch,
    URL,
    console,
    setTimeout,
    clearTimeout,
  }
  context.window = context
  context.window.tinymce = { editors: [editor] }
  context.window.mhnWizard = {
    ajaxUrl: 'https://example.test/admin-ajax.php',
    saved: 'Draft saved.',
    postBlocks: 'mhn_post_blocks',
    postsFailed: 'failed',
    postsEmpty: 'empty',
  }
  context.window.location = new URL('https://example.test/wp-admin/admin.php?page=mhn-wizard&step=2')
  context.window.history = { replaceState() {} }
  vm.createContext(context)
  vm.runInContext(source, context)
  const ready = documentListeners.map.get('DOMContentLoaded')
  ready.forEach((fn) => fn())

  return { form, formListeners, textarea, editor, requests, release }
}

const visual = createHarness()
const submitListeners = visual.formListeners.map.get('submit')
assert.equal(submitListeners.length, 1)
const next = { name: 'mhn_action', value: 'next' }
let prevented = false
submitListeners[0]({
  preventDefault() {
    prevented = true
  },
  submitter: next,
})
assert.equal(prevented, true)
assert.equal(visual.requests.length, 1)
assert.equal(visual.requests[0], '<p>The letter I wrote.</p>')
assert.equal(visual.textarea.value, '<p>The letter I wrote.</p>')
assert.equal(visual.form.submitCalls, 0)

visual.editor.content = '<p>The letter I wrote.</p><p>One more line.</p>'
const inputListeners = visual.formListeners.map.get('input')
inputListeners[0]({})
await new Promise((resolve) => setTimeout(resolve, 850))
assert.equal(visual.requests.length, 1)

visual.release()
await new Promise((resolve) => setTimeout(resolve, 0))
await new Promise((resolve) => setTimeout(resolve, 0))
assert.equal(visual.requests.length, 2)
assert.equal(visual.requests[1], '<p>The letter I wrote.</p><p>One more line.</p>')
assert.equal(visual.form.submitCalls, 1)
assert.equal(visual.form.elements.some((item) => item.name === 'mhn_action' && item.value === 'next'), true)

const textMode = createHarness()
textMode.editor.hidden = true
textMode.textarea.value = '<p>Typed in the Text tab.</p>'
textMode.editor.content = '<p>Stale visual letter.</p>'
let textPrevented = false
textMode.formListeners.map.get('submit')[0]({
  preventDefault() {
    textPrevented = true
  },
  submitter: { name: 'mhn_action', value: 'send' },
})
assert.equal(textPrevented, true)
assert.equal(textMode.requests[0], '<p>Typed in the Text tab.</p>')
assert.equal(textMode.textarea.value, '<p>Typed in the Text tab.</p>')
textMode.release()
await new Promise((resolve) => setTimeout(resolve, 0))
assert.equal(textMode.form.submitCalls, 1)

console.log('wizard autosave checks passed')
