@php
  $writeId = $writeId ?? \App\mh_writing_id();
  $exclude = (int) ($exclude ?? 0);
  $years = \App\mh_journal_years();
  $discussed = \App\mh_popular_posts(4, $exclude);
  $tags = get_tags(['hide_empty' => true, 'number' => 16, 'orderby' => 'count', 'order' => 'DESC']);
  $currentYear = is_year() ? (int) get_query_var('year') : 0;
  $currentTag = is_tag() ? (int) get_queried_object_id() : 0;
  $devtoFollowers = \App\mh_devto_followers(18);
  $devtoUrl = \App\mh_devto_profile_url();
  $hasTags = $tags && ! is_wp_error($tags);
@endphp
<aside class="write-aside" aria-label="{{ __('Journal tools', 'sage') }}">
  <section class="write-aside-card write-aside-devto">
    <h2 class="write-aside-h">{{ \App\field('write_aside_devto', __('DEV.to friends', 'sage'), $writeId) }}</h2>
    <p class="write-aside-devto__thanks">
      {{ \App\field('write_aside_devto_thanks', __('Thank you to everyone following along on DEV.to. You make posting worth it.', 'sage'), $writeId) }}
    </p>
    @if ($devtoFollowers)
      <ul class="write-devto-followers">
        @foreach ($devtoFollowers as $f)
          <li>
            <a
              class="write-devto-follower"
              href="{{ esc_url($f['url']) }}"
              rel="noopener noreferrer"
              target="_blank"
              title="{{ esc_attr($f['name']) }}"
            >
              @if ($f['image'] !== '')
                <img
                  class="write-devto-follower__img"
                  src="{{ esc_url($f['image']) }}"
                  alt=""
                  width="36"
                  height="36"
                  loading="lazy"
                  decoding="async"
                >
              @else
                <span class="write-devto-follower__img write-devto-follower__img--empty" aria-hidden="true">
                  {{ mb_strtoupper(mb_substr($f['name'], 0, 1)) }}
                </span>
              @endif
              <span class="write-devto-follower__meta">
                <span class="write-devto-follower__name">{{ $f['name'] }}</span>
                <span class="write-devto-follower__user">{{ '@'.$f['username'] }}</span>
              </span>
            </a>
          </li>
        @endforeach
      </ul>
    @endif
    <a class="write-aside-devto__link" href="{{ esc_url($devtoUrl) }}" rel="me noopener noreferrer" target="_blank">
      {!! \App\mh_svg_icon('devto', 14) !!}
      {{ \App\field('write_aside_devto_cta', __('Follow on DEV.to', 'sage'), $writeId) }}
    </a>
  </section>

  @if ($years)
    <section class="write-aside-card">
      <h2 class="write-aside-h">{{ \App\field('write_aside_years', __('Years', 'sage'), $writeId) }}</h2>
      <ul class="write-year-list">
        @foreach ($years as $row)
          <li>
            <a class="{{ $currentYear === $row['year'] ? 'is-active' : '' }}" href="{{ esc_url($row['url']) }}" @if ($currentYear === $row['year']) aria-current="page" @endif>
              {{ $row['year'] }}
              <span class="filter-count">{{ $row['count'] }}</span>
            </a>
          </li>
        @endforeach
      </ul>
    </section>
  @endif

  @if ($discussed)
    <section class="write-aside-card">
      <h2 class="write-aside-h">{{ \App\field('write_aside_discussed', __('Most discussed', 'sage'), $writeId) }}</h2>
      <ol class="write-discussed">
        @foreach ($discussed as $item)
          <li>
            <a href="{{ esc_url($item['url']) }}">{{ $item['title'] }}</a>
            <p class="write-discussed-meta">
              {{ sprintf(_n('%d comment', '%d comments', $item['comments'], 'sage'), $item['comments']) }}
              <span aria-hidden="true"> · </span>
              {{ $item['date'] }}
            </p>
          </li>
        @endforeach
      </ol>
    </section>
  @endif

  @if ($hasTags)
    <section class="write-aside-card">
      <h2 class="write-aside-h">{{ \App\field('write_aside_tags', __('Tags', 'sage'), $writeId) }}</h2>
      <nav class="write-tag-cloud" aria-label="{{ __('Tags', 'sage') }}">
        @foreach ($tags as $tag)
          <a class="filter-pill{{ $currentTag === (int) $tag->term_id ? ' is-active' : '' }}" href="{{ esc_url(get_tag_link($tag)) }}" @if ($currentTag === (int) $tag->term_id) aria-current="page" @endif>
            {{ $tag->name }}
            <span class="filter-count">{{ (int) $tag->count }}</span>
          </a>
        @endforeach
      </nav>
    </section>
  @endif
</aside>
