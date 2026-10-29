{{-- Product type tabs and the product count, shown right above the first set of product cards.
     Expects $tabs (list of [key, label, url]), $scope and $count. Plain links, no script: each view is its own page,
     so it works without JavaScript and can be shared. --}}
<nav class="shop-tabs" aria-label="{{ __('Product types', 'sage') }}">
  <ul>
    @foreach ($tabs as [$key, $label, $url])
      <li>
        <a class="btn{{ $key === $scope ? '' : ' btn-outline' }}" href="{{ esc_url($url) }}"@if ($key === $scope) aria-current="page"@endif>{{ $label }}</a>
      </li>
    @endforeach
  </ul>
  <p class="shop-tabs__count">{{ sprintf(_n('%d product', '%d products', $count, 'sage'), $count) }}</p>
</nav>
