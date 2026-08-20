@php
  $writeId = $writeId ?? \App\mh_writing_id();
  $writeUrl = $writeUrl ?? ($writeId ? get_permalink($writeId) : home_url('/blog/'));
  $cats = $cats ?? get_categories(['hide_empty' => true]);
  $current = is_category() ? get_queried_object_id() : 0;
  $allActive = ! is_category() && ! is_search();
@endphp
@if ($cats)
  <nav class="filter-row" aria-label="{{ __('Filter by topic', 'sage') }}">
    <a class="filter-pill{{ $allActive ? ' is-active' : '' }}" href="{{ esc_url($writeUrl) }}" @if ($allActive) aria-current="page" @endif>{{ __('All', 'sage') }}</a>
    @foreach ($cats as $c)
      <a class="filter-pill{{ $current === (int) $c->term_id ? ' is-active' : '' }}" href="{{ esc_url(get_category_link($c)) }}" @if ($current === (int) $c->term_id) aria-current="page" @endif>
        {{ $c->name }}
        <span class="filter-count">{{ (int) $c->count }}</span>
      </a>
    @endforeach
  </nav>
@endif
