@if (is_singular('projects'))
  @include('partials.cta')
@endif

@php
  $mhFoot = \App\mh_footer();
  $mhFootSoc = \App\mh_social_links();
  $cols = max(1, min(4, (int) get_theme_mod('mh_footer_cols', 3)));
  $hasFooterWidgets = false;
  for ($i = 1; $i <= $cols; $i++) {
    if (is_active_sidebar("footer-{$i}")) { $hasFooterWidgets = true; break; }
  }
@endphp
<footer class="content-info">
  @if ($hasFooterWidgets)
    <div class="footer-widgets footer-widgets--cols-{{ $cols }}">
      @for ($i = 1; $i <= $cols; $i++)
        <div class="footer-col">
          @if (is_active_sidebar("footer-{$i}"))
            @php(dynamic_sidebar("footer-{$i}"))
          @endif
        </div>
      @endfor
    </div>
  @endif

  @if (is_active_sidebar('sidebar-footer'))
    @php(dynamic_sidebar('sidebar-footer'))
  @endif

  @if ($mhFoot['show_social'] && $mhFootSoc)
    <div class="footer-socials">
      @foreach ($mhFootSoc as $s)
        <a href="{{ esc_url($s['url']) }}" aria-label="{{ $s['label'] }}" rel="me noopener" target="_blank">{!! \App\mh_social_icon($s['key']) !!}</a>
      @endforeach
    </div>
  @endif

  @php($mhFooterText = apply_filters('matthummel/footer_text', ''))
  @if ($mhFooterText)
    <p class="footer-tagline">{!! wp_kses_post($mhFooterText) !!}</p>
  @endif

  <p>&copy; {{ date('Y') }} {{ $siteName }}. {{ __('Built with Sage.', 'matthummel') }}</p>
</footer>
