{{-- matthummel/github-repos: pinned or recently pushed repos from the stored GitHub snapshot. Shows a plain link when GitHub has nothing to give. --}}
@php
  $snap = \App\mh_code_gh_snapshot();
  $isRecent = ($attributes['source'] ?? 'pinned') === 'recent';
  $count = min(12, max(1, (int) ($attributes['count'] ?? 6)));
  $repos = array_slice((array) ($snap[$isRecent ? 'recent' : 'featured'] ?? []), 0, $count);
  $login = \App\mh_github_login();
  $gh = \App\Github::fetchUser($login);
  $ghUrl = ($gh['url'] ?? '') ?: 'https://github.com/'.$login;
  $showAll = ! empty($attributes['showAllLink']);
  // Most-starred repos, pinned list only: the star counts live in the snapshot already.
  $starred = (! $isRecent && ! empty($attributes['showStarred']))
    ? array_slice(array_values(array_filter((array) ($snap['star_repos'] ?? []), static fn ($s) => (int) ($s['stars'] ?? 0) > 0)), 0, 3)
    : [];
@endphp
<div class="{{ trim('mh-data mh-github-repos '.($attributes['className'] ?? '')) }}" id="{{ $isRecent ? 'gh-updated' : 'gh-featured' }}">
  @if ($showAll)
    <div class="mh-data__meta">
      <a class="btn btn-outline code-block__cta" href="{{ esc_url($ghUrl.'?tab=repositories') }}" rel="noopener" target="_blank">
        {!! \App\mh_svg_icon('github', 14) !!}
        @if ((int) ($snap['repo_count'] ?? 0) > 0)
          {{-- translators: %s: number of public repos --}}
          {{ sprintf(__('All %s repos', 'sage'), number_format_i18n((int) $snap['repo_count'])) }}
        @else
          {{ __('All repos', 'sage') }}
        @endif
        <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
      </a>
    </div>
  @endif
  @if ($repos === [])
    <p class="h-journal__empty">
      {{ __('GitHub is not answering right now.', 'sage') }}
      <a href="{{ esc_url($ghUrl.'?tab=repositories') }}" rel="noopener" target="_blank">{{ __('See the repos on GitHub', 'sage') }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
    </p>
  @elseif ($isRecent)
    <ul class="code-recent">
      @foreach ($repos as $r)
        @php $color = ($r['lang'] ?? '') !== '' ? \App\mh_github_lang_color($r['lang']) : ''; @endphp
        <li class="code-recent__row">
          <a class="code-recent__hit" href="{{ esc_url($r['url']) }}" rel="noopener" target="_blank">
            <span class="code-recent__name">{{ $r['title'] }}</span>
            @if (($r['desc'] ?? '') !== '')
              <span class="code-recent__desc">{{ $r['desc'] }}</span>
            @endif
            <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
          </a>
          <span class="code-recent__meta">
            @if (($r['lang'] ?? '') !== '')
              <span class="repo-lang"><span class="repo-lang__dot" style="--lang-color: {{ esc_attr($color) }}" aria-hidden="true"></span>{{ \App\mh_title_label($r['lang']) }}</span>
            @endif
            @if ((int) ($r['stars'] ?? 0) > 0)
              <span>{!! \App\mh_svg_icon('star', 12) !!} {{ number_format_i18n((int) $r['stars']) }}</span>
            @endif
            @if (($r['pushed'] ?? '') !== '')
              <time datetime="{{ esc_attr($r['pushed']) }}">{{ \App\mh_github_ago($r['pushed']) }}</time>
            @endif
          </span>
        </li>
      @endforeach
    </ul>
  @else
    <ol class="code-repos-grid code-repos-grid--pinned">
      @foreach ($repos as $i => $r)
        <li class="code-repos-grid__item">
          @include('partials.repo-card', ['r' => $r, 'index' => $i + 1, 'variant' => 'featured'])
        </li>
      @endforeach
    </ol>
    @if ($starred !== [])
      <div class="gh-starred">
        <h4 class="gh-starred__title">{{ __('Most starred', 'sage') }}</h4>
        <ul class="gh-starred__list">
          @foreach ($starred as $s)
            <li>
              <a class="gh-starred__item" href="{{ esc_url($s['url']) }}" rel="noopener" target="_blank">
                <span class="gh-starred__name">{{ \App\mh_title_label((string) $s['name']) }}</span>
                <span class="gh-starred__stars">{!! \App\mh_svg_icon('star', 13) !!} {{ number_format_i18n((int) $s['stars']) }}</span>
                <span class="visually-hidden"> {{ sprintf(_n('%s star', '%s stars', (int) $s['stars'], 'sage'), number_format_i18n((int) $s['stars'])) }} {{ __('(opens in a new window)', 'sage') }}</span>
              </a>
            </li>
          @endforeach
        </ul>
      </div>
    @endif
  @endif
</div>
