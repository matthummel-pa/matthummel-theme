<?php

/**
 * Maps Rank Math and Yoast post meta onto MH SEO meta.
 */

declare(strict_types=1);

namespace MH_SEO;

/**
 * Plans a copy from Rank Math and Yoast keys to MH SEO keys. It never deletes the old keys.
 */
class Migrator
{
    /**
     * Rank Math and Yoast meta keys the planner reads.
     *
     * @return array<int, string>
     */
    public static function source_keys(): array
    {
        return [
            'rank_math_title',
            'rank_math_description',
            'rank_math_focus_keyword',
            'rank_math_robots',
            'rank_math_advanced_robots',
            'rank_math_primary_category',
            'rank_math_canonical_url',
            'rank_math_facebook_title',
            'rank_math_facebook_description',
            'rank_math_facebook_image_id',
            'rank_math_twitter_title',
            'rank_math_twitter_description',
            'rank_math_twitter_image_id',
            'rank_math_twitter_card_type',
            'rank_math_breadcrumb_title',
            'rank_math_pillar_content',
            '_yoast_wpseo_title',
            '_yoast_wpseo_metadesc',
            '_yoast_wpseo_focuskw',
            '_yoast_wpseo_canonical',
            '_yoast_wpseo_meta-robots-noindex',
            '_yoast_wpseo_meta-robots-nofollow',
            '_yoast_wpseo_meta-robots-adv',
            '_yoast_wpseo_opengraph-title',
            '_yoast_wpseo_opengraph-description',
            '_yoast_wpseo_opengraph-image-id',
            '_yoast_wpseo_twitter-title',
            '_yoast_wpseo_twitter-description',
            '_yoast_wpseo_twitter-image-id',
            '_yoast_wpseo_bctitle',
            '_yoast_wpseo_is_cornerstone',
        ];
    }

    /**
     * MH SEO meta keys the planner can fill.
     *
     * @return array<int, string>
     */
    public static function destination_keys(): array
    {
        return [
            '_mh_seo_title',
            '_mh_seo_description',
            '_mh_seo_focus_keyword',
            '_mh_seo_keywords',
            '_mh_seo_canonical',
            '_mh_seo_noindex',
            '_mh_seo_nofollow',
            '_mh_seo_noarchive',
            '_mh_seo_nosnippet',
            '_mh_seo_noimageindex',
            '_mh_seo_max_snippet',
            '_mh_seo_max_image_preview',
            '_mh_seo_max_video_preview',
            '_mh_seo_primary_term',
            '_mh_seo_og_title',
            '_mh_seo_og_description',
            '_mh_seo_og_image',
            '_mh_seo_twitter_title',
            '_mh_seo_twitter_description',
            '_mh_seo_twitter_image',
            '_mh_seo_twitter_card',
            '_mh_seo_breadcrumb_title',
            '_mh_seo_pillar',
        ];
    }

    /**
     * Plan meta writes for one post.
     *
     * Existing MH SEO values are left alone so a second run does not overwrite edits.
     * Rank Math is read first, then Yoast, and the first useful value for a key wins.
     *
     * @param  array<string, string>  $source  Rank Math and Yoast meta values keyed by meta key. Lists are comma-separated, and Rank Math's advanced robots are "name:value" pairs.
     * @param  array<string, string>  $existing  Current MH SEO meta values.
     * @param  string  $post_title  Post title, used when a stored title still has tokens.
     * @param  string  $site_name  Site name, used for the %sitename% token.
     * @return array<int, array{key: string, value: string, source: string}>
     */
    public function plan(array $source, array $existing, string $post_title, string $site_name): array
    {
        $changes = [];
        $get = static function (string $key) use ($source): string {
            return trim((string) ($source[$key] ?? ''));
        };
        $put = function (string $key, string $value, string $from) use (&$changes, $existing, $get): void {
            $this->add($changes, $key, $value, (string) ($existing[$key] ?? ''), $get($from));
        };

        $put('_mh_seo_title', $this->expand($get('rank_math_title'), $post_title, $site_name), 'rank_math_title');
        $put('_mh_seo_title', $this->plain_value($get('_yoast_wpseo_title')), '_yoast_wpseo_title');

        $put('_mh_seo_description', $this->plain_value($get('rank_math_description')), 'rank_math_description');
        $put('_mh_seo_description', $this->plain_value($get('_yoast_wpseo_metadesc')), '_yoast_wpseo_metadesc');

        $keywords = Meta::keyword_list($get('rank_math_focus_keyword'));
        $put('_mh_seo_focus_keyword', (string) ($keywords[0] ?? ''), 'rank_math_focus_keyword');
        $put('_mh_seo_focus_keyword', $get('_yoast_wpseo_focuskw'), '_yoast_wpseo_focuskw');
        $put('_mh_seo_keywords', implode(', ', array_slice($keywords, 1)), 'rank_math_focus_keyword');

        $put('_mh_seo_canonical', $get('rank_math_canonical_url'), 'rank_math_canonical_url');
        $put('_mh_seo_canonical', $get('_yoast_wpseo_canonical'), '_yoast_wpseo_canonical');

        $robots = array_map('trim', explode(',', $get('rank_math_robots')));
        $yoast_adv = array_map('trim', explode(',', $get('_yoast_wpseo_meta-robots-adv')));
        if (in_array('noindex', $robots, true)) {
            $put('_mh_seo_noindex', '1', 'rank_math_robots');
        }
        if ($get('_yoast_wpseo_meta-robots-noindex') === '1') {
            $put('_mh_seo_noindex', '1', '_yoast_wpseo_meta-robots-noindex');
        }
        if (in_array('nofollow', $robots, true)) {
            $put('_mh_seo_nofollow', '1', 'rank_math_robots');
        }
        if ($get('_yoast_wpseo_meta-robots-nofollow') === '1') {
            $put('_mh_seo_nofollow', '1', '_yoast_wpseo_meta-robots-nofollow');
        }
        foreach (['noarchive', 'nosnippet', 'noimageindex'] as $flag) {
            if (in_array($flag, $robots, true)) {
                $put('_mh_seo_'.$flag, '1', 'rank_math_robots');
            }
            if (in_array($flag, $yoast_adv, true)) {
                $put('_mh_seo_'.$flag, '1', '_yoast_wpseo_meta-robots-adv');
            }
        }
        foreach ($this->limits($get('rank_math_advanced_robots')) as $key => $value) {
            $put($key, $value, 'rank_math_advanced_robots');
        }

        if ((int) $get('rank_math_primary_category') > 0) {
            $put('_mh_seo_primary_term', (string) (int) $get('rank_math_primary_category'), 'rank_math_primary_category');
        }

        $put('_mh_seo_og_title', $this->plain_value($get('rank_math_facebook_title')), 'rank_math_facebook_title');
        $put('_mh_seo_og_title', $this->plain_value($get('_yoast_wpseo_opengraph-title')), '_yoast_wpseo_opengraph-title');
        $put('_mh_seo_og_description', $this->plain_value($get('rank_math_facebook_description')), 'rank_math_facebook_description');
        $put('_mh_seo_og_description', $this->plain_value($get('_yoast_wpseo_opengraph-description')), '_yoast_wpseo_opengraph-description');
        $put('_mh_seo_twitter_title', $this->plain_value($get('rank_math_twitter_title')), 'rank_math_twitter_title');
        $put('_mh_seo_twitter_title', $this->plain_value($get('_yoast_wpseo_twitter-title')), '_yoast_wpseo_twitter-title');
        $put('_mh_seo_twitter_description', $this->plain_value($get('rank_math_twitter_description')), 'rank_math_twitter_description');
        $put('_mh_seo_twitter_description', $this->plain_value($get('_yoast_wpseo_twitter-description')), '_yoast_wpseo_twitter-description');
        foreach (['rank_math_facebook_image_id', '_yoast_wpseo_opengraph-image-id'] as $from) {
            if ((int) $get($from) > 0) {
                $put('_mh_seo_og_image', (string) (int) $get($from), $from);
            }
        }
        foreach (['rank_math_twitter_image_id', '_yoast_wpseo_twitter-image-id'] as $from) {
            if ((int) $get($from) > 0) {
                $put('_mh_seo_twitter_image', (string) (int) $get($from), $from);
            }
        }
        if (in_array($get('rank_math_twitter_card_type'), ['summary', 'summary_large_image'], true)) {
            $put('_mh_seo_twitter_card', $get('rank_math_twitter_card_type'), 'rank_math_twitter_card_type');
        }

        $put('_mh_seo_breadcrumb_title', $get('rank_math_breadcrumb_title'), 'rank_math_breadcrumb_title');
        $put('_mh_seo_breadcrumb_title', $get('_yoast_wpseo_bctitle'), '_yoast_wpseo_bctitle');
        if ($get('rank_math_pillar_content') === 'on') {
            $put('_mh_seo_pillar', '1', 'rank_math_pillar_content');
        }
        if ($get('_yoast_wpseo_is_cornerstone') === '1') {
            $put('_mh_seo_pillar', '1', '_yoast_wpseo_is_cornerstone');
        }

        return $changes;
    }

    /**
     * A stored value, or an empty string while it still holds %tokens%.
     *
     * @param  string  $value  Stored value.
     */
    private function plain_value(string $value): string
    {
        return str_contains($value, '%') ? '' : trim($value);
    }

    /**
     * Rank Math preview limits that differ from the site defaults.
     *
     * @param  string  $value  Comma-separated "name:value" pairs.
     * @return array<string, string>
     */
    private function limits(string $value): array
    {
        $keys = [
            'max-snippet' => ['_mh_seo_max_snippet', '-1'],
            'max-video-preview' => ['_mh_seo_max_video_preview', '-1'],
            'max-image-preview' => ['_mh_seo_max_image_preview', 'large'],
        ];
        $found = [];
        foreach (explode(',', $value) as $pair) {
            $parts = array_map('trim', explode(':', $pair, 2));
            if (count($parts) !== 2 || ! isset($keys[$parts[0]])) {
                continue;
            }
            [$key, $default] = $keys[$parts[0]];
            if ($parts[1] === '' || $parts[1] === $default) {
                continue;
            }
            $found[$key] = $parts[1];
        }

        return $found;
    }

    /**
     * First keyword in a comma-separated list.
     *
     * @param  string  $value  Stored keyword value.
     */
    public function first_keyword(string $value): string
    {
        $parts = array_map('trim', explode(',', $value));

        return (string) ($parts[0] ?? '');
    }

    /**
     * Replace the Rank Math tokens this site actually stores.
     *
     * @param  string  $value  Stored title.
     * @param  string  $post_title  Post title.
     * @param  string  $site_name  Site name.
     * @return string Empty when an unknown token is left over.
     */
    public function expand(string $value, string $post_title, string $site_name): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (! str_contains($value, '%')) {
            return $value;
        }
        $expanded = str_ireplace(
            ['%title%', '%sitename%', '%sep%', '%page%', '%excerpt%', '%sitedesc%'],
            [$post_title, $site_name, '|', '', '', ''],
            $value
        );
        $expanded = trim((string) preg_replace('/\s+/u', ' ', $expanded));
        $expanded = trim($expanded, "| \t");
        if (str_contains($expanded, '%')) {
            return '';
        }

        return $expanded;
    }

    /**
     * Append a change when the new value is useful, the destination is empty, and no earlier source filled it.
     *
     * @param  array<int, array{key: string, value: string, source: string}>  $changes  Change list.
     * @param  string  $key  Destination meta key.
     * @param  string  $value  New value.
     * @param  string  $existing  Current MH SEO value.
     * @param  string  $source  Original Rank Math or Yoast value.
     */
    private function add(array &$changes, string $key, string $value, string $existing, string $source): void
    {
        if ($value === '' || $existing !== '') {
            return;
        }
        foreach ($changes as $change) {
            if ($change['key'] === $key) {
                return;
            }
        }
        $changes[] = [
            'key' => $key,
            'value' => $value,
            'source' => $source,
        ];
    }
}
