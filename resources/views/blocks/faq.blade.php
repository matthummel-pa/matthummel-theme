{{-- matthummel/faq: core Details blocks inside the theme's question list. --}}
<div class="{{ trim('faq-list mh-block-faq '.($attributes['className'] ?? '')) }}">
  {!! $content !!}
</div>
