{{-- Single Project → project page or product landing (/projects/{slug}/) --}}
@php
  $postId = (int) get_the_ID();
  $card = \App\mh_project_post_to_card(get_post($postId));
  $story = \App\mh_project_concept_narrative($postId);
  $title = (string) ($card['title'] ?? get_the_title());
  $shot = (string) ($card['image'] ?? '');
  $cat = (string) ($card['cat'] ?? '');
  $place = (string) ($card['place'] ?? '');
  $tech = $card['tech'] ?? [];
  $demo = (string) ($story['demo'] !== '' ? $story['demo'] : ($card['demo'] ?? ''));
  $useUrl = function_exists('\\App\\mh_work_help_url') ? \App\mh_work_help_url($card) : \App\mh_work_contact_url($card);
  $projectsUrl = home_url('/projects/');
  $shopUrl = function_exists('wc_get_page_permalink') ? (string) wc_get_page_permalink('shop') : home_url('/shop/');
  $related = \App\mh_related_concept_cards($card, 3);
  $summary = (string) ($story['summary'] !== '' ? $story['summary'] : ($card['blurb'] ?? ''));
  $isProduct = ! empty($story['is_product']);
  $productType = (string) ($story['product_type'] ?? 'concept');
  $product = is_array($story['product'] ?? null) ? $story['product'] : null;
  $isTheme = $productType === 'theme';
  $isPlugin = $productType === 'plugin';

  if ($isTheme) {
    $defaultEyebrow = __('WordPress theme', 'sage');
    $primaryLabel = ! empty($product['is_free']) ? __('Get the theme', 'sage') : __('Buy theme', 'sage');
    $hireLabel = __('Get help', 'sage');
    $shotCaption = __('Live theme demo — brand it under Customize → Identity.', 'sage');
    $asideCtaTitle = $title !== '' ? sprintf(__('Get %s', 'sage'), $title) : __('Get this theme', 'sage');
    $asideNote = __('Instant download after checkout. Need it customized? Get help and I’ll ship the full build.', 'sage');
    $includedHeading = __('What’s in the theme', 'sage');
    $problemHeading = __('Built for this niche', 'sage');
    $approachHeading = __('How the theme works', 'sage');
    $resultHeading = __('What you get', 'sage');
    $relatedHeading = __('More projects', 'sage');
    $crumbBrowse = __('All work', 'sage');
    $ctaKicker = __('From this project', 'sage');
    $ctaTitle = sprintf(__('Ready for %s?', 'sage'), $title);
    $ctaText = __('Hire me to customize it for your shop, or buy the theme for a self-serve install.', 'sage');
    $ctaLabel = $primaryLabel;
    $ctaHref = ! empty($product['add_to_cart_url']) ? $product['add_to_cart_url'] : $useUrl;
  } elseif ($isPlugin) {
    $defaultEyebrow = __('WordPress plugin', 'sage');
    $primaryLabel = ! empty($product['is_free']) ? __('Download the plugin', 'sage') : __('Buy plugin', 'sage');
    $hireLabel = __('Get help', 'sage');
    $shotCaption = __('Plugin product page — install, activate, done.', 'sage');
    $asideCtaTitle = sprintf(__('Get %s', 'sage'), $title);
    $asideNote = __('Digital download after checkout. Want a custom feature or support? Get help.', 'sage');
    $includedHeading = __('What’s included', 'sage');
    $problemHeading = __('The problem it solves', 'sage');
    $approachHeading = __('How it works', 'sage');
    $resultHeading = __('What you get', 'sage');
    $relatedHeading = __('More projects', 'sage');
    $crumbBrowse = __('All work', 'sage');
    $ctaKicker = __('From this project', 'sage');
    $ctaTitle = sprintf(__('Get %s', 'sage'), $title);
    $ctaText = __('Hire me to extend it for your stack, or download the plugin for a self-serve install.', 'sage');
    $ctaLabel = $primaryLabel;
    $ctaHref = ! empty($product['add_to_cart_url']) ? $product['add_to_cart_url'] : $useUrl;
  } else {
    $defaultEyebrow = __('Project', 'sage');
    $primaryLabel = __('Get help', 'sage');
    $hireLabel = __('Get help', 'sage');
    $shotCaption = __('Example layout — starting point for a real shop build.', 'sage');
    $asideCtaTitle = __('Want this for your shop?', 'sage');
    $asideNote = __('This is an example project on matthummel.com — not a live client site. Hire me to adapt it for your shop.', 'sage');
    $includedHeading = __('Included in this project', 'sage');
    $problemHeading = __('The problem', 'sage');
    $approachHeading = __('How I shaped it', 'sage');
    $resultHeading = __('What you get', 'sage');
    $relatedHeading = __('More projects', 'sage');
    $crumbBrowse = __('All projects', 'sage');
    $ctaKicker = __('Work with me', 'sage');
    $ctaTitle = sprintf(__('Like %s?', 'sage'), $title);
    $ctaText = __('Say which project fits your shop and what you’d change. I usually reply within a day.', 'sage');
    $ctaLabel = __('Get help', 'sage');
    $ctaHref = $useUrl;
  }

  $eyebrow = (string) ($story['eyebrow'] !== '' ? $story['eyebrow'] : $defaultEyebrow);
  $buyUrl = is_array($product) ? (string) ($product['add_to_cart_url'] ?? '') : '';
  $pageClass = $isProduct ? 'concept-page concept-page--product concept-page--'.$productType : 'concept-page';
  $sidebar = \App\mh_project_sidebar($postId);
  $detailRows = [];
  if ($sidebar['version'] !== '') {
    $detailRows[] = [__('Version', 'sage'), $sidebar['version'], $sidebar['release_url']];
  }
  if ($sidebar['compatible'] !== '') {
    $detailRows[] = [__('Compatible with', 'sage'), $sidebar['compatible'], ''];
  }
  if ($sidebar['license'] !== '') {
    $detailRows[] = [__('License', 'sage'), $sidebar['license'], ''];
  }
  if ($sidebar['last_updated'] !== '') {
    $detailRows[] = [__('Last updated', 'sage'), $sidebar['last_updated'], ''];
  }
  if ($sidebar['stars'] > 0) {
    $detailRows[] = [__('GitHub stars', 'sage'), (string) $sidebar['stars'], $sidebar['github']];
  }
@endphp

<article @php(post_class($pageClass))>
  @component('partials.page-hero')
    <p class="eyebrow">
      <a class="concept-crumb" href="{{ esc_url($projectsUrl) }}">{{ __('Work', 'sage') }}</a>
      <span aria-hidden="true"> / </span>
      {{ $eyebrow }}
    </p>
    <h1 class="display-title is-hero">{{ $title }}</h1>
    <p class="lead">{{ $summary }}</p>
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
      @if ($isProduct && is_array($product) && ! empty($product['price_html']))
        <span aria-hidden="true"> · </span>
        <span class="concept-price-inline">{!! $product['price_html'] !!}</span>
      @endif
    </p>
    <div class="concept-hero-actions">
      @if ($buyUrl !== '')
        <a class="btn" href="{{ esc_url($buyUrl) }}">
          {!! \App\mh_svg_icon('cart', 16) !!}
          {{ $primaryLabel }}
        </a>
        <a class="btn btn-outline" href="{{ esc_url($useUrl) }}">
          {!! \App\mh_svg_icon('mail', 16) !!}
          {{ $hireLabel }}
        </a>
        @if ($demo !== '')
          <a class="btn btn-outline" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
            {!! \App\mh_svg_icon('globe', 15) !!}
            {{ __('Live demo', 'sage') }} <span aria-hidden="true">↗</span>
          </a>
        @endif
      @else
        <a class="btn" href="{{ esc_url($useUrl) }}">
          {!! \App\mh_svg_icon('mail', 16) !!}
          {{ $hireLabel }}
        </a>
        @if ($demo !== '')
          <a class="btn btn-outline" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">
            {!! \App\mh_svg_icon('globe', 15) !!}
            {{ __('Live demo', 'sage') }} <span aria-hidden="true">↗</span>
          </a>
        @endif
      @endif
      <a class="h-text-arrow" href="{{ esc_url($isProduct && $shopUrl !== '' ? $shopUrl : $projectsUrl) }}">
        {{ $isProduct ? __('Browse shop', 'sage') : $crumbBrowse }} →
      </a>
    </div>
  @endcomponent

  <div class="container wide page-block concept-layout">
    @if ($shot !== '')
      <figure class="concept-shot">
        <img
          src="{{ esc_url($shot) }}"
          alt="{{ esc_attr(sprintf($isTheme ? __('Screenshot of the %s theme', 'sage') : ($isPlugin ? __('Screenshot of the %s plugin', 'sage') : __('Screenshot of the %s concept', 'sage')), $title)) }}"
          width="1200"
          height="675"
          loading="eager"
          decoding="async"
        >
        <figcaption>{{ $shotCaption }}</figcaption>
      </figure>
    @endif

    @if ($sidebar['screenshots'] !== [])
      <div class="concept-gallery" aria-label="{{ __('More screenshots', 'sage') }}">
        @foreach ($sidebar['screenshots'] as $shotRow)
          <figure>
            <img
              src="{{ esc_url($shotRow[0]) }}"
              alt="{{ esc_attr($shotRow[1] !== '' ? $shotRow[1] : sprintf(__('%s screenshot', 'sage'), $title)) }}"
              width="800"
              height="450"
              loading="lazy"
              decoding="async"
            >
            @if ($shotRow[1] !== '')
              <figcaption>{{ $shotRow[1] }}</figcaption>
            @endif
          </figure>
        @endforeach
      </div>
    @endif

    @if ($story['metrics'] !== [])
      <div class="concept-metrics" role="list">
        @foreach ($story['metrics'] as $metric)
          <div class="concept-metric" role="listitem">
            <strong>{{ $metric[0] }}</strong>
            <span>{{ $metric[1] }}</span>
          </div>
        @endforeach
      </div>
    @endif

    <div class="concept-grid">
      <div class="concept-main">
        @if ($story['benefits'] !== [])
          <section class="concept-section" aria-labelledby="concept-benefits">
            <h2 id="concept-benefits">{{ __('Why teams pick it', 'sage') }}</h2>
            <ul class="concept-list concept-list--benefits">
              @foreach ($story['benefits'] as $item)
                <li>{{ $item }}</li>
              @endforeach
            </ul>
          </section>
        @endif

        @if ($story['challenge'] !== '')
          <section class="concept-section" aria-labelledby="concept-challenge">
            <h2 id="concept-challenge">{{ $problemHeading }}</h2>
            <p>{{ $story['challenge'] }}</p>
          </section>
        @endif

        @if ($story['approach'] !== '')
          <section class="concept-section" aria-labelledby="concept-approach">
            <h2 id="concept-approach">{{ $approachHeading }}</h2>
            <p>{{ $story['approach'] }}</p>
          </section>
        @endif

        @if ($story['result'] !== '')
          <section class="concept-section" aria-labelledby="concept-result">
            <h2 id="concept-result">{{ $resultHeading }}</h2>
            <p>{{ $story['result'] }}</p>
          </section>
        @endif

        @if ($story['deliverables'] !== [])
          <section class="concept-section" aria-labelledby="concept-deliverables">
            <h2 id="concept-deliverables">{{ $includedHeading }}</h2>
            <ul class="concept-list">
              @foreach ($story['deliverables'] as $item)
                <li>{{ $item }}</li>
              @endforeach
            </ul>
          </section>
        @endif

        @if ($story['faq'] !== [])
          <section class="concept-section concept-faq" aria-labelledby="concept-faq">
            <h2 id="concept-faq">{{ __('FAQ', 'sage') }}</h2>
            <div class="concept-faq__list">
              @foreach ($story['faq'] as $pair)
                <details class="concept-faq__item">
                  <summary>{{ $pair[0] }}</summary>
                  <p>{{ $pair[1] }}</p>
                </details>
              @endforeach
            </div>
          </section>
        @endif
      </div>

      <aside class="concept-aside" aria-label="{{ $isProduct ? __('Buy details', 'sage') : __('Concept details', 'sage') }}">
        @if ($isProduct && is_array($product))
          <div class="concept-aside-card concept-aside-card--buy">
            <p class="concept-buy-kicker">{{ $isTheme ? __('Theme license', 'sage') : __('Plugin license', 'sage') }}</p>
            <p class="concept-buy-price">{!! $product['price_html'] !!}</p>
            <p class="concept-buy-note">
              @if (! empty($product['is_free']))
                {{ __('Free digital download — checkout captures your email for updates.', 'sage') }}
              @else
                {{ __('One-time purchase · Instant download · Updates via your account', 'sage') }}
              @endif
            </p>
            @if ($buyUrl !== '')
              <a class="btn" href="{{ esc_url($buyUrl) }}">{!! \App\mh_svg_icon('cart', 16) !!} {{ $primaryLabel }}</a>
            @endif
            <a class="btn btn-outline" href="{{ esc_url($useUrl) }}">{!! \App\mh_svg_icon('mail', 16) !!} {{ $hireLabel }}</a>
            @if ($demo !== '')
              <a class="btn btn-outline" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">{{ __('Open live demo', 'sage') }} ↗</a>
            @endif
          </div>
        @endif

        @if ($detailRows !== [] || $sidebar['files_included'] !== [] || $sidebar['docs'] !== [] || $sidebar['github'] !== '')
          <div class="concept-aside-card concept-aside-card--facts">
            <h2 class="concept-aside-title">{{ $isTheme ? __('Theme details', 'sage') : ($isPlugin ? __('Plugin details', 'sage') : __('Concept details', 'sage')) }}</h2>
            @if ($detailRows !== [])
              <dl class="concept-fact-list">
                @foreach ($detailRows as $row)
                  <div class="concept-fact">
                    <dt>{{ $row[0] }}</dt>
                    <dd>
                      @if ($row[2] !== '')
                        <a href="{{ esc_url($row[2]) }}" rel="noopener" target="_blank">{{ $row[1] }}</a>
                      @else
                        {{ $row[1] }}
                      @endif
                    </dd>
                  </div>
                @endforeach
              </dl>
            @endif
            @if ($sidebar['files_included'] !== [])
              <h3 class="concept-aside-subtitle">{{ __('Files included', 'sage') }}</h3>
              <ul class="concept-fact-files">
                @foreach ($sidebar['files_included'] as $file)
                  <li>{{ $file }}</li>
                @endforeach
              </ul>
            @endif
            @if ($sidebar['docs'] !== [])
              <h3 class="concept-aside-subtitle">{{ __('Documentation', 'sage') }}</h3>
              <ul class="concept-fact-docs">
                @foreach ($sidebar['docs'] as $doc)
                  <li><a href="{{ esc_url($doc[1]) }}" rel="noopener" target="_blank">{{ $doc[0] }} <span aria-hidden="true">↗</span></a></li>
                @endforeach
              </ul>
            @elseif ($sidebar['support'] !== '')
              <p class="concept-fact-support">
                <a href="{{ esc_url($sidebar['support']) }}" rel="noopener" target="_blank">{{ __('Support docs', 'sage') }} ↗</a>
              </p>
            @endif
            @if ($sidebar['github'] !== '')
              <p class="concept-fact-github">
                <a href="{{ esc_url($sidebar['github']) }}" rel="noopener" target="_blank">{!! \App\mh_svg_icon('github', 14) !!} {{ __('View on GitHub', 'sage') }}</a>
              </p>
            @endif
          </div>
        @endif

        @if (! empty($tech) || $sidebar['languages'] !== [] || $sidebar['topics'] !== [])
          <div class="concept-aside-card">
            <h2 class="concept-aside-title">{{ __('Stack', 'sage') }}</h2>
            @if (! empty($tech))
              <p class="pill-row">
                @foreach ($tech as $t)
                  <span class="pill">{!! \App\mh_svg_icon($t, 14) !!} {{ $t }}</span>
                @endforeach
              </p>
            @elseif ($sidebar['languages'] !== [])
              <p class="pill-row">
                @foreach ($sidebar['languages'] as $t)
                  <span class="pill">{{ $t }}</span>
                @endforeach
              </p>
            @endif
            @if ($sidebar['topics'] !== [])
              <p class="concept-topic-row">
                @foreach ($sidebar['topics'] as $topic)
                  <span class="concept-topic">{{ $topic }}</span>
                @endforeach
              </p>
            @endif
          </div>
        @endif

        @if ($sidebar['brand_tagline'] !== '' || $sidebar['brand_palette'] !== [])
          <div class="concept-aside-card concept-aside-card--brand">
            <h2 class="concept-aside-title">{{ __('Brand', 'sage') }}</h2>
            @if ($sidebar['brand_tagline'] !== '')
              <p class="concept-brand-tagline">{{ $sidebar['brand_tagline'] }}</p>
            @endif
            @if ($sidebar['brand_palette'] !== [])
              <ul class="concept-brand-palette" aria-label="{{ __('Color palette', 'sage') }}">
                @foreach ($sidebar['brand_palette'] as $swatch)
                  <li>
                    <span class="concept-brand-swatch" style="--swatch: {{ esc_attr($swatch[1]) }}"></span>
                    <span>{{ $swatch[0] }}</span>
                    <code>{{ $swatch[1] }}</code>
                  </li>
                @endforeach
              </ul>
            @endif
          </div>
        @endif

        @if (! $isProduct)
          <div class="concept-aside-card concept-aside-card--cta">
            <h2 class="concept-aside-title">{{ $asideCtaTitle }}</h2>
            <p>{{ __('Tell me which parts fit and what you’d change. I usually reply within a day.', 'sage') }}</p>
            <a class="btn" href="{{ esc_url($useUrl) }}">{!! \App\mh_svg_icon('mail', 16) !!} {{ $hireLabel }}</a>
            @if ($demo !== '')
              <a class="btn btn-outline" href="{{ esc_url($demo) }}" rel="noopener" target="_blank">{{ __('Open live demo', 'sage') }} ↗</a>
            @endif
          </div>
        @else
          <div class="concept-aside-card concept-aside-card--cta">
            <h2 class="concept-aside-title">{{ __('Need it customized?', 'sage') }}</h2>
            <p>
              @if ($isTheme)
                {{ __('Get help to brand this theme for your shop — I usually reply within a day.', 'sage') }}
              @else
                {{ __('Get help to extend this plugin for your stack, or retain support.', 'sage') }}
              @endif
            </p>
            <a class="btn" href="{{ esc_url($useUrl) }}">{!! \App\mh_svg_icon('mail', 16) !!} {{ $hireLabel }}</a>
          </div>
        @endif

        <p class="concept-aside-note">{{ $asideNote }}</p>
      </aside>
    </div>

    @if ($related !== [])
      <section class="concept-related" aria-labelledby="concept-related-heading">
        <div class="concept-related__head">
          <h2 id="concept-related-heading">{{ $relatedHeading }}</h2>
          <a class="h-text-arrow" href="{{ esc_url($projectsUrl) }}">{{ __('Browse all', 'sage') }} →</a>
        </div>
        <div class="concept-related__grid">
          @foreach ($related as $p)
            <article class="concept-related-card">
              @if (! empty($p['image']))
                <a class="concept-related-card__img" href="{{ esc_url($p['url'] ?? \App\mh_concept_page_url((string) ($p['slug'] ?? ''))) }}" aria-hidden="true" tabindex="-1">
                  <img src="{{ esc_url($p['image']) }}" alt="" width="640" height="360" loading="lazy" decoding="async">
                </a>
              @endif
              <div class="concept-related-card__body">
                <p class="pf-meta">{{ $p['cat'] ?? '' }} · {{ $p['place'] ?? '' }}</p>
                <h3><a href="{{ esc_url($p['url'] ?? \App\mh_concept_page_url((string) ($p['slug'] ?? ''))) }}">{{ $p['title'] ?? '' }}</a></h3>
                <p>{{ $p['blurb'] ?? '' }}</p>
              </div>
            </article>
          @endforeach
        </div>
      </section>
    @endif
  </div>

  @include('partials.cta-band', [
    'kicker' => $ctaKicker,
    'title' => $ctaTitle,
    'text' => $ctaText,
    'label' => $ctaLabel,
    'href' => $ctaHref,
    'secondary' => $isProduct ? __('Get help', 'sage') : __('See services', 'sage'),
    'secondaryHref' => $isProduct ? $useUrl : home_url('/services/'),
  ])
</article>
