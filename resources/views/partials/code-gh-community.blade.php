{{-- Code page: GitHub profile, badges, followers, stargazers, starred repos. Data: \App\mh_code_gh_snapshot(). --}}
@php
  $profile = $gh['profile'];
  $ghUrl = $gh['url'];
  $login = $gh['login'];
  $watchingItems = $gh['watching']['items'] ?? [];
  $watchingSource = (string) ($gh['watching']['source'] ?? 'starred');
  $watchShown = 12;
@endphp
@if (! empty($profile['login']) || $gh['followers'] || $gh['badges'])
<section class="pf-section pf-section--alt code-gh code-community-sec" id="community" aria-labelledby="code-comm-heading">
  <div class="container wide">
    <header class="code-sec-head">
      <div>
        <p class="eyebrow">{{ __('Community', 'sage') }}</p>
        <h2 id="code-comm-heading" class="display-title is-section">{{ \App\field('code_comm_h2', __('People who follow and star my repos', 'sage')) }}</h2>
        <p class="sec-intro">{{ \App\field('code_comm_intro', __('Public GitHub followers and stargazers below. Thank you for reading the code, starring a repo, or following along.', 'sage')) }}</p>
      </div>
    </header>

    <div class="code-comm-grid">
      @if (! empty($profile['login']))
        <div class="code-comm-profile">
          @if (! empty($profile['avatar']))
            <img class="code-comm-profile__avatar" src="{{ esc_url($profile['avatar']) }}" width="72" height="72" alt="{{ esc_attr(($profile['name'] ?: $profile['login']).' GitHub avatar') }}" loading="lazy" decoding="async">
          @endif
          <p class="code-comm-profile__name">{{ $profile['name'] ?: $profile['login'] }}</p>
          <p class="code-comm-profile__login">
            <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">{{ '@'.$login }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
          </p>
          @if (! empty($profile['bio']))
            <p class="code-comm-profile__bio">{{ $profile['bio'] }}</p>
          @endif
          <ul class="code-comm-profile__facts">
            @if (! empty($profile['location']))
              <li>{!! \App\mh_svg_icon('map', 13) !!} {{ $profile['location'] }}</li>
            @endif
            @if (! empty($profile['created']))
              <li>{!! \App\mh_svg_icon('calendar', 13) !!} {{ sprintf(__('On GitHub since %s', 'sage'), $profile['created']) }}</li>
            @endif
            <li>{!! \App\mh_svg_icon('users', 13) !!} {{ sprintf(__('%1$s followers · %2$s following', 'sage'), number_format_i18n((int) ($profile['followers'] ?? 0)), number_format_i18n((int) ($profile['following'] ?? 0))) }}</li>
          </ul>
          <a class="btn btn-outline" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
            {!! \App\mh_svg_icon('github', 15) !!} {{ __('Follow on GitHub', 'sage') }}
            <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
          </a>
        </div>
      @endif

      <div class="code-comm-main">
        @if ($gh['badges'])
          <section class="code-gh-community__panel code-gh-community__panel--badges" aria-labelledby="code-badges-heading">
            <h3 class="code-gh-community__panel-title" id="code-badges-heading">
              {!! \App\mh_svg_icon('shield', 16) !!}
              {{ \App\field('code_badges_h3', __('Badges earned', 'sage')) }}
            </h3>
            <p class="code-gh-community__thanks">{{ \App\field('code_badges_intro', __('Milestone badges from live GitHub stats — stars, followers, contributions, and repo activity.', 'sage')) }}</p>
            <ul class="code-gh-badges__grid code-gh-badges__grid--panel">
              @foreach ($gh['badges'] as $badge)
                <li class="code-gh-badge {{ esc_attr($badge['class']) }}">
                  <span class="code-gh-badge__icon" aria-hidden="true">{!! \App\mh_svg_icon($badge['icon'], 18) !!}</span>
                  <span class="code-gh-badge__copy">
                    <span class="code-gh-badge__label">{{ $badge['label'] }}</span>
                    <span class="code-gh-badge__detail">{{ $badge['detail'] }}</span>
                  </span>
                </li>
              @endforeach
            </ul>
          </section>
        @endif

        @if ($gh['followers'])
          <section class="code-gh-community__panel" aria-labelledby="code-follow-heading">
            <h3 class="code-gh-community__panel-title" id="code-follow-heading">
              {!! \App\mh_svg_icon('users', 16) !!}
              {{ \App\field('code_follow_h3', __('GitHub followers', 'sage')) }}
            </h3>
            <p class="code-gh-community__thanks">{{ \App\field('code_follow_thanks', __('Thank you for following on GitHub. I notice every new follower.', 'sage')) }}</p>
            <ul class="code-avatars">
              @foreach ($gh['followers'] as $f)
                <li>
                  <a class="code-avatars__hit" href="{{ esc_url($f['url']) }}" rel="noopener noreferrer" target="_blank" title="{{ esc_attr($f['name'].' (@'.$f['login'].')') }}">
                    @if ($f['avatar'] !== '')
                      <img src="{{ esc_url(add_query_arg('s', 80, $f['avatar'])) }}" alt="{{ esc_attr('@'.$f['login']) }}" width="40" height="40" loading="lazy" decoding="async">
                    @else
                      <span class="code-avatars__empty" aria-hidden="true">{{ mb_strtoupper(mb_substr($f['name'], 0, 1)) }}</span>
                      <span class="visually-hidden">{{ '@'.$f['login'] }}</span>
                    @endif
                  </a>
                </li>
              @endforeach
            </ul>
            <a class="code-gh-community__link" href="{{ esc_url($ghUrl.'?tab=followers') }}" rel="noopener" target="_blank">
              {!! \App\mh_svg_icon('github', 14) !!}
              {{ sprintf(__('All %s followers on GitHub', 'sage'), number_format_i18n($gh['follower_count'])) }}
              <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
            </a>
          </section>
        @endif

        @if ($gh['star_total'] > 0)
          <section class="code-gh-community__panel" aria-labelledby="code-star-heading">
            <h3 class="code-gh-community__panel-title" id="code-star-heading">
              {!! \App\mh_svg_icon('star', 16) !!}
              {{ \App\field('code_star_h3', __('Stars earned', 'sage')) }}
              <span class="code-gh-community__count">{{ number_format_i18n($gh['star_total']) }}</span>
            </h3>
            <p class="code-gh-community__thanks">{{ \App\field('code_star_thanks', __('Thank you to everyone who starred a public repo. Stars help other developers find the work.', 'sage')) }}</p>
            @if ($gh['star_repos'])
              <ul class="code-gh-star-repos">
                @foreach ($gh['star_repos'] as $sr)
                  <li>
                    <a class="code-gh-star-repo" href="{{ esc_url($sr['url']) }}" rel="noopener" target="_blank">
                      <span class="code-gh-star-repo__name">{{ \App\mh_title_label($sr['name']) }}</span>
                      <span class="code-gh-star-repo__n">{!! \App\mh_svg_icon('star', 12) !!} {{ number_format_i18n((int) $sr['stars']) }}</span>
                      <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
                    </a>
                  </li>
                @endforeach
              </ul>
            @endif
            @if ($gh['stargazers'])
              <ul class="code-avatars code-avatars--sm">
                @foreach ($gh['stargazers'] as $s)
                  <li>
                    <a class="code-avatars__hit" href="{{ esc_url($s['url']) }}" rel="noopener noreferrer" target="_blank" title="{{ esc_attr($s['name'].' starred '.$s['repo']) }}">
                      @if ($s['avatar'] !== '')
                        <img src="{{ esc_url(add_query_arg('s', 64, $s['avatar'])) }}" alt="{{ esc_attr('@'.$s['login']) }}" width="32" height="32" loading="lazy" decoding="async">
                      @else
                        <span class="code-avatars__empty" aria-hidden="true">{{ mb_strtoupper(mb_substr($s['name'], 0, 1)) }}</span>
                        <span class="visually-hidden">{{ '@'.$s['login'] }}</span>
                      @endif
                    </a>
                  </li>
                @endforeach
              </ul>
            @endif
          </section>
        @endif
      </div>
    </div>

    @if ($watchingItems)
      <section class="code-gh-watching" id="gh-watching" aria-labelledby="code-watch-heading">
        <div class="code-gh-watching__head">
          <h3 class="code-gh-watching__title" id="code-watch-heading">
            {!! \App\mh_svg_icon('globe', 16) !!}
            {{ \App\field('code_watch_h3', __('Repos I watch', 'sage')) }}
          </h3>
          <p class="code-gh-watching__intro">
            @if ($watchingSource === 'watching')
              {{ \App\field('code_watch_intro', __('Public repositories I watch on GitHub.', 'sage')) }}
            @else
              {{ \App\field('code_watch_intro_starred', __('Repos I star and follow on GitHub.', 'sage')) }}
            @endif
            <a href="{{ esc_url($ghUrl.'?tab=stars') }}" rel="me noopener" target="_blank">
              {{ __('All starred on GitHub', 'sage') }}
              <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
            </a>
          </p>
        </div>
        <ul class="code-gh-watching__list">
          @foreach (array_slice($watchingItems, 0, $watchShown) as $w)
            @include('partials.code-gh-watch-item', ['w' => $w])
          @endforeach
        </ul>
        @if (count($watchingItems) > $watchShown)
          <details class="code-gh-watching__more">
            <summary>{{ sprintf(__('Show %s more', 'sage'), number_format_i18n(count($watchingItems) - $watchShown)) }}</summary>
            <ul class="code-gh-watching__list">
              @foreach (array_slice($watchingItems, $watchShown) as $w)
                @include('partials.code-gh-watch-item', ['w' => $w])
              @endforeach
            </ul>
          </details>
        @endif
      </section>
    @endif
  </div>
</section>
@endif
