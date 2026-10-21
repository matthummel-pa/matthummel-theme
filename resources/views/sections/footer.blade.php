@php
  $gh          = \App\Github::fetchUser(\App\mh_github_login());
  $footerName  = ($gh['name'] ?? '') ?: 'Matt Hummel';
  $writing     = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $footerHomeId = (int) get_option('page_on_front');
  $privacyUrl  = \App\mh_privacy_policy_url();
  $footerBlurb = \App\field(
    'footer_blurb',
    __('Full-stack and WordPress developer. Concept work, public GitHub, and tools I recommend — with clear affiliate disclosure when a link is compensated.', 'sage'),
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
        <a class="header-avail footer-avail header-updates__link" href="{{ esc_url(home_url('/get-updates/')) }}">
          <span class="header-updates__icon" aria-hidden="true">{!! \App\mh_svg_icon('mail', 16) !!}</span>
          <span class="header-avail__label">{{ __('Get updates', 'sage') }}</span>
        </a>
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

    {{-- Get updates: the Hostinger Reach form block in the Footer: Get updates widget area. --}}
    <div class="footer-nav-col footer-follow" id="footer-signup">
      @if (is_active_sidebar('footer-updates'))
        @php dynamic_sidebar('footer-updates'); @endphp
      @else
      <p class="footer-nav-label">{{ \App\field('footer_signup_label', __('Get updates', 'sage'), $footerHomeId > 0 ? $footerHomeId : null) }}</p>
      <p class="footer-follow__lede">{{ \App\field(
        'footer_signup_lede',
        __('Occasional notes on WordPress work and new posts. No daily blast.', 'sage'),
        $footerHomeId > 0 ? $footerHomeId : null
      ) }}</p>
      <p class="footer-follow__note"><a href="{{ esc_url(home_url('/get-updates/')) }}">{{ \App\field('footer_signup_button', __('Sign up', 'sage'), $footerHomeId > 0 ? $footerHomeId : null) }}</a> · <a href="{{ esc_url($privacyUrl) }}">{{ __('Privacy', 'sage') }}</a></p>
      @endif
    </div>

  </div>

  <div class="footer-bottom container wide">
    <p class="footer-copy">&copy; {{ date('Y') }} {{ $footerName }}.</p>
    @if (is_active_sidebar('footer-legal'))
      <nav class="footer-legal footer-widgets" aria-label="{{ esc_attr__('Legal', 'sage') }}">
        @php dynamic_sidebar('footer-legal'); @endphp
      </nav>
    @else
@php
      $bottomMenuItems = has_nav_menu('footer_bottom_navigation')
        ? wp_get_nav_menu_items(get_nav_menu_locations()['footer_bottom_navigation'] ?? 0) ?: []
        : [];
    @endphp
    @if ($bottomMenuItems)
      <nav class="footer-legal-links" aria-label="Legal">
        @foreach ($bottomMenuItems as $i => $item)
          @if ($i > 0)<span aria-hidden="true">·</span>@endif
          <a href="{{ esc_url($item->url) }}"{{ $item->target ? ' target="'.esc_attr($item->target).'" rel="noopener"' : '' }}>{{ esc_html($item->title) }}</a>
        @endforeach
      </nav>
    @else
      <nav class="footer-legal-links" aria-label="Legal">
        <a href="{{ esc_url(\App\mh_privacy_policy_url()) }}">{{ __('Privacy', 'sage') }}</a>
        <span aria-hidden="true">·</span>
        <a href="{{ esc_url(\App\mh_terms_url()) }}">{{ __('Terms', 'sage') }}</a>
        <span aria-hidden="true">·</span>
        <a href="{{ home_url('/affiliate-disclosure/') }}">{{ __('Affiliate disclosure', 'sage') }}</a>
        <span aria-hidden="true">·</span>
        <a href="{{ home_url('/accessibility/') }}">Accessibility</a>
      </nav>
    @endif
    <p class="footer-stack">Built with <a href="https://roots.io/sage/" rel="noopener" target="_blank">Sage<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>, WordPress, and PHP. Planned with <a href="https://cursor.com" rel="noopener" target="_blank">Cursor AI<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>.</p>
    @endif
  </div>
</footer>
