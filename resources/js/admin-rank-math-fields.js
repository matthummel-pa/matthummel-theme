/**
 * Rank Math Content Analysis API — include Page content (theme) fields.
 *
 * @see https://rankmath.com/kb/content-analysis-api/
 */
(function ($) {
  var refreshTimer = null

  function fieldSelector() {
    return '#mh_page_content input[type="text"], #mh_page_content input[type="url"], #mh_page_content textarea'
  }

  function collectFields(content) {
    $(fieldSelector()).each(function () {
      var val = $(this).val()
      if (val) {
        content += '\n' + val
      }
    })
    return content
  }

  function refresh() {
    if (typeof rankMathEditor !== 'undefined' && rankMathEditor.refresh) {
      rankMathEditor.refresh('content')
    }
  }

  function scheduleRefresh() {
    if (refreshTimer) {
      clearTimeout(refreshTimer)
    }
    refreshTimer = setTimeout(refresh, 500)
  }

  function boot() {
    if (!window.wp || !wp.hooks || !wp.hooks.addFilter) {
      return
    }

    wp.hooks.addFilter('rank_math_content', 'matthummel', collectFields, 11)

    $(document).on('keyup change', fieldSelector(), scheduleRefresh)

    // Classic pages have no editor body — seed analysis once fields are in the DOM.
    setTimeout(refresh, 800)
  }

  $(boot)
})(jQuery)
