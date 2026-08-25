<form role="search" method="get" class="search-form" action="{{ home_url('/') }}">
  <label>
    <span class="visually-hidden">
      {{ _x('Search for:', 'label', 'sage') }}
    </span>

    <input
      type="search"
      class="js-mh-search"
      placeholder="{{ esc_attr($placeholder ?? _x('Search …', 'placeholder', 'sage')) }}"
      value="{{ esc_attr(get_search_query()) }}"
      name="s"
      autocomplete="off"
    >
  </label>

  <button type="submit">{{ _x('Search', 'submit button', 'sage') }}</button>
</form>
