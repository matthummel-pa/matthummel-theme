{{-- Home hero "Code collage" background: decorative code cards, faint topic watermarks, and accent dots. --}}
{{-- Pure markup on purpose (no copy, aria-hidden): the cards are set dressing, not content. Styles: resources/css/hero-collage.css. --}}
<div class="h-hero__media h-collage" aria-hidden="true">
  <div class="h-collage__grid"></div>

  <div class="h-collage__wm h-collage__wm--wp">
    <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="9.5"/><path d="M5.5 8.5l3 9 3-7 3 7 3-9"/></svg>WordPress
  </div>
  <div class="h-collage__wm h-collage__wm--seo">
    <svg viewBox="0 0 24 24" focusable="false"><circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L21 21"/></svg>SEO
  </div>
  <div class="h-collage__wm h-collage__wm--ai">
    <svg viewBox="0 0 24 24" focusable="false"><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 16l.7 2.3L22 19l-2.3.7L19 22l-.7-2.3L16 19l2.3-.7z"/></svg>AI
  </div>
  <div class="h-collage__wm h-collage__wm--tw">
    <svg viewBox="0 0 24 24" focusable="false"><path d="M3 8c3 0 3-3 6-3s3 3 6 3 3-3 6-3"/><path d="M3 16c3 0 3-3 6-3s3 3 6 3 3-3 6-3"/></svg>Tailwind
  </div>
  <div class="h-collage__wm h-collage__wm--sage">
    <svg viewBox="0 0 24 24" focusable="false"><path d="M20 4c-9 0-15 5-15 13 0 1 .2 2 .5 3C9 14 13 10 17 8c-3 3-6 7-8 12 7 .5 11-5 11-16z"/></svg>Sage
  </div>

  <div class="h-collage__card h-collage__card--php is-cyan">
    <div class="h-collage__bar"><i></i><i></i><i></i><span>functions.php</span></div>
    <div><span class="t-fn">add_action</span><span class="t-pl">( </span><span class="t-str">'init'</span><span class="t-pl">, </span><span class="t-kw">fn</span><span class="t-pl">() =&gt;</span></div>
    <div>&nbsp;&nbsp;<span class="t-fn">ship_it</span><span class="t-pl">(</span><span class="t-str">'fast'</span><span class="t-pl">)</span></div>
    <div><span class="t-pl">);</span></div>
  </div>

  <div class="h-collage__card h-collage__card--blade is-lime">
    <div class="h-collage__bar"><i></i><i></i><i></i><span>single.blade.php</span></div>
    <div><span class="t-at">@@extends</span><span class="t-pl">(</span><span class="t-str">'layouts.app'</span><span class="t-pl">)</span></div>
    <div><span class="t-at">@@section</span><span class="t-pl">(</span><span class="t-str">'content'</span><span class="t-pl">)</span></div>
    <div>&nbsp;&nbsp;<span class="t-pl">&lt;h1&gt;</span><span class="t-kw">&#123;&#123; $title &#125;&#125;</span><span class="t-pl">&lt;/h1&gt;</span></div>
    <div><span class="t-at">@@endsection</span></div>
  </div>

  <div class="h-collage__card h-collage__card--json is-blue is-optional">
    <div class="h-collage__bar"><i></i><i></i><i></i><span>block.json</span></div>
    <div><span class="t-pl">{</span></div>
    <div>&nbsp;&nbsp;<span class="t-fn">"name"</span><span class="t-pl">: </span><span class="t-str">"matthummel/hero"</span><span class="t-pl">,</span></div>
    <div>&nbsp;&nbsp;<span class="t-fn">"apiVersion"</span><span class="t-pl">: </span><span class="t-kw">3</span></div>
    <div><span class="t-pl">}</span></div>
  </div>

  <div class="h-collage__card h-collage__card--term is-lime">
    <div class="h-collage__bar"><i></i><i></i><i></i><span>terminal</span></div>
    <div><span class="t-pr">$</span><span class="t-pl">npm run build</span></div>
    <div><span class="t-ok">✓</span> <span class="t-pl">built in </span><span class="t-kw">1.2s</span></div>
    <div><span class="t-pr">$</span><span class="t-pl">wp theme activate</span> <span class="t-str">matthummel</span></div>
  </div>

  <div class="h-collage__card h-collage__card--css is-cyan">
    <div class="h-collage__bar"><i></i><i></i><i></i><span>app.css</span></div>
    <div><span class="t-at">@@import</span> <span class="t-str">"tailwindcss"</span><span class="t-pl">;</span></div>
    <div><span class="t-at">@@theme</span> <span class="t-pl">{</span></div>
    <div>&nbsp;&nbsp;<span class="t-fn">--color-navy</span><span class="t-pl">: </span><span class="t-kw">#0a1c3a</span><span class="t-pl">;</span></div>
    <div><span class="t-pl">}</span></div>
  </div>

  <div class="h-collage__card h-collage__card--rest is-blue is-optional">
    <div class="h-collage__bar"><i></i><i></i><i></i><span>rest</span></div>
    <div><span class="t-kw">GET</span> <span class="t-pl">/wp/v2/posts</span></div>
    <div><span class="t-pl">?per_page=</span><span class="t-kw">5</span></div>
    <div><span class="t-ok">200 OK</span> <span class="t-cm">· 48 ms</span></div>
  </div>

  <div class="h-collage__card h-collage__card--git is-lime">
    <div class="h-collage__bar"><i></i><i></i><i></i><span>git</span></div>
    <div><span class="t-pr">$</span><span class="t-pl">git commit -m </span><span class="t-str">"feat: ship it"</span></div>
    <div><span class="t-pr">$</span><span class="t-pl">git push origin main</span><span class="t-cur"></span></div>
  </div>

  <div class="h-collage__card h-collage__card--js is-cyan">
    <div class="h-collage__bar"><i></i><i></i><i></i><span>app.js</span></div>
    <div><span class="t-kw">import</span> <span class="t-pl">{ </span><span class="t-fn">initNav</span><span class="t-pl"> } </span><span class="t-kw">from</span> <span class="t-str">'./nav'</span></div>
    <div><span class="t-fn">initNav</span><span class="t-pl">({ root: </span><span class="t-str">'[data-nav]'</span><span class="t-pl"> })</span></div>
  </div>

  <div class="h-collage__card h-collage__card--view is-blue is-optional">
    <div class="h-collage__bar"><i></i><i></i><i></i><span>hero.blade.php</span></div>
    <div><span class="t-pl">&lt;section </span><span class="t-fn">class</span><span class="t-pl">=</span><span class="t-str">"grid gap-6"</span><span class="t-pl">&gt;</span></div>
    <div>&nbsp;&nbsp;<span class="t-kw">&#123;!! $content !!&#125;</span></div>
    <div><span class="t-pl">&lt;/section&gt;</span></div>
  </div>

  <div class="h-collage__card h-collage__card--today is-lime">
    <div class="h-collage__bar h-collage__bar--label">Today</div>
    <ul class="h-collage__list">
      <li><span class="h-collage__check"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 12l5 5L20 6"/></svg></span>Build it</li>
      <li><span class="h-collage__check"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 12l5 5L20 6"/></svg></span>Break it</li>
      <li><span class="h-collage__check"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 12l5 5L20 6"/></svg></span>Ship it</li>
    </ul>
  </div>

  <span class="h-collage__dot h-collage__dot--1"></span>
  <span class="h-collage__dot h-collage__dot--2"></span>
  <span class="h-collage__dot h-collage__dot--3"></span>
  <span class="h-collage__dot h-collage__dot--4"></span>
</div>
