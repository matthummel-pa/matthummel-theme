<?php

/**
 * Smoke test. Load with: wp eval-file /path/to/smoke.php
 * Mail is captured and never delivered.
 */

use MattHummel\Newsletter;

if (! defined('ABSPATH') || ! defined('MHN_VERSION')) {
    fwrite(STDERR, "Activate the plugin and run this with wp eval-file.\n");
    exit(1);
}

$GLOBALS['mhn_fail'] = 0;

function mhn_add_extra_template(array $templates): array
{
    $templates['extra'] = [
        'label' => 'Extra',
        'summary' => 'Added later.',
        'min_posts' => 0,
        'max_posts' => 0,
        'has_image' => false,
        'has_button' => false,
        'has_ps' => false,
    ];

    return $templates;
}

function mhn_check(bool $ok, string $label): void
{
    echo ($ok ? 'OK   ' : 'FAIL ').$label.PHP_EOL;
    if (! $ok) {
        $GLOBALS['mhn_fail']++;
    }
}

wp_set_current_user(1);
update_option('admin_email', 'owner@example.com');
wp_update_user(['ID' => 1, 'user_email' => 'owner@example.com']);
delete_option('mhn_settings');

foreach ([
    'legacy@example.com',
    'new@example.com',
    'batch@example.com',
    'import-ok@example.com',
    'pending-quiet@example.com',
] as $sampleEmail) {
    $sample = Newsletter\find_by_email($sampleEmail);
    if ($sample) {
        Newsletter\delete_subscriber((int) $sample['id']);
    }
}

$GLOBALS['mhn_outbox'] = [];
add_filter('pre_wp_mail', static function ($shortCircuit, array $atts) {
    $GLOBALS['mhn_outbox'][] = $atts;

    return true;
}, 10, 2);

add_filter('mhn_send_allowlist', static function () {
    return ['batch@example.com'];
});

if (function_exists('App\\mh_newsletter_install')) {
    App\mh_newsletter_install();
}
global $wpdb;
$legacy = $wpdb->prefix.'mh_newsletter';
$wpdb->query($wpdb->prepare("DELETE FROM {$legacy} WHERE email = %s", 'legacy@example.com'));
$wpdb->insert($legacy, [
    'email' => 'legacy@example.com',
    'created_at' => current_time('mysql'),
], ['%s', '%s']);

$copied = Newsletter\migrate_legacy();
$legacyRow = Newsletter\find_by_email('legacy@example.com');
mhn_check($legacyRow !== null && $legacyRow['status'] === 'subscribed', 'legacy row is subscribed');
mhn_check($legacyRow !== null && $legacyRow['opt_in'] === 'legacy_single', 'legacy row is marked single opt-in');
mhn_check($copied >= 1, 'legacy migration copies a new row');
mhn_check(Newsletter\migrate_legacy() === 0, 'legacy migration does not duplicate');
$legacyCount = (int) $wpdb->get_var($wpdb->prepare(
    'SELECT COUNT(*) FROM '.Newsletter\subscribers_table().' WHERE email = %s',
    'legacy@example.com'
));
mhn_check($legacyCount === 1, 'legacy address exists once');
$legacyStill = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$legacy} WHERE email = %s", 'legacy@example.com'));
mhn_check((int) $legacyStill === 1, 'legacy source row is kept');

$updates = get_page_by_path('get-updates');
$prefs = get_page_by_path('email-preferences');
mhn_check($updates instanceof WP_Post && has_shortcode($updates->post_content, 'mhn_updates'), 'get-updates page exists');
$probe = Newsletter\ensure_page('mhn-activation-probe', 'Probe', 'Leave this body', '');
wp_update_post([
    'ID' => $probe,
    'post_title' => 'Custom title keep me',
    'post_content' => 'Custom body that must stay',
]);
Newsletter\ensure_page('mhn-activation-probe', 'Probe', 'Leave this body', '');
$beforeActivateMail = count($GLOBALS['mhn_outbox']);
Newsletter\activate();
Newsletter\activate();
$kept = get_post($probe);
mhn_check($kept instanceof WP_Post && $kept->post_title === 'Custom title keep me', 'activation does not replace an existing page title');
mhn_check($kept instanceof WP_Post && $kept->post_content === 'Custom body that must stay', 'activation does not replace existing page content');
mhn_check(count($GLOBALS['mhn_outbox']) === $beforeActivateMail, 'activation does not send email');
wp_delete_post($probe, true);
mhn_check($prefs instanceof WP_Post && has_shortcode($prefs->post_content, 'mhn_preferences'), 'preferences page exists');
mhn_check(Newsletter\settings()['auto_send'] === 0, 'auto-send is off');
mhn_check(Newsletter\settings()['track_opens'] === 0 && Newsletter\settings()['track_clicks'] === 0, 'tracking is off');

$beforeMail = count($GLOBALS['mhn_outbox']);
$code = Newsletter\subscribe_address('new@example.com', 'Ada', 'page');
mhn_check($code === 'confirm', 'new signup asks for confirmation');
mhn_check(count($GLOBALS['mhn_outbox']) === $beforeMail + 1, 'confirmation is the only new message');
$confirm = $GLOBALS['mhn_outbox'][$beforeMail];
mhn_check(($confirm['to'] ?? '') === 'new@example.com', 'confirmation goes to the new address');
$confirmHeaders = is_array($confirm['headers'] ?? null) ? implode("\n", $confirm['headers']) : (string) ($confirm['headers'] ?? '');
mhn_check(! str_contains($confirmHeaders, 'List-Unsubscribe'), 'confirmation has no list-unsubscribe header');

$pending = Newsletter\find_by_email('new@example.com');
mhn_check($pending !== null && $pending['status'] === 'pending', 'new address stays pending');
preg_match('/mhn_confirm=([a-f0-9]{32})/', (string) ($confirm['message'] ?? ''), $tokenMatch);
mhn_check(isset($tokenMatch[1]), 'confirmation link is in the message');
if (isset($tokenMatch[1])) {
    mhn_check(Newsletter\confirm_subscriber($tokenMatch[1]) === 'ok', 'confirmation link subscribes');
}
$confirmed = Newsletter\find_by_email('new@example.com');
mhn_check($confirmed !== null && $confirmed['status'] === 'subscribed', 'confirmed address is subscribed');

$beforePublish = count($GLOBALS['mhn_outbox']);
$postId = wp_insert_post([
    'post_type' => 'post',
    'post_status' => 'publish',
    'post_title' => 'Smoke note',
    'post_content' => 'A short note about a WordPress build.',
]);
$issueIds = get_posts([
    'post_type' => 'newsletter_issue',
    'post_status' => 'any',
    'fields' => 'ids',
    'meta_key' => '_mhn_source_post',
    'meta_value' => (string) $postId,
    'posts_per_page' => 1,
    'no_found_rows' => true,
]);
$issueId = (int) ($issueIds[0] ?? 0);
mhn_check($issueId > 0, 'publishing a post creates an issue');
mhn_check(Newsletter\issue_status($issueId) === 'draft', 'generated issue stays a draft');
mhn_check(count($GLOBALS['mhn_outbox']) === $beforePublish, 'publishing does not send mail');

$message = Newsletter\issue_message($issueId, [
    'id' => '0',
    'email' => 'you@example.com',
    'first_name' => 'Ada',
    'status' => 'preview',
], true);
$html = $message['html'];
mhn_check(str_contains($html, 'width="600"'), 'email shell is 600px');
mhn_check(str_contains($html, 'v:roundrect'), 'read more button includes Outlook VML');
mhn_check(str_contains($html, 'mhn-preheader'), 'preheader is hidden in the message');
mhn_check(str_contains($html, 'Unsubscribe'), 'footer includes unsubscribe');
mhn_check(str_contains($html, 'Manage preferences'), 'footer includes preferences');
mhn_check(str_contains($html, 'Gettysburg, PA'), 'footer includes the mailing address');
mhn_check(! str_contains($html, 'mhn_open'), 'open tracking is absent by default');
mhn_check(str_contains($message['text'], 'Smoke note'), 'plain text includes the title');
mhn_check(str_contains($message['text'], 'Unsubscribe'), 'plain text includes unsubscribe');

$columnId = wp_insert_post([
    'post_type' => 'newsletter_issue',
    'post_status' => 'draft',
    'post_title' => 'Columns',
    'post_content' => '<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>Left *|FNAME|*</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>Right</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->'
        .'<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link" href="https://matthummel.com/notes/">Read more</a></div><!-- /wp:button --></div><!-- /wp:buttons -->',
]);
update_post_meta((int) $columnId, '_mhn_subject', 'Column test');
update_post_meta((int) $columnId, '_mhn_preheader', 'Two columns');
$columnHtml = Newsletter\issue_message((int) $columnId, [
    'id' => '9',
    'email' => 'ada@example.com',
    'first_name' => 'Ada',
    'status' => 'subscribed',
], true)['html'];
mhn_check(str_contains($columnHtml, 'mhn-col'), 'columns render as email columns');
mhn_check(str_contains($columnHtml, 'Left Ada'), 'merge tag replaces the first name');
mhn_check(str_contains($columnHtml, 'max-width:620px') || str_contains($columnHtml, 'max-width:620px'), 'mobile media query is present');
mhn_check(str_contains($columnHtml, '@media (prefers-color-scheme: dark)') || str_contains($columnHtml, 'prefers-color-scheme:dark'), 'dark mode query is present');

$context = new WP_Block_Editor_Context(['post' => get_post($issueId)]);
$allowed = Newsletter\allowed_blocks(true, $context);
mhn_check(is_array($allowed) && in_array('core/paragraph', $allowed, true) && in_array('core/list-item', $allowed, true), 'email-safe blocks are allowed');
mhn_check(is_array($allowed) && ! in_array('core/video', $allowed, true) && ! in_array('core/html', $allowed, true), 'unsafe blocks are excluded');

$beforeTest = count($GLOBALS['mhn_outbox']);
mhn_check(Newsletter\send_test($issueId) === true, 'test send reports success');
$testMail = $GLOBALS['mhn_outbox'][$beforeTest] ?? [];
mhn_check(($testMail['to'] ?? '') === 'owner@example.com', 'test send goes only to the current user');

Newsletter\insert_subscriber([
    'email' => 'batch@example.com',
    'first_name' => 'Batch',
    'status' => 'subscribed',
    'opt_in' => 'double',
    'source' => 'test',
    'confirmed_at' => current_time('mysql'),
]);
$beforeBatch = count($GLOBALS['mhn_outbox']);
Newsletter\set_issue_status($issueId, 'draft');
mhn_check(Newsletter\start_campaign($issueId) === true, 'campaign can be queued');
Newsletter\send_batch($issueId);
$batchMail = $GLOBALS['mhn_outbox'][$beforeBatch] ?? null;
mhn_check(is_array($batchMail) && ($batchMail['to'] ?? '') === 'batch@example.com', 'batch sends only to the allowed test address');
$batchHeaders = is_array($batchMail['headers'] ?? null) ? implode("\n", $batchMail['headers']) : (string) ($batchMail['headers'] ?? '');
mhn_check(str_contains($batchHeaders, 'List-Unsubscribe:'), 'campaign sets List-Unsubscribe');
mhn_check(str_contains($batchHeaders, 'List-Unsubscribe-Post: List-Unsubscribe=One-Click'), 'campaign sets one-click unsubscribe');
mhn_check(Newsletter\issue_status($issueId) === 'sent', 'campaign finishes as sent');

foreach ($GLOBALS['mhn_outbox'] as $entry) {
    $to = (string) ($entry['to'] ?? '');
    mhn_check(str_ends_with(strtolower($to), '@example.com'), 'captured recipient stays on example.com: '.$to);
}

$csv = tempnam(sys_get_temp_dir(), 'mhn');
file_put_contents((string) $csv, "email,first_name\nimport-ok@example.com,Ada\nnot-an-email,Nope\n");
$beforeImport = count($GLOBALS['mhn_outbox']);
$imported = Newsletter\import_rows((string) $csv, 'consented', false, false);
mhn_check($imported['imported'] === 1 && $imported['invalid'] === 1, 'csv import counts consented rows');
$importRow = Newsletter\find_by_email('import-ok@example.com');
mhn_check($importRow !== null && $importRow['status'] === 'subscribed' && $importRow['opt_in'] === 'import', 'consented import is subscribed');
mhn_check(count($GLOBALS['mhn_outbox']) === $beforeImport, 'consented import does not send mail');

$csvPending = tempnam(sys_get_temp_dir(), 'mhn');
file_put_contents((string) $csvPending, "email,first_name\npending-quiet@example.com,Bea\n");
$quiet = Newsletter\import_rows((string) $csvPending, 'pending', false, false);
$quietRow = Newsletter\find_by_email('pending-quiet@example.com');
mhn_check($quiet['pending'] === 1 && $quietRow !== null && $quietRow['status'] === 'pending', 'pending import does not subscribe');
mhn_check(count($GLOBALS['mhn_outbox']) === $beforeImport, 'pending import does not send mail');

$templateSlugs = array_keys(Newsletter\email_templates());
mhn_check(in_array('blog-update', $templateSlugs, true), 'blog update template is registered');
mhn_check(in_array('blog-digest', $templateSlugs, true), 'blog digest template is registered');
mhn_check(in_array('custom', $templateSlugs, true), 'custom message template is registered');
mhn_check((string) get_post_meta($issueId, '_mhn_template', true) === 'blog-update', 'auto draft uses the blog update template');
mhn_check((string) get_post_meta($issueId, '_mhn_note', true) === '', 'auto draft note starts empty');
mhn_check((string) get_post_meta($issueId, '_mhn_ps', true) === '', 'auto draft P.S. starts empty');
$patterns = WP_Block_Patterns_Registry::get_instance();
mhn_check($patterns->is_registered('mhn/blog-update') && $patterns->is_registered('mhn/blog-digest') && $patterns->is_registered('mhn/custom'), 'templates are block patterns');

$rich = Newsletter\render_blocks(Newsletter\rich_text_blocks('<p>Hello <strong>there</strong></p><ul><li>One item</li></ul>'), 'Alt');
mhn_check(str_contains($rich, '<strong>there</strong>'), 'rich text keeps bold');
mhn_check(str_contains($rich, 'One item'), 'rich text keeps a list');

$beforeWizard = count($GLOBALS['mhn_outbox']);
$draft = Newsletter\wizard_save([
    'mhn_action' => 'next',
    'mhn_step' => '1',
    'mhn_template' => 'custom',
    'mhn_issue' => '0',
]);
mhn_check($draft['id'] > 0 && $draft['step'] === 2, 'wizard creates a draft on the content step');
mhn_check(count($GLOBALS['mhn_outbox']) === $beforeWizard, 'wizard save does not send mail');
$emptyNote = Newsletter\wizard_save([
    'mhn_action' => 'next',
    'mhn_step' => '2',
    'mhn_issue' => (string) $draft['id'],
    'mhn_template' => 'custom',
    'mhn_note' => '<p></p>',
]);
mhn_check($emptyNote['error'] === 'message' && $emptyNote['step'] === 2, 'custom message requires a note');
$withNote = Newsletter\wizard_save([
    'mhn_action' => 'next',
    'mhn_step' => '2',
    'mhn_issue' => (string) $draft['id'],
    'mhn_template' => 'custom',
    'mhn_note' => '<p>Hello <strong>there</strong></p><ul><li>One item</li></ul>',
    'mhn_button_label' => 'Read the note',
    'mhn_button_url' => 'https://matthummel.com/notes/',
]);
mhn_check($withNote['error'] === '' && $withNote['step'] === 3, 'custom message step continues');
$customHtml = Newsletter\issue_message((int) $draft['id'], [
    'id' => '0',
    'email' => 'you@example.com',
    'first_name' => '',
    'status' => 'preview',
], true)['html'];
mhn_check(str_contains($customHtml, '<strong>there</strong>'), 'custom message renders bold');
mhn_check(str_contains($customHtml, 'One item'), 'custom message renders a list');
mhn_check(str_contains($customHtml, 'v:roundrect'), 'custom message button is bulletproof');
mhn_check(count($GLOBALS['mhn_outbox']) === $beforeWizard, 'building a draft does not send mail');

$postA = wp_insert_post([
    'post_type' => 'post',
    'post_status' => 'publish',
    'post_title' => 'Digest one',
    'post_content' => 'First digest post body for the card.',
]);
$postB = wp_insert_post([
    'post_type' => 'post',
    'post_status' => 'publish',
    'post_title' => 'Digest two',
    'post_content' => 'Second digest post body for the card.',
]);
$digest = Newsletter\wizard_save([
    'mhn_action' => 'stay',
    'mhn_step' => '2',
    'mhn_template' => 'blog-digest',
    'mhn_issue' => '0',
    'mhn_note' => '<p>A note above the posts.</p>',
    'mhn_ps' => '<p>A line under the posts.</p>',
    'mhn_posts' => [(string) $postA, (string) $postB],
]);
$digestHtml = Newsletter\issue_message((int) $digest['id'], [
    'id' => '0',
    'email' => 'you@example.com',
    'first_name' => '',
    'status' => 'preview',
], true)['html'];
mhn_check(str_contains($digestHtml, 'Digest one') && str_contains($digestHtml, 'Digest two'), 'digest lists the selected posts');
mhn_check(str_contains($digestHtml, 'A note above the posts.'), 'digest keeps the note');
mhn_check(str_contains($digestHtml, 'A line under the posts.'), 'digest keeps the P.S.');
mhn_check(str_contains($digestHtml, 'mhn-group'), 'digest posts render as cards');

$refused = Newsletter\wizard_save([
    'mhn_action' => 'send',
    'mhn_step' => '5',
    'mhn_issue' => (string) $draft['id'],
]);
mhn_check(in_array($refused['error'], ['confirm', 'empty'], true), 'send without confirm does not go out');
mhn_check(Newsletter\issue_status((int) $draft['id']) === 'draft', 'refused send stays a draft');
mhn_check(count($GLOBALS['mhn_outbox']) === $beforeWizard, 'refused send adds no mail');

add_filter('mhn_email_templates', 'mhn_add_extra_template');
mhn_check(isset(Newsletter\email_templates()['extra']), 'templates can be extended');
remove_all_filters('mhn_email_templates');

$batch = Newsletter\find_by_email('batch@example.com');
if ($batch) {
    mhn_check(Newsletter\unsubscribe((int) $batch['id']) === true, 'unsubscribe works without a login');
    $after = Newsletter\find((int) $batch['id']);
    mhn_check($after !== null && $after['status'] === 'unsubscribed', 'unsubscribed status is stored');
}

if ($GLOBALS['mhn_fail'] > 0) {
    echo $GLOBALS['mhn_fail']." failed\n";
    exit(1);
}

echo "All checks passed\n";
exit(0);
