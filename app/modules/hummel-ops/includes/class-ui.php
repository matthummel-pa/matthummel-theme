<?php
defined('ABSPATH') || exit;

/**
 * Shared interface pieces, so every Hummel Ops screen uses the same header, summary strip,
 * status pill, and empty state. The matching JS builders live in assets/admin.js.
 */
class HOPS_UI
{
    /** Status pill. Tone: ok | bad | warn | info | neutral. The text always carries the meaning. */
    public static function pill($text, $tone = 'neutral')
    {
        return '<span class="hops-pill is-'.esc_attr($tone).'">'.esc_html($text).'</span>';
    }

    /** Result of an n8n run, as a pill. */
    public static function run_pill($code)
    {
        $code = (int) $code;
        if ($code >= 200 && $code < 300) {
            return self::pill('Succeeded', 'ok');
        }

        return self::pill($code ? 'Failed (HTTP '.$code.')' : 'Could not reach n8n', 'bad');
    }

    /** Page header: title, one-line purpose, and the screen's main actions ($actions is trusted HTML). */
    public static function head($title, $lede, $actions = '', $lede_id = '')
    {
        ?>
		<header class="hops-head">
			<div class="hops-head-text">
				<h1><?php echo esc_html($title); ?></h1>
				<p class="hops-lede"<?php echo $lede_id ? ' id="'.esc_attr($lede_id).'" aria-live="polite"' : ''; ?>><?php echo esc_html($lede); ?></p>
			</div>
			<?php if ($actions) { ?>
				<div class="hops-actions"><?php echo $actions; // phpcs:ignore WordPress.Security.EscapeOutput?></div>
			<?php } ?>
		</header>
		<hr class="wp-header-end">
		<?php
    }

    /** One tile of the summary strip. $sub_html is trusted HTML (a pill or short text). */
    public static function stat($label, $value, $sub_html = '', $id = '')
    {
        return '<div class="hops-stat"'.($id ? ' id="'.esc_attr($id).'"' : '').'><dt>'.esc_html($label).'</dt><dd class="hops-stat-value">'.esc_html($value).'</dd><dd class="hops-stat-sub">'.$sub_html.'</dd></div>'; // phpcs:ignore
    }

    public static function stats($tiles_html)
    {
        echo '<dl class="hops-stats">'.$tiles_html.'</dl>'; // phpcs:ignore WordPress.Security.EscapeOutput
    }

    /** Empty or first-run state with one clear next step. */
    public static function empty_state($title, $body, $cta_label = '', $cta_url = '', $primary = true)
    {
        $out = '<div class="hops-empty"><h2>'.esc_html($title).'</h2><p>'.esc_html($body).'</p>';
        if ($cta_label && $cta_url) {
            $out .= '<p><a class="button'.($primary ? ' button-primary' : '').'" href="'.esc_url($cta_url).'">'.esc_html($cta_label).'</a></p>';
        }

        return $out.'</div>';
    }

    public static function url($page, $hash = '')
    {
        return admin_url('admin.php?page='.$page).$hash;
    }

    /** "9:30 am today", "Oct 6, 2026", used wherever a run or release time appears. */
    public static function when($ts)
    {
        return wp_date('M j, g:i a', $ts);
    }

    public static function seconds($ms)
    {
        return number_format_i18n($ms / 1000, 1).' s';
    }
}
