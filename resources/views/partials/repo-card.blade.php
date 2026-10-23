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
  $commits = (int) ($r['commits'] ?? 0);
  $release = (string) ($r['release'] ?? '');
  $releaseUrl = (string) ($r['release_url'] ?? '');
  $releaseDate = (string) ($r['release_date'] ?? '');
  $license = (string) ($r['license'] ?? '');
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
      <span class="repo-card__kind">
        {{ $category }}
        @if (! empty($r['archived']))
          <span class="repo-card__archived">{{ __('Archived', 'sage') }}</span>
        @endif
      </span>
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
    @php
      $stackShown = array_slice($stack, 0, 4);
      $stackRest = array_slice($stack, 4);
    @endphp
    <p class="pill-row repo-card__stack" aria-label="{{ __('Built with', 'sage') }}">
      @foreach ($stackShown as $t)
        <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ is_string($t) ? \App\mh_title_label($t) : $t }}</span>
      @endforeach
      @if ($variant !== 'live' && $stackRest !== [])
        <span class="pill pill--more" title="{{ esc_attr(implode(', ', array_map(static fn ($t) => is_string($t) ? \App\mh_title_label($t) : (string) $t, $stackRest))) }}">+{{ count($stackRest) }}<span class="visually-hidden"> {{ __('more', 'sage') }}</span></span>
      @endif
    </p>
  @endif
  @php $hasRelease = $variant === 'featured' && $release !== '' && $releaseUrl !== ''; @endphp
  @if ($hasRelease || ! empty($r['stars']) || ! empty($r['forks']) || $lang !== '' || $license !== '' || ($variant === 'featured' && $pushed !== ''))
    <p class="pf-meta repo-stats repo-card__stats">
      @if ($hasRelease)
        @php $releasedAgo = $releaseDate !== '' ? sprintf(__('released %s', 'sage'), \App\mh_github_ago($releaseDate)) : ''; @endphp
        <a class="repo-card__ver" href="{{ esc_url($releaseUrl) }}" rel="noopener" target="_blank" @if ($releasedAgo !== '') title="{{ esc_attr($releasedAgo) }}" @endif>
          {{ $release }}<span class="visually-hidden"> {{ $releasedAgo }} {{ __('(opens in a new window)', 'sage') }}</span>
        </a>
      @endif
      @if ($lang !== '')
        <span class="repo-lang">
          <span class="repo-lang__dot" style="--lang-color: {{ esc_attr($langColor) }}" aria-hidden="true"></span>
          {{ \App\mh_title_label($lang) }}
        </span>
      @endif
      @if (! empty($r['stars']))
        <span class="repo-stat">{!! \App\mh_svg_icon('star', 13) !!} {{ sprintf(_n('%s star', '%s stars', (int) $r['stars'], 'sage'), number_format_i18n((int) $r['stars'])) }}</span>
      @endif
      @if (! empty($r['forks']))
        <span class="repo-stat">{{ sprintf(_n('%s fork', '%s forks', (int) $r['forks'], 'sage'), number_format_i18n((int) $r['forks'])) }}</span>
      @endif
      @if ($commits > 0)
        <span class="repo-stat">{{ sprintf(_n('%s commit', '%s commits', $commits, 'sage'), number_format_i18n($commits)) }}</span>
      @endif
      @if ($license !== '')
        <span class="repo-card__license" title="{{ esc_attr__('License', 'sage') }}">{{ $license }}</span>
      @endif
      @if ($variant === 'featured' && $pushed !== '')
        <span class="repo-stat repo-stat--when">{!! \App\mh_svg_icon('calendar', 13) !!} {{ sprintf(__('Pushed %s', 'sage'), \App\mh_github_ago($pushed)) }}</span>
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
