@php
  $url = $url ?? get_permalink();
  $name = $name ?? wp_strip_all_tags(\App\mh_post_title());
  $label = $label ?? __('Read article', 'sage');
  $external = ! empty($external);
@endphp
{{-- A real button, same .btn as the rest of the site. The post title sits in the link text (hidden visually), so the anchor reads "Read article: Title" to crawlers and screen readers instead of a bare "Read more". --}}
<a
  class="btn btn-outline post-card-more"
  href="{{ esc_url($url) }}"
  @if ($external)
    rel="noopener"
    target="_blank"
  @endif
>
  <span>{{ $label }}<span class="visually-hidden">{{ sprintf(__(': %s', 'sage'), $name) }}@if ($external) {{ __(' (opens in a new window)', 'sage') }}@endif</span></span>
  <span aria-hidden="true">&rarr;</span>
</a>
