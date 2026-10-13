{{-- matthummel/faq: core Details blocks inside the theme's question list. --}}
<div class="{{ trim('faq-list mh-block-faq '.($attributes['className'] ?? '')) }}">
  {!! $content !!}
</div>
@if (! empty($attributes['schema']))
  @php
    // FAQPage structured data from the questions above: summary = question, the rest = answer.
    $faqEntities = [];
    preg_match_all('#<summary[^>]*>(.*?)</summary>(.*?)</details>#s', (string) $content, $faqMatches, PREG_SET_ORDER);
    foreach ($faqMatches as $faqMatch) {
        $faqQuestion = trim(html_entity_decode(wp_strip_all_tags($faqMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $faqAnswer = trim(html_entity_decode(wp_strip_all_tags($faqMatch[2], true), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($faqQuestion !== '' && $faqAnswer !== '') {
            $faqEntities[] = ['@type' => 'Question', 'name' => $faqQuestion, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faqAnswer]];
        }
    }
    $faqJsonLd = $faqEntities === [] ? '' : wp_json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqEntities], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
  @endphp
  @if ($faqJsonLd)
    <script type="application/ld+json">{!! $faqJsonLd !!}</script>
  @endif
@endif
