<?php

namespace App;

/**
 * LinkedIn profile helpers for the Hire page.
 *
 * LinkedIn’s self-serve OpenID product only returns name, picture, and email
 * for the authenticated member — not positions or skills. Work history stays
 * in page fields. When a token is set (Customizer / MH_LINKEDIN_TOKEN), we
 * refresh lite profile data from /v2/userinfo. Otherwise we try public OG
 * tags, then field/GitHub fallbacks. Always fails soft.
 */

/**
 * Resolve a LinkedIn OAuth access token (OpenID userinfo).
 */
function linkedin_token(): string
{
    if (defined('MH_LINKEDIN_TOKEN') && is_string(MH_LINKEDIN_TOKEN) && MH_LINKEDIN_TOKEN !== '') {
        return trim(MH_LINKEDIN_TOKEN);
    }
    $mod = function_exists('get_theme_mod') ? trim((string) get_theme_mod('mh_li_token', '')) : '';

    return (string) apply_filters('mh/linkedin_token', $mod);
}

class LinkedIn
{
    /**
     * Public profile URL from social defaults / Customizer.
     */
    public static function profileUrl(): string
    {
        $url = (string) (mh_portfolio_social_defaults()['linkedin'] ?? '');
        $url = trim($url);
        if ($url === '') {
            $url = 'https://www.linkedin.com/in/matt-hummel-pa';
        }

        return $url;
    }

    /**
     * Share-this-page URL for LinkedIn’s share dialog.
     */
    public static function shareUrl(string $pageUrl = ''): string
    {
        $pageUrl = $pageUrl !== '' ? $pageUrl : home_url('/hire/');

        return 'https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($pageUrl);
    }

    /**
     * Cached hire-page LinkedIn profile card data.
     *
     * @return array{
     *   name: string,
     *   headline: string,
     *   about: string,
     *   picture: string,
     *   url: string,
     *   location: string,
     *   open_to_work: bool,
     *   source: string
     * }
     */
    public static function fetchProfile(): array
    {
        $url = self::profileUrl();
        $key = 'mh_li_profile_v1_'.md5($url.linkedin_token());
        $cached = get_transient($key);
        if (is_array($cached) && isset($cached['url'])) {
            return self::withOpenToWork($cached);
        }

        $profile = self::fromUserinfo()
            ?? self::fromOpenGraph($url)
            ?? self::fallback($url);

        set_transient($key, $profile, 6 * HOUR_IN_SECONDS);

        return self::withOpenToWork($profile);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    protected static function withOpenToWork(array $profile): array
    {
        $forced = get_theme_mod('mh_li_open_to_work', '');
        if ($forced === 'yes') {
            $profile['open_to_work'] = true;
        } elseif ($forced === 'no') {
            $profile['open_to_work'] = false;
        } else {
            $gh = Github::fetchUser(mh_github_login());
            $profile['open_to_work'] = mh_is_hireable($gh);
        }

        return $profile;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function fromUserinfo(): ?array
    {
        $token = linkedin_token();
        if ($token === '') {
            return null;
        }

        $res = wp_remote_get('https://api.linkedin.com/v2/userinfo', [
            'timeout' => 12,
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
                'User-Agent' => 'matthummel-theme/3 (+'.home_url('/').')',
            ],
        ]);

        if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200) {
            return null;
        }

        $data = json_decode((string) wp_remote_retrieve_body($res), true);
        if (! is_array($data)) {
            return null;
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $name = trim(((string) ($data['given_name'] ?? '')).' '.((string) ($data['family_name'] ?? '')));
        }

        $headline = trim((string) get_theme_mod('mh_li_headline', ''));
        if ($headline === '') {
            $headline = __('Full-stack developer and WordPress specialist — PHP, JavaScript, React, APIs, and maintainable web platforms.', 'sage');
        }

        return [
            'name' => $name !== '' ? $name : 'Matt Hummel',
            'headline' => $headline,
            'about' => trim((string) get_theme_mod('mh_li_about', '')),
            'picture' => (string) ($data['picture'] ?? ''),
            'url' => self::profileUrl(),
            'location' => trim((string) get_theme_mod('mh_li_location', __('Gettysburg, PA', 'sage'))),
            'open_to_work' => false,
            'source' => 'api',
        ];
    }

    /**
     * Soft scrape of public OG tags (often blocked — fail soft).
     *
     * @return array<string, mixed>|null
     */
    protected static function fromOpenGraph(string $url): ?array
    {
        $res = wp_remote_get($url, [
            'timeout' => 10,
            'redirection' => 3,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; matthummel-theme/3; +'.home_url('/').')',
                'Accept' => 'text/html',
            ],
        ]);

        if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200) {
            return null;
        }

        $html = (string) wp_remote_retrieve_body($res);
        if ($html === '') {
            return null;
        }

        $og = static function (string $prop) use ($html): string {
            if (preg_match('/property=["\']og:'.preg_quote($prop, '/').'["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) {
                return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            if (preg_match('/content=["\']([^"\']+)["\']\s+property=["\']og:'.preg_quote($prop, '/').'["\']/i', $html, $m)) {
                return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            return '';
        };

        $title = $og('title');
        $desc = $og('description');
        $image = $og('image');
        if ($title === '' && $desc === '') {
            return null;
        }

        $name = $title;
        $headline = '';
        if (str_contains($title, ' - ')) {
            [$name, $headline] = array_map('trim', explode(' - ', $title, 2));
        } elseif (str_contains($title, ' | ')) {
            [$name, $headline] = array_map('trim', explode(' | ', $title, 2));
        }

        if ($headline === '') {
            $headline = trim((string) get_theme_mod('mh_li_headline', $desc));
        }

        return [
            'name' => $name !== '' ? $name : 'Matt Hummel',
            'headline' => $headline,
            'about' => $desc !== '' ? $desc : trim((string) get_theme_mod('mh_li_about', '')),
            'picture' => $image,
            'url' => $url,
            'location' => trim((string) get_theme_mod('mh_li_location', __('Gettysburg, PA', 'sage'))),
            'open_to_work' => false,
            'source' => 'og',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function fallback(string $url): array
    {
        $gh = Github::fetchUser(mh_github_login());
        $headline = trim((string) get_theme_mod('mh_li_headline', ''));
        if ($headline === '') {
            $headline = ! empty($gh['bio'])
                ? (string) $gh['bio']
                : __('Full-stack developer and WordPress specialist — PHP, JavaScript, React, APIs, and maintainable web platforms.', 'sage');
        }

        return [
            'name' => ! empty($gh['name']) ? (string) $gh['name'] : 'Matt Hummel',
            'headline' => $headline,
            'about' => trim((string) get_theme_mod('mh_li_about', '')),
            'picture' => mh_profile_photo_url(),
            'url' => $url,
            'location' => trim((string) get_theme_mod(
                'mh_li_location',
                ! empty($gh['location']) ? (string) $gh['location'] : __('Gettysburg, PA', 'sage')
            )),
            'open_to_work' => false,
            'source' => 'fallback',
        ];
    }
}

add_action('customize_register', function (\WP_Customize_Manager $wp): void {
    $wp->add_section('mh_linkedin', [
        'title' => __('LinkedIn', 'sage'),
        'priority' => 34,
        'description' => __('Optional OpenID access token for live name/photo on the Hire page (Sign In with LinkedIn → /v2/userinfo). Work history stays in Page content. Token: MH_LINKEDIN_TOKEN or the field below.', 'sage'),
    ]);

    $wp->add_setting('mh_li_token', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp->add_control('mh_li_token', [
        'label' => __('Access token (OpenID)', 'sage'),
        'section' => 'mh_linkedin',
        'type' => 'password',
    ]);

    $wp->add_setting('mh_li_headline', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp->add_control('mh_li_headline', [
        'label' => __('Headline override', 'sage'),
        'section' => 'mh_linkedin',
        'type' => 'text',
    ]);

    $wp->add_setting('mh_li_about', [
        'default' => '',
        'sanitize_callback' => 'sanitize_textarea_field',
    ]);
    $wp->add_control('mh_li_about', [
        'label' => __('About blurb', 'sage'),
        'section' => 'mh_linkedin',
        'type' => 'textarea',
    ]);

    $wp->add_setting('mh_li_location', [
        'default' => 'Gettysburg, PA',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp->add_control('mh_li_location', [
        'label' => __('Location label', 'sage'),
        'section' => 'mh_linkedin',
        'type' => 'text',
    ]);

    $wp->add_setting('mh_li_open_to_work', [
        'default' => '',
        'sanitize_callback' => static function ($v) {
            return in_array($v, ['', 'yes', 'no'], true) ? $v : '';
        },
    ]);
    $wp->add_control('mh_li_open_to_work', [
        'label' => __('Open to work badge', 'sage'),
        'section' => 'mh_linkedin',
        'type' => 'select',
        'choices' => [
            '' => __('Follow GitHub hireable', 'sage'),
            'yes' => __('Force on', 'sage'),
            'no' => __('Force off', 'sage'),
        ],
    ]);
});

add_action('customize_save_after', function (): void {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mh_li_profile_v1_%' OR option_name LIKE '_transient_timeout_mh_li_profile_v1_%'");
});
