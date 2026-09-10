/**
 * Product gallery: thumbnail swap, keyboard tabs, and a native dialog lightbox.
 *
 * Works with the Blade hero gallery and the screenshots grid. Slides come from
 * #pf-gallery-data (JSON) when present, otherwise from thumbnail data attrs.
 */
export function initProductGallery() {
  const root = document.querySelector('[data-product-gallery]')
  const lightbox = document.querySelector('[data-product-lightbox]')
  if (!root && !lightbox) return

  const slides = readSlides(root)
  if (!slides.length) return

  const mainImg = root?.querySelector('[data-gallery-main]')
  const panel = root?.querySelector('#pf-gallery-panel')
  const thumbs = [...(root?.querySelectorAll('.pf-product-gallery__thumb') || [])]
  let index = 0

  function showSlide(next, { announce = true } = {}) {
    if (!slides.length) return
    index = (next + slides.length) % slides.length
    const slide = slides[index]

    if (mainImg) {
      mainImg.src = slide.src
      mainImg.alt = slide.alt
    }

    thumbs.forEach((thumb, i) => {
      const active = i === index
      thumb.classList.toggle('is-active', active)
      thumb.setAttribute('aria-selected', active ? 'true' : 'false')
      thumb.tabIndex = active ? 0 : -1
      if (active && announce) {
        thumb.focus({ preventScroll: true })
      }
    })

    if (panel) {
      const activeThumb = thumbs[index]
      if (activeThumb?.id) {
        panel.setAttribute('aria-labelledby', activeThumb.id)
      }
    }

    syncLightbox()
  }

  function syncLightbox() {
    if (!lightbox) return
    const slide = slides[index]
    const img = lightbox.querySelector('[data-lightbox-image]')
    const caption = lightbox.querySelector('[data-lightbox-caption]')
    if (img) {
      img.src = slide.src
      img.alt = slide.alt
    }
    if (caption) {
      caption.textContent = slide.alt
    }
  }

  function openLightbox(at) {
    if (!lightbox || typeof lightbox.showModal !== 'function') return
    showSlide(at, { announce: false })
    syncLightbox()
    lightbox.showModal()
    lightbox.querySelector('[data-lightbox-close]')?.focus()
  }

  function closeLightbox() {
    if (!lightbox?.open) return
    lightbox.close()
  }

  thumbs.forEach((thumb) => {
    thumb.addEventListener('click', () => {
      const next = Number(thumb.dataset.galleryIndex || 0)
      showSlide(next, { announce: false })
    })
    thumb.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
        event.preventDefault()
        showSlide(index + 1)
      }
      if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
        event.preventDefault()
        showSlide(index - 1)
      }
      if (event.key === 'Home') {
        event.preventDefault()
        showSlide(0)
      }
      if (event.key === 'End') {
        event.preventDefault()
        showSlide(slides.length - 1)
      }
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault()
        showSlide(Number(thumb.dataset.galleryIndex || index), { announce: false })
      }
    })
  })

  document.querySelectorAll('[data-gallery-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const next = Number(button.dataset.galleryIndex || index)
      openLightbox(next)
    })
  })

  if (lightbox) {
    lightbox.querySelector('[data-lightbox-close]')?.addEventListener('click', closeLightbox)
    lightbox.querySelector('[data-lightbox-prev]')?.addEventListener('click', () => showSlide(index - 1, { announce: false }))
    lightbox.querySelector('[data-lightbox-next]')?.addEventListener('click', () => showSlide(index + 1, { announce: false }))
    lightbox.addEventListener('click', (event) => {
      if (event.target === lightbox) closeLightbox()
    })
    lightbox.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowRight') {
        event.preventDefault()
        showSlide(index + 1, { announce: false })
      }
      if (event.key === 'ArrowLeft') {
        event.preventDefault()
        showSlide(index - 1, { announce: false })
      }
    })
  }
}

function readSlides(root) {
  const dataNode = document.getElementById('pf-gallery-data')
  if (dataNode) {
    try {
      const parsed = JSON.parse(dataNode.textContent || '[]')
      if (Array.isArray(parsed) && parsed.length) {
        return parsed
          .map((item) => ({
            src: String(item.src || ''),
            alt: String(item.alt || ''),
          }))
          .filter((item) => item.src !== '')
      }
    } catch {
      // Fall through to DOM thumbs.
    }
  }

  return [...(root?.querySelectorAll('.pf-product-gallery__thumb') || [])]
    .map((thumb) => ({
      src: String(thumb.dataset.gallerySrc || ''),
      alt: String(thumb.dataset.galleryAlt || ''),
    }))
    .filter((item) => item.src !== '')
}
