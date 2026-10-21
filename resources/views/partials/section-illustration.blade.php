{{--
  Decorative line-art for home page sections (3.7.25). Inline SVG so each
  drawing can animate its own parts; colors come from CSS custom properties
  (resources/css/illustrations.css) so one markup serves light and dark.
  aria-hidden: purely decorative, never announced. Renders nothing for
  anchors it does not know.
--}}
@php
  $anchor = (string) ($anchor ?? '');
  $id = 'mh-il-'.$anchor;
  $g = "url(#{$id}-g)";
  $v = "url(#{$id}-v)";
@endphp
@if (in_array($anchor, ['inside', 'journal-posts', 'newsletter', 'uses', 'write', 'now', 'questions'], true))
<svg class="mh-illus mh-illus--{{ $anchor }}" viewBox="0 0 360 250" preserveAspectRatio="{{ $anchor === 'questions' ? 'xMinYMax' : 'xMaxYMin' }} meet" fill="none" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="{{ $id }}-g" x1="0" y1="0" x2="1" y2="0"><stop class="il-s1" offset="0"/><stop class="il-s2" offset=".55"/><stop class="il-s3" offset="1"/></linearGradient>
    <linearGradient id="{{ $id }}-v" x1="0" y1="0" x2="1" y2="1"><stop class="il-s1" offset="0"/><stop class="il-s3" offset="1"/></linearGradient>
  </defs>
  <g class="il" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
  @switch($anchor)
    @case('inside')
      {{-- Browser window: chart draws in, status dots blink, code card types. --}}
      <rect class="il-card" x="20.75" y="24.75" width="250" height="180" rx="14" stroke="{{ $g }}"/>
      <path d="M20.75 52.75h250" stroke="{{ $g }}" stroke-opacity=".4"/>
      <circle class="il-fa il-blink" cx="37" cy="39" r="3.5"/>
      <circle class="il-fb il-blink il-d2" cx="49" cy="39" r="3.5" fill-opacity=".8"/>
      <circle class="il-fc il-blink il-d3" cx="61" cy="39" r="3.5" fill-opacity=".8"/>
      <rect class="il-bar" x="80" y="35.5" width="150" height="7" rx="3.5"/>
      <rect class="il-b" x="36.75" y="66.75" width="218" height="72" rx="8" stroke-opacity=".5"/>
      <path class="il-draw il-draw--chart" d="M48 128l34-30 22 18 26-26 46 38" pathLength="1" stroke="{{ $g }}" stroke-opacity=".8"/>
      <circle class="il-c il-ring" cx="214" cy="88" r="9" stroke-opacity=".8"/>
      <rect class="il-fb il-type" x="37" y="152" width="140" height="6" rx="3"/>
      <rect class="il-bar il-type il-d2" x="37" y="166" width="170" height="5" rx="2.5"/>
      <rect class="il-fc il-pulse" x="37" y="180" width="64" height="14" rx="7"/>
      <g class="il-float il-d3">
        <rect class="il-card" x="214.75" y="128.75" width="122" height="96" rx="12" stroke="{{ $v }}"/>
        <path class="il-b il-draw il-draw--code" d="M240 160l-10 10 10 10M262 160l10 10-10 10M256 156l-10 28" pathLength="1" stroke-width="2"/>
        <rect class="il-bar il-type il-d3" x="286" y="160" width="36" height="5" rx="2.5"/>
        <rect class="il-bar il-type il-d4" x="286" y="172" width="28" height="5" rx="2.5"/>
        <rect class="il-bar il-type il-d5" x="230" y="198" width="90" height="5" rx="2.5"/>
      </g>
      <path class="il-c il-twinkle" d="M318 35v10M313 40h10"/>
      <path class="il-b il-twinkle il-d3" d="M300 88.5v7M296.5 92h7"/>
      <circle class="il-fa il-blink il-d4" cx="8" cy="150" r="2"/>
      <circle class="il-a il-twinkle il-d2" cx="340" cy="112" r="2.5"/>
      @break

    @case('journal-posts')
      {{-- Two stacked posts: back card sways, front card floats, pencil writes. --}}
      <g transform="rotate(6 172 114)"><rect class="il-card il-sway" x="72.75" y="14.75" width="200" height="200" rx="14" stroke="{{ $g }}" stroke-opacity=".35"/></g>
      <g class="il-float">
        <rect class="il-card" x="40.75" y="28.75" width="200" height="208" rx="14" stroke="{{ $g }}"/>
        <rect class="il-b" x="56.75" y="44.75" width="168" height="70" rx="8" stroke-opacity=".5"/>
        <path class="il-draw il-draw--chart" d="M80 92l16-14 16 14M124 92l-8 8M128 70h40M128 80h28" pathLength="1" stroke="{{ $g }}" stroke-opacity=".8"/>
        <rect class="il-b il-pulse" x="57" y="128" width="54" height="12" rx="6" stroke-opacity=".7"/>
        <rect class="il-fb il-type" x="57" y="152" width="150" height="6" rx="3"/>
        <rect class="il-bar il-type il-d2" x="57" y="166" width="165" height="5" rx="2.5"/>
        <rect class="il-bar il-type il-d3" x="57" y="178" width="140" height="5" rx="2.5"/>
        <rect class="il-bar il-type il-d4" x="57" y="190" width="120" height="5" rx="2.5"/>
        <path class="il-c" d="M57 214h40"/>
        <path class="il-c il-nudge" d="M90 209l7 5-7 5"/>
      </g>
      <g class="il-pencil">
        <path class="il-card" d="M300 40l26 26-78 78-32 8 8-32z" stroke="{{ $v }}"/>
        <path d="M290 50l26 26M224 120l26 26" stroke="{{ $v }}" stroke-opacity=".6"/>
        <path class="il-c" d="M216 152l8-8" stroke-width="2.5"/>
      </g>
      <path class="il-c il-twinkle" d="M318 145v10M313 150h10"/>
      <path class="il-b il-twinkle il-d3" d="M20 56.5v7M16.5 60h7"/>
      <circle class="il-a il-twinkle il-d2" cx="300" cy="200" r="2.5"/>
      <circle class="il-fa il-blink il-d4" cx="16" cy="180" r="2"/>
      @break

    @case('newsletter')
      {{-- A note rises out of the envelope, the check pops, then it settles back. --}}
      <g class="il-letter">
        <rect class="il-card" x="94.75" y="22.75" width="170" height="130" rx="10" stroke="{{ $g }}"/>
        <rect class="il-fb il-type" x="112" y="42" width="90" height="6" rx="3"/>
        <rect class="il-bar il-type il-d2" x="112" y="58" width="130" height="5" rx="2.5"/>
        <rect class="il-bar il-type il-d3" x="112" y="70" width="120" height="5" rx="2.5"/>
        <rect class="il-bar il-type il-d4" x="112" y="82" width="100" height="5" rx="2.5"/>
        <rect class="il-fc il-pulse" x="112" y="98" width="50" height="12" rx="6"/>
      </g>
      <path class="il-card" d="M60.75 110.75h238v108a14 14 0 0 1-14 14h-210a14 14 0 0 1-14-14z" stroke="{{ $g }}"/>
      <path d="M60.75 112l119 72 119-72" stroke="{{ $g }}"/>
      <path d="M62 230l84-66M298 230l-84-66" stroke="{{ $g }}" stroke-opacity=".45"/>
      <g class="il-pop">
        <circle class="il-fc" cx="292" cy="108" r="13"/>
        <path class="il-draw il-draw--tick" d="M286.5 108l4 4 7-8" pathLength="1" stroke="#fff" stroke-width="2"/>
      </g>
      <path class="il-b il-whoosh" d="M30 70l18 4M28 92h16M34 112l14-6" stroke-opacity=".7"/>
      <path class="il-b il-twinkle" d="M326 53v10M321 58h10"/>
      <path class="il-c il-twinkle il-d3" d="M40 166.5v7M36.5 170h7"/>
      <circle class="il-a il-twinkle il-d2" cx="330" cy="200" r="2.5"/>
      <circle class="il-fa il-blink il-d4" cx="76" cy="30" r="2"/>
      @break

    @case('uses')
      {{-- Stacked tiers of the stack float at staggered rhythms. --}}
      <g class="il-tier il-tier--1">
        <path class="il-card" d="M180 130l110 46-110 46-110-46z" stroke="{{ $g }}" stroke-opacity=".4"/>
        <path d="M70 176v14l110 46 110-46v-14" stroke="{{ $g }}" stroke-opacity=".3"/>
      </g>
      <g class="il-tier il-tier--2">
        <path class="il-card" d="M180 92l110 46-110 46-110-46z" stroke="{{ $g }}" stroke-opacity=".65"/>
        <path d="M70 138v10l110 46 110-46v-10" stroke="{{ $g }}" stroke-opacity=".45"/>
      </g>
      <g class="il-tier il-tier--3">
        <path class="il-card" d="M180 46l110 46-110 46-110-46z" stroke="{{ $g }}"/>
        <path d="M70 92v10l110 46 110-46v-10" stroke="{{ $g }}" stroke-opacity=".7"/>
        <path class="il-fc il-pulse" d="M150 80l30-12 30 12-30 12z"/>
        <path class="il-b il-blink il-d2" d="M196 94l22-9 22 9-22 9z"/>
        <path class="il-b il-blink il-d3" d="M120 94l22-9 22 9-22 9z" stroke-opacity=".7"/>
      </g>
      <path class="il-b il-type" d="M300 60h34M300 72h22" stroke-opacity=".7"/>
      <path class="il-a il-type il-d3" d="M300 152h30M300 164h20" stroke-opacity=".6"/>
      <path class="il-c il-type il-d2" d="M24 118h30M24 130h20" stroke-opacity=".6"/>
      <path class="il-c il-twinkle" d="M330 105v10M325 110h10"/>
      <path class="il-b il-twinkle il-d3" d="M36 48.5v7M32.5 52h7"/>
      <circle class="il-a il-twinkle il-d2" cx="40" cy="200" r="2.5"/>
      <circle class="il-fa il-blink il-d4" cx="320" cy="214" r="2"/>
      @break

    @case('write')
      {{-- Paper plane glides on its line, the trail streams, the reply bubble types. --}}
      <g class="il-plane">
        <path class="il-card" d="M330 30l-196 70 66 26 22 66 108-162z" stroke="{{ $v }}"/>
        <path d="M330 30l-130 96M222 192l14-50 94-112" stroke="{{ $v }}" stroke-opacity=".7"/>
        <path d="M200 126l36 16" stroke="{{ $v }}" stroke-opacity=".5"/>
      </g>
      <path class="il-b il-trail" d="M150 128c-40 20-70 14-82-6s6-40 26-30-2 50-34 66" stroke-opacity=".7" stroke-dasharray="2 7"/>
      <g class="il-float il-d2">
        <path class="il-card" d="M30.75 176.75h84a12 12 0 0 1 12 12v24a12 12 0 0 1-12 12h-56l-14 12v-12h-14a12 12 0 0 1-12-12v-24a12 12 0 0 1 12-12z" stroke="{{ $g }}"/>
        <circle class="il-fa il-typing" cx="56" cy="200.5" r="3.5"/>
        <circle class="il-fb il-typing il-d2" cx="72" cy="200.5" r="3.5"/>
        <circle class="il-fc il-typing il-d3" cx="88" cy="200.5" r="3.5"/>
      </g>
      <path class="il-c il-twinkle" d="M270 195v10M265 200h10"/>
      <path class="il-b il-twinkle il-d3" d="M110 36.5v7M106.5 40h7"/>
      <circle class="il-a il-twinkle il-d2" cx="300" cy="150" r="2.5"/>
      <circle class="il-fa il-blink il-d4" cx="20" cy="110" r="2"/>
      @break

    @case('now')
      {{-- Calendar: today hops along the grid. Clock: hands keep real time ratios. --}}
      <rect class="il-card" x="30.75" y="34.75" width="210" height="190" rx="14" stroke="{{ $g }}"/>
      <path d="M30.75 70.75h210" stroke="{{ $g }}" stroke-opacity=".4"/>
      <path d="M70 26v18M200 26v18" stroke="{{ $g }}" stroke-width="3"/>
      <rect class="il-fb il-type" x="46" y="49" width="80" height="7" rx="3.5"/>
      @foreach ([86, 114, 142, 170] as $row)
        @foreach ([46, 76, 106, 136, 166, 196] as $col)
          <rect class="il-bar" x="{{ $col }}" y="{{ $row }}" width="20" height="16" rx="4"/>
        @endforeach
      @endforeach
      <rect class="il-b il-blink" x="76.75" y="86.75" width="18.5" height="14.5" rx="4" stroke-opacity=".8"/>
      <rect class="il-b il-blink il-d2" x="166.75" y="142.75" width="18.5" height="14.5" rx="4" stroke-opacity=".8"/>
      <rect class="il-b il-blink il-d3" x="106.75" y="170.75" width="18.5" height="14.5" rx="4" stroke-opacity=".8"/>
      <rect class="il-fc il-today" x="136" y="114" width="20" height="16" rx="4"/>
      <circle class="il-card" cx="262" cy="170" r="50" stroke="{{ $v }}"/>
      <circle cx="262" cy="170" r="40" stroke="{{ $v }}" stroke-opacity=".35"/>
      <path class="il-b" d="M262 124v5M262 211v5M216 170h5M303 170h5"/>
      <path class="il-c il-hand il-hand--min" d="M262 142v28" stroke-width="2.5"/>
      <path class="il-c il-hand il-hand--hour" d="M262 170l18 12" stroke-width="2.5"/>
      <circle class="il-fc" cx="262" cy="170" r="3"/>
      <path class="il-c il-twinkle" d="M310 45v10M305 50h10"/>
      <path class="il-b il-twinkle il-d3" d="M290 88.5v7M286.5 92h7"/>
      <circle class="il-fa il-blink il-d4" cx="14" cy="120" r="2"/>
      <circle class="il-a il-twinkle il-d2" cx="336" cy="232" r="2.5"/>
      @break

    @case('questions')
      {{-- Accordion rows: chevrons flip open in turn, the open answer types, the "?" bubble floats. --}}
      <g class="il-row il-row--1">
        <rect class="il-card" x="20.75" y="30.75" width="250" height="40" rx="12" stroke="{{ $g }}" stroke-opacity=".55"/>
        <rect class="il-bar" x="36" y="47" width="120" height="6" rx="3"/>
        <rect class="il-b" x="236" y="40" width="22" height="22" rx="6" stroke-opacity=".6"/>
        <path class="il-b il-chev" d="M242.5 49l4.5 4.5 4.5-4.5" stroke-opacity=".8"/>
      </g>
      <rect class="il-card" x="20.75" y="84.75" width="250" height="96" rx="12" stroke="{{ $g }}"/>
      <rect class="il-fb il-type" x="36" y="101" width="150" height="6" rx="3" fill-opacity=".85"/>
      <rect class="il-fc il-pulse" x="236" y="94" width="22" height="22" rx="6"/>
      <path class="il-chev-open" d="M242.5 107.5l4.5-4.5 4.5 4.5" stroke-width="2"/>
      <path d="M36 127.25h220" stroke="{{ $g }}" stroke-opacity=".35"/>
      <rect class="il-bar il-type il-d2" x="36" y="139" width="205" height="5" rx="2.5"/>
      <rect class="il-bar il-type il-d3" x="36" y="151" width="185" height="5" rx="2.5"/>
      <rect class="il-bar il-type il-d4" x="36" y="163" width="120" height="5" rx="2.5"/>
      <g class="il-row il-row--3">
        <rect class="il-card" x="20.75" y="194.75" width="250" height="40" rx="12" stroke="{{ $g }}" stroke-opacity=".55"/>
        <rect class="il-bar" x="36" y="211" width="100" height="6" rx="3"/>
        <rect class="il-b" x="236" y="204" width="22" height="22" rx="6" stroke-opacity=".6"/>
        <path class="il-b il-chev il-d3" d="M242.5 213l4.5 4.5 4.5-4.5" stroke-opacity=".8"/>
      </g>
      <g class="il-float il-d2">
        <path class="il-card" d="M286 14h52a12 12 0 0 1 12 12v40a12 12 0 0 1-12 12h-30l-14 13v-13h-8a12 12 0 0 1-12-12V26a12 12 0 0 1 12-12z" stroke="{{ $v }}"/>
        <path class="il-draw il-draw--chart" d="M303 37a9 9 0 1 1 13.5 7.8c-2.6 1.5-4.5 3.2-4.5 6.2v2" pathLength="1" stroke="{{ $v }}" stroke-width="3"/>
        <circle class="il-fc il-blink" cx="312" cy="62" r="2.4"/>
      </g>
      <path class="il-c il-twinkle" d="M300 122v10M295 127h10"/>
      <path class="il-b il-twinkle il-d3" d="M326 160v7M322.5 163.5h7" stroke-opacity=".8"/>
      <circle class="il-fa il-blink il-d4" cx="8" cy="132" r="2"/>
      <circle class="il-a il-twinkle il-d2" cx="296" cy="208" r="2.5"/>
      @break
  @endswitch
  </g>
</svg>
@endif
