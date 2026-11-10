@php
  $url = $url ?? get_permalink();
  $name = $name ?? wp_strip_all_tags(\App\mh_post_title());
  $label = $label ?? __('Read more', 'sage');
  $external = ! empty($external);
@endphp
{{-- The shared card button (gradient pill, arrow chip). The post title sits in the link text (hidden visually), so the anchor reads "Read more: Title" to crawlers and screen readers instead of a bare "read more". --}}
<a
  class="shop-link shop-link--forward post-card-more"
  href="{{ esc_url($url) }}"
  @if ($external)
    rel="noopener"
    target="_blank"
  @endif
>
  {{ $label }}<span class="visually-hidden">{{ sprintf(__(': %s', 'sage'), $name) }}@if ($external) {{ __(' (opens in a new window)', 'sage') }}@endif</span>
  <span class="shop-link__icon" aria-hidden="true">&rarr;</span>
</a>
