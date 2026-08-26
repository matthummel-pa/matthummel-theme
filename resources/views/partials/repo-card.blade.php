@php
  $title = $r['title'] ?? \App\mh_title_label((string) ($r['name'] ?? ''));
  $url = (string) ($r['url'] ?? '');
  $demo = (string) ($r['demo'] ?? '');
  $stack = $r['stack'] ?? [];
  if ($stack === [] && ! empty($r['tags'])) {
      $stack = $r['tags'];
  }
  $lang = (string) ($r['lang'] ?? '');
  $langColor = $lang !== '' ? \App\mh_github_lang_color($lang) : '';
  $index = isset($index) ? (int) $index : 0;
  $variant = $variant ?? (! empty($featured) ? 'featured' : 'default');
  $category = \App\mh_code_repo_category($r);
  $slug = \App\mh_code_repo_slug($r);
  $pushed = (string) ($r['pushed'] ?? '');
@endphp
<article class="repo-card repo-card--{{ $variant }}">
  @if ($variant === 'featured' && $index > 0)
    <span class="repo-card__index" aria-hidden="true">{{ sprintf('%02d', $index) }}</span>
  @endif
  @if ($variant === 'live' && $pushed !== '')
    <p class="repo-card__fresh">
      <time datetime="{{ esc_attr($pushed) }}">{{ sprintf(__('Pushed %s', 'sage'), \App\mh_github_ago($pushed)) }}</time>
    </p>
  @endif
  <div class="repo-card__head">
    <span class="repo-card__mark" aria-hidden="true">{!! \App\mh_svg_icon('github', 16) !!}</span>
    <div class="repo-card__title-wrap">
      <span class="repo-card__kind">{{ $category }}</span>
      <h3>
        @if ($url !== '')
          <a href="{{ esc_url($url) }}" rel="noopener" target="_blank">{{ $title }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
        @else
          {{ $title }}
        @endif
      </h3>
      @if ($slug !== '')
        <span class="repo-card__slug">{{ $slug }}</span>
      @endif
    </div>
  </div>
  @if (! empty($r['desc']))
    <p class="repo-card__desc">{{ $r['desc'] }}</p>
  @endif
  @if ($stack)
    <p class="pill-row repo-card__stack" aria-label="{{ __('Built with', 'sage') }}">
      @foreach (array_slice($stack, 0, $variant === 'live' ? 4 : 6) as $t)
        <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ is_string($t) ? \App\mh_title_label($t) : $t }}</span>
      @endforeach
    </p>
  @endif
  @if (! empty($r['stars']) || ! empty($r['forks']) || $lang !== '' || ($variant === 'featured' && $pushed !== ''))
    <p class="pf-meta repo-stats repo-card__stats">
      @if ($lang !== '')
        <span class="repo-lang">
          <span class="repo-lang__dot" style="--lang-color: {{ esc_attr($langColor) }}" aria-hidden="true"></span>
          {{ \App\mh_title_label($lang) }}
        </span>
      @endif
      @if (! empty($r['stars']))
        <span>{{ sprintf(_n('%s star', '%s stars', (int) $r['stars'], 'sage'), number_format_i18n((int) $r['stars'])) }}</span>
      @endif
      @if (! empty($r['forks']))
        <span>{{ sprintf(_n('%s fork', '%s forks', (int) $r['forks'], 'sage'), number_format_i18n((int) $r['forks'])) }}</span>
      @endif
      @if ($variant === 'featured' && $pushed !== '')
        <span>{{ sprintf(__('Updated %s', 'sage'), \App\mh_github_ago($pushed)) }}</span>
      @endif
    </p>
  @endif
  <div class="repo-card__foot">
    @if ($url !== '')
      <a class="repo-card__hit" href="{{ esc_url($url) }}" rel="noopener" target="_blank">
        <span class="repo-card__open">{{ __('Open repo', 'sage') }} →</span>
        <span class="visually-hidden"> {{ $title }} {{ __('(opens in a new window)', 'sage') }}</span>
      </a>
    @endif
    @if ($demo !== '')
      <a class="btn btn-outline repo-card__demo" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
        {{ __('Live demo', 'sage') }}
        <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
      </a>
    @endif
  </div>
</article>
