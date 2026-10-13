{{-- matthummel/skills: the grouped skills list (App\mh_code_page_skills_grouped). --}}
@php
  $typed = array_values(array_filter(array_map('trim', explode(',', (string) ($attributes['list'] ?? '')))));
  $skillGroups = $typed !== [] ? \App\mh_skills_grouped($typed) : \App\mh_code_page_skills_grouped(\App\mh_code_page_id() ?: null);
@endphp
@if ($skillGroups !== [])
  <div class="{{ trim('mh-data mh-skills code-skills-groups '.($attributes['className'] ?? '')) }}">
    @foreach ($skillGroups as $group)
      <div class="code-skills-group" id="skill-{{ sanitize_title($group['label']) }}">
        <div class="code-skills-group__head">
          <span class="code-skills-group__mark" aria-hidden="true">{!! \App\mh_svg_icon($group['icon'], 16) !!}</span>
          <h3 class="code-skills-group__title">{{ $group['label'] }}</h3>
          <span class="code-skills-group__rule" aria-hidden="true"></span>
          <span class="code-skills-group__count">{{ number_format_i18n(count($group['items'])) }}</span>
        </div>
        <ul class="code-skills-grid">
          @foreach ($group['items'] as $skill)
            <li class="code-skills__card">
              {!! \App\mh_skill_tile($skill['name'], $skill['hint']) !!}
            </li>
          @endforeach
        </ul>
      </div>
    @endforeach
  </div>
@endif
