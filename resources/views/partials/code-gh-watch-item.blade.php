<li>
  <a class="code-gh-watch-repo" href="{{ esc_url($w['url']) }}" rel="noopener" target="_blank">
    <span class="code-gh-watch-repo__full">{{ $w['full'] }}</span>
    @if (($w['lang'] ?? '') !== '')
      <span class="code-gh-watch-repo__lang">{{ $w['lang'] }}</span>
    @endif
    @if ((int) ($w['stars'] ?? 0) > 0)
      <span class="code-gh-watch-repo__stars">{!! \App\mh_svg_icon('star', 11) !!} {{ number_format_i18n((int) $w['stars']) }}</span>
    @endif
    <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
  </a>
</li>
