{{-- matthummel/faq: core Details blocks inside the theme's question list.
     This view prints no structured data; the SEO plugin owns the page's JSON-LD. --}}
<div class="{{ trim('faq-list mh-block-faq '.($attributes['className'] ?? '')) }}">
  {!! $content !!}
</div>
