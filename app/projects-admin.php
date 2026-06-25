<?php

/**
 * Projects custom admin area: "Project Details" meta box (GitHub owner/repo,
 * eyebrow, demo URL) feeding the project template, plus admin list columns.
 */

namespace App;

add_action('add_meta_boxes', function () {
    add_meta_box('mh_project_details', __('Project Details', 'matthummel'), 'App\\mh_project_metabox', 'projects', 'side', 'high');
});

function mh_project_metabox($post)
{
    wp_nonce_field('mh_project_save', 'mh_project_nonce');
    $fields = [
        '_mh_gh_owner' => [__('GitHub owner', 'matthummel'), (function_exists('get_theme_mod') ? get_theme_mod('mh_proj_owner', 'matthummel-pa') : 'matthummel-pa')],
        '_mh_gh_repo'  => [__('GitHub repo', 'matthummel'), $post->post_name],
        '_mh_eyebrow'  => [__('Eyebrow / label', 'matthummel'), 'GitHub Project'],
        '_mh_demo_url' => [__('Live demo URL', 'matthummel'), 'https://'],
    ];
    echo '<div class="mh-project-meta">';
    foreach ($fields as $key => $f) {
        $val = get_post_meta($post->ID, $key, true);
        echo '<p style="margin:0 0 12px">';
        echo '<label for="' . esc_attr($key) . '" style="display:block;font-weight:600;margin-bottom:4px">' . esc_html($f[0]) . '</label>';
        echo '<input type="text" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '" placeholder="' . esc_attr($f[1]) . '" style="width:100%">';
        echo '</p>';
    }
    echo '<p class="description">' . esc_html__('Owner + repo power the live GitHub data section. Leave repo blank to use the post slug.', 'matthummel') . '</p>';
    echo '</div>';
}

add_action('save_post_projects', function ($post_id) {
    if (! isset($_POST['mh_project_nonce']) || ! wp_verify_nonce($_POST['mh_project_nonce'], 'mh_project_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (! current_user_can('edit_post', $post_id)) {
        return;
    }
    foreach (['_mh_gh_owner', '_mh_gh_repo', '_mh_eyebrow', '_mh_demo_url'] as $key) {
        if (isset($_POST[$key])) {
            $raw = wp_unslash($_POST[$key]);
            $val = ($key === '_mh_demo_url') ? esc_url_raw($raw) : sanitize_text_field($raw);
            update_post_meta($post_id, $key, $val);
        }
    }
});

/** Admin list columns. */
add_filter('manage_projects_posts_columns', function ($cols) {
    $new = [];
    foreach ($cols as $k => $v) {
        $new[$k] = $v;
        if ($k === 'title') {
            $new['mh_repo']    = __('Repo', 'matthummel');
            $new['mh_eyebrow'] = __('Label', 'matthummel');
        }
    }
    return $new;
});

add_action('manage_projects_posts_custom_column', function ($col, $post_id) {
    if ($col === 'mh_repo') {
        $owner = get_post_meta($post_id, '_mh_gh_owner', true) ?: get_theme_mod('mh_proj_owner', 'matthummel-pa');
        $repo  = get_post_meta($post_id, '_mh_gh_repo', true);
        if ($repo) {
            echo '<a href="' . esc_url("https://github.com/{$owner}/{$repo}") . '" target="_blank" rel="noopener">' . esc_html("{$owner}/{$repo}") . '</a>';
        } else {
            echo '&mdash;';
        }
    }
    if ($col === 'mh_eyebrow') {
        echo esc_html(get_post_meta($post_id, '_mh_eyebrow', true) ?: '—');
    }
}, 10, 2);
