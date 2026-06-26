<?php

/**
 * Block patterns — matthummel theme.
 *
 * All patterns use design-language.css classes + mh/* blocks.
 * Design language: dark ink heroes, bento stats, monospace accents,
 * availability badge, minimal green accent. 2025-26 developer site trends.
 */

namespace App;

add_action('init', function () {

    register_block_pattern_category('matthummel', [
        'label'       => __('Matthummel', 'matthummel'),
        'description' => __('Pre-built layouts for the matthummel.com theme.', 'matthummel'),
    ]);

    /* ── 1. Hero — dark with availability badge ────────────────────── */
    register_block_pattern('matthummel/hero', [
        'title'       => __('Hero — dark with badge', 'matthummel'),
        'description' => __('Full-width ink hero: availability badge, oversized headline, lead, terminal code snippet, and two CTA buttons.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['hero', 'home', 'intro', 'dark', 'badge'],
        'content'     => '<!-- wp:mh/section {"bgColor":"ink","paddingTop":"xl","paddingBottom":"xl","containerWidth":"contained","textColor":"light"} -->
<!-- wp:paragraph {"className":"badge-available"} --><p class="badge-available">Available for projects</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"display-xl"} --><h1 class="wp-block-heading display-xl">I build things<br>for the web.</h1><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lead"} --><p class="lead">WordPress developer &amp; Power Platform specialist. Clean code, accessible interfaces, real results.</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"code-accent"} --><p class="code-accent">npm create matthummel-theme</p><!-- /wp:paragraph -->
<!-- wp:buttons {"style":{"spacing":{"blockGap":"12px"}}} -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/projects/">View work</a></div><!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/blog/">Read blog →</a></div><!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 2. Hero split — text + terminal box ──────────────────────── */
    register_block_pattern('matthummel/hero-split', [
        'title'       => __('Hero split — text + terminal', 'matthummel'),
        'description' => __('Two-column dark hero: intro text left, terminal box right.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['hero', 'terminal', 'split', 'about', 'dark'],
        'content'     => '<!-- wp:mh/section {"bgColor":"ink","paddingTop":"xl","paddingBottom":"xl","containerWidth":"contained","textColor":"light"} -->
<!-- wp:columns {"isStackedOnMobile":true,"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"width":"60%"} -->
<div class="wp-block-column" style="flex-basis:60%">
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">About me</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"display-lg"} --><h1 class="wp-block-heading display-lg">Hi, I\'m Matt.<br>I write code<br>that ships.</h1><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lead"} --><p class="lead">WordPress &amp; Power Platform developer with 15+ years turning complex requirements into clean, fast, accessible software.</p><!-- /wp:paragraph -->
</div><!-- /wp:column -->
<!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%">
<!-- wp:group {"className":"terminal-box"} -->
<div class="wp-block-group terminal-box">
<!-- wp:html --><div class="term-bar"><div class="term-dot term-dot-red"></div><div class="term-dot term-dot-amber"></div><div class="term-dot term-dot-green"></div></div><!-- /wp:html -->
<!-- wp:paragraph --><p class="term-prompt">whoami</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>Matt Hummel</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"term-info"} --><p class="term-info">role: Senior Developer</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"term-dim"} --><p class="term-dim">stack: WP + M365</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"term-dim"} --><p class="term-dim">location: Remote</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"term-success"} --><p class="term-success">status: available ✓</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div><!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 3. Stat bento — 4 up ─────────────────────────────────────── */
    register_block_pattern('matthummel/stats-bento', [
        'title'       => __('Stats — bento grid', 'matthummel'),
        'description' => __('Cream background bento with 4 key numbers.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['stats', 'numbers', 'bento', 'metrics'],
        'content'     => '<!-- wp:mh/section {"bgColor":"cream","paddingTop":"lg","paddingBottom":"lg","containerWidth":"contained"} -->
<!-- wp:mh/stat-strip {"stats":"[{\"value\":\"15+\",\"label\":\"Years experience\"},{\"value\":\"50+\",\"label\":\"Projects delivered\"},{\"value\":\"8\",\"label\":\"Certifications\"},{\"value\":\"100%\",\"label\":\"Remote-friendly\"}]","columns":4} /-->
<!-- /wp:mh/section -->',
    ]);

    /* ── 4. About bento — big stat + two smalls ───────────────────── */
    register_block_pattern('matthummel/about-bento', [
        'title'       => __('About — bento stats (2fr+1fr+1fr)', 'matthummel'),
        'description' => __('Asymmetric bento: dark wide card + two light cards.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['about', 'bento', 'stats', 'asymmetric'],
        'content'     => '<!-- wp:mh/section {"bgColor":"paper","paddingTop":"md","paddingBottom":"md","containerWidth":"contained"} -->
<!-- wp:columns {"isStackedOnMobile":false} -->
<div class="wp-block-columns"><!-- wp:column {"width":"50%","className":"bento-card bento-card--dark"} -->
<div class="wp-block-column bento-card bento-card--dark" style="flex-basis:50%">
<!-- wp:heading {"level":3,"className":"bento-num"} --><h3 class="wp-block-heading bento-num">15+</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"bento-lbl"} --><p class="bento-lbl">Years building for the web</p><!-- /wp:paragraph -->
</div><!-- /wp:column -->
<!-- wp:column {"width":"25%","className":"bento-card"} -->
<div class="wp-block-column bento-card" style="flex-basis:25%">
<!-- wp:heading {"level":3,"className":"bento-num"} --><h3 class="wp-block-heading bento-num">50+</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"bento-lbl"} --><p class="bento-lbl">Projects</p><!-- /wp:paragraph -->
</div><!-- /wp:column -->
<!-- wp:column {"width":"25%","className":"bento-card bento-card--tint"} -->
<div class="wp-block-column bento-card bento-card--tint" style="flex-basis:25%">
<!-- wp:heading {"level":3,"className":"bento-num"} --><h3 class="wp-block-heading bento-num">8</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"bento-lbl"} --><p class="bento-lbl">Certifications</p><!-- /wp:paragraph -->
</div><!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 5. Skills grid section ───────────────────────────────────── */
    register_block_pattern('matthummel/skills', [
        'title'       => __('Skills — 3-column grid', 'matthummel'),
        'description' => __('White background, heading + lead, then skills grid.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['skills', 'capabilities', 'services', 'grid'],
        'content'     => '<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"lg","containerWidth":"contained"} -->
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Core skills</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":"display-lg"} --><h2 class="wp-block-heading display-lg">What I work with.</h2><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lead"} --><p class="lead">A broad toolkit — from pixel-perfect themes to enterprise Power Platform.</p><!-- /wp:paragraph -->
<!-- wp:mh/skills-grid {"skills":"[{\"icon\":\"🌐\",\"title\":\"WordPress\",\"description\":\"Sage / Roots themes, Gutenberg blocks, WooCommerce, REST API, headless CMS.\"},{\"icon\":\"⚡\",\"title\":\"Power Platform\",\"description\":\"Power Apps, Power Automate, Power BI, SharePoint, Dataverse solutions.\"},{\"icon\":\"🎨\",\"title\":\"Design & UX\",\"description\":\"Accessible interfaces, responsive CSS, Figma, Core Web Vitals, performance.\"}]","columns":3} /-->
<!-- /wp:mh/section -->',
    ]);

    /* ── 6. Timeline section ──────────────────────────────────────── */
    register_block_pattern('matthummel/timeline', [
        'title'       => __('Timeline — experience', 'matthummel'),
        'description' => __('Vertical timeline of roles with year, title, company, and description.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['timeline', 'experience', 'career', 'resume', 'history'],
        'content'     => '<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"lg","containerWidth":"narrow"} -->
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Experience</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Where I\'ve worked.</h2><!-- /wp:heading -->
<!-- wp:mh/timeline {"entries":"[{\"dates\":\"2020–Present\",\"title\":\"Senior Developer\",\"org\":\"Freelance · Remote\",\"body\":\"WordPress, Power Platform, and M365 solutions for clients worldwide. Sage themes, headless CMS, Power Apps, Power Automate.\"},{\"dates\":\"2016–2020\",\"title\":\"Web Developer\",\"org\":\"Digital Agency\",\"body\":\"Built and maintained 30+ WordPress sites. Led front-end development and introduced modern tooling.\"},{\"dates\":\"2012–2016\",\"title\":\"Junior Developer\",\"org\":\"First Company\",\"body\":\"PHP and front-end development. Began specialising in WordPress.\"}]"} /-->
<!-- /wp:mh/section -->',
    ]);

    /* ── 7. CTA band — ink dark ───────────────────────────────────── */
    register_block_pattern('matthummel/cta', [
        'title'       => __('CTA — ink band', 'matthummel'),
        'description' => __('Dark ink call-to-action with headline and button.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['cta', 'call to action', 'contact', 'hire', 'dark'],
        'content'     => '<!-- wp:mh/section {"bgColor":"ink","paddingTop":"lg","paddingBottom":"lg","textColor":"light","containerWidth":"narrow"} -->
<!-- wp:heading {"level":2,"textAlign":"center","className":"display-lg"} --><h2 class="wp-block-heading has-text-align-center display-lg">Let\'s build something together.</h2><!-- /wp:heading -->
<!-- wp:paragraph {"textAlign":"center","className":"lead"} --><p class="lead has-text-align-center">Available for freelance projects, consulting, and contract work.</p><!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"12px"}}} -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Get in touch →</a></div><!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/projects/">View work</a></div><!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 8. Projects — bento featured layout ─────────────────────── */
    register_block_pattern('matthummel/projects-bento', [
        'title'       => __('Projects — bento layout', 'matthummel'),
        'description' => __('Featured dark card (2fr) + stack of secondary cards (1fr).', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['projects', 'portfolio', 'bento', 'work', 'featured'],
        'content'     => '<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"lg","containerWidth":"contained"} -->
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Selected work</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":"display-lg"} --><h2 class="wp-block-heading display-lg">Things I\'ve built.</h2><!-- /wp:heading -->
<!-- wp:columns {"isStackedOnMobile":true,"className":"proj-bento"} -->
<div class="wp-block-columns proj-bento"><!-- wp:column -->
<div class="wp-block-column">
<!-- wp:group {"className":"proj-featured"} -->
<div class="wp-block-group proj-featured">
<!-- wp:paragraph {"className":"proj-featured-label"} --><p class="proj-featured-label">Featured</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"proj-featured-title"} --><h3 class="wp-block-heading proj-featured-title">matthummel-theme</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"proj-featured-desc"} --><p class="proj-featured-desc">A Sage 10 WordPress theme with custom Gutenberg blocks, Vite build pipeline, block patterns, and a full Customizer suite.</p><!-- /wp:paragraph -->
<!-- wp:mh/project-card {"heading":"","excerpt":"","tags":"PHP, Sage, Gutenberg, Vite","liveUrl":"/projects/matthummel-theme/","githubUrl":"https://github.com/matthummel-pa/matthummel-theme"} /-->
</div>
<!-- /wp:group -->
</div><!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:group {"className":"proj-stack"} -->
<div class="wp-block-group proj-stack">
<!-- wp:group {"className":"proj-secondary"} -->
<div class="wp-block-group proj-secondary">
<!-- wp:paragraph {"className":"proj-secondary-num"} --><p class="proj-secondary-num">02</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"proj-secondary-title"} --><h3 class="wp-block-heading proj-secondary-title">Power Apps portal</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"proj-secondary-desc"} --><p class="proj-secondary-desc">SharePoint-backed employee self-service portal built in Power Apps with Dataverse.</p><!-- /wp:paragraph -->
</div><!-- /wp:group -->
<!-- wp:group {"className":"proj-secondary proj-secondary--tint"} -->
<div class="wp-block-group proj-secondary proj-secondary--tint">
<!-- wp:paragraph {"className":"proj-secondary-num"} --><p class="proj-secondary-num">03</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3,"className":"proj-secondary-title"} --><h3 class="wp-block-heading proj-secondary-title">Headless CMS</h3><!-- /wp:heading -->
<!-- wp:paragraph {"className":"proj-secondary-desc"} --><p class="proj-secondary-desc">WordPress REST API + Next.js frontend with ISR and on-demand revalidation.</p><!-- /wp:paragraph -->
</div><!-- /wp:group -->
</div><!-- /wp:group -->
</div><!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 9. Resources — tabs + groups ────────────────────────────── */
    register_block_pattern('matthummel/resources', [
        'title'       => __('Resources — 3 groups', 'matthummel'),
        'description' => __('Three resource link groups in a grid.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['resources', 'links', 'reading', 'tools', 'bookmarks'],
        'content'     => '<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"lg","containerWidth":"contained"} -->
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Resources</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":"display-lg"} --><h2 class="wp-block-heading display-lg">Things I return to.</h2><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lead"} --><p class="lead">Tools, docs, and references that actually get used. Updated regularly.</p><!-- /wp:paragraph -->
<!-- wp:mh/resource-group {"heading":"WordPress","emoji":"🌐","links":"[{\"label\":\"Roots / Sage docs\",\"url\":\"https://roots.io/sage/\"},{\"label\":\"WP Developer Hub\",\"url\":\"https://developer.wordpress.org/\"},{\"label\":\"Block Editor handbook\",\"url\":\"https://developer.wordpress.org/block-editor/\"},{\"label\":\"WooCommerce docs\",\"url\":\"https://woocommerce.com/documentation/\"}]"} /-->
<!-- wp:mh/resource-group {"heading":"Power Platform","emoji":"⚡","links":"[{\"label\":\"Microsoft Learn\",\"url\":\"https://learn.microsoft.com/\"},{\"label\":\"Power Apps docs\",\"url\":\"https://learn.microsoft.com/en-us/power-apps/\"},{\"label\":\"Power Automate docs\",\"url\":\"https://learn.microsoft.com/en-us/power-automate/\"},{\"label\":\"Community forums\",\"url\":\"https://powerusers.microsoft.com/\"}]"} /-->
<!-- wp:mh/resource-group {"heading":"Dev tools","emoji":"🔧","links":"[{\"label\":\"MDN Web Docs\",\"url\":\"https://developer.mozilla.org/\"},{\"label\":\"web.dev\",\"url\":\"https://web.dev/\"},{\"label\":\"CSS-Tricks\",\"url\":\"https://css-tricks.com/\"},{\"label\":\"Can I Use\",\"url\":\"https://caniuse.com/\"}]"} /-->
<!-- /wp:mh/section -->',
    ]);

    /* ── 10. Full About page ──────────────────────────────────────── */
    register_block_pattern('matthummel/about-page', [
        'title'       => __('Full page — About', 'matthummel'),
        'description' => __('Complete About page: split dark hero → cream stats → skills → timeline → CTA.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['about', 'page', 'full', 'bio'],
        'content'     =>
            /* Hero split */
            '<!-- wp:mh/section {"bgColor":"ink","paddingTop":"xl","paddingBottom":"xl","containerWidth":"contained","textColor":"light"} -->
<!-- wp:columns {"isStackedOnMobile":true,"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"width":"60%"} -->
<div class="wp-block-column" style="flex-basis:60%">
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">About me</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1,"className":"display-xl"} --><h1 class="wp-block-heading display-xl">Hi, I\'m Matt.<br>I write code<br>that ships.</h1><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lead"} --><p class="lead">WordPress &amp; Power Platform developer with 15+ years turning complex requirements into clean, fast, accessible software.</p><!-- /wp:paragraph -->
</div><!-- /wp:column -->
<!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%">
<!-- wp:group {"className":"terminal-box"} -->
<div class="wp-block-group terminal-box">
<!-- wp:html --><div class="term-bar"><div class="term-dot term-dot-red"></div><div class="term-dot term-dot-amber"></div><div class="term-dot term-dot-green"></div></div><!-- /wp:html -->
<!-- wp:paragraph {"className":"term-prompt"} --><p class="term-prompt">whoami</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>Matt Hummel</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"term-info"} --><p class="term-info">role: Senior Developer</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"term-dim"} --><p class="term-dim">stack: WordPress + M365</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"term-dim"} --><p class="term-dim">location: Remote</p><!-- /wp:paragraph -->
<!-- wp:paragraph {"className":"term-success"} --><p class="term-success">status: available ✓</p><!-- /wp:paragraph -->
</div><!-- /wp:group -->
</div><!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:mh/section -->
'
            /* Stat strip */
            . '<!-- wp:mh/section {"bgColor":"cream","paddingTop":"md","paddingBottom":"md","containerWidth":"contained"} -->
<!-- wp:mh/stat-strip {"stats":"[{\"value\":\"15+\",\"label\":\"Years experience\"},{\"value\":\"50+\",\"label\":\"Projects delivered\"},{\"value\":\"8\",\"label\":\"Certifications\"},{\"value\":\"100%\",\"label\":\"Remote-friendly\"}]","columns":4} /-->
<!-- /wp:mh/section -->
'
            /* Skills */
            . '<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"lg","containerWidth":"contained"} -->
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Core skills</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">What I work with.</h2><!-- /wp:heading -->
<!-- wp:mh/skills-grid {"skills":"[{\"icon\":\"🌐\",\"title\":\"WordPress\",\"description\":\"Sage themes, Gutenberg blocks, WooCommerce, REST API.\"},{\"icon\":\"⚡\",\"title\":\"Power Platform\",\"description\":\"Power Apps, Power Automate, Power BI, Dataverse.\"},{\"icon\":\"🎨\",\"title\":\"Design & UX\",\"description\":\"Accessible UI, responsive CSS, Figma, performance.\"}]","columns":3} /-->
<!-- /wp:mh/section -->
'
            /* Timeline */
            . '<!-- wp:mh/section {"bgColor":"paper","paddingTop":"lg","paddingBottom":"lg","containerWidth":"narrow"} -->
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Experience</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Where I\'ve worked.</h2><!-- /wp:heading -->
<!-- wp:mh/timeline {"entries":"[{\"dates\":\"2020–Present\",\"title\":\"Senior Developer\",\"org\":\"Freelance · Remote\",\"body\":\"WordPress, Power Platform, and M365 solutions for clients worldwide.\"},{\"dates\":\"2016–2020\",\"title\":\"Web Developer\",\"org\":\"Digital Agency\",\"body\":\"Built and maintained 30+ WordPress sites. Led front-end development.\"},{\"dates\":\"2012–2016\",\"title\":\"Junior Developer\",\"org\":\"First Company\",\"body\":\"PHP and front-end development, specialising in WordPress.\"}]"} /-->
<!-- /wp:mh/section -->
'
            /* CTA */
            . '<!-- wp:mh/section {"bgColor":"ink","paddingTop":"lg","paddingBottom":"lg","textColor":"light","containerWidth":"narrow"} -->
<!-- wp:heading {"level":2,"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Open to select projects.</h2><!-- /wp:heading -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Get in touch →</a></div><!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 11. Full Résumé page ─────────────────────────────────────── */
    register_block_pattern('matthummel/resume-page', [
        'title'       => __('Full page — Résumé', 'matthummel'),
        'description' => __('Complete résumé: dark name header → experience timeline → skills pills → CTA.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['resume', 'cv', 'experience', 'page', 'career'],
        'content'     =>
            /* Header */
            '<!-- wp:mh/section {"bgColor":"ink","paddingTop":"xl","paddingBottom":"lg","containerWidth":"contained","textColor":"light"} -->
<!-- wp:group {"className":"resume-header"} -->
<div class="wp-block-group resume-header">
<!-- wp:group {} -->
<div class="wp-block-group">
<!-- wp:heading {"level":1,"className":"resume-header-name"} --><h1 class="wp-block-heading resume-header-name">Matt Hummel</h1><!-- /wp:heading -->
<!-- wp:paragraph {"className":"resume-header-role"} --><p class="resume-header-role">Web Developer · Power Platform · Remote</p><!-- /wp:paragraph -->
</div><!-- /wp:group -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button resume-dl-btn" href="/matt-hummel-cv.pdf">↓ Download CV</a></div><!-- /wp:button --></div>
<!-- /wp:buttons -->
</div><!-- /wp:group -->
<!-- /wp:mh/section -->
'
            /* Experience */
            . '<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"md","containerWidth":"narrow"} -->
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Experience</p><!-- /wp:paragraph -->
<!-- wp:mh/timeline {"entries":"[{\"dates\":\"2020–Present\",\"title\":\"Senior Developer\",\"org\":\"Freelance · Remote\",\"body\":\"WordPress, Power Platform, and M365 solutions for clients worldwide. Sage themes, headless CMS, Power Apps, Power Automate, Power BI dashboards.\"},{\"dates\":\"2016–2020\",\"title\":\"Web Developer\",\"org\":\"Digital Agency\",\"body\":\"Built and maintained 30+ WordPress sites. Led front-end development and introduced Vite-based tooling.\"},{\"dates\":\"2012–2016\",\"title\":\"Junior Developer\",\"org\":\"First Company\",\"body\":\"PHP and front-end development. Began specialising in WordPress and web performance.\"}]"} /-->
<!-- /wp:mh/section -->
'
            /* Skills */
            . '<!-- wp:mh/section {"bgColor":"cream","paddingTop":"md","paddingBottom":"lg","containerWidth":"narrow"} -->
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Skills</p><!-- /wp:paragraph -->
<!-- wp:mh/skills-grid {"skills":"[{\"icon\":\"🌐\",\"title\":\"WordPress & PHP\",\"description\":\"Sage, WooCommerce, Gutenberg, ACF, REST API.\"},{\"icon\":\"⚡\",\"title\":\"Power Platform\",\"description\":\"Power Apps, Power Automate, Power BI, Dataverse.\"},{\"icon\":\"🖥️\",\"title\":\"Front-End\",\"description\":\"HTML5, CSS3, JavaScript ES6+, React, Tailwind, Vite.\"}]","columns":3} /-->
<!-- /wp:mh/section -->
'
            /* CTA */
            . '<!-- wp:mh/section {"bgColor":"ink","paddingTop":"lg","paddingBottom":"lg","textColor":"light","containerWidth":"narrow"} -->
<!-- wp:heading {"level":2,"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Want to hire me?</h2><!-- /wp:heading -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"12px"}}} -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Get in touch →</a></div><!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/matt-hummel-cv.pdf">Download CV</a></div><!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 12. Two-column split — text + image ─────────────────────── */
    register_block_pattern('matthummel/two-col', [
        'title'       => __('Two columns — text + image', 'matthummel'),
        'description' => __('50/50 vertical-centre split: text left, image right.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['columns', 'image', 'split', 'two col'],
        'content'     => '<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"lg","containerWidth":"contained"} -->
<!-- wp:columns {"isStackedOnMobile":true,"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%">
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Section label</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">A heading about this section.</h2><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lead"} --><p class="lead">Write a benefit-focused paragraph here. Keep it concise and specific.</p><!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Learn more →</a></div><!-- /wp:button --></div>
<!-- /wp:buttons -->
</div><!-- /wp:column -->
<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%">
<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="" alt="Descriptive alt text here" /></figure>
<!-- /wp:image -->
</div><!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:mh/section -->',
    ]);

}, 20);
