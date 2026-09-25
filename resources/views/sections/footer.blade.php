@php
  $gh          = \App\Github::fetchUser(\App\mh_github_login());
  $footerName  = $gh['name'] ?: 'Matt Hummel';
  $writing     = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $footerHomeId = (int) get_option('page_on_front');
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
      <p class="footer-blurb">{{ $footerBlurb }}</p>
      <div class="footer-brand-meta">
        @if (\App\mh_is_hireable($gh))
          <a
            class="header-avail footer-avail"
            href="{{ home_url('/now/') }}"
            aria-label="{{ sprintf(__('%s — see what I\'m doing now', 'sage'), \App\mh_availability_label($gh, __('Open for work', 'sage'))) }}"
          >
            @include('partials.avail-mark', ['gh' => $gh])
            <span class="header-avail__label">{{ \App\mh_availability_label($gh, __('Open for work', 'sage')) }}</span>
          </a>
        @endif
        <nav class="footer-brand-social" aria-label="{{ __('Elsewhere', 'sage') }}">
          @include('partials.social', ['compact' => true])
        </nav>
      </div>
    </div>

    {{-- Work --}}
    <nav class="footer-nav-col" aria-label="Work">
      <p class="footer-nav-label">{{ __('Work', 'sage') }}</p>
      <ul class="footer-nav">
        <li><a href="{{ esc_url(\App\mh_work_listing_url()) }}">{{ __('Projects', 'sage') }}</a></li>
        <li><a href="{{ home_url('/hire/') }}">{{ __('Hire me', 'sage') }}</a></li>
        <li><a href="{{ home_url('/code/') }}">{{ __('Code', 'sage') }}</a></li>
      </ul>
    </nav>

    {{-- Site --}}
    <nav class="footer-nav-col" aria-label="Site">
      <p class="footer-nav-label">Site</p>
      <ul class="footer-nav">
        <li><a href="{{ home_url('/about/') }}">{{ __('About', 'sage') }}</a></li>
        <li><a href="{{ $writing }}">{{ __('Journal', 'sage') }}</a></li>
        <li><a href="{{ home_url('/now/') }}">{{ __('Now', 'sage') }}</a></li>
        <li><a href="{{ home_url('/contact/') }}">{{ __('Contact', 'sage') }}</a></li>
      </ul>
    </nav>

    {{-- Get updates --}}
    @php
      $signupStatus = isset($_GET['signup']) ? sanitize_key(wp_unslash($_GET['signup'])) : '';
      $privacyUrl = home_url('/privacy-policy/');
    @endphp
    <div class="footer-nav-col footer-follow" id="footer-signup">
      <p class="footer-nav-label">{{ \App\field('footer_signup_label', __('Get updates', 'sage'), $footerHomeId > 0 ? $footerHomeId : null) }}</p>
      <p class="footer-follow__lede">{{ \App\field(
        'footer_signup_lede',
        __('Occasional notes on WordPress work and new posts. No daily blast.', 'sage'),
        $footerHomeId > 0 ? $footerHomeId : null
      ) }}</p>
      @if (function_exists('mhn_render_footer_form'))
        {!! mhn_render_footer_form() !!}
        <p class="footer-follow__note">{{ __('I keep the address on this site. I do not send it to a newsletter service.', 'sage') }} <a href="{{ esc_url($privacyUrl) }}">{{ __('Privacy', 'sage') }}</a></p>
      @else
      @if ($signupStatus === 'ok')
        <p class="footer-follow__status" role="status">{{ __('You are on the list. Thanks.', 'sage') }}</p>
      @elseif ($signupStatus === 'confirm')
        <p class="footer-follow__status" role="status">{{ __('Check your email to confirm.', 'sage') }}</p>
      @elseif ($signupStatus === 'dup')
        <p class="footer-follow__status" role="status">{{ __('That address is already signed up.', 'sage') }}</p>
      @elseif ($signupStatus === 'wait')
        <p class="footer-follow__status footer-follow__status--error" role="alert">{{ __('Please wait a while, then try again.', 'sage') }}</p>
      @elseif ($signupStatus === 'error')
        <p class="footer-follow__status footer-follow__status--error" role="alert">{{ __('Use a valid email, then try again.', 'sage') }}</p>
      @endif
      <form class="footer-follow__form" method="post" action="{{ esc_url(home_url('/')) }}">
        <input type="hidden" name="action" value="mh_newsletter">
        {!! wp_nonce_field('mh_newsletter', 'mh_newsletter_nonce', true, false) !!}
        <p class="visually-hidden" aria-hidden="true">
          <label for="mh-nl-hp">{{ __('Leave blank', 'sage') }}</label>
          <input id="mh-nl-hp" type="text" name="mh_nl_hp" value="" tabindex="-1" autocomplete="off">
        </p>
        <div class="footer-follow__row">
          <label class="visually-hidden" for="mh-nl-email">{{ __('Email', 'sage') }}</label>
          <input
            id="mh-nl-email"
            class="footer-follow__email"
            type="email"
            name="mh_nl_email"
            required
            autocomplete="email"
            placeholder="{{ esc_attr__('you@example.com', 'sage') }}"
          >
          <button type="submit" class="btn">{{ \App\field('footer_signup_button', __('Sign up', 'sage'), $footerHomeId > 0 ? $footerHomeId : null) }}</button>
        </div>
        <p class="footer-follow__note">{{ __('I keep the address on this site. I do not send it to a newsletter service.', 'sage') }} <a href="{{ esc_url($privacyUrl) }}">{{ __('Privacy', 'sage') }}</a></p>
      </form>
      @endif
    </div>

  </div>

  <div class="footer-bottom container wide">
    <p class="footer-copy">&copy; {{ date('Y') }} {{ $footerName }}.</p>
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
        <a href="{{ home_url('/privacy-policy/') }}">Privacy</a>
        <span aria-hidden="true">·</span>
        <a href="{{ home_url('/terms-of-use/') }}">Terms</a>
        <span aria-hidden="true">·</span>
        <a href="{{ home_url('/affiliate-disclosure/') }}">{{ __('Affiliate disclosure', 'sage') }}</a>
        <span aria-hidden="true">·</span>
        <a href="{{ home_url('/accessibility/') }}">Accessibility</a>
      </nav>
    @endif
    <p class="footer-stack">Built with <a href="https://roots.io/sage/" rel="noopener" target="_blank">Sage</a>, WordPress, and PHP. Planned with <a href="https://cursor.com" rel="noopener" target="_blank">Cursor AI</a>.</p>
  </div>
</footer>
