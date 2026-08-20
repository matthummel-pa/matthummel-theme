@php
  $gh = \App\Github::fetchUser(\App\mh_github_login());
  $footerName = $gh['name'] ?: 'Matt Hummel';
  $footerBlurb = __('Notes, code, and Gettysburg work. Developers, shops, and agencies are welcome.', 'sage');
  if (! empty($gh['location'])) {
      $footerBlurb = sprintf(
          /* translators: %s: GitHub location */
          __('Notes, code, and work from %s. Developers, shops, and agencies are welcome.', 'sage'),
          $gh['location']
      );
  }
@endphp
<footer class="site-footer">
  <div class="container wide footer-grid">
    <div>
      <p class="brand-name footer-brand">{{ $footerName }}</p>
      <p>{{ \App\field('footer_blurb', $footerBlurb ?: __('Notes, code, and Gettysburg work. Developers, shops, and agencies are welcome.', 'sage'), \App\mh_front_id()) }}</p>
    </div>
    @if (has_nav_menu('footer_navigation'))
      <nav aria-label="{{ __('Footer', 'sage') }}">
        {!! wp_nav_menu([
          'theme_location' => 'footer_navigation',
          'menu_class'     => 'footer-nav',
          'echo'           => false,
          'container'      => false,
          'depth'          => 1,
        ]) !!}
      </nav>
    @elseif (has_nav_menu('primary_navigation'))
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
    @include('partials.social')
  </div>
  <p class="footer-copy container wide">&copy; {{ date('Y') }} {{ $footerName }}</p>
</footer>
