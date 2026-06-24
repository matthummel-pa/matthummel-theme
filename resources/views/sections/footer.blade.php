@if (is_singular('projects'))
  @include('partials.cta')
@endif

<footer class="content-info">
  @if (is_active_sidebar('sidebar-footer'))
    @php(dynamic_sidebar('sidebar-footer'))
  @endif

  @php($mhFooter = apply_filters('matthummel/footer_text', ''))
  @if ($mhFooter)
    <p class="footer-tagline">{!! wp_kses_post($mhFooter) !!}</p>
  @endif

  <p>&copy; {{ date('Y') }} {{ $siteName }}. {{ __('Built with Sage.', 'matthummel') }}</p>
</footer>
