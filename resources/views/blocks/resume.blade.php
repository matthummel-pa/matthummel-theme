{{-- matthummel/resume: the resume timeline. Roles come from the saved resume rows (App\mh_code_page_resume). --}}
@php
  $jobs = \App\mh_code_page_resume();
  $linkedin = ! empty($attributes['showLinkedIn']) ? \App\LinkedIn::profileUrl() : '';
@endphp
<div class="{{ trim('mh-data mh-resume '.($attributes['className'] ?? '')) }}" itemscope itemtype="https://schema.org/Person">
  <meta itemprop="name" content="Matt Hummel">
  <meta itemprop="jobTitle" content="Full-Stack Developer and WordPress Specialist">
  @if ($linkedin !== '')
    <div class="code-resume-links">
      <a class="btn" href="{{ esc_url($linkedin) }}" rel="noopener" target="_blank">
        {!! \App\mh_svg_icon('linkedin', 16) !!} {{ __('LinkedIn', 'sage') }}
        <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
      </a>
    </div>
  @endif

  <ol class="resume-timeline">
    @foreach ($jobs as $job)
      @php
        $period = trim((string) ($job['period'] ?? ''));
        // 3.7.35: "Mar 2025 – Jul 2026" becomes two calendar chips on the line, end on top
        // (newest first, so time runs down the rail). A period of "Current", or one that
        // ends in it ("Mar 2025 – Current"), is an ongoing role: a single "Now" end chip.
        $parts = array_values(array_filter(array_map('trim', preg_split('/\s+[–—-]\s+/u', $period) ?: [])));
        $current = $parts !== [] && strcasecmp((string) end($parts), 'Current') === 0;
        $toChip = static function (string $label): array {
            $bits = preg_split('/\s+/', trim($label)) ?: [];
            $year = (string) array_pop($bits);

            return ['m' => implode(' ', $bits), 'y' => $year];
        };
        $end = $current ? ['m' => '', 'y' => __('Now', 'sage')] : ($parts !== [] ? $toChip((string) end($parts)) : null);
        $start = count($parts) > 1 ? $toChip((string) $parts[0]) : null;
      @endphp
      <li class="resume-item{{ $current ? ' is-current' : '' }}">
        @if ($end)
          <div class="resume-when">
            <span class="visually-hidden">{{ $period }}</span>
            <span class="resume-cal resume-cal--end" aria-hidden="true">
              @if ($end['m'] !== '')<span class="resume-cal__m">{{ $end['m'] }}</span>@endif
              <span class="resume-cal__y">{{ $end['y'] }}</span>
            </span>
            @if ($start)
              <span class="resume-cal resume-cal--start" aria-hidden="true">
                @if ($start['m'] !== '')<span class="resume-cal__m">{{ $start['m'] }}</span>@endif
                <span class="resume-cal__y">{{ $start['y'] }}</span>
              </span>
            @endif
          </div>
        @endif
        <article class="resume-card{{ $current ? ' is-current' : '' }}" itemscope itemtype="https://schema.org/OrganizationRole">
          <header class="resume-head">
            <div class="resume-who">
              @if ($current)
                <p class="resume-now">{{ __('Current', 'sage') }}</p>
              @endif
              <h3 itemprop="roleName">{{ $job['role'] }}</h3>
              @if (($job['org'] ?? '') !== '')
                <p class="resume-org">
                  {!! \App\mh_svg_icon('briefcase', 15) !!}
                  @if (! empty($job['url']))
                    <a href="{{ esc_url($job['url']) }}" rel="noopener" target="_blank" itemprop="worksFor">{{ $job['org'] }}<span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span></a>
                  @else
                    <span itemprop="worksFor">{{ $job['org'] }}</span>
                  @endif
                </p>
              @endif
            </div>
            <p class="resume-tags">
              {{-- Dates moved to the calendar chips on the timeline (3.7.35). --}}
              @if (($job['type'] ?? '') !== '')
                <span class="resume-tag">{{ $job['type'] }}</span>
              @endif
            </p>
          </header>
          @if (! empty($job['bullets']))
            <ul class="resume-points">
              @foreach ($job['bullets'] as $b)
                <li>{{ $b }}</li>
              @endforeach
            </ul>
          @endif
        </article>
      </li>
    @endforeach
  </ol>
</div>
