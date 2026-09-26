<?php

declare(strict_types=1);

namespace MattHummel\Newsletter;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Public profiles. A saved empty string stays empty, so clearing a field removes the link.
 *
 * @return array{social_site: string, social_github: string, social_linkedin: string, social_bluesky: string, social_youtube: string, social_instagram: string}
 */
function social_link_defaults(): array
{
    return [
        'social_site' => 'https://matthummel.com',
        'social_github' => 'https://github.com/matthummel-pa',
        'social_linkedin' => 'https://www.linkedin.com/in/matt-hummel-pa',
        'social_bluesky' => 'https://bsky.app/profile/matthummel.bsky.social',
        'social_youtube' => '',
        'social_instagram' => '',
    ];
}

/**
 * @return array<string, string>
 */
function letter_social_fields(): array
{
    return [
        'social_site' => __('Site', 'matthummel-newsletter'),
        'social_github' => 'GitHub',
        'social_linkedin' => 'LinkedIn',
        'social_bluesky' => 'Bluesky',
        'social_youtube' => 'YouTube',
        'social_instagram' => 'Instagram',
    ];
}

function subscribers_table(): string
{
    global $wpdb;

    return $wpdb->prefix.'mhn_subscribers';
}

function events_table(): string
{
    global $wpdb;

    return $wpdb->prefix.'mhn_events';
}

function archive_table(): string
{
    global $wpdb;

    return $wpdb->prefix.'mhn_archive';
}

/**
 * Saved settings merged over defaults. Auto-send and tracking stay off until turned on.
 *
 * @return array{
 *     from_name: string,
 *     from_email: string,
 *     reply_to: string,
 *     address: string,
 *     auto_draft: int,
 *     auto_send: int,
 *     track_opens: int,
 *     track_clicks: int,
 *     batch_size: int,
 *     intro: string,
 *     signoff: string,
 *     welcome_subject: string,
 *     welcome_body: string,
 *     welcome_points: string,
 *     letter_style: string,
 *     letter_masthead: string,
 *     letter_button: string,
 *     letter_font: string,
 *     letter_size: string,
 *     letter_variant: string,
 *     header_image: string,
 *     header_alt: string,
 *     social_site: string,
 *     social_github: string,
 *     social_linkedin: string,
 *     social_bluesky: string,
 *     social_youtube: string,
 *     social_instagram: string,
 *     skip_sent_post: int,
 *     rule_categories: list<string>
 * }
 */
function settings(): array
{
    $saved = get_option('mhn_settings', []);
    if (! is_array($saved)) {
        $saved = [];
    }

    $admin = (string) get_option('admin_email');
    $copy = layout_copy_defaults();
    $social = social_link_defaults();
    $defaults = [
        'from_name' => (string) get_bloginfo('name'),
        'from_email' => $admin,
        'reply_to' => $admin,
        'address' => 'Gettysburg, PA',
        'auto_draft' => 1,
        'auto_send' => 0,
        'track_opens' => 0,
        'track_clicks' => 0,
        'batch_size' => 25,
        'intro' => $copy['intro'],
        'signoff' => $copy['signoff'],
        'welcome_subject' => $copy['welcome_subject'],
        'welcome_body' => $copy['welcome_body'],
        'welcome_points' => $copy['welcome_points'],
        'letter_style' => 'card',
        'letter_masthead' => 'left',
        'letter_button' => 'solid',
        'letter_font' => 'sans',
        'letter_size' => 'regular',
        'letter_variant' => 'detailed',
        'header_image' => '',
        'header_alt' => '',
        'social_site' => $social['social_site'],
        'social_github' => $social['social_github'],
        'social_linkedin' => $social['social_linkedin'],
        'social_bluesky' => $social['social_bluesky'],
        'social_youtube' => $social['social_youtube'],
        'social_instagram' => $social['social_instagram'],
        'skip_sent_post' => 1,
        'rule_categories' => [],
    ];

    $merged = array_merge($defaults, $saved);
    $intro = trim((string) $merged['intro']);
    $signoff = trim((string) $merged['signoff']);
    $welcomeSubject = trim((string) $merged['welcome_subject']);
    $welcomeBody = trim((string) $merged['welcome_body']);
    $welcomePoints = trim((string) $merged['welcome_points']);

    return [
        'from_name' => (string) $merged['from_name'],
        'from_email' => (string) $merged['from_email'],
        'reply_to' => (string) $merged['reply_to'],
        'address' => (string) $merged['address'],
        'auto_draft' => (int) $merged['auto_draft'] === 1 ? 1 : 0,
        'auto_send' => (int) $merged['auto_send'] === 1 ? 1 : 0,
        'track_opens' => (int) $merged['track_opens'] === 1 ? 1 : 0,
        'track_clicks' => (int) $merged['track_clicks'] === 1 ? 1 : 0,
        'batch_size' => max(5, min(100, (int) $merged['batch_size'])),
        'intro' => $intro !== '' ? $intro : $copy['intro'],
        'signoff' => $signoff !== '' ? $signoff : $copy['signoff'],
        'welcome_subject' => $welcomeSubject !== '' ? $welcomeSubject : $copy['welcome_subject'],
        'welcome_body' => $welcomeBody !== '' ? $welcomeBody : $copy['welcome_body'],
        'welcome_points' => $welcomePoints !== '' ? $welcomePoints : $copy['welcome_points'],
        'letter_style' => choice_from_input(['letter_style' => $merged['letter_style'] ?? ''], 'letter_style', letter_style_choices(), 'card'),
        'letter_masthead' => choice_from_input(['letter_masthead' => $merged['letter_masthead'] ?? ''], 'letter_masthead', letter_masthead_choices(), 'left'),
        'letter_button' => choice_from_input(['letter_button' => $merged['letter_button'] ?? ''], 'letter_button', letter_button_choices(), 'solid'),
        'letter_font' => choice_from_input(['letter_font' => $merged['letter_font'] ?? ''], 'letter_font', letter_font_choices(), 'sans'),
        'letter_size' => choice_from_input(['letter_size' => $merged['letter_size'] ?? ''], 'letter_size', letter_size_choices(), 'regular'),
        'letter_variant' => choice_from_input(
            trim((string) ($merged['letter_variant'] ?? '')) !== '' ? ['letter_variant' => $merged['letter_variant']] : [],
            'letter_variant',
            letter_variant_choices(),
            'detailed'
        ),
        'header_image' => sanitize_letter_asset_url((string) ($merged['header_image'] ?? '')),
        'header_alt' => mb_substr(sanitize_text_field((string) ($merged['header_alt'] ?? '')), 0, 140),
        'social_site' => sanitize_https_url((string) ($merged['social_site'] ?? '')),
        'social_github' => sanitize_https_url((string) ($merged['social_github'] ?? '')),
        'social_linkedin' => sanitize_https_url((string) ($merged['social_linkedin'] ?? '')),
        'social_bluesky' => sanitize_https_url((string) ($merged['social_bluesky'] ?? '')),
        'social_youtube' => sanitize_https_url((string) ($merged['social_youtube'] ?? '')),
        'social_instagram' => sanitize_https_url((string) ($merged['social_instagram'] ?? '')),
        'skip_sent_post' => (int) $merged['skip_sent_post'] === 1 ? 1 : 0,
        'rule_categories' => sanitize_rule_categories($merged['rule_categories'] ?? []),
    ];
}

/**
 * Reusable letter copy. Empty saved values fall back to these.
 *
 * @return array{intro: string, signoff: string, welcome_subject: string, welcome_body: string, welcome_points: string}
 */
function layout_copy_defaults(): array
{
    return [
        'intro' => __('Here is what I have been building. The useful part is what you can edit later.', 'matthummel-newsletter'),
        'signoff' => __('Talk soon,', 'matthummel-newsletter'),
        'welcome_subject' => __('You are on the list', 'matthummel-newsletter'),
        'welcome_body' => __("Thanks for signing up.\n\nNew notes arrive by email. I keep the address on this site. I do not send it to a newsletter service.\n\nYou can unsubscribe any time.", 'matthummel-newsletter'),
        'welcome_points' => __("I write when I ship something worth reading.\nYour address stays on this site.\nUnsubscribe any time from the footer.", 'matthummel-newsletter'),
    ];
}

/**
 * @param  array<string, mixed>  $input
 */
function update_settings(array $input): void
{
    $admin = (string) get_option('admin_email');
    $from = sanitize_email((string) ($input['from_email'] ?? ''));
    $reply = sanitize_email((string) ($input['reply_to'] ?? ''));
    $name = sanitize_text_field((string) ($input['from_name'] ?? ''));
    $address = sanitize_textarea_field((string) ($input['address'] ?? ''));
    $current = settings();
    $defaults = layout_copy_defaults();

    update_option('mhn_settings', [
        'from_name' => $name !== '' ? $name : (string) get_bloginfo('name'),
        'from_email' => is_email($from) ? $from : $admin,
        'reply_to' => is_email($reply) ? $reply : $admin,
        'address' => $address !== '' ? $address : 'Gettysburg, PA',
        'auto_draft' => empty($input['auto_draft']) ? 0 : 1,
        'auto_send' => empty($input['auto_send']) ? 0 : 1,
        'track_opens' => empty($input['track_opens']) ? 0 : 1,
        'track_clicks' => empty($input['track_clicks']) ? 0 : 1,
        'batch_size' => max(5, min(100, absint($input['batch_size'] ?? 25))),
        'intro' => posted_copy($input, 'intro', $current['intro'], $defaults['intro'], false),
        'signoff' => posted_copy($input, 'signoff', $current['signoff'], $defaults['signoff'], false),
        'welcome_subject' => posted_copy($input, 'welcome_subject', $current['welcome_subject'], $defaults['welcome_subject'], false),
        'welcome_body' => posted_copy($input, 'welcome_body', $current['welcome_body'], $defaults['welcome_body'], true),
        'welcome_points' => posted_copy($input, 'welcome_points', $current['welcome_points'], $defaults['welcome_points'], true),
        'letter_style' => choice_from_input($input, 'letter_style', letter_style_choices(), $current['letter_style']),
        'letter_masthead' => choice_from_input($input, 'letter_masthead', letter_masthead_choices(), $current['letter_masthead']),
        'letter_button' => choice_from_input($input, 'letter_button', letter_button_choices(), $current['letter_button']),
        'letter_font' => choice_from_input($input, 'letter_font', letter_font_choices(), $current['letter_font']),
        'letter_size' => choice_from_input($input, 'letter_size', letter_size_choices(), $current['letter_size']),
        'letter_variant' => choice_from_input($input, 'letter_variant', letter_variant_choices(), $current['letter_variant']),
        'header_image' => sanitize_letter_asset_url((string) ($input['header_image'] ?? $current['header_image'])),
        'header_alt' => mb_substr(sanitize_text_field((string) ($input['header_alt'] ?? $current['header_alt'])), 0, 140),
        'social_site' => sanitize_https_url((string) ($input['social_site'] ?? $current['social_site'])),
        'social_github' => sanitize_https_url((string) ($input['social_github'] ?? $current['social_github'])),
        'social_linkedin' => sanitize_https_url((string) ($input['social_linkedin'] ?? $current['social_linkedin'])),
        'social_bluesky' => sanitize_https_url((string) ($input['social_bluesky'] ?? $current['social_bluesky'])),
        'social_youtube' => sanitize_https_url((string) ($input['social_youtube'] ?? $current['social_youtube'])),
        'social_instagram' => sanitize_https_url((string) ($input['social_instagram'] ?? $current['social_instagram'])),
        'skip_sent_post' => array_key_exists('skip_sent_post', $input) ? (empty($input['skip_sent_post']) ? 0 : 1) : $current['skip_sent_post'],
        'rule_categories' => array_key_exists('mhn_rules_present', $input)
            ? sanitize_rule_categories($input['rule_categories'] ?? [])
            : $current['rule_categories'],
    ]);
}

/**
 * @return array<string, string>
 */
function rule_category_choices(): array
{
    $choices = [];
    if (function_exists('get_categories')) {
        $terms = get_categories([
            'hide_empty' => false,
            'taxonomy' => 'category',
        ]);
        foreach (is_array($terms) ? $terms : [] as $term) {
            if (! $term instanceof \WP_Term || $term->slug === 'uncategorized') {
                continue;
            }
            $choices['category:'.$term->term_id] = $term->name;
        }
    }

    foreach (project_category_labels() as $label) {
        $token = project_label_token($label);
        if ($token === '' || isset($choices[$token])) {
            continue;
        }
        $choices[$token] = $label;
    }

    return $choices;
}

/**
 * @return list<string>
 */
function project_category_labels(): array
{
    if (! function_exists('get_posts') || ! function_exists('get_post_meta')) {
        return [];
    }
    if (function_exists('post_type_exists') && ! post_type_exists('project')) {
        return [];
    }

    $posts = get_posts([
        'post_type' => 'project',
        'post_status' => 'publish',
        'posts_per_page' => 100,
        'no_found_rows' => true,
        'orderby' => 'title',
        'order' => 'ASC',
        'fields' => 'ids',
    ]);
    $labels = [];
    foreach (is_array($posts) ? $posts : [] as $id) {
        $label = trim((string) get_post_meta((int) $id, '_mh_project_cat', true));
        if ($label === '' || in_array($label, $labels, true)) {
            continue;
        }
        $labels[] = $label;
    }
    sort($labels);

    return $labels;
}

/**
 * @return list<string>
 */
function sanitize_rule_categories(mixed $input): array
{
    if (! is_array($input)) {
        return [];
    }

    $choices = function_exists('get_categories') ? rule_category_choices() : [];
    $clean = [];
    foreach ($input as $value) {
        $value = sanitize_text_field((string) $value);
        if (preg_match('/^(?:category:[1-9]\d*|project:[a-z0-9\-]+)$/', $value) !== 1) {
            continue;
        }
        if ($choices !== [] && ! isset($choices[$value])) {
            continue;
        }
        $clean[] = $value;
    }

    return array_values(array_unique($clean));
}

require_once __DIR__.'/styles.php';

/**
 * @param  array<string, mixed>  $input
 */
function posted_copy(array $input, string $key, string $current, string $fallback, bool $multiline): string
{
    if (! array_key_exists($key, $input)) {
        return $current !== '' ? $current : $fallback;
    }

    $value = $multiline
        ? sanitize_textarea_field((string) $input[$key])
        : sanitize_text_field((string) $input[$key]);
    $value = trim($value);
    $limit = $multiline ? 4000 : 200;
    if (mb_strlen($value) > $limit) {
        $value = mb_substr($value, 0, $limit);
    }

    return $value !== '' ? $value : $fallback;
}
