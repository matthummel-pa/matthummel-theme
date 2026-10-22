{{-- matthummel/faq: core Details blocks inside the theme's question list.
     FAQPage structured data for these questions is part of the page's one
     JSON-LD graph (MH SEO, class-schema.php), so this view prints none. --}}
<div class="{{ trim('faq-list mh-block-faq '.($attributes['className'] ?? '')) }}">
  {!! $content !!}
</div>
