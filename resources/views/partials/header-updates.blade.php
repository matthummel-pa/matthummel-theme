{{--
  Header call to action: the Get updates pill plus a small preview of what a
  subscriber gets. Hover opens the preview on a fine pointer; the chevron
  button toggles it for keyboard and touch (aria-expanded, Escape closes).
  Below 900px only the pill shows. Logic lives in resources/js/updates-preview.js.
--}}
@php
  $updatesUrl = home_url('/get-updates/');
  $previewItems = [
    ['icon' => 'pen', 'title' => __('New posts', 'sage'), 'text' => __('A short note when something is up.', 'sage')],
    ['icon' => 'wordpress', 'title' => __('Release notes', 'sage'), 'text' => __('What to test before you update a client site.', 'sage')],
    ['icon' => 'check', 'title' => __('Checklists', 'sage'), 'text' => __('The handoff checklist comes with the welcome note.', 'sage')],
  ];
@endphp
<div class="header-updates" data-updates>
  <a class="header-avail header-updates__link" href="{{ esc_url($updatesUrl) }}">
    <span class="header-updates__icon" aria-hidden="true">{!! \App\mh_svg_icon('mail', 16) !!}</span>
    <span class="header-avail__label">{{ __('Get updates', 'sage') }}</span>
  </a>
  <button
    type="button"
    class="header-updates__toggle"
    aria-expanded="false"
    aria-controls="header-updates-panel"
    aria-label="{{ esc_attr__('Preview what you get', 'sage') }}"
  >
    <svg class="header-updates__chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
  </button>
  <div id="header-updates-panel" class="header-updates__panel" hidden>
    <p class="header-updates__kicker">{{ __('What you get', 'sage') }}</p>
    <ul class="header-updates__list">
      @foreach ($previewItems as $item)
        <li class="header-updates__item">
          <span class="header-updates__ico" aria-hidden="true">{!! \App\mh_svg_icon($item['icon'], 16) !!}</span>
          <span class="header-updates__copy">
            <strong class="header-updates__title">{{ $item['title'] }}</strong>
            <span class="header-updates__text">{{ $item['text'] }}</span>
          </span>
        </li>
      @endforeach
    </ul>
    <p class="header-updates__foot">{{ __('A few a month. Unsubscribe from any email.', 'sage') }}</p>
    <a class="header-updates__cta" href="{{ esc_url($updatesUrl) }}">{{ __('Sign up', 'sage') }}</a>
  </div>
</div>
