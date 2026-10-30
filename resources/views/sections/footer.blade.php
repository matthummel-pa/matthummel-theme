@php
  $gh          = \App\Github::fetchUser(\App\mh_github_login());
  $footerName  = ($gh['name'] ?? '') ?: 'Matt Hummel';
  $writing     = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $footerHomeId = (int) get_option('page_on_front');
  $privacyUrl  = \App\mh_privacy_policy_url();
  $footerBlurb = \App\field(
    'footer_blurb',
    \App\mh_footer_blurb_default(),
    $footerHomeId > 0 ? $footerHomeId : null
  );
@endphp
<footer class="site-footer">
  <div class="container wide footer-inner">

    {{-- Brand + blurb --}}
    <div class="footer-brand-col">
      <a class="footer-brand-link" href="{{ home_url('/') }}" rel="home">
        <span class="brand-name footer-brand">{{ $footerName }}</span>
      </a>
      @if (is_active_sidebar('footer-about'))
        <div class="footer-blurb footer-widgets">
          @php dynamic_sidebar('footer-about'); @endphp
        </div>
      @else
        <p class="footer-blurb">{{ $footerBlurb }}</p>
      @endif
      <div class="footer-brand-meta">
        <nav class="footer-brand-social" aria-label="{{ __('Elsewhere', 'sage') }}">
          @include('partials.social', ['compact' => true])
        </nav>
      </div>
    </div>

    {{-- Site: one link column (the Work column was folded in here in 3.6.62) --}}
    <nav class="footer-nav-col" aria-label="{{ esc_attr__('Site', 'sage') }}">
      @if (is_active_sidebar('footer-links'))
        @php dynamic_sidebar('footer-links'); @endphp
      @else
      <p class="footer-nav-label">{{ __('Site', 'sage') }}</p>
      <ul class="footer-nav footer-nav--split">
        <li><a href="{{ esc_url(home_url('/about/')) }}">{{ __('About', 'sage') }}</a></li>
        <li><a href="{{ esc_url(\App\mh_work_listing_url()) }}">{{ __('Projects', 'sage') }}</a></li>
        <li><a href="{{ esc_url($writing) }}">{{ __('Journal', 'sage') }}</a></li>
        <li><a href="{{ esc_url(home_url('/resources/')) }}">{{ __('Resources', 'sage') }}</a></li>
        <li><a href="{{ esc_url(home_url('/support/')) }}">{{ __('Support', 'sage') }}</a></li>
        <li><a href="{{ esc_url(home_url('/account/')) }}">{{ __('Your account', 'sage') }}</a></li>
        {{-- These two are written in wp-admin; each link shows once its page is published. --}}
        @if (\App\mh_is_published_path('/agency-white-label-wordpress/'))
          <li><a href="{{ esc_url(home_url('/agency-white-label-wordpress/')) }}">{{ __('For agencies', 'sage') }}</a></li>
        @endif
        @if (\App\mh_is_published_path('/wordpress-handoff-checklist/'))
          <li><a href="{{ esc_url(home_url('/wordpress-handoff-checklist/')) }}">{{ __('Handoff checklist', 'sage') }}</a></li>
        @endif
        <li><a href="{{ esc_url(home_url('/changelog/')) }}">{{ __('Changelog', 'sage') }}</a></li>
        <li><a href="{{ esc_url(home_url('/contact/')) }}">{{ __('Contact', 'sage') }}</a></li>
      </ul>
      @endif
    </nav>

    {{-- Updates: the Hostinger Reach signup form lives in the Footer updates widget area. --}}
    @if (is_active_sidebar('footer-updates'))
      <div class="footer-nav-col footer-follow" id="footer-signup">
        @php dynamic_sidebar('footer-updates'); @endphp
      </div>
    @endif
  </div>

  <div class="footer-bottom container wide">
    <p class="footer-copy">&copy; {{ date('Y') }} {{ $footerName }}.</p>
    @if (is_active_sidebar('footer-legal'))
      <nav class="footer-legal footer-widgets" aria-label="{{ __('Legal', 'sage') }}">
        @php dynamic_sidebar('footer-legal'); @endphp
      </nav>
    @else
@php
      $bottomMenuItems = has_nav_menu('footer_bottom_navigation')
        ? wp_get_nav_menu_items(get_nav_menu_locations()['footer_bottom_navigation'] ?? 0) ?: []
        : [];
    @endphp
    @if ($bottomMenuItems)
      <nav class="footer-legal-links" aria-label="{{ __('Legal', 'sage') }}">
        @foreach ($bottomMenuItems as $i => $item)
          @if ($i > 0)<span aria-hidden="true">·</span>@endif
          <a href="{{ esc_url($item->url) }}"{{ $item->target ? ' target="'.esc_attr($item->target).'" rel="noopener"' : '' }}>{{ esc_html($item->title) }}</a>
        @endforeach
      </nav>
    @else
      <nav class="footer-legal-links" aria-label="{{ __('Legal', 'sage') }}">
        <a href="{{ esc_url(\App\mh_privacy_policy_url()) }}">{{ __('Privacy', 'sage') }}</a>
        <span aria-hidden="true">·</span>
        <a href="{{ esc_url(\App\mh_terms_url()) }}">{{ __('Terms', 'sage') }}</a>
        <span aria-hidden="true">·</span>
        <a href="{{ home_url('/affiliate-disclosure/') }}">{{ __('Affiliate disclosure', 'sage') }}</a>
        <span aria-hidden="true">·</span>
        <a href="{{ home_url('/accessibility/') }}">{{ __('Accessibility', 'sage') }}</a>
      </nav>
    @endif
    <p class="footer-stack">Built with <a href="https://roots.io/sage/" rel="noopener" target="_blank">Sage<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>, WordPress, and PHP. Planned with <a href="https://claude.com/product/claude-code" rel="noopener" target="_blank">Claude Code<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>.</p>
    @endif
  </div>
</footer>
