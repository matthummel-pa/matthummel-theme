<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$mhn_heading = isset($attributes['heading']) && is_string($attributes['heading']) ? trim($attributes['heading']) : '';
$mhn_description = isset($attributes['description']) && is_string($attributes['description']) ? trim($attributes['description']) : '';
$mhn_button = isset($attributes['buttonText']) && is_string($attributes['buttonText']) ? trim($attributes['buttonText']) : '';

if ($mhn_heading === '') {
    $mhn_heading = __('Get updates', 'matthummel-newsletter');
}
if ($mhn_description === '') {
    $mhn_description = __('I keep the address on this site. I do not send it to a newsletter service.', 'matthummel-newsletter');
}
if ($mhn_button === '') {
    $mhn_button = __('Sign up', 'matthummel-newsletter');
}

$mhn_uid = wp_unique_id('mhn-signup-');
$mhn_heading_id = $mhn_uid.'-heading';
$mhn_help_id = $mhn_uid.'-help';
$mhn_status_id = $mhn_uid.'-status';
$mhn_email_id = $mhn_uid.'-email';
$mhn_hp_id = $mhn_uid.'-hp';
$mhn_describedby = $mhn_help_id.' '.$mhn_status_id;
?>
<div <?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes the wrapper attributes.
echo get_block_wrapper_attributes(['class' => 'mhn-signup-block']);
?>>
	<form
		class="mhn-signup-block__form"
		method="post"
		action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
		data-rest-url="<?php echo esc_url(rest_url('matthummel-newsletter/v1/subscribe')); ?>"
		data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>"
		data-error="<?php echo esc_attr__('Use a valid email, then try again.', 'matthummel-newsletter'); ?>"
		aria-labelledby="<?php echo esc_attr($mhn_heading_id); ?>"
	>
		<h2 class="mhn-signup-block__heading" id="<?php echo esc_attr($mhn_heading_id); ?>"><?php echo esc_html($mhn_heading); ?></h2>
		<p class="mhn-signup-block__help" id="<?php echo esc_attr($mhn_help_id); ?>"><?php echo esc_html($mhn_description); ?></p>
		<input type="hidden" name="action" value="mhn_signup">
		<input type="hidden" name="mhn_source" value="page">
		<?php wp_nonce_field('mhn_signup', 'mhn_signup_nonce'); ?>
		<p class="mhn-signup-block__hp" aria-hidden="true">
			<label for="<?php echo esc_attr($mhn_hp_id); ?>"><?php esc_html_e('Leave blank', 'matthummel-newsletter'); ?></label>
			<input id="<?php echo esc_attr($mhn_hp_id); ?>" type="text" name="mhn_hp" value="" tabindex="-1" autocomplete="off">
		</p>
		<div class="mhn-signup-block__row">
			<div class="mhn-signup-block__field">
				<label for="<?php echo esc_attr($mhn_email_id); ?>"><?php esc_html_e('Email', 'matthummel-newsletter'); ?></label>
				<input
					id="<?php echo esc_attr($mhn_email_id); ?>"
					name="mhn_email"
					type="email"
					required
					autocomplete="email"
					inputmode="email"
					placeholder="<?php echo esc_attr__('you@example.com', 'matthummel-newsletter'); ?>"
					aria-describedby="<?php echo esc_attr($mhn_describedby); ?>"
				>
			</div>
			<button type="submit"><?php echo esc_html($mhn_button); ?></button>
		</div>
		<p class="mhn-signup-block__status" id="<?php echo esc_attr($mhn_status_id); ?>" role="status" aria-live="polite"></p>
	</form>
</div>
