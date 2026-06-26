<?php

/**
 * Block patterns for the matthummel theme.
 *
 * Registers the 'matthummel' pattern category and pre-built patterns
 * covering every page type.  Each pattern uses core blocks + mh/* blocks
 * so they work without any classic block or hardcoded HTML.
 *
 * Loaded at 'init' priority 20 (after blocks are registered at 10/12).
 */

namespace App;

add_action('init', function () {

    /* ── Pattern category ──────────────────────────────────────────── */
    register_block_pattern_category('matthummel', [
        'label'       => __('Matthummel', 'matthummel'),
        'description' => __('Pre-built layouts for the matthummel.com theme.', 'matthummel'),
    ]);

    /* ── Helper: common section wrapper open/close ─────────────────ー */
    // Patterns use mh/section where layout containers are needed.
    // Self-closing SSR custom blocks use the /  syntax.

    /* ── 1. Page hero ──────────────────────────────────────────────── */
    register_block_pattern('matthummel/hero', [
        'title'         => __('Hero – Page intro', 'matthummel'),
        'description'   => __('Full-width hero with eyebrow, headline, lead paragraph and two buttons.', 'matthummel'),
        'categories'    => ['matthummel'],
        'keywords'      => ['hero', 'intro', 'header', 'banner'],
        'content'       => '<!-- wp:mh/section {"bgColor":"paper","paddingTop":"xl","paddingBottom":"lg","containerWidth":"narrow"} -->
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Web Developer · Power Platform · WordPress</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Clean, fast software for the web.</h1><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lead"} --><p class="lead">I build performant WordPress themes and Microsoft Power Platform solutions that are accessible, standards-compliant, and a pleasure to use.</p><!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/projects/">View projects</a></div><!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/blog/">Read the blog</a></div><!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 2. Stat strip section ─────────────────────────────────────── */
    register_block_pattern('matthummel/stats', [
        'title'       => __('Stats – Key numbers', 'matthummel'),
        'description' => __('Section heading + animated stat strip.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['stats', 'numbers', 'figures', 'metrics'],
        'content'     => '<!-- wp:mh/section {"bgColor":"cream","paddingTop":"lg","paddingBottom":"lg"} -->
<!-- wp:heading {"level":2,"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">By the numbers</h2><!-- /wp:heading -->
<!-- wp:mh/stat-strip {"stats":"[{\"value\":\"15+\",\"label\":\"Years experience\"},{\"value\":\"50+\",\"label\":\"Projects delivered\"},{\"value\":\"8\",\"label\":\"Certifications\"},{\"value\":\"100%\",\"label\":\"Remote-ready\"}]","columns":4} /-->
<!-- /wp:mh/section -->',
    ]);

    /* ── 3. Skills grid section ────────────────────────────────────── */
    register_block_pattern('matthummel/skills', [
        'title'       => __('Skills – Core capabilities', 'matthummel'),
        'description' => __('Three-column skills grid with icon, heading and description.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['skills', 'capabilities', 'services', 'expertise'],
        'content'     => '<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"lg"} -->
<!-- wp:heading {"level":2,"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Core skills</h2><!-- /wp:heading -->
<!-- wp:paragraph {"textAlign":"center","className":"lead"} --><p class="lead has-text-align-center">A broad toolkit — from pixel-perfect themes to enterprise Power Platform.</p><!-- /wp:paragraph -->
<!-- wp:mh/skills-grid {"skills":"[{\"icon\":\"🌐\",\"title\":\"WordPress Development\",\"description\":\"Custom Sage/Roots themes, Gutenberg blocks, WooCommerce, headless CMS.\"},{\"icon\":\"⚡\",\"title\":\"Power Platform\",\"description\":\"Power Apps, Power Automate, Power BI, SharePoint, Dataverse solutions.\"},{\"icon\":\"🎨\",\"title\":\"Design & UX\",\"description\":\"Figma prototypes, accessible UI, responsive CSS, performance optimisation.\"}]","columns":3} /-->
<!-- /wp:mh/section -->',
    ]);

    /* ── 4. Focus / service cards (green tint) ─────────────────────── */
    register_block_pattern('matthummel/focus', [
        'title'       => __('Focus – What I do', 'matthummel'),
        'description' => __('Green-tint section with two-column focus cards.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['focus', 'services', 'what i do', 'offer'],
        'content'     => '<!-- wp:mh/section {"bgColor":"tint","paddingTop":"lg","paddingBottom":"lg","containerWidth":"contained"} -->
<!-- wp:columns {"isStackedOnMobile":true} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">What I focus on</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>From greenfield builds to legacy rescues, I deliver clean, maintainable solutions.</p><!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item --><li>Custom WordPress theme development</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Microsoft Power Platform automation</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Accessible, performant front-end</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>REST API integrations & headless CMS</li><!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 5. Timeline / experience section ──────────────────────────── */
    register_block_pattern('matthummel/timeline', [
        'title'       => __('Timeline – Experience', 'matthummel'),
        'description' => __('Vertical timeline of roles / milestones.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['timeline', 'experience', 'history', 'career', 'resume'],
        'content'     => '<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"lg"} -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Experience</h2><!-- /wp:heading -->
<!-- wp:mh/timeline {"items":"[{\"year\":\"2020–Present\",\"title\":\"Senior Developer\",\"company\":\"Freelance\",\"description\":\"WordPress, Power Platform, and Microsoft 365 solutions for clients worldwide.\"},{\"year\":\"2016–2020\",\"title\":\"Web Developer\",\"company\":\"Agency Name\",\"description\":\"Built and maintained 30+ client websites using WordPress and modern JS frameworks.\"},{\"year\":\"2012–2016\",\"title\":\"Junior Developer\",\"company\":\"First Company\",\"description\":\"Began career focusing on front-end development and PHP.\"}]"} /-->
<!-- /wp:mh/section -->',
    ]);

    /* ── 6. CTA band ───────────────────────────────────────────────── */
    register_block_pattern('matthummel/cta', [
        'title'       => __('CTA – Call to action', 'matthummel'),
        'description' => __('Dark ink band with headline and action button.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['cta', 'call to action', 'contact', 'hire', 'button'],
        'content'     => '<!-- wp:mh/section {"bgColor":"ink","paddingTop":"lg","paddingBottom":"lg","textColor":"light","containerWidth":"narrow"} -->
<!-- wp:heading {"level":2,"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Ready to work together?</h2><!-- /wp:heading -->
<!-- wp:paragraph {"textAlign":"center"} --><p class="has-text-align-center">I\'m available for freelance projects and consulting engagements.</p><!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Get in touch</a></div><!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 7. Resource group ─────────────────────────────────────────── */
    register_block_pattern('matthummel/resources', [
        'title'       => __('Resources – Link groups', 'matthummel'),
        'description' => __('Three resource groups in a grid layout.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['resources', 'links', 'reading', 'tools', 'bookmarks'],
        'content'     => '<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"lg"} -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Resources</h2><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lead"} --><p class="lead">Tools, articles, and references I return to regularly.</p><!-- /wp:paragraph -->
<!-- wp:mh/resource-group {"title":"WordPress","links":"[{\"label\":\"Roots / Sage docs\",\"url\":\"https://roots.io/sage/\"},{\"label\":\"Developer Resources\",\"url\":\"https://developer.wordpress.org/\"},{\"label\":\"Block Editor Handbook\",\"url\":\"https://developer.wordpress.org/block-editor/\"}]"} /-->
<!-- wp:mh/resource-group {"title":"Power Platform","links":"[{\"label\":\"Microsoft Learn\",\"url\":\"https://learn.microsoft.com/\"},{\"label\":\"Power Platform docs\",\"url\":\"https://learn.microsoft.com/en-us/power-platform/\"},{\"label\":\"Community forums\",\"url\":\"https://powerusers.microsoft.com/\"}]"} /-->
<!-- wp:mh/resource-group {"title":"Development","links":"[{\"label\":\"MDN Web Docs\",\"url\":\"https://developer.mozilla.org/\"},{\"label\":\"CSS-Tricks\",\"url\":\"https://css-tricks.com/\"},{\"label\":\"web.dev\",\"url\":\"https://web.dev/\"}]"} /-->
<!-- /wp:mh/section -->',
    ]);

    /* ── 8. Project card grid ──────────────────────────────────────── */
    register_block_pattern('matthummel/projects', [
        'title'       => __('Projects – Featured work', 'matthummel'),
        'description' => __('Two project cards side by side.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['projects', 'portfolio', 'work', 'case study'],
        'content'     => '<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"lg"} -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Selected projects</h2><!-- /wp:heading -->
<!-- wp:columns {"isStackedOnMobile":true} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:mh/project-card {"title":"Project One","description":"A description of this project and the technologies used to build it.","tags":"[\"WordPress\",\"PHP\",\"Gutenberg\"]","url":"/projects/project-one/"} /--></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:mh/project-card {"title":"Project Two","description":"Another project description showcasing different skills and outcomes.","tags":"[\"Power Platform\",\"SharePoint\",\"Power Automate\"]","url":"/projects/project-two/"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 9. About page (full composition) ─────────────────────────── */
    register_block_pattern('matthummel/about-page', [
        'title'       => __('Full page – About', 'matthummel'),
        'description' => __('Complete About page: hero → stats → skills → focus → CTA.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['about', 'page', 'full', 'bio', 'intro'],
        'content'     => '<!-- wp:mh/section {"bgColor":"paper","paddingTop":"xl","paddingBottom":"lg","containerWidth":"narrow"} -->
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">About me</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Hi, I\'m Matt Hummel.</h1><!-- /wp:heading -->
<!-- wp:paragraph {"className":"lead"} --><p class="lead">Web developer and Power Platform specialist with 15+ years delivering clean, fast, accessible software.</p><!-- /wp:paragraph -->
<!-- /wp:mh/section -->
<!-- wp:mh/section {"bgColor":"cream","paddingTop":"md","paddingBottom":"md"} -->
<!-- wp:mh/stat-strip {"stats":"[{\"value\":\"15+\",\"label\":\"Years experience\"},{\"value\":\"50+\",\"label\":\"Projects delivered\"},{\"value\":\"8\",\"label\":\"Certifications\"},{\"value\":\"100%\",\"label\":\"Remote-ready\"}]","columns":4} /-->
<!-- /wp:mh/section -->
<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"lg"} -->
<!-- wp:heading {"level":2,"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Core skills</h2><!-- /wp:heading -->
<!-- wp:mh/skills-grid {"skills":"[{\"icon\":\"🌐\",\"title\":\"WordPress Development\",\"description\":\"Custom Sage/Roots themes, Gutenberg blocks, WooCommerce.\"},{\"icon\":\"⚡\",\"title\":\"Power Platform\",\"description\":\"Power Apps, Power Automate, Power BI, SharePoint, Dataverse.\"},{\"icon\":\"🎨\",\"title\":\"Design & UX\",\"description\":\"Accessible UI, responsive CSS, Figma, performance.\"}]","columns":3} /-->
<!-- /wp:mh/section -->
<!-- wp:mh/section {"bgColor":"ink","paddingTop":"lg","paddingBottom":"lg","textColor":"light","containerWidth":"narrow"} -->
<!-- wp:heading {"level":2,"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Let\'s build something together.</h2><!-- /wp:heading -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Get in touch</a></div><!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 10. Résumé page (full composition) ────────────────────────── */
    register_block_pattern('matthummel/resume-page', [
        'title'       => __('Full page – Résumé', 'matthummel'),
        'description' => __('Complete résumé layout: header → timeline → skills tags → CTA.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['resume', 'cv', 'experience', 'page', 'career'],
        'content'     => '<!-- wp:mh/section {"bgColor":"paper","paddingTop":"xl","paddingBottom":"md","containerWidth":"narrow"} -->
<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Résumé</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Matt Hummel</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p>Web Developer · Power Platform Specialist · Remote</p><!-- /wp:paragraph -->
<!-- /wp:mh/section -->
<!-- wp:mh/section {"paddingTop":"md","paddingBottom":"lg"} -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Experience</h2><!-- /wp:heading -->
<!-- wp:mh/timeline {"items":"[{\"year\":\"2020–Present\",\"title\":\"Senior Developer\",\"company\":\"Freelance\",\"description\":\"WordPress, Power Platform, and Microsoft 365 solutions for clients worldwide.\"},{\"year\":\"2016–2020\",\"title\":\"Web Developer\",\"company\":\"Digital Agency\",\"description\":\"Built and maintained 30+ WordPress sites, leading front-end development.\"}]"} /-->
<!-- wp:separator /-->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Skills</h2><!-- /wp:heading -->
<!-- wp:mh/skills-grid {"skills":"[{\"icon\":\"🌐\",\"title\":\"WordPress & PHP\",\"description\":\"Sage, WooCommerce, Gutenberg, ACF, REST API.\"},{\"icon\":\"⚡\",\"title\":\"Power Platform\",\"description\":\"Power Apps, Power Automate, Power BI, Dataverse.\"},{\"icon\":\"🖥️\",\"title\":\"Front-End\",\"description\":\"HTML5, CSS3, JavaScript (ES6+), React, Tailwind.\"}]","columns":3} /-->
<!-- /wp:mh/section -->
<!-- wp:mh/section {"bgColor":"ink","paddingTop":"lg","paddingBottom":"lg","textColor":"light","containerWidth":"narrow"} -->
<!-- wp:heading {"level":2,"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">Want to hire me?</h2><!-- /wp:heading -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Get in touch</a></div><!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/matt-hummel-cv.pdf">Download CV (PDF)</a></div><!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:mh/section -->',
    ]);

    /* ── 11. Two-column text + image ───────────────────────────────── */
    register_block_pattern('matthummel/two-col', [
        'title'       => __('Two columns – Text + image', 'matthummel'),
        'description' => __('Responsive 50/50 split with text on left and image on right.', 'matthummel'),
        'categories'  => ['matthummel'],
        'keywords'    => ['columns', 'two col', 'image', 'split'],
        'content'     => '<!-- wp:mh/section {"paddingTop":"lg","paddingBottom":"lg"} -->
<!-- wp:columns {"isStackedOnMobile":true,"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">A heading about this section</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Write a paragraph here explaining what this section is about. Keep it concise and benefit-focused.</p><!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Learn more</a></div><!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->
<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:image {"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="" alt="Descriptive alt text" /></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:mh/section -->',
    ]);

}, 20);
