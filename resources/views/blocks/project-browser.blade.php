{{-- matthummel/project-browser: every project card with search, type pills and grid/list (resources/js/work-tools.js). --}}
@php
  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter from a public link.
  $cat = isset($_GET['cat']) ? sanitize_text_field(wp_unslash($_GET['cat'])) : '';
  $all = \App\mh_work_page_items();
  $counts = [];
  foreach ($all as $item) {
      $key = (string) ($item['cat'] ?? '');
      if ($key === '') {
          continue;
      }
      $counts[$key] = ($counts[$key] ?? 0) + 1;
  }
  $cats = array_keys($counts);
  sort($cats);
  $pageUrl = (string) get_permalink();
  $shownCount = $cat === '' ? count($all) : count(array_filter($all, fn ($p) => ($p['cat'] ?? '') === $cat));
  $total = count($all);
  $label = static fn (string $key): string => trim((string) ($attributes[$key] ?? ''));
  $wrapper = trim('mh-data mh-project-browser '.($attributes['className'] ?? ''));
@endphp
@if ($total === 0)
  <div class="{{ $wrapper }}">
    <div class="work-empty" role="status">
      <div class="work-empty__icon" aria-hidden="true">{!! \App\mh_svg_icon('briefcase', 28) !!}</div>
      @if ($label('noProjectsTitle') !== '')
        <h3 class="work-empty__title">{{ $label('noProjectsTitle') }}</h3>
      @endif
      @if ($label('noProjectsText') !== '')
        <p class="work-empty__text">{{ $label('noProjectsText') }}</p>
      @endif
      @if ($label('noProjectsButton') !== '')
        <div class="work-empty__actions">
          <a class="btn" href="{{ esc_url(home_url('/contact/')) }}">
            {!! \App\mh_svg_icon('mail', 16) !!}
            {{ $label('noProjectsButton') }}
          </a>
        </div>
      @endif
    </div>
  </div>
@else
  <div class="{{ $wrapper }}" data-work-hub>
    <div class="write-tools">
      <div class="search-wrap search-wrap--inline">
        <form role="search" class="search-form" action="{{ esc_url($pageUrl) }}" data-work-filter-form>
          <label for="work-site-search">
            <span class="visually-hidden">{{ __('Filter projects', 'sage') }}</span>
            <input
              id="work-site-search"
              type="search"
              class="js-mh-search"
              data-work-filter
              placeholder="{{ $label('searchPlaceholder') }}"
              autocomplete="off"
            >
          </label>
        </form>
      </div>
      <div class="write-tool-actions">
        <p class="write-count" data-work-count role="status" data-label-one="{{ esc_attr__('%d project', 'sage') }}" data-label-many="{{ esc_attr__('%d projects', 'sage') }}">{{ sprintf(_n('%d project', '%d projects', $shownCount, 'sage'), $shownCount) }}</p>
        <div class="write-view" role="group" aria-label="{{ __('Layout', 'sage') }}">
          <button type="button" class="write-view-btn is-active" data-work-view="grid" aria-pressed="true">{{ $label('gridLabel') !== '' ? $label('gridLabel') : __('Grid', 'sage') }}</button>
          <button type="button" class="write-view-btn" data-work-view="list" aria-pressed="false">{{ $label('listLabel') !== '' ? $label('listLabel') : __('List', 'sage') }}</button>
        </div>
        <p class="write-kbd"><kbd>/</kbd> {{ __('to search', 'sage') }}</p>
      </div>
    </div>

    {{-- Type filter: links that work without JS (?cat=…); work-tools.js filters the grid in place. --}}
    <nav class="filter-row" aria-label="{{ __('Filter by type', 'sage') }}" data-work-cats>
      <a class="filter-pill{{ $cat === '' ? ' is-active' : '' }}" href="{{ esc_url($pageUrl) }}" data-work-cat="" @if ($cat === '') aria-current="true" @endif>
        {{ __('All', 'sage') }} <span class="filter-count">{{ $total }}</span>
      </a>
      @foreach ($cats as $c)
        <a class="filter-pill{{ $cat === $c ? ' is-active' : '' }}" href="{{ esc_url(add_query_arg('cat', rawurlencode($c), $pageUrl)) }}" data-work-cat="{{ $c }}" @if ($cat === $c) aria-current="true" @endif>
          {{ $c }} <span class="filter-count">{{ (int) $counts[$c] }}</span>
        </a>
      @endforeach
    </nav>

    {{-- Every card is in the HTML; the ones outside the current type are hidden. --}}
    <div class="work-grid work-grid--roomy" data-work-grid>
      @foreach ($all as $p)
        @include('partials.work-card', [
          'p' => $p,
          'pageUrl' => $pageUrl,
          'isHidden' => $cat !== '' && ($p['cat'] ?? '') !== $cat,
        ])
      @endforeach
    </div>
    <div class="work-empty work-empty--compact" data-work-empty role="status" @if ($shownCount > 0) hidden @endif>
      <h3 class="work-empty__title">{{ $label('emptyTitle') }}</h3>
      <p class="work-empty__text">{{ $label('emptyText') }}</p>
      @if ($label('resetLabel') !== '')
        <div class="work-empty__actions">
          <a class="btn btn-outline" href="{{ esc_url($pageUrl) }}" data-work-reset>{{ $label('resetLabel') }}</a>
        </div>
      @endif
    </div>
  </div>
@endif
