@php
  $events = \App\mh_github_events(6);
  $repos = \App\mh_github_live_repos(4);
  $posts = \App\mh_latest_posts(3);
  $social = \App\mh_now_social_feed(3);
  $journal = get_permalink(get_option('page_for_posts')) ?: home_url('/blog/');
  $githubUrl = $ghUrl ?? ('https://github.com/'.\App\mh_github_login());
@endphp

<section class="now-block now-desk" id="activity" data-now-desk aria-labelledby="now-activity-heading">
  <div class="now-desk__head">
    <p class="now-block__eyebrow">{{ __('Activity', 'sage') }}</p>
    <h2 id="now-activity-heading" class="now-block__title">{{ \App\field('now_activity_h2', __('Right now', 'sage')) }}</h2>
    <p>{{ \App\field('now_activity_intro', __('Public GitHub events, recent journal posts, and posts from DEV.to and Bluesky when those feeds respond.', 'sage')) }}</p>
  </div>

  <div class="now-desk__tabs">
    <a class="now-desk__tab" href="#now-github" data-now-tab="now-github">{{ __('GitHub', 'sage') }}</a>
    <a class="now-desk__tab" href="#now-posts" data-now-tab="now-posts">{{ __('Latest posts', 'sage') }}</a>
    <a class="now-desk__tab" href="#now-social" data-now-tab="now-social">{{ __('Social', 'sage') }}</a>
  </div>

  <div class="now-desk__panel" id="now-github" data-now-panel>
    @if ($events === [] && $repos === [])
      <p class="now-desk__empty">{{ \App\field('now_gh_empty', __('GitHub did not return public activity just now. The profile link still works.', 'sage')) }}</p>
      <a class="h-text-arrow" href="{{ esc_url($githubUrl) }}" rel="me noopener" target="_blank">
        {{ __('View GitHub', 'sage') }} <span aria-hidden="true">→</span>
        <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
      </a>
    @else
      @if ($events !== [])
        <ol class="now-feed">
          @foreach ($events as $ev)
            @php
              $evUrl = (string) ($ev['url'] ?? '');
              $evRepo = (string) ($ev['repo'] ?? '');
              $evWhen = (string) ($ev['when'] ?? '');
            @endphp
            @if ($evUrl !== '')
              <li class="now-feed__item">
                <a class="now-feed__link" href="{{ esc_url($evUrl) }}" rel="noopener" target="_blank">
                  <em>{{ \App\mh_github_event_label((string) ($ev['type'] ?? '')) }}</em>
                  @if ($evRepo !== '')
                    <strong>{{ $evRepo }}</strong>
                  @endif
                  <span>{{ (string) ($ev['text'] ?? '') }}</span>
                  <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
                </a>
                @if ($evWhen !== '')
                  <time datetime="{{ esc_attr($evWhen) }}">{{ \App\mh_github_ago($evWhen) }}</time>
                @endif
              </li>
            @endif
          @endforeach
        </ol>
      @endif

      @if ($repos !== [])
        <h3 class="now-desk__sub">{{ __('Recently updated', 'sage') }}</h3>
        <ul class="now-repos">
          @foreach ($repos as $repo)
            @php
              $repoUrl = (string) ($repo['url'] ?? '');
              $repoTitle = (string) ($repo['title'] ?? $repo['name'] ?? '');
              $repoPushed = (string) ($repo['pushed'] ?? '');
            @endphp
            @if ($repoUrl !== '' && $repoTitle !== '')
              <li>
                <a href="{{ esc_url($repoUrl) }}" rel="noopener" target="_blank">
                  <strong>{{ $repoTitle }}</strong>
                  @if (! empty($repo['desc']))
                    <span>{{ $repo['desc'] }}</span>
                  @endif
                  @if ($repoPushed !== '')
                    <span>{{ sprintf(__('Updated %s', 'sage'), \App\mh_github_ago($repoPushed)) }}</span>
                  @endif
                  <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
                </a>
              </li>
            @endif
          @endforeach
        </ul>
      @endif
    @endif
  </div>

  <div class="now-desk__panel" id="now-posts" data-now-panel>
    @if ($posts === [])
      <p class="now-desk__empty">{{ \App\field('now_posts_empty', __('No journal posts are published yet.', 'sage')) }}</p>
    @else
      <ol class="now-posts">
        @foreach ($posts as $post)
          <li>
            <a href="{{ esc_url((string) ($post['url'] ?? '')) }}">
              @if (! empty($post['date']))
                <time datetime="{{ esc_attr((string) ($post['date_iso'] ?? '')) }}">{{ $post['date'] }}</time>
              @endif
              <strong>{{ $post['title'] }}</strong>
              @if (! empty($post['ex']))
                <span>{{ $post['ex'] }}</span>
              @endif
            </a>
          </li>
        @endforeach
      </ol>
    @endif
    <a class="h-text-arrow" href="{{ esc_url($journal) }}">{{ __('Read the journal', 'sage') }} <span aria-hidden="true">→</span></a>
  </div>

  <div class="now-desk__panel" id="now-social" data-now-panel>
    <div class="now-social">
      <p>{{ \App\field('now_social_note', __('These are the profiles I keep. Posts below come from public feeds only.', 'sage')) }}</p>
      @include('partials.social', ['labeled' => true])
      @if ($social !== [])
        <ol class="now-posts">
          @foreach ($social as $item)
            @php
              $itemUrl = (string) ($item['url'] ?? '');
              $itemWhen = (string) ($item['when'] ?? '');
              $itemAgo = $itemWhen !== '' ? \App\mh_github_ago($itemWhen) : '';
              $itemText = trim((string) ($item['text'] ?? ''));
              if ($itemText === '') {
                $itemText = (string) ($item['title'] ?? '');
              }
            @endphp
            @if ($itemUrl !== '' && $itemText !== '')
              <li>
                <a href="{{ esc_url($itemUrl) }}" rel="noopener" target="_blank">
                  <em>{{ (string) ($item['network'] ?? '') }}</em>
                  @if ($itemAgo !== '')
                    <time datetime="{{ esc_attr($itemWhen) }}">{{ $itemAgo }}</time>
                  @endif
                  <strong>{{ $itemText }}</strong>
                  <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
                </a>
              </li>
            @endif
          @endforeach
        </ol>
      @endif
    </div>
  </div>
</section>
