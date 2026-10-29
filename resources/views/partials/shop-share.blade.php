{{-- Docs to read first, and ways to share the page. Expects $docGroups (list of [product title, list of [label, url]]),
     $share (from \App\mh_post_share_urls()), $docsHeading and $shareHeading. Uses the post share buttons. --}}
<div class="shop-extras">
  @if ($docGroups !== [])
    <div class="shop-extras__card">
      <h3 class="shop-extras__title">{{ $docsHeading }}</h3>
      <ul class="shop-docs">
        @foreach ($docGroups as [$productTitle, $links])
          <li>
            <p class="shop-docs__product">{{ $productTitle }}</p>
            <p class="shop-card__links">
              @foreach ($links as [$label, $url])
                @continue(preg_match('/brand kit/i', $label))
                <a class="shop-link" href="{{ esc_url($url) }}" rel="noopener" target="_blank">
                  {{ $label }}<span class="visually-hidden">{{ sprintf(__(' for %s (opens in a new window)', 'sage'), $productTitle) }}</span>
                  <span class="shop-link__icon" aria-hidden="true">↗</span>
                </a>
              @endforeach
            </p>
          </li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="shop-extras__card">
    <h3 class="shop-extras__title">{{ $shareHeading }}</h3>
    <div class="shop-share">
      <a class="post-share-btn" href="{{ esc_url($share['bluesky']) }}" rel="noopener" target="_blank" aria-label="{{ __('Share on Bluesky (opens in a new window)', 'sage') }}">
        {!! \App\mh_svg_icon('bluesky', 15) !!} Bluesky
      </a>
      <a class="post-share-btn" href="{{ esc_url($share['linkedin']) }}" rel="noopener" target="_blank" aria-label="{{ __('Share on LinkedIn (opens in a new window)', 'sage') }}">
        {!! \App\mh_svg_icon('linkedin', 15) !!} LinkedIn
      </a>
      <a class="post-share-btn" href="{{ esc_url($share['facebook']) }}" rel="noopener" target="_blank" aria-label="{{ __('Share on Facebook (opens in a new window)', 'sage') }}">
        {!! \App\mh_svg_icon('facebook', 15) !!} Facebook
      </a>
      <a class="post-share-btn" href="{{ esc_url($share['reddit']) }}" rel="noopener" target="_blank" aria-label="{{ __('Share on Reddit (opens in a new window)', 'sage') }}">
        {!! \App\mh_svg_icon('reddit', 15) !!} Reddit
      </a>
      <a class="post-share-btn" href="{{ esc_url('mailto:?subject='.rawurlencode($share['title']).'&body='.rawurlencode($share['url'])) }}">
        {!! \App\mh_svg_icon('mail', 15) !!} {{ __('Email', 'sage') }}
      </a>
      <button class="post-share-btn post-copy-link" type="button" data-copy="{{ esc_attr($share['url']) }}" aria-label="{{ __('Copy link', 'sage') }}">
        {!! \App\mh_svg_icon('share', 15) !!} <span>{{ __('Copy link', 'sage') }}</span>
      </button>
    </div>
  </div>
</div>
