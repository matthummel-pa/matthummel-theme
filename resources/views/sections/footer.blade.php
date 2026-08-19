@php
  $mhSoc = \App\mh_social_links();
@endphp
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <p class="brand-name footer-brand">Matt Hummel</p>
      <p>{{ \App\field('footer_blurb', __('Notes, code, and Gettysburg work. Visitors, new developers, and business owners are welcome.', 'sage'), \App\mh_front_id()) }}</p>
    </div>
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
    @if ($mhSoc)
      <ul class="elsewhere">
        @foreach ($mhSoc as $s)
          <li><a href="{{ esc_url($s['url']) }}" rel="me noopener" target="_blank">{{ $s['label'] }}</a></li>
        @endforeach
      </ul>
    @endif
  </div>
  <p class="footer-copy">&copy; {{ date('Y') }} Matt Hummel · Sage 11</p>
</footer>
