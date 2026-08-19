@php
  $mhSoc = \App\mh_social_links();
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

  @if ($mhSoc)
    <ul class="elsewhere">
      @foreach ($mhSoc as $s)
        <li><a href="{{ esc_url($s['url']) }}" rel="me noopener" target="_blank">{{ $s['label'] }}</a></li>
      @endforeach
    </ul>
  @endif

  <p>&copy; {{ date('Y') }} Matt Hummel · Gettysburg, PA · Sage 11.</p>
</footer>
