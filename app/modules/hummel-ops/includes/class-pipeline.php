<?php
defined('ABSPATH') || exit;

/**
 * Receives progress updates from the n8n blog pipeline and shows them on the Workflows page.
 *
 * n8n posts to /wp-json/mh-pipeline/v1/status with the WordPress credential saved in n8n
 * (an application password). Each update carries a run_id, so one run builds up one row.
 */
class HOPS_Pipeline
{
    const OPT = 'hops_pipeline_runs';

    const KEEP = 20;

    const STAGES = ['queued', 'started', 'dry_run', 'drafting', 'checking', 'checks_done', 'post_saved', 'complete', 'failed'];

    public static function init()
    {
        add_action('rest_api_init', [__CLASS__, 'routes']);
    }

    public static function routes()
    {
        register_rest_route('mh-pipeline/v1', '/status', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'receive'],
            'permission_callback' => static function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    public static function receive(WP_REST_Request $req)
    {
        $p = $req->get_json_params();
        $p = is_array($p) ? $p : [];

        $run_id = sanitize_text_field((string) ($p['run_id'] ?? ''));
        if ($run_id === '') {
            return new WP_Error('mh_pipeline_run', 'run_id is required.', ['status' => 400]);
        }
        $stage = sanitize_key((string) ($p['stage'] ?? ''));
        if (! in_array($stage, self::STAGES, true)) {
            return new WP_Error('mh_pipeline_stage', 'Unknown stage.', ['status' => 400]);
        }
        $message = sanitize_text_field((string) ($p['message'] ?? ''));

        $runs = self::runs();
        $run = $runs[$run_id] ?? ['started' => time(), 'posts' => [], 'topics' => [], 'count' => 0];
        $run['stage'] = $stage;
        $run['message'] = $message;
        $run['updated'] = time();
        $run['execution_id'] = sanitize_text_field((string) ($p['execution_id'] ?? ($run['execution_id'] ?? '')));
        if (isset($p['post_count'])) {
            $run['count'] = max(0, min(50, (int) $p['post_count']));
        }
        if (! empty($p['topics']) && is_array($p['topics'])) {
            $run['topics'] = array_slice(array_map('sanitize_text_field', array_map('strval', $p['topics'])), 0, 20);
        }
        if ($stage === 'post_saved') {
            $post = [
                'post_id' => (int) ($p['post_id'] ?? 0),
                'title' => sanitize_text_field((string) ($p['title'] ?? '')),
                'score' => isset($p['score']) ? (int) $p['score'] : null,
                'grade' => isset($p['readability_grade']) ? round((float) $p['readability_grade'], 1) : null,
                'passed' => ! empty($p['passed']),
                'issues' => self::strings($p['issues'] ?? []),
                'fixes' => self::strings($p['suggested_fixes'] ?? []),
            ];
            // Only link to a post that exists on this site. The edit link is built here, never taken from n8n.
            $run['posts'][] = $post;
        }

        $runs[$run_id] = $run;
        uasort($runs, static function ($a, $b) {
            return ($b['updated'] ?? 0) <=> ($a['updated'] ?? 0);
        });
        update_option(self::OPT, array_slice($runs, 0, self::KEEP, true), false);

        return [
            'success' => true,
            'run_id' => $run_id,
            'execution_id' => $run['execution_id'],
            'stage' => $stage,
            'message' => 'Status recorded.',
            'received_at' => gmdate('c'),
        ];
    }

    /** Called by the Run button so the run shows as soon as it starts. */
    public static function queue($run_id, $label, $count, $dry)
    {
        $runs = self::runs();
        $runs[$run_id] = [
            'started' => time(), 'updated' => time(), 'stage' => 'queued', 'label' => $label,
            'message' => $dry ? 'Plan only: waiting for n8n.' : 'Waiting for n8n to start.',
            'count' => (int) $count, 'dry' => (bool) $dry, 'posts' => [], 'topics' => [], 'execution_id' => '',
        ];
        uasort($runs, static function ($a, $b) {
            return ($b['updated'] ?? 0) <=> ($a['updated'] ?? 0);
        });
        update_option(self::OPT, array_slice($runs, 0, self::KEEP, true), false);
    }

    public static function runs()
    {
        $r = get_option(self::OPT, []);

        return is_array($r) ? $r : [];
    }

    private static function strings($v)
    {
        $v = is_array($v) ? $v : [];

        return array_slice(array_map('sanitize_text_field', array_map('strval', $v)), 0, 10);
    }

    private static function tone($stage)
    {
        if ($stage === 'complete' || $stage === 'dry_run') {
            return 'ok';
        }

        return $stage === 'failed' ? 'bad' : 'neutral';
    }

    public static function panel()
    {
        $runs = self::runs();
        ?>
		<section class="hops-panel hops-wide" id="hops-pipeline">
			<div class="hops-panel-head"><h2>Editorial pipeline runs</h2></div>
			<?php if (! $runs) { ?>
				<p class="hops-note">Runs of a workflow with <strong>Ask how many</strong> on appear here, with each draft, its quality score and a link to edit it.</p>
			<?php } else { ?>
				<div class="hops-table-wrap">
				<table class="widefat striped hops-table">
					<thead><tr><th>When</th><th>Status</th><th>Drafts</th><th>Details</th></tr></thead>
					<tbody>
					<?php foreach (array_slice($runs, 0, 10) as $r) { ?>
						<tr>
							<td><?php echo esc_html(HOPS_UI::when($r['updated'] ?? time())); ?></td>
							<td><?php echo HOPS_UI::pill(ucwords(str_replace('_', ' ', $r['stage'] ?? '')), self::tone($r['stage'] ?? '')); // phpcs:ignore WordPress.Security.EscapeOutput?></td>
							<td><?php echo esc_html(count($r['posts'] ?? []).' of '.(int) ($r['count'] ?? 0)); ?></td>
							<td>
								<?php echo esc_html($r['message'] ?? ''); ?>
								<?php foreach (($r['topics'] ?? []) as $t) { ?>
									<div class="hops-note">Would write: <?php echo esc_html($t); ?></div>
								<?php } ?>
								<?php foreach (($r['posts'] ?? []) as $post) {
								    $link = $post['post_id'] && current_user_can('edit_post', $post['post_id']) ? get_edit_post_link($post['post_id'], 'raw') : '';
								    ?>
									<div>
										<?php if ($link) { ?><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($post['title'] ?: 'Untitled'); ?></a><?php } else { ?><?php echo esc_html($post['title']); ?><?php } ?>
										<?php if ($post['score'] !== null) { ?> · score <?php echo (int) $post['score']; ?><?php } ?>
										<?php if ($post['grade'] !== null) { ?> · grade <?php echo esc_html((string) $post['grade']); ?><?php } ?>
										<?php if (! $post['passed']) { ?> <?php echo HOPS_UI::pill('Needs review', 'warn'); // phpcs:ignore WordPress.Security.EscapeOutput?><?php } ?>
										<?php foreach ($post['fixes'] as $fix) { ?><div class="hops-note"><?php echo esc_html($fix); ?></div><?php } ?>
									</div>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				</div>
			<?php } ?>
		</section>
		<?php
    }
}
