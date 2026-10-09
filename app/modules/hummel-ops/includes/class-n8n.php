<?php
defined('ABSPATH') || exit;

class HOPS_N8n
{
    const LOG = 'hops_run_log';

    const MAX_DRAFTS = 10;

    public static function init()
    {
        add_action('wp_ajax_hops_run_workflow', [__CLASS__, 'ajax_run']);
    }

    public static function ajax_run()
    {
        check_ajax_referer('hops');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Not allowed.'], 403);
        }

        $s = HOPS_Settings::get();
        $index = isset($_POST['index']) ? (int) $_POST['index'] : -1;
        if (! isset($s['workflows'][$index])) {
            wp_send_json_error(['message' => 'Workflow not found.'], 404);
        }
        $wf = $s['workflows'][$index];

        $drafts = isset($_POST['drafts']) ? max(1, min(self::MAX_DRAFTS, (int) $_POST['drafts'])) : 0;
        $payload = isset($_POST['payload']) ? trim(wp_unslash($_POST['payload'])) : '';
        if ($payload !== '') {
            $decoded = json_decode($payload, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => 'Payload is not valid JSON.'], 400);
            }
        } else {
            $user = wp_get_current_user();
            $decoded = [
                'source' => 'hummel-ops',
                'triggered_by' => $user->user_login,
                'triggered_at' => gmdate('c'),
            ];
        }

        // A workflow with the counter on receives the number of drafts to write.
        if (! empty($wf['counter']) && $drafts > 0 && is_array($decoded) && ! isset($decoded['drafts'])) {
            $decoded['drafts'] = $drafts;
        }

        $r = self::send($wf, $decoded);
        if (is_wp_error($r)) {
            wp_send_json_error(['message' => $r->get_error_message(), 'when' => HOPS_UI::when(time())]);
        }
        $data = $r;
        $data['when'] = HOPS_UI::when(time());
        if ($data['status'] >= 200 && $data['status'] < 300) {
            wp_send_json_success($data);
        }
        $data['message'] = 'n8n returned HTTP '.$data['status'];
        wp_send_json_error($data);
    }

    /**
     * POST a payload to a workflow webhook and log the run.
     * Returns array( status, ms, body ) or WP_Error. Used by the Run buttons and by WP Releases.
     */
    public static function send($wf, $payload)
    {
        $s = HOPS_Settings::get();
        $headers = ['Content-Type' => 'application/json'];
        if ($s['n8n_header_name'] !== '' && $s['n8n_header_value'] !== '') {
            $headers[$s['n8n_header_name']] = $s['n8n_header_value'];
        }

        $started = microtime(true);
        $res = wp_remote_post($wf['url'], [
            'timeout' => 45,
            'headers' => $headers,
            'body' => wp_json_encode($payload),
        ]);
        $ms = (int) round((microtime(true) - $started) * 1000);

        if (is_wp_error($res)) {
            self::log($wf['name'], 0, $ms);

            return $res;
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        self::log($wf['name'], $code, $ms);

        return [
            'status' => $code,
            'ms' => $ms,
            'body' => mb_substr(wp_remote_retrieve_body($res), 0, 3000),
        ];
    }

    private static function log($name, $code, $ms)
    {
        $log = get_option(self::LOG, []);
        array_unshift($log, [
            'time' => time(),
            'name' => $name,
            'code' => $code,
            'ms' => $ms,
            'user' => get_current_user_id(),
        ]);
        update_option(self::LOG, array_slice($log, 0, 20), false);
    }

    /** Most recent logged run for a workflow name, or null. */
    public static function last_run($name)
    {
        foreach (get_option(self::LOG, []) as $row) {
            if ($row['name'] === $name) {
                return $row;
            }
        }

        return null;
    }

    /** Pill plus time for a workflow's latest run (HTML). */
    public static function last_run_html($name)
    {
        $r = self::last_run($name);
        if (! $r) {
            return HOPS_UI::pill('Never run', 'neutral');
        }

        return HOPS_UI::run_pill($r['code']).' <span>'.esc_html(HOPS_UI::when($r['time'])).'</span>';
    }

    /** One workflow run card. Shared by the Workflows and Today screens. */
    public static function card($i, $wf, $compact = false)
    {
        ?>
		<div class="hops-wf hops-card" data-index="<?php echo (int) $i; ?>">
			<div class="hops-wf-top">
				<h3><?php echo esc_html($wf['name']); ?></h3>
				<span class="hops-run-ctl">
					<?php if (! empty($wf['counter'])) { ?>
						<label class="hops-count"><span>Drafts</span>
							<input type="number" class="hops-drafts" min="1" max="<?php echo (int) self::MAX_DRAFTS; ?>" step="1" value="1" inputmode="numeric" aria-label="Number of drafts to write from your editorial calendar for <?php echo esc_attr($wf['name']); ?>">
						</label>
					<?php } ?>
					<button type="button" class="button <?php echo $compact ? '' : 'button-primary'; ?> hops-run"><?php echo ! empty($wf['counter']) ? 'Write drafts' : 'Run now'; ?></button>
				</span>
			</div>
			<div class="hops-wf-meta hops-last" aria-live="polite"><?php echo self::last_run_html($wf['name']); // phpcs:ignore WordPress.Security.EscapeOutput?></div>
			<?php if (! $compact) { ?>
				<details>
					<summary>Send custom JSON (optional)</summary>
					<textarea class="hops-payload" rows="4" aria-label="Custom JSON payload for <?php echo esc_attr($wf['name']); ?>" placeholder='{"example": "value"}'></textarea>
				</details>
			<?php } else { ?>
				<textarea class="hops-payload" hidden></textarea>
			<?php } ?>
			<pre class="hops-result" hidden></pre>
		</div>
		<?php
    }

    public static function render()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        HOPS_N8nViz::maybe_refresh();
        $s = HOPS_Settings::get();
        $log = get_option(self::LOG, []);
        echo '<div class="wrap hops">';
        HOPS_UI::head(
            'Workflows',
            'Run your n8n workflows with one click, and see how each one is built and whether it is switched on.',
            '<a class="button" href="'.esc_url(HOPS_UI::url('hops-settings', '#hops-sec-n8n')).'">Manage workflows</a>'
        );
        ?>
			<?php if (empty($s['workflows'])) { ?>
				<?php echo HOPS_UI::empty_state( // phpcs:ignore WordPress.Security.EscapeOutput
				    'Add your first n8n workflow',
				    'Paste the webhook URL of an n8n workflow and it appears here as a Run button. Start with the workflow you run most often.',
				    'Add a workflow',
				    HOPS_UI::url('hops-settings', '#hops-sec-n8n')
				); ?>
			<?php } else { ?>
				<div class="hops-wfs">
				<?php foreach ($s['workflows'] as $i => $wf) {
				    self::card($i, $wf, false);
				} ?>
				</div>
			<?php } ?>

			<?php HOPS_N8nViz::panels(); ?>

			<section class="hops-panel hops-wide" id="hops-runs">
				<div class="hops-panel-head"><h2>Recent runs</h2></div>
				<?php if ($log) { ?>
					<div class="hops-table-wrap">
					<table class="widefat striped hops-table">
						<thead><tr><th>When</th><th>Workflow</th><th>Result</th><th>Duration</th><th>Started by</th></tr></thead>
						<tbody>
						<?php foreach ($log as $row) {
						    $u = get_userdata($row['user']);
						    ?>
							<tr>
								<td><?php echo esc_html(HOPS_UI::when($row['time'])); ?></td>
								<td><?php echo esc_html($row['name']); ?></td>
								<td><?php echo HOPS_UI::run_pill($row['code']); // phpcs:ignore WordPress.Security.EscapeOutput?></td>
								<td><?php echo esc_html(HOPS_UI::seconds($row['ms'])); ?></td>
								<td><?php echo esc_html($u ? $u->user_login : 'Schedule'); ?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					</div>
				<?php } else { ?>
					<p class="hops-note">Runs appear here after you press Run now on a workflow.</p>
				<?php } ?>
			</section>
		</div>
		<?php
    }
}
