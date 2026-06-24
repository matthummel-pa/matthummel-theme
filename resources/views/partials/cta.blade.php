@php
  $cta_heading = apply_filters('matthummel/cta_heading', __('Have a project in mind?', 'matthummel'));
  $cta_text    = apply_filters('matthummel/cta_text', __('Whether it’s a new website, a redesign, or a custom WordPress build, I’d love to help bring it to life.', 'matthummel'));
  $cta_url     = apply_filters('matthummel/cta_url', home_url('/contact/'));
  $cta_label   = apply_filters('matthummel/cta_label', __('Start a conversation', 'matthummel'));
@endphp
<aside class="mh-project-cta">
  <div class="cta-card">
    <h2>{{ $cta_heading }}</h2>
    <p>{{ $cta_text }}</p>
    <a class="btn" href="{{ esc_url($cta_url) }}">{{ $cta_label }}</a>
  </div>
</aside>
