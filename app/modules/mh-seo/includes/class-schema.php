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
     * BlogPosting for posts and Article for pages and projects.
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
        $type = $post_type === 'post' ? 'BlogPosting' : 'Article';
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
