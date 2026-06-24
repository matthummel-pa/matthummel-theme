<?php

/**
 * Plugin-free contact form handler + small archive tweak.
 */

namespace App;

/** Clean archive titles ("Category: Foo" -> "Foo"). */
add_filter('get_the_archive_title_prefix', '__return_empty_string');

/** Handle the contact form submission (template-contact.blade.php). */
add_action('init', function () {
    if (! isset($_POST['action']) || $_POST['action'] !== 'mh_contact') {
        return;
    }

    $back = wp_get_referer() ?: home_url('/');
    $back = remove_query_arg('contact', $back);

    $redirect = function ($status) use ($back) {
        wp_safe_redirect(add_query_arg('contact', $status, $back));
        exit;
    };

    $nonce = isset($_POST['mh_contact_nonce']) ? $_POST['mh_contact_nonce'] : '';
    if (! wp_verify_nonce($nonce, 'mh_contact')) {
        $redirect('error');
    }

    // Honeypot: bots fill this; pretend success and bail.
    if (! empty($_POST['mh_hp'])) {
        $redirect('success');
    }

    $name    = sanitize_text_field($_POST['mh_name'] ?? '');
    $email   = sanitize_email($_POST['mh_email'] ?? '');
    $subject = sanitize_text_field($_POST['mh_subject'] ?? '');
    $message = sanitize_textarea_field($_POST['mh_message'] ?? '');

    if ($name === '' || ! is_email($email) || $message === '') {
        $redirect('error');
    }

    $to      = get_option('admin_email');
    $subject = $subject !== '' ? $subject : __('New contact form message', 'matthummel');
    $body    = "Name: {$name}\nEmail: {$email}\n\n{$message}";
    $headers = ['Reply-To: ' . $name . ' <' . $email . '>'];

    wp_mail($to, '[matthummel.com] ' . $subject, $body, $headers);

    $redirect('success');
});
