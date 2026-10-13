{{-- matthummel/affiliate-note: the disclosure, printed only while the catalog or the stack list holds a compensated link. --}}
@if (\App\mh_resources_have_affiliate_links())
  @include('partials.affiliate-note', ['class' => trim('mh-affiliate-note '.($attributes['className'] ?? ''))])
@endif
