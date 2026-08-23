@php
  $writeId = $writeId ?? \App\mh_writing_id();
  $exclude = (int) ($exclude ?? 0);
  $years = \App\mh_journal_years();
  $discussed = \App\mh_popular_posts(4, $exclude);
  $tags = get_tags(['hide_empty' => true, 'number' => 16, 'orderby' => 'count', 'order' => 'DESC']);
  $currentYear = is_year() ? (int) get_query_var('year') : 0;
  $currentTag = is_tag() ? (int) get_queried_object_id() : 0;
@endphp
@if ($years || $discussed || ($tags && ! is_wp_error($tags)))
  <aside class="write-aside" aria-label="{{ __('Journal tools', 'sage') }}">
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

    @if ($tags && ! is_wp_error($tags))
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
@endif
