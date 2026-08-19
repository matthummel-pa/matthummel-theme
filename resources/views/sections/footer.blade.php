@php
  $mhFootSoc = \App\mh_social_links();
@endphp
<footer class="content-info">
  @if (has_nav_menu('primary_navigation'))
    <nav aria-label="{{ __('Footer', 'sage') }}">
      {!! wp_nav_menu([
        'theme_location' => 'primary_navigation',
        'menu_class'     => 'footer-nav',
        'echo'           => false,
        'container'      => false,
        'depth'          => 1,
      ]) !!}
    </nav>
  @endif

  @if ($mhFootSoc)
    <div class="footer-socials">
      @foreach ($mhFootSoc as $s)
        <a href="{{ esc_url($s['url']) }}" aria-label="{{ $s['label'] }}" rel="me noopener" target="_blank">
          {!! \App\mh_social_icon($s['key']) !!}
        </a>
      @endforeach
    </div>
  @endif

  <p>Also on <a href="https://dev.to/matthummel" rel="me">DEV.to</a>,
    <a href="https://bsky.app/profile/matthummel.bsky.social" rel="me">Bluesky</a>,
    <a href="https://www.reddit.com/user/matt-hummel" rel="me">Reddit</a>,
    and <a href="https://github.com/matthummel-pa" rel="me">GitHub</a>.</p>
  <p>&copy; {{ date('Y') }} Matt Hummel · Gettysburg, PA · Built with Sage 11.</p>
</footer>
