@php
  $title = $r['title'] ?? \App\mh_title_label((string) ($r['name'] ?? ''));
  $url = (string) ($r['url'] ?? '');
  $demo = (string) ($r['demo'] ?? '');
  $stack = $r['stack'] ?? [];
  if ($stack === [] && ! empty($r['tags'])) {
      $stack = $r['tags'];
  }
@endphp
<article class="pf-card repo-card">
  <h3>
    @if ($url !== '')
      <a href="{{ esc_url($url) }}" rel="noopener" target="_blank">{{ $title }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
    @else
      {{ $title }}
    @endif
  </h3>
  @if (! empty($r['desc']))
    <p>{{ $r['desc'] }}</p>
  @endif
  @if ($stack)
    <p class="pill-row" aria-label="{{ __('Built with', 'sage') }}">
      @foreach ($stack as $t)
        <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ is_string($t) ? \App\mh_title_label($t) : $t }}</span>
      @endforeach
    </p>
  @endif
  @if (! empty($r['stars']) || ! empty($r['forks']) || ! empty($r['pushed']) || ! empty($r['lang']))
    <p class="pf-meta repo-stats">
      @if (! empty($r['lang']))
        <span>{{ $r['lang'] }}</span>
      @endif
      @if (! empty($r['stars']))
        <span>{{ sprintf(_n('%s star', '%s stars', (int) $r['stars'], 'sage'), number_format_i18n((int) $r['stars'])) }}</span>
      @endif
      @if (! empty($r['forks']))
        <span>{{ sprintf(_n('%s fork', '%s forks', (int) $r['forks'], 'sage'), number_format_i18n((int) $r['forks'])) }}</span>
      @endif
      @if (! empty($r['pushed']))
        <span>{{ sprintf(__('Updated %s', 'sage'), \App\mh_github_ago((string) $r['pushed'])) }}</span>
      @endif
    </p>
  @endif
  <p class="repo-card-links">
    @if ($url !== '')
      <a class="btn" href="{{ esc_url($url) }}" rel="noopener" target="_blank">{{ __('View code', 'sage') }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
    @endif
    @if ($demo !== '')
      <a class="btn btn-outline" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">{{ __('Live demo', 'sage') }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
    @endif
  </p>
</article>
