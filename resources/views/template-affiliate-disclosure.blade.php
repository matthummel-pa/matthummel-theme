{{--
  Template Name: Affiliate Disclosure
--}}

@extends('layouts.app')

@section('content')
  <article class="container narrow legal-page">
    <header class="pf-hero compact">
      <p class="eyebrow">Transparency</p>
      <h1>Affiliate Disclosure</h1>
      <p class="lede">How recommendations and compensated links work on MattHummel.com — Journal, Uses, Resources, and elsewhere.</p>
    </header>

    <div class="post-prose">
      <p>Some pages on this site include affiliate links. If you follow one of those links and make a purchase, I may earn a commission at no additional cost to you.</p>

      <h2>Where affiliate links appear</h2>
      <p>Compensated links may show up in Journal posts, on <a href="{{ home_url('/uses/') }}">Uses</a>, on <a href="{{ home_url('/resources/') }}">Resources</a>, and in related tool lists. This site stays a hireable portfolio first — themes for sale and tool recommendations are secondary.</p>

      <h2>How I choose what to recommend</h2>
      <p>Payment does not determine whether a product is included or how it is evaluated. I aim to recommend tools that fit the specific use case described, and I will state when an evaluation is based on research rather than firsthand use.</p>

      <h2>How affiliate content is labeled</h2>
      <p>Pages with compensated links include a clear disclosure near the top. Individual compensated links are marked with an Affiliate label and use a sponsored <code>rel</code> attribute in the markup.</p>

      <h2>Prices, features, and results</h2>
      <p>Products change. Check the provider’s current pricing, terms, and documentation before purchasing. Any performance result or comparison should be understood in the context of the test described; your results may differ.</p>

      <h2>Questions</h2>
      <p>If you have a question about a recommendation or a relationship mentioned on this site, <a href="{{ home_url('/contact/') }}">send me a note</a>. For work, start on <a href="{{ home_url('/hire/') }}">Hire</a>.</p>

      <p><small>Last updated September 1, 2026.</small></p>
    </div>
  </article>
@endsection
