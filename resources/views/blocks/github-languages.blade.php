{{-- matthummel/github-languages: main language of each public repo, as a bar and a legend. --}}
@php
  $languages = (array) (\App\mh_code_gh_snapshot()['languages'] ?? []);
@endphp
<div class="{{ trim('mh-data mh-github-languages '.($attributes['className'] ?? '')) }}">
  @if ($languages === [])
    <p class="h-journal__empty">{{ __('GitHub is not answering right now, so the language counts are missing.', 'sage') }}</p>
  @else
    <div class="code-langs__bar" aria-hidden="true">
      @foreach ($languages as $l)
        <span style="--w: {{ (float) $l['pct'] }}%; --c: {{ esc_attr($l['color']) }}"></span>
      @endforeach
    </div>
    <ul class="code-langs__legend">
      @foreach ($languages as $l)
        <li>
          <span class="repo-lang__dot" style="--lang-color: {{ esc_attr($l['color']) }}" aria-hidden="true"></span>
          <span class="code-langs__name">{{ $l['lang'] }}</span>
          <span class="code-langs__pct">{{ number_format_i18n($l['pct'], 0) }}%</span>
          <span class="code-langs__n">{{ sprintf(_n('%s repo', '%s repos', $l['count'], 'sage'), number_format_i18n($l['count'])) }}</span>
        </li>
      @endforeach
    </ul>
  @endif
</div>
