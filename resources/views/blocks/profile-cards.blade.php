{{-- matthummel/profile-cards: GitHub and LinkedIn cards (the old About story aside). Empty values are left out when GitHub or LinkedIn is down. --}}
@php
  $login = \App\mh_github_login();
  $gh = \App\Github::fetchUser($login);
  $ghUrl = ($gh['url'] ?? '') ?: 'https://github.com/'.$login;
  $li = \App\LinkedIn::fetchProfile();
  $liUrl = (string) ($li['url'] ?? \App\LinkedIn::profileUrl());
  $showGithub = ! isset($attributes['showGithub']) || ! empty($attributes['showGithub']);
  $showLinkedin = ! isset($attributes['showLinkedin']) || ! empty($attributes['showLinkedin']);
@endphp
@if ($showGithub || $showLinkedin)
  <div class="{{ trim('mh-data mh-profile-cards about-story__aside '.($attributes['className'] ?? '')) }}">
    @if ($showGithub)
      <div class="about-aside-card">
        <div class="about-aside-card__head">
          <span class="about-aside-card__mark" aria-hidden="true">{!! \App\mh_svg_icon('github', 18) !!}</span>
          <div>
            <p class="about-aside-card__name">
              <a href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
                {{ '@'.$login }}
                <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
              </a>
            </p>
            @if (! empty($gh['created']))
              {{-- translators: %s: year the GitHub account was created --}}
              <p class="about-aside-card__meta">{{ sprintf(__('On GitHub since %s', 'sage'), $gh['created']) }}</p>
            @endif
          </div>
        </div>
        @if (! empty($gh['bio']))
          <p class="about-aside-card__bio">{{ $gh['bio'] }}</p>
        @endif
        @if (! empty($gh['public_repos']) || ! empty($gh['followers']))
          <ul class="about-aside-card__stats">
            @if (! empty($gh['public_repos']))
              <li>
                <strong>{{ number_format_i18n($gh['public_repos']) }}</strong>
                <span>{{ __('public repos', 'sage') }}</span>
              </li>
            @endif
            @if (! empty($gh['followers']))
              <li>
                <strong>{{ number_format_i18n($gh['followers']) }}</strong>
                <span>{{ __('followers', 'sage') }}</span>
              </li>
            @endif
          </ul>
        @endif
        @if (\App\mh_is_hireable($gh))
          <p class="about-aside-avail">
            @include('partials.avail-mark', ['gh' => $gh])
            {{ \App\mh_availability_label($gh, __('Available for hire', 'sage')) }}
          </p>
        @endif
        <a class="about-aside-card__link" href="{{ esc_url($ghUrl) }}" rel="me noopener" target="_blank">
          {{ __('View GitHub profile', 'sage') }} →
          <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
        </a>
      </div>
    @endif

    @if ($showLinkedin)
      <div class="about-aside-card about-aside-card--linkedin">
        <div class="about-aside-card__head">
          <span class="about-aside-card__mark" aria-hidden="true">{!! \App\mh_svg_icon('linkedin', 18) !!}</span>
          <div>
            <p class="about-aside-card__name">
              <a href="{{ esc_url($liUrl) }}" rel="me noopener" target="_blank">
                {{ $li['name'] ?? 'Matt Hummel' }}
                <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
              </a>
            </p>
            @if (! empty($li['headline']))
              <p class="about-aside-card__meta">{{ $li['headline'] }}</p>
            @endif
          </div>
        </div>
        @if (! empty($li['location']))
          <p class="about-aside-card__bio">{!! \App\mh_svg_icon('map', 13) !!} {{ $li['location'] }}</p>
        @endif
        @if (! empty($li['open_to_work']))
          <p class="about-aside-avail">
            <span class="h-badge__dot" aria-hidden="true"></span>
            {{ __('Open to work', 'sage') }}
          </p>
        @endif
        <a class="about-aside-card__link" href="{{ esc_url($liUrl) }}" rel="me noopener" target="_blank">
          {{ __('View LinkedIn profile', 'sage') }} →
          <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
        </a>
      </div>
    @endif
  </div>
@endif
