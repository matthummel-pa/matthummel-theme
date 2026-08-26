@php
  $jobs = $jobs ?? [];
  $linkedin = $linkedin ?? '';
  $headingId = $headingId ?? 'resume-heading';
  $h2 = $h2 ?? __('Resume.', 'sage');
  $intro = $intro ?? '';
  $eyebrow = $eyebrow ?? __('Experience', 'sage');
  $showLinkedIn = $showLinkedIn ?? true;
  $extraLinks = $extraLinks ?? [];
@endphp
<section class="pf-section" id="resume" aria-labelledby="{{ $headingId }}" itemscope itemtype="https://schema.org/Person">
  <meta itemprop="name" content="Matt Hummel">
  <meta itemprop="jobTitle" content="WordPress Developer">
  <div class="container wide">
    <div class="code-section-head">
      <div>
        <p class="eyebrow">{{ $eyebrow }}</p>
        <h2 id="{{ $headingId }}" class="display-title is-section">{{ $h2 }}</h2>
        @if ($intro !== '')
          <p class="sec-intro">{{ $intro }}</p>
        @endif
      </div>
      <div class="code-resume-links">
        @if ($showLinkedIn && $linkedin !== '')
          <a class="btn" href="{{ esc_url($linkedin) }}" rel="noopener" target="_blank">
            {!! \App\mh_svg_icon('linkedin', 16) !!} {{ __('LinkedIn', 'sage') }}
            <span class="visually-hidden"> {{ __('(opens in a new window)', 'sage') }}</span>
          </a>
        @endif
        @foreach ($extraLinks as $link)
          <a class="about-text-link" href="{{ esc_url($link['href'] ?? '#') }}">{{ $link['label'] ?? '' }}</a>
        @endforeach
      </div>
    </div>

    <ol class="resume-timeline">
      @foreach ($jobs as $job)
        @php $current = strcasecmp((string) ($job['period'] ?? ''), 'Current') === 0; @endphp
        <li>
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
                @if (($job['period'] ?? '') !== '')
                  <span class="resume-tag{{ $current ? ' is-now' : '' }}">{{ $job['period'] }}</span>
                @endif
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
</section>
