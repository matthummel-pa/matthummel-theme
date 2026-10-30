{{-- matthummel/changelog-feed: the newest releases from the theme's CHANGELOG.md (mh_theme_changelog). It ships in the zip, so the page updates with every release and makes no network call. --}}
@php
  $count = min(60, max(1, (int) ($attributes['count'] ?? 20)));
  $notes = min(12, max(1, (int) ($attributes['notes'] ?? 4)));
  $entries = \App\mh_theme_changelog($count);
  $labels = ['added' => __('Added', 'sage'), 'changed' => __('Changed', 'sage'), 'fixed' => __('Fixed', 'sage'), 'removed' => __('Removed', 'sage')];
  $moreUrl = 'https://github.com/'.\App\mh_github_login().'/matthummel-theme/blob/main/CHANGELOG.md';
@endphp
<div class="{{ trim('mh-data mh-changelog-feed '.($attributes['className'] ?? '')) }}">
  @if ($entries === [])
    <p class="h-journal__empty">
      {{ __('The release notes are not available right now.', 'sage') }}
      <a href="{{ esc_url($moreUrl) }}" rel="noopener" target="_blank">{{ __('Read them on GitHub', 'sage') }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
    </p>
  @else
    <div class="cl-feed">
      @foreach ($entries as $i => $entry)
        @php
          $slug = 'cl-'.\Illuminate\Support\Str::slug($entry['version']);
          $shown = array_slice($entry['changes'], 0, $notes);
          $hidden = count($entry['changes']) - count($shown);
        @endphp
        <article class="cl-entry" aria-labelledby="{{ $entry['title'] !== '' ? $slug.'-title' : $slug }}">
          <div class="cl-entry__meta">
            <span class="cl-version" id="{{ $slug }}">{{ $entry['version'] }}</span>
            @if ($i === 0)
              <span class="cl-label">{{ __('Latest', 'sage') }}</span>
            @endif
            @if ($entry['date'] !== '')
              <time class="cl-date" datetime="{{ esc_attr($entry['date']) }}">{{ date_i18n('M j, Y', (int) strtotime($entry['date'])) }}</time>
            @endif
          </div>
          @if ($entry['title'] !== '')
            <h3 class="cl-entry__title" id="{{ $slug }}-title">{!! \App\mh_changelog_inline($entry['title']) !!}</h3>
          @endif
          @if ($shown !== [])
            <ul class="cl-changes">
              @foreach ($shown as [$type, $text])
                <li class="cl-change cl-change--{{ $type }}">
                  @if ($type !== 'changed')
                    <span class="cl-change__badge">{{ $labels[$type] ?? $type }}</span>
                  @endif
                  <span class="cl-change__text">{!! \App\mh_changelog_inline($text) !!}</span>
                </li>
              @endforeach
            </ul>
          @endif
          @if ($hidden > 0)
            <p class="cl-entry__more">
              {{-- translators: %s: number of notes not shown --}}
              <a href="{{ esc_url($moreUrl) }}" rel="noopener" target="_blank">{{ sprintf(_n('%s more note on GitHub', '%s more notes on GitHub', $hidden, 'sage'), number_format_i18n($hidden)) }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
            </p>
          @endif
        </article>
      @endforeach
    </div>
    <p class="cl-entry__more">
      <a href="{{ esc_url($moreUrl) }}" rel="noopener" target="_blank">{{ __('Older releases are in the full changelog on GitHub', 'sage') }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
    </p>
  @endif
</div>
