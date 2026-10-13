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
            $this->webpage_node($context, $title, $description, $page, $website, $crumb),
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
     * @param  array<string, mixed>  $context  Request context.
     * @param  string  $title  Title.
     * @param  string  $description  Description.
     * @param  string  $id  Node id.
     * @param  string  $website  WebSite node id.
     * @param  string  $crumb  Breadcrumb node id.
     * @return array<string, mixed>
     */
    private function webpage_node(array $context, string $title, string $description, string $id, string $website, string $crumb): array
    {
        $view = (string) ($context['view'] ?? '');
        $type = 'WebPage';
        if (in_array($view, ['category', 'tag', 'blog', 'tax', 'date'], true)) {
            $type = 'CollectionPage';
        } elseif ($view === 'author') {
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
        if ($chosen === 'none') {
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
     * FAQPage built from the question headings on the page, when the post asks for it.
     *
     * @param  array<string, mixed>  $context  Request context.
     * @param  string  $id  Node id.
     * @param  string  $page  WebPage node id.
     * @return array<string, mixed>|null
     */
    private function faq_node(array $context, string $id, string $page): ?array
    {
        if (empty($context['schema_faq']) || ! in_array((string) ($context['view'] ?? ''), ['singular', 'front'], true)) {
            return null;
        }
        $questions = self::faq_items((string) ($context['content_html'] ?? ''));
        if ($questions === []) {
            return null;
        }
        $entities = [];
        foreach ($questions as $item) {
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
