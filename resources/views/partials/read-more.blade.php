@php
  $url = $url ?? get_permalink();
  $name = $name ?? wp_strip_all_tags(get_the_title());
  $label = $label ?? __('Read more', 'sage');
  $external = ! empty($external);
@endphp
<a
  class="post-card-more"
  href="{{ esc_url($url) }}"
  @if ($external)
    rel="noopener"
    target="_blank"
  @endif
>
  {{ $label }}<span class="visually-hidden">{{ sprintf(__(': %s', 'sage'), $name) }}@if ($external) {{ __(' (opens in a new window)', 'sage') }}@endif</span>
  <span aria-hidden="true">&rarr;</span>
</a>
