/**
 * Project hero video: poster plus a play button. Nothing loads or plays until the
 * visitor presses play, then the native controls take over (keyboard, captions, fullscreen).
 */
export function initProjectVideo() {
  document.querySelectorAll('[data-project-video]').forEach((wrap) => {
    const video = wrap.querySelector('video')
    const button = wrap.querySelector('[data-project-video-play]')
    if (!video || !button) return

    const start = () => {
      video.controls = true
      video.preload = 'auto'
      const played = video.play()
      if (played && typeof played.catch === 'function') {
        played.catch(() => {
          // Blocked or failed: leave the native controls up so the visitor can retry.
          wrap.classList.remove('is-playing')
        })
      }
      wrap.classList.add('is-playing')
      video.focus({ preventScroll: true })
    }

    button.addEventListener('click', start)
    video.addEventListener('ended', () => {
      wrap.classList.remove('is-playing')
      video.controls = false
      video.currentTime = 0
    })
  })
}
