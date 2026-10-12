{{-- matthummel/cards: grid wrapper. Each card renders itself. --}}
@php
  $columns = min(4, max(2, (int) ($attributes['columns'] ?? 3)));
  $classes = trim('mh-cards mh-cards--'.$columns.' '.(! empty($attributes['numbered']) ? 'mh-cards--numbered ' : '').($attributes['className'] ?? ''));
@endphp
<div class="{{ $classes }}">
  {!! $content !!}
</div>
