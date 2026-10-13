/**
 * MH SEO meta box: tabs, length meters, search and social previews, image
 * pickers, and (in the block editor) two-way sync with the SEO sidebar.
 *
 * Plain JavaScript on purpose. The box still works with this file missing:
 * every panel shows and every field posts with the form.
 */
(function () {
  'use strict'

  var config = window.mhSeoBox || {}
  var i18n = config.i18n || {}
  var box = document.querySelector('[data-mh-seo-box]')
  if (!box) return

  var editor = null
  if (window.wp && window.wp.data && window.wp.data.select('core/editor')) {
    editor = window.wp.data.select('core/editor')
  }

  /** Fill %1$d-style and %d placeholders in a translated string. */
  function format(text, values) {
    var index = 0
    return String(text || '').replace(/%(?:(\d+)\$)?[ds]/g, function (match, position) {
      var value = position ? values[Number(position) - 1] : values[index++]
      return value === undefined ? '' : String(value)
    })
  }

  function field(name) {
    return box.querySelector('[name="' + name + '"]')
  }

  function text(name) {
    var element = field(name)
    return element ? element.value.trim() : ''
  }

  function count(value) {
    return Array.from(value).length
  }

  function clip(value, max) {
    var characters = Array.from(value)
    if (characters.length <= max) return value
    return characters.slice(0, max).join('').replace(/\s+\S*$/, '') + '…'
  }

  /** The values search engines get once blank fields fall back to defaults. */
  function resolved() {
    var title = text('mh_seo_title') || config.fallbackTitle || ''
    var description = text('mh_seo_description') || config.fallbackDescription || ''
    var ogTitle = text('mh_seo_og_title') || title
    var ogDescription = text('mh_seo_og_description') || description
    var ogImage = imageUrl('mh_seo_og_image') || config.fallbackImage || ''

    return {
      title: title,
      description: description,
      ogTitle: ogTitle,
      ogDescription: ogDescription,
      ogImage: ogImage,
      twitterTitle: text('mh_seo_twitter_title') || ogTitle,
      twitterDescription: text('mh_seo_twitter_description') || ogDescription,
      twitterImage: imageUrl('mh_seo_twitter_image') || ogImage,
    }
  }

  function imageUrl(name) {
    var element = field(name)
    return element && Number(element.value) > 0 ? element.dataset.url || '' : ''
  }

  // ---------------------------------------------------------------- Tabs

  /** Tabs with automatic activation, after the ARIA Authoring Practices pattern. */
  function initTabs() {
    var list = box.querySelector('[role="tablist"]')
    if (!list) return
    var tabs = Array.prototype.slice.call(list.querySelectorAll('[role="tab"]'))

    function select(tab, shouldFocus) {
      tabs.forEach(function (item) {
        var isSelected = item === tab
        item.setAttribute('aria-selected', isSelected ? 'true' : 'false')
        item.tabIndex = isSelected ? 0 : -1
        document.getElementById(item.getAttribute('aria-controls')).hidden = !isSelected
      })
      if (shouldFocus) tab.focus()
      try {
        window.sessionStorage.setItem('mhSeoTab', tab.id)
      } catch (error) {
        // Storage can be blocked. The first tab is a fine default.
      }
    }

    list.addEventListener('click', function (event) {
      var tab = event.target.closest('[role="tab"]')
      if (tab) select(tab, false)
    })
    list.addEventListener('keydown', function (event) {
      var current = tabs.indexOf(document.activeElement)
      if (current < 0) return
      var next = -1
      if (event.key === 'ArrowRight') next = (current + 1) % tabs.length
      if (event.key === 'ArrowLeft') next = (current - 1 + tabs.length) % tabs.length
      if (event.key === 'Home') next = 0
      if (event.key === 'End') next = tabs.length - 1
      if (next < 0) return
      event.preventDefault()
      select(tabs[next], true)
    })

    var stored = null
    try {
      stored = document.getElementById(window.sessionStorage.getItem('mhSeoTab') || '')
    } catch (error) {
      stored = null
    }
    list.hidden = false
    box.classList.add('mh-seo-box--js')
    select(stored && tabs.indexOf(stored) > -1 ? stored : tabs[0], false)
  }

  // -------------------------------------------------------------- Meters

  function meterStatus(length, range) {
    if (length >= range.min && length <= range.max) return 'pass'
    if (length >= range.warnMin && length <= range.warnMax) return 'warn'
    return 'fail'
  }

  function updateMeters(values) {
    box.querySelectorAll('[data-meter]').forEach(function (meter) {
      var kind = meter.dataset.meter
      var range = config[kind]
      var input = document.getElementById(meter.dataset.for)
      if (!range || !input) return
      var isBlank = input.value.trim() === ''
      var length = count(values[kind])
      var status = meterStatus(length, range)
      var scale = range.warnMax * 1.15
      var message = format(i18n.count, [length, range.max])
      if (length === 0) {
        message += ' ' + i18n.missing
      } else if (status === 'pass') {
        message += ' ' + i18n.good
      } else {
        message += ' ' + format(length < range.min ? i18n.short : i18n.long, [range.min, range.max])
      }
      if (isBlank && length > 0) message += ' ' + i18n.usingDefault

      meter.className = 'mh-seo-meter mh-seo-meter--' + status
      meter.querySelector('.mh-seo-meter__fill').style.width = Math.min(100, (length / scale) * 100) + '%'
      var zone = meter.querySelector('.mh-seo-meter__zone')
      zone.style.left = (range.min / scale) * 100 + '%'
      zone.style.width = ((range.max - range.min) / scale) * 100 + '%'
      meter.querySelector('.mh-seo-meter__text').textContent = message
    })

    box.querySelectorAll('[data-counter]').forEach(function (counter) {
      var input = document.getElementById(counter.dataset.for)
      if (!input) return
      var max = Number(counter.dataset.counter)
      var length = count(input.value.trim())
      counter.textContent = format(i18n.count, [length, max])
      counter.classList.toggle('mh-seo-counter--over', length > max)
    })
  }

  // ------------------------------------------------------------ Previews

  function permalink() {
    if (editor && typeof editor.getPermalink === 'function') {
      var live = editor.getPermalink()
      if (live) return live
    }
    return config.permalink || ''
  }

  function breadcrumb(url) {
    var host = config.siteHost || ''
    var parts = []
    try {
      var parsed = new URL(url)
      host = parsed.host
      parts = parsed.pathname.split('/').filter(Boolean)
    } catch (error) {
      parts = []
    }
    return [host].concat(parts).join(' › ')
  }

  function updateSerp(values) {
    var serp = box.querySelector('[data-serp]')
    if (!serp) return
    var isMobile = serp.classList.contains('mh-seo-serp--mobile')
    serp.querySelector('[data-serp-url]').textContent = breadcrumb(permalink())
    serp.querySelector('[data-serp-title]').textContent = clip(values.title, isMobile ? 78 : 60) || i18n.missing
    serp.querySelector('[data-serp-desc]').textContent = clip(values.description, isMobile ? 120 : 160) || i18n.missing
  }

  function initSerpToggle() {
    var serp = box.querySelector('[data-serp]')
    var buttons = box.querySelectorAll('[data-serp-device]')
    if (!serp || !buttons.length) return
    buttons[0].parentNode.hidden = false
    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        buttons.forEach(function (item) {
          item.setAttribute('aria-pressed', item === button ? 'true' : 'false')
        })
        serp.classList.toggle('mh-seo-serp--mobile', button.dataset.serpDevice === 'mobile')
        refresh()
      })
    })
  }

  function fillCard(kind, title, description, image, isSmall) {
    var card = box.querySelector('[data-card="' + kind + '"]')
    if (!card) return
    var picture = card.querySelector('.mh-seo-card__image img')
    card.querySelector('.mh-seo-card__title').textContent = clip(title, 90) || i18n.missing
    card.querySelector('.mh-seo-card__desc').textContent = clip(description, 160)
    card.classList.toggle('mh-seo-card--small', Boolean(isSmall))
    card.querySelector('.mh-seo-card__empty').hidden = image !== ''
    picture.hidden = image === ''
    if (image !== '' && picture.getAttribute('src') !== image) picture.setAttribute('src', image)
  }

  function updateCards(values) {
    var cardSize = text('mh_seo_twitter_card')
    var isSmall = cardSize === 'summary' || (cardSize === '' && values.twitterImage === '')
    fillCard('og', values.ogTitle, values.ogDescription, values.ogImage, false)
    fillCard('twitter', values.twitterTitle, values.twitterDescription, values.twitterImage, isSmall)
  }

  function updateRobots() {
    var target = box.querySelector('[data-robots-preview]')
    if (!target) return
    var flags = {}
    box.querySelectorAll('[data-robots]').forEach(function (input) {
      flags[input.dataset.robots] = input.checked
    })
    var parts = [flags.noindex ? 'noindex' : 'index', flags.nofollow ? 'nofollow' : 'follow']
    ;['noarchive', 'nosnippet', 'noimageindex'].forEach(function (flag) {
      if (flags[flag]) parts.push(flag)
    })
    parts.push('max-image-preview:' + (text('mh_seo_max_image_preview') || 'large'))
    parts.push('max-snippet:' + (text('mh_seo_max_snippet') || '-1'))
    parts.push('max-video-preview:' + (text('mh_seo_max_video_preview') || '-1'))
    target.textContent = parts.join(', ')
  }

  // ------------------------------------------------------- Content checks

  /** The post content as a detached document, or null when it cannot be read. */
  function contentDocument() {
    var html = ''
    if (editor && typeof editor.getEditedPostContent === 'function') {
      html = editor.getEditedPostContent() || ''
    } else {
      var textarea = document.getElementById('content')
      html = textarea ? textarea.value : ''
    }
    if (html === '') return null
    var parsed = new DOMParser().parseFromString(html, 'text/html')
    parsed.querySelectorAll('pre, script, style').forEach(function (node) {
      node.remove()
    })
    return parsed
  }

  function updateContentChecks() {
    var parsed = contentDocument()
    var plain = parsed ? (parsed.body.textContent || '').toLowerCase() : ''

    var chips = box.querySelector('[data-keyword-chips]')
    if (chips) {
      var keywords = text('mh_seo_keywords')
        .split(',')
        .map(function (keyword) {
          return keyword.trim()
        })
        .filter(function (keyword, index, list) {
          var lower = keyword.toLowerCase()
          return (
            keyword !== '' &&
            list.findIndex(function (other) {
              return other.toLowerCase() === lower
            }) === index
          )
        })
      chips.textContent = ''
      keywords.slice(0, config.maxKeywords || 5).forEach(function (keyword) {
        var item = document.createElement('li')
        var name = document.createElement('strong')
        name.textContent = keyword
        item.appendChild(name)
        if (parsed) {
          var found = plain.split(keyword.toLowerCase()).length - 1
          var note = document.createElement('span')
          note.textContent = ' ' + format(i18n.keywordCount, [found])
          item.className = found > 0 ? 'mh-seo-chip mh-seo-chip--found' : 'mh-seo-chip'
          item.appendChild(note)
        } else {
          item.className = 'mh-seo-chip'
        }
        chips.appendChild(item)
      })
      if (keywords.length > (config.maxKeywords || 5)) {
        var limit = document.createElement('li')
        limit.className = 'mh-seo-chip mh-seo-chip--note'
        limit.textContent = format(i18n.keywordLimit, [config.maxKeywords || 5])
        chips.appendChild(limit)
      }
    }

    var faq = box.querySelector('[data-faq-count]')
    var howto = box.querySelector('[data-howto-count]')
    if (!parsed) return
    if (faq) {
      var questions = Array.prototype.filter.call(parsed.querySelectorAll('h2, h3, h4'), function (heading) {
        return (heading.textContent || '').trim().slice(-1) === '?'
      })
      faq.textContent = format(i18n.faqCount, [questions.length])
    }
    if (howto) {
      var list = parsed.querySelector('ol')
      var steps = list
        ? Array.prototype.filter.call(list.children, function (child) {
            return child.tagName === 'LI'
          }).length
        : 0
      howto.textContent = format(i18n.howtoCount, [steps])
    }
  }

  // --------------------------------------------------------- Image fields

  function setImage(wrap, id, url) {
    var input = wrap.querySelector('.mh-seo-image__id')
    var thumb = wrap.querySelector('.mh-seo-image__thumb')
    var hasImage = Number(id) > 0 && url !== ''
    input.value = Number(id) > 0 ? String(id) : ''
    input.dataset.url = url
    thumb.hidden = !hasImage
    if (hasImage) thumb.setAttribute('src', url)
    wrap.querySelector('[data-image-clear]').hidden = !hasImage
    wrap.querySelector('[data-image-pick]').textContent = hasImage ? i18n.changeImage : i18n.chooseImage
  }

  function initImages() {
    if (!window.wp || !window.wp.media) return
    box.querySelectorAll('[data-image-field]').forEach(function (wrap) {
      var input = wrap.querySelector('.mh-seo-image__id')
      var frame = null
      wrap.classList.add('mh-seo-image--js')
      wrap.querySelector('.mh-seo-image__row').hidden = false

      wrap.querySelector('[data-image-pick]').addEventListener('click', function () {
        if (!frame) {
          frame = window.wp.media({
            title: i18n.chooseImage,
            button: { text: i18n.useImage },
            library: { type: 'image' },
            multiple: false,
          })
          frame.on('select', function () {
            var file = frame.state().get('selection').first().toJSON()
            var large = file.sizes && file.sizes.large ? file.sizes.large.url : file.url
            setImage(wrap, file.id, large || '')
            input.dispatchEvent(new Event('change', { bubbles: true }))
          })
        }
        frame.open()
      })
      wrap.querySelector('[data-image-clear]').addEventListener('click', function () {
        setImage(wrap, 0, '')
        input.dispatchEvent(new Event('change', { bubbles: true }))
        wrap.querySelector('[data-image-pick]').focus()
      })
    })
  }

  // ------------------------------------------------- Block editor sync

  function readField(name) {
    var element = field(name)
    var type = config.meta[name].type
    if (element.type === 'checkbox') return element.checked ? '1' : '0'
    if (type === 'integer') return Number(element.value) > 0 ? Number(element.value) : 0
    return element.value
  }

  /**
   * Keep these fields and the SEO sidebar on the same values.
   *
   * Both edit the same post meta. Without this, the box would post its own
   * (older) values after the sidebar's and quietly undo a sidebar edit.
   */
  function initSync() {
    if (!editor || !config.meta) return
    var dispatch = window.wp.data.dispatch('core/editor')
    var lastMeta = null

    function push(event) {
      var name = event.target && event.target.name
      if (!name || !config.meta[name]) return
      var meta = {}
      meta[config.meta[name].key] = readField(name)
      dispatch.editPost({ meta: meta })
    }
    box.addEventListener('input', push)
    box.addEventListener('change', push)

    window.wp.data.subscribe(function () {
      var meta = editor.getEditedPostAttribute('meta')
      if (!meta || meta === lastMeta) return
      lastMeta = meta
      var hasChanged = false
      Object.keys(config.meta).forEach(function (name) {
        var element = field(name)
        var stored = meta[config.meta[name].key]
        if (!element || stored === undefined || stored === null || element === document.activeElement) return
        if (element.type === 'checkbox') {
          // An empty flag was never saved. Leave the box as the server printed it.
          if (stored === '' || element.checked === (String(stored) === '1')) return
          element.checked = String(stored) === '1'
          hasChanged = true
          return
        }
        var next = config.meta[name].type === 'integer' ? (Number(stored) > 0 ? String(stored) : '') : String(stored)
        if (element.value === next) return
        element.value = next
        hasChanged = true
        var wrap = element.closest('[data-image-field]')
        if (wrap) syncImage(wrap, Number(stored))
      })
      if (hasChanged) refresh()
    })
  }

  /** Show the right thumbnail after the sidebar picks a different image. */
  function syncImage(wrap, id) {
    if (id < 1) {
      setImage(wrap, 0, '')
      return
    }
    var media = window.wp.data.select('core').getMedia(id)
    if (media && media.source_url) setImage(wrap, id, media.source_url)
  }

  // --------------------------------------------------------------- Wiring

  var contentTimer = null

  function refresh() {
    var values = resolved()
    updateMeters(values)
    updateSerp(values)
    updateCards(values)
    updateRobots()
    window.clearTimeout(contentTimer)
    contentTimer = window.setTimeout(updateContentChecks, 400)
  }

  function init() {
    initTabs()
    initSerpToggle()
    initImages()
    initSync()
    box.addEventListener('input', refresh)
    box.addEventListener('change', refresh)
    if (editor) {
      var lastContent = null
      window.wp.data.subscribe(function () {
        var content = editor.getEditedPostContent()
        if (content === lastContent) return
        lastContent = content
        window.clearTimeout(contentTimer)
        contentTimer = window.setTimeout(updateContentChecks, 800)
      })
    }
    refresh()
  }

  if (window.wp && window.wp.domReady) {
    window.wp.domReady(init)
  } else if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
})()
