{{-- matthummel/last-updated: "Last updated" plus the date this page was last saved, or the text typed in the sidebar. --}}
@php
  $text = trim((string) ($attributes['text'] ?? ''));
  $post = get_post();
  $ts = $post instanceof \WP_Post ? strtotime($post->post_modified_gmt.' UTC') : false;
@endphp
@if ($text !== '' || $ts !== false)
  <p class="{{ trim('about-now__updated mh-last-updated '.($attributes['className'] ?? '')) }}">
    {{ __('Last updated', 'sage') }}
    @if ($text !== '')
      {{ $text }}
    @else
      <time datetime="{{ esc_attr(gmdate('c', $ts)) }}">{{ date_i18n(get_option('date_format') ?: 'F j, Y', $ts) }}</time>
    @endif
  </p>
@endif
