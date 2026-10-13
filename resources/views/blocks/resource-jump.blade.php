{{-- matthummel/resource-jump: a counted jump link for each Section on this page that holds Resource links or the Stack list. --}}
@php $rows = \App\mh_resources_jump_rows(); @endphp
@if ($rows !== [])
  <ul class="{{ trim('resources-jump mh-resource-jump '.($attributes['className'] ?? '')) }}">
    @foreach ($rows as $row)
      <li>
        <a href="#{{ $row['anchor'] }}">
          <span class="resources-jump__n">{{ number_format_i18n($row['count']) }}</span>
          <span class="resources-jump__label">{{ $row['label'] }}</span>
          <span class="resources-jump__go" aria-hidden="true">↓</span>
        </a>
      </li>
    @endforeach
  </ul>
@endif
