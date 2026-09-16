{{-- Single project at /projects/{slug}/ --}}
@php
  $postId = (int) get_the_ID();
  $post = get_post($postId);
  $card = $post instanceof WP_Post ? \App\mh_project_post_to_card($post) : [];
  $story = \App\mh_project_concept_narrative($postId);
  $case = \App\mh_project_case_study($postId, $card, $story);
  $title = (string) ($card['title'] ?? get_the_title());
  $shot = (string) ($card['image'] ?? '');
  $cat = (string) ($card['cat'] ?? '');
  $place = (string) ($card['place'] ?? '');
  $tech = $card['tech'] ?? [];
  $demo = (string) ($story['demo'] !== '' ? $story['demo'] : ($card['demo'] ?? ''));
  $github = (string) ($card['github'] ?? $card['concept'] ?? '');
  $helloUrl = function_exists('\\App\\mh_work_help_url') ? \App\mh_work_help_url($card) : \App\mh_work_contact_url($card);
  $projectsUrl = home_url('/projects/');
  $related = \App\mh_related_concept_cards($card, 3);
  $summary = (string) ($story['summary'] !== '' ? $story['summary'] : ($card['blurb'] ?? ''));
  $eyebrow = \App\mh_project_display_eyebrow((string) ($story['eyebrow'] ?? ''));
  $deliverables = is_array($story['deliverables'] ?? null) ? $story['deliverables'] : [];
  $architecture = (string) ($case['architecture'] ?? '');
  $handoff = (string) ($case['handoff'] ?? '');
@endphp

<article @php(post_class('concept-page'))>
  @component('partials.page-hero')
    <p class="eyebrow">
      <a class="concept-crumb" href="{{ esc_url($projectsUrl) }}">{{ __('Projects', 'sage') }}</a>
      <span aria-hidden="true"> / </span>
      {{ $eyebrow }}
    </p>
    <h1 class="display-title is-hero">{{ $title }}</h1>
    @if (! empty($case['notice']))
      <p class="concept-spec-banner" role="note">{{ $case['notice'] }}</p>
    @endif
    @if ($summary !== '')
      <p class="lead">{{ $summary }}</p>
    @endif
    <p class="pf-meta" style="margin-top:.85rem">
      @if ($cat !== '')
        <span>{{ $cat }}</span>
      @endif
      @if ($cat !== '' && $place !== '')
        <span aria-hidden="true"> · </span>
      @endif
      @if ($place !== '')
        <span>{!! \App\mh_svg_icon('map', 14) !!} {{ $place }}</span>
      @endif
    </p>
    <div class="concept-hero-actions">
      @if ($demo !== '')
        <a class="btn" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
          {!! \App\mh_svg_icon('globe', 15) !!}
          {{ __('Live demo', 'sage') }} <span aria-hidden="true">↗</span>
        </a>
      @endif
      <a class="{{ $demo !== '' ? 'btn btn-outline' : 'btn' }}" href="{{ esc_url($helloUrl) }}">
        {!! \App\mh_svg_icon('mail', 16) !!}
        {{ __('Say hello', 'sage') }}
      </a>
      @if ($github !== '' && str_starts_with($github, 'http'))
        <a class="h-text-arrow" href="{{ esc_url($github) }}" rel="noopener" target="_blank">
          {{ __('View code', 'sage') }} <span aria-hidden="true">↗</span>
        </a>
      @endif
      <a class="h-text-arrow" href="{{ esc_url($projectsUrl) }}">
        {{ __('All projects', 'sage') }} →
      </a>
    </div>
  @endcomponent

  <div class="container wide page-block concept-layout">
    @if ($shot !== '')
      <figure class="concept-shot">
        <img
          src="{{ esc_url($shot) }}"
          alt="{{ esc_attr(sprintf(__('Screenshot of %s', 'sage'), $title)) }}"
          width="1280"
          height="720"
          loading="eager"
          decoding="async"
        >
        <figcaption>{{ __('Sample project. Not a client site.', 'sage') }}</figcaption>
      </figure>
    @endif

    @if (! empty($tech))
      <p class="pill-row">
        @foreach ($tech as $t)
          <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ $t }}</span>
        @endforeach
      </p>
    @endif

    <div class="concept-story">
      @if (($story['challenge'] ?? '') !== '')
        <section class="concept-story__block">
          <h2>{{ __('Why I built it', 'sage') }}</h2>
          <p>{{ $story['challenge'] }}</p>
        </section>
      @endif
      @if (($story['approach'] ?? '') !== '')
        <section class="concept-story__block">
          <h2>{{ __('What I did', 'sage') }}</h2>
          <p>{{ $story['approach'] }}</p>
        </section>
      @endif
      @if (($story['result'] ?? '') !== '')
        <section class="concept-story__block">
          <h2>{{ __('What you can use', 'sage') }}</h2>
          <p>{{ $story['result'] }}</p>
        </section>
      @endif
    </div>

    @if ($deliverables !== [])
      <section class="pf-section" aria-labelledby="project-included">
        <h2 id="project-included" class="display-title is-section">{{ __('What is in this sample', 'sage') }}</h2>
        <ul class="concept-deliverables">
          @foreach ($deliverables as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>
      </section>
    @endif

    @if ($architecture !== '')
      <details class="concept-dev">
        <summary>{{ __('For developers', 'sage') }}</summary>
        <p>{{ $architecture }}</p>
        @if ($handoff !== '')
          <p>{{ $handoff }}</p>
        @endif
      </details>
    @endif
  </div>

  @if ($related !== [])
    <section class="pf-section pf-section--alt" aria-labelledby="related-projects">
      <div class="container wide">
        <h2 id="related-projects" class="display-title is-section">{{ __('More projects', 'sage') }}</h2>
        <div class="work-grid">
          @foreach ($related as $p)
            @include('partials.work-card', ['p' => $p])
          @endforeach
        </div>
      </div>
    </section>
  @endif

  @include('partials.cta-band', [
    'kicker' => __('Work with me', 'sage'),
    'title' => sprintf(__('Like %s?', 'sage'), $title),
    'text' => __('Tell me what you would change, or write about a role. I usually reply in a day.', 'sage'),
    'label' => __('Say hello', 'sage'),
    'secondary' => __('Hire me', 'sage'),
    'secondaryHref' => home_url('/hire/'),
  ])
</article>
