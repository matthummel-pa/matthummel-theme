@if (is_singular('projects'))
  @include('partials.cta')
@endif

@php
  $mhFoot = \App\mh_footer();
  $mhFootSoc = \App\mh_social_links();
@endphp
<footer class="content-info">
  @if (is_active_sidebar('sidebar-footer'))
    @php(dynamic_sidebar('sidebar-footer'))
  @endif

  @if ($mhFoot['show_social'] && $mhFootSoc)
    <div class="footer-socials">
      @foreach ($mhFootSoc as $s)
        <a href="{{ esc_url($s['url']) }}" aria-label="{{ $s['label'] }}" rel="me noopener" target="_blank"><i class="{{ $s['icon'] }}" aria-hidden="true"></i></a>
      @endforeach
    </div>
  @endif

  @php($mhFooterText = apply_filters('matthummel/footer_text', ''))
  @if ($mhFooterText)
    <p class="footer-tagline">{!! wp_kses_post($mhFooterText) !!}</p>
  @endif

  <p>&copy; {{ date('Y') }} {{ $siteName }}. {{ __('Built with Sage.', 'matthummel') }}</p>
</footer>
