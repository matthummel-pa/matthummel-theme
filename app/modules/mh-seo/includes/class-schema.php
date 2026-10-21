<?php

/**
 * JSON-LD graph for one request.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Builds one linked schema.org graph. The identity is always a Person, never an Organization.
 */
class Schema
{
    /**
     * Build the @graph.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  array<string, mixed>  $settings  Plugin settings.
     * @param  string  $title  Resolved title.
     * @param  string  $description  Resolved description.
     * @return array<string, mixed>
     */
    public function graph(array $context, array $settings, string $title, string $description): array
    {
        $home = (string) ($context['site_url'] ?? '');
        $url = (string) ($context['url'] ?? $home);
        $person = $this->fragment($home, 'person');
        $website = $this->fragment($home, 'website');
        $page = $this->fragment($url, 'webpage');
        $crumb = $this->fragment($url, 'breadcrumb');
        $article = $this->fragment($url, 'article');

        $graph = [
            $this->website_node($context, $settings, $website, $person),
            $this->person_node($context, $settings, $person),
            $this->webpage_node($context, $title, $description, $page, $website, $crumb, $person),
        ];

        $crumbs = $this->breadcrumb_node($context, $crumb);
        if ($crumbs !== null) {
            $graph[] = $crumbs;
        }

        $article_node = $this->article_node($context, $title, $description, $article, $page, $person);
        if ($article_node !== null) {
            $graph[] = $article_node;
        }

        $faq_node = $this->faq_node($context, $this->fragment($url, 'faq'), $page);
        if ($faq_node !== null) {
            $graph[] = $faq_node;
        }

        $howto_node = $this->howto_node($context, $title, $this->fragment($url, 'howto'), $page);
        if ($howto_node !== null) {
            $graph[] = $howto_node;
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    /**
     * WebSite node with a search action.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  array<string, mixed>  $settings  Plugin settings.
     * @param  string  $id  Node id.
     * @param  string  $person  Person node id.
     * @return array<string, mixed>
     */
    private function website_node(array $context, array $settings, string $id, string $person): array
    {
        $home = (string) ($context['site_url'] ?? '');
        $name = trim((string) ($settings['site_name'] ?? ''));
        if ($name === '') {
            $name = (string) ($context['site_name'] ?? '');
        }

        return [
            '@type' => 'WebSite',
            '@id' => $id,
            'url' => $home,
            'name' => $name,
            'publisher' => ['@id' => $person],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => rtrim($home, '/').'/?s={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Person node for Matt.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  array<string, mixed>  $settings  Plugin settings.
     * @param  string  $id  Node id.
     * @return array<string, mixed>
     */
    private function person_node(array $context, array $settings, string $id): array
    {
        $node = [
            '@type' => 'Person',
            '@id' => $id,
            'name' => (string) ($settings['person_name'] ?? 'Matt Hummel'),
            'url' => (string) ($context['site_url'] ?? ''),
        ];
        $job = trim((string) ($settings['person_job_title'] ?? ''));
        if ($job !== '') {
            $node['jobTitle'] = $job;
        }
        $image = trim((string) ($settings['person_image'] ?? ''));
        if ($image !== '') {
            $node['image'] = $image;
        }
        $same = [];
        $links = $settings['same_as'] ?? [];
        if (is_array($links)) {
            foreach ($links as $link) {
                $link = trim((string) $link);
                if ($link !== '') {
                    $same[] = $link;
                }
            }
        }
        if ($same !== []) {
            $node['sameAs'] = array_values(array_unique($same));
        }

        return $node;
    }

    /**
     * WebPage, CollectionPage, or ProfilePage.
     *
     * The About page (slug `about`, or any page whose schema type is set to
     * ProfilePage) is a ProfilePage whose mainEntity is the site Person.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  string  $title  Title.
     * @param  string  $description  Description.
     * @param  string  $id  Node id.
     * @param  string  $website  WebSite node id.
     * @param  string  $crumb  Breadcrumb node id.
     * @param  string  $person  Person node id.
     * @return array<string, mixed>
     */
    private function webpage_node(array $context, string $title, string $description, string $id, string $website, string $crumb, string $person): array
    {
        $view = (string) ($context['view'] ?? '');
        $type = 'WebPage';
        $is_profile = self::is_profile_page($context);
        if (in_array($view, ['category', 'tag', 'blog', 'tax', 'date'], true)) {
            $type = 'CollectionPage';
        } elseif ($view === 'author' || $is_profile) {
            $type = 'ProfilePage';
        }
        $node = [
            '@type' => $type,
            '@id' => $id,
            'url' => (string) ($context['url'] ?? ''),
            'name' => $title,
            'isPartOf' => ['@id' => $website],
            'breadcrumb' => ['@id' => $crumb],
        ];
        if ($is_profile) {
            // The page is about Matt, so it points at the one Person node in the graph.
            $node['mainEntity'] = ['@id' => $person];
            if (! empty($context['published'])) {
                $node['dateCreated'] = (string) $context['published'];
            }
            if (! empty($context['modified'])) {
                $node['dateModified'] = (string) $context['modified'];
            }
        }
        if ($description !== '') {
            $node['description'] = $description;
        }
        $language = trim((string) ($context['language'] ?? ''));
        if ($language !== '') {
            $node['inLanguage'] = $language;
        }

        return $node;
    }

    /**
     * Whether this request is the About page (or another page set to ProfilePage).
     *
     * @param  array<string, mixed>  $context  Request context.
     */
    public static function is_profile_page(array $context): bool
    {
        if (! in_array((string) ($context['view'] ?? ''), ['singular', 'front'], true)) {
            return false;
        }
        if ((string) ($context['schema_type'] ?? '') === 'ProfilePage') {
            return true;
        }

        return (string) ($context['post_type'] ?? '') === 'page'
            && (string) ($context['post_slug'] ?? '') === 'about';
    }

    /**
     * BreadcrumbList, or null when the trail is empty.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  string  $id  Node id.
     * @return array<string, mixed>|null
     */
    private function breadcrumb_node(array $context, string $id): ?array
    {
        $crumbs = $context['breadcrumbs'] ?? [];
        if (! is_array($crumbs) || $crumbs === []) {
            return null;
        }
        $items = [];
        $position = 1;
        foreach ($crumbs as $crumb) {
            if (! is_array($crumb)) {
                continue;
            }
            $name = trim((string) ($crumb['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $item = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $name,
            ];
            $url = trim((string) ($crumb['url'] ?? ''));
            if ($url !== '') {
                $item['item'] = $url;
            }
            $items[] = $item;
            $position++;
        }
        if ($items === []) {
            return null;
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => $id,
            'itemListElement' => $items,
        ];
    }

    /**
     * BlogPosting for posts and Article for projects and other post types.
     *
     * Pages (Home, About, Projects, Contact, legal) are not articles. They keep
     * their WebPage node and get no Article node.
     *
     * A post's own schema type wins: a named type is used on any post or page,
     * and "none" leaves the Article node out.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  string  $title  Title.
     * @param  string  $description  Description.
     * @param  string  $id  Node id.
     * @param  string  $page  WebPage node id.
     * @param  string  $person  Person node id.
     * @return array<string, mixed>|null
     */
    private function article_node(array $context, string $title, string $description, string $id, string $page, string $person): ?array
    {
        $view = (string) ($context['view'] ?? '');
        if ($view !== 'singular' && $view !== 'front') {
            return null;
        }
        $post_type = (string) ($context['post_type'] ?? 'page');
        $chosen = (string) ($context['schema_type'] ?? '');
        // A ProfilePage (About, or the explicit choice) is a WebPage type, so it carries no article node.
        if ($chosen === 'none' || self::is_profile_page($context)) {
            return null;
        }
        if (! in_array($chosen, ['Article', 'BlogPosting', 'TechArticle', 'NewsArticle'], true)) {
            $chosen = '';
        }
        if ($chosen === '' && ($view === 'front' || $post_type === 'page')) {
            return null;
        }
        $type = $post_type === 'post' ? 'BlogPosting' : 'Article';
        if ($chosen !== '') {
            $type = $chosen;
        }
        $node = [
            '@type' => $type,
            '@id' => $id,
            'headline' => $title,
            'mainEntityOfPage' => ['@id' => $page],
            'author' => ['@id' => $person],
            'publisher' => ['@id' => $person],
        ];
        if ($description !== '') {
            $node['description'] = $description;
        }
        if (! empty($context['published'])) {
            $node['datePublished'] = (string) $context['published'];
        }
        if (! empty($context['modified'])) {
            $node['dateModified'] = (string) $context['modified'];
        }
        $image = is_array($context['image'] ?? null) ? $context['image'] : [];
        if (! empty($image['url'])) {
            $node['image'] = [
                '@type' => 'ImageObject',
                'url' => (string) $image['url'],
            ];
            if (! empty($image['width'])) {
                $node['image']['width'] = (int) $image['width'];
            }
            if (! empty($image['height'])) {
                $node['image']['height'] = (int) $image['height'];
            }
        }
        if (! empty($context['section'])) {
            $node['articleSection'] = (string) $context['section'];
        }
        $tags = $context['tags'] ?? [];
        if (is_array($tags) && $tags !== []) {
            $node['keywords'] = implode(', ', array_map('strval', $tags));
        }
        if (isset($context['word_count'])) {
            $node['wordCount'] = (int) $context['word_count'];
        }
        $language = trim((string) ($context['language'] ?? ''));
        if ($language !== '') {
            $node['inLanguage'] = $language;
        }

        return $node;
    }

    /**
     * FAQPage for the questions on the page.
     *
     * Questions blocks (matthummel/faq) always count, so every page with a
     * question list gets FAQPage data without a checkbox. Question headings
     * count only when the post's "Add FAQ data" box is ticked.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  string  $id  Node id.
     * @param  string  $page  WebPage node id.
     * @return array<string, mixed>|null
     */
    private function faq_node(array $context, string $id, string $page): ?array
    {
        if (! in_array((string) ($context['view'] ?? ''), ['singular', 'front'], true)) {
            return null;
        }
        $html = (string) ($context['content_html'] ?? '');
        $questions = self::faq_block_items($html);
        if (! empty($context['schema_faq'])) {
            $questions = array_merge($questions, self::faq_items($html));
        }
        if ($questions === []) {
            return null;
        }
        $entities = [];
        $seen = [];
        foreach ($questions as $item) {
            // One entry per question, even when a heading repeats a block question.
            $key = mb_strtolower($item['question']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $entities[] = [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ];
        }

        return [
            '@type' => 'FAQPage',
            '@id' => $id,
            'isPartOf' => ['@id' => $page],
            'mainEntity' => $entities,
        ];
    }

    /**
     * HowTo built from the first numbered list on the page, when the post asks for it.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  string  $title  Title.
     * @param  string  $id  Node id.
     * @param  string  $page  WebPage node id.
     * @return array<string, mixed>|null
     */
    private function howto_node(array $context, string $title, string $id, string $page): ?array
    {
        if (empty($context['schema_howto']) || ! in_array((string) ($context['view'] ?? ''), ['singular', 'front'], true)) {
            return null;
        }
        $steps = self::howto_steps((string) ($context['content_html'] ?? ''));
        if (count($steps) < 2) {
            return null;
        }
        $list = [];
        foreach ($steps as $index => $text) {
            $list[] = [
                '@type' => 'HowToStep',
                'position' => $index + 1,
                'text' => $text,
            ];
        }

        return [
            '@type' => 'HowTo',
            '@id' => $id,
            'name' => $title,
            'isPartOf' => ['@id' => $page],
            'step' => $list,
        ];
    }

    /**
     * Questions and answers visible on the page.
     *
     * A question is an H2, H3, or H4 that ends with a question mark. Its answer
     * is the text up to the next heading. Code blocks are left out.
     *
     * @param  string  $html  Rendered post content.
     * @return array<int, array{question: string, answer: string}>
     */
    public static function faq_items(string $html): array
    {
        $html = Text::prose_html($html);
        if (! preg_match_all('#<h([2-4])\b[^>]*>(.*?)</h\1>(.*?)(?=<h[1-6]\b|\z)#is', $html, $matches, PREG_SET_ORDER)) {
            return [];
        }
        $items = [];
        foreach ($matches as $match) {
            $question = Text::plain((string) $match[2]);
            $answer = Text::plain((string) $match[3]);
            if ($answer === '' || ! str_ends_with($question, '?')) {
                continue;
            }
            $items[] = [
                'question' => html_entity_decode($question, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'answer' => html_entity_decode($answer, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ];
        }

        return $items;
    }

    /**
     * Questions and answers from the Questions blocks on the page.
     *
     * Each core Details block inside a `.mh-block-faq` list is one question:
     * the summary is the question, everything after it is the answer.
     *
     * @param  string  $html  Rendered post content.
     * @return array<int, array{question: string, answer: string}>
     */
    public static function faq_block_items(string $html): array
    {
        if (! str_contains($html, 'mh-block-faq') || ! class_exists(\DOMDocument::class)) {
            return [];
        }
        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            return [];
        }
        $xpath = new \DOMXPath($dom);
        // A details nested inside another question is part of that answer, not a second question.
        // A details that wraps the whole Questions block (outside the list) does not count as a question.
        $in_list = "ancestor::*[contains(concat(' ', normalize-space(@class), ' '), ' mh-block-faq ')]";
        $details = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' mh-block-faq ')]//details[not(ancestor::details[{$in_list}])]");
        if ($details === false) {
            return [];
        }
        $items = [];
        foreach ($details as $detail) {
            $question = '';
            $answer = '';
            foreach ($detail->childNodes as $child) {
                if ($child instanceof \DOMElement && strtolower($child->tagName) === 'summary' && $question === '') {
                    $question = (string) $child->textContent;

                    continue;
                }
                if ($child instanceof \DOMElement && in_array(strtolower($child->tagName), ['script', 'style', 'pre'], true)) {
                    continue;
                }
                $answer .= ' '.$child->textContent;
            }
            $question = trim((string) preg_replace('/\s+/u', ' ', $question));
            $answer = trim((string) preg_replace('/\s+/u', ' ', $answer));
            if ($question === '' || $answer === '') {
                continue;
            }
            // textContent is already decoded text; a second html_entity_decode would eat literal "&not"-style runs.
            $items[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $items;
    }

    /**
     * The items of the first numbered list on the page.
     *
     * @param  string  $html  Rendered post content.
     * @return array<int, string>
     */
    public static function howto_steps(string $html): array
    {
        $html = Text::prose_html($html);
        if (! preg_match('#<ol\b[^>]*>(.*?)</ol>#is', $html, $list)) {
            return [];
        }
        if (! preg_match_all('#<li\b[^>]*>(.*?)</li>#is', (string) $list[1], $items)) {
            return [];
        }
        $steps = [];
        foreach ($items[1] as $item) {
            $text = html_entity_decode(Text::plain((string) $item), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($text !== '') {
                $steps[] = $text;
            }
        }

        return $steps;
    }

    /**
     * Node id on a URL.
     *
     * @param  string  $url  Absolute URL.
     * @param  string  $fragment  Fragment without a hash.
     */
    private function fragment(string $url, string $fragment): string
    {
        $url = $url === '' ? '/' : $url;

        return rtrim($url, '/').'/#'.$fragment;
    }
}
