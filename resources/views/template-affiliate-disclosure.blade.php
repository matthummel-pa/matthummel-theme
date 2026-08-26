{{--
  Template Name: Affiliate Disclosure
--}}

@extends('layouts.app')

@section('content')
  <article class="container narrow legal-page">
    <header class="pf-hero compact">
      <p class="eyebrow">Transparency</p>
      <h1>Affiliate Disclosure</h1>
      <p class="lede">How recommendations and compensated links work on MattHummel.com.</p>
    </header>

    <div class="post-prose">
      <p>Some articles on this site may include affiliate links. If you follow one of those links and make a purchase, I may earn a commission at no additional cost to you.</p>

      <h2>How I choose what to recommend</h2>
      <p>Payment does not determine whether a product is included or how it is evaluated. I aim to recommend tools that fit the specific use case described, and I will state when an evaluation is based on research rather than firsthand use.</p>

      <h2>How affiliate content is labeled</h2>
      <p>Posts containing compensated links include a disclosure near the beginning of the article. Individual compensated links are also marked in the page code as sponsored.</p>

      <h2>Prices, features, and results</h2>
      <p>Products change. Check the provider’s current pricing, terms, and documentation before purchasing. Any performance result or comparison should be understood in the context of the test described; your results may differ.</p>

      <h2>Questions</h2>
      <p>If you have a question about a recommendation or a relationship mentioned on this site, <a href="{{ home_url('/contact/') }}">send me a note</a>.</p>

      <p><small>Last updated August 26, 2026.</small></p>
    </div>
  </article>
@endsection
