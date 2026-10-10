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

    /** Marks a queued run as failed when n8n could not be reached or rejected the request. */
    public static function fail($run_id, $message)
    {
        $runs = self::runs();
        if (! isset($runs[$run_id])) {
            return;
        }
        $runs[$run_id]['stage'] = 'failed';
        $runs[$run_id]['message'] = sanitize_text_field($message);
        $runs[$run_id]['updated'] = time();
        update_option(self::OPT, $runs, false);
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
        if ($stage === 'failed') {
            return 'bad';
        }

        return in_array($stage, ['started', 'drafting', 'checking', 'checks_done', 'post_saved'], true) ? 'warn' : 'neutral';
    }

    /** Steps a single post moves through, in order. */
    const STEPS = ['Draft', 'Check', 'Saved'];

    /** How many of the three steps are finished for the post being written, from the run's latest stage. */
    private static function steps_done($stage)
    {
        switch ($stage) {
            case 'checking':
                return 1;
            case 'checks_done':
                return 2;
            case 'post_saved':
                return 3;
            default:
                return 0;
        }
    }

    /** Percent complete for a run, counting each post as three steps. */
    private static function percent($r)
    {
        $stage = $r['stage'] ?? '';
        if ($stage === 'complete' || $stage === 'dry_run') {
            return 100;
        }
        $total = max(1, (int) ($r['count'] ?? 0)) * 3;
        $posts = count($r['posts'] ?? []);
        $done = $posts * 3;
        if ($stage !== 'post_saved') {
            $done += self::steps_done($stage);
        }

        return (int) max(0, min(99, round($done / $total * 100)));
    }

    private static function is_active($r)
    {
        return ! in_array($r['stage'] ?? '', ['complete', 'dry_run', 'failed'], true) && (($r['updated'] ?? 0) > time() - 1800);
    }

    /** Three small step boxes: green with a check when done, yellow for the step in progress. */
    private static function step_bar($done, $active)
    {
        $out = '<span class="hops-steps" role="img" aria-label="'.esc_attr($done.' of 3 steps done').'">';
        foreach (self::STEPS as $i => $label) {
            $cls = $i < $done ? 'is-done' : ($active && $i === $done ? 'is-active' : '');
            $out .= '<span class="hops-step '.$cls.'">'.($i < $done ? '<span class="hops-stepmark" aria-hidden="true">✓</span>' : '').esc_html($label).'</span>';
        }

        return $out.'</span>';
    }

    public static function panel()
    {
        $runs = self::runs();
        $any_active = false;
        foreach ($runs as $r) {
            $any_active = $any_active || self::is_active($r);
        }
        ?>
		<section class="hops-panel hops-wide" id="hops-pipeline" data-active="<?php echo $any_active ? '1' : '0'; ?>">
			<div class="hops-panel-head"><h2>Editorial pipeline runs</h2></div>
			<?php if (! $runs) { ?>
				<p class="hops-note">Runs of a workflow with <strong>Ask how many</strong> on appear here, with each draft, its quality score and a link to edit it.</p>
			<?php } else { ?>
				<div class="hops-runs">
				<?php foreach (array_slice($runs, 0, 10) as $r) {
				    $stage = $r['stage'] ?? '';
				    $tone = self::tone($stage);
				    $active = self::is_active($r);
				    $pct = $stage === 'failed' ? 100 : self::percent($r);
				    $finished = $tone === 'ok';
				    $posts = $r['posts'] ?? [];
				    $count = (int) ($r['count'] ?? 0);
				    $writing = $active && count($posts) < $count && in_array($stage, ['drafting', 'checking', 'checks_done', 'post_saved'], true);
				    ?>
					<div class="hops-run is-<?php echo esc_attr($tone); ?><?php echo $active ? ' is-active' : ''; ?>">
						<div class="hops-run-head">
							<?php if ($finished) { ?><span class="hops-tick" aria-hidden="true">✓</span><?php } ?>
							<?php echo HOPS_UI::pill(ucwords(str_replace('_', ' ', $stage)), $tone); // phpcs:ignore WordPress.Security.EscapeOutput?>
							<span class="hops-run-count"><?php echo esc_html(count($posts).' of '.$count.' drafts'); ?></span>
							<span class="hops-muted hops-run-when"><?php echo esc_html(HOPS_UI::when($r['updated'] ?? time())); ?></span>
						</div>
						<div class="hops-bar <?php echo $stage === 'failed' ? 'is-bad' : ($finished ? 'is-done' : 'is-active'); ?>" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $pct; ?>"><span style="width:<?php echo (int) $pct; ?>%"></span></div>
						<?php if (! empty($r['message'])) { ?><div class="hops-note"><?php echo esc_html($r['message']); ?></div><?php } ?>
						<?php foreach (($r['topics'] ?? []) as $t) { ?>
							<div class="hops-note">Would write: <?php echo esc_html($t); ?></div>
						<?php } ?>
						<?php foreach ($posts as $post) {
						    $link = $post['post_id'] && current_user_can('edit_post', $post['post_id']) ? get_edit_post_link($post['post_id'], 'raw') : '';
						    ?>
							<div class="hops-post is-done">
								<?php echo self::step_bar(3, false); // phpcs:ignore WordPress.Security.EscapeOutput?>
								<span class="hops-post-title"><?php if ($link) { ?><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($post['title'] ?: 'Untitled'); ?></a><?php } else { ?><?php echo esc_html($post['title'] ?: 'Untitled'); ?><?php } ?></span>
								<span class="hops-muted hops-post-meta">
									<?php if ($post['score'] !== null) { ?>score <?php echo (int) $post['score']; ?><?php } ?>
									<?php if ($post['grade'] !== null) { ?> · grade <?php echo esc_html((string) $post['grade']); ?><?php } ?>
								</span>
								<?php if (! $post['passed']) { ?><?php echo HOPS_UI::pill('Needs review', 'warn'); // phpcs:ignore WordPress.Security.EscapeOutput?><?php } ?>
								<?php foreach ($post['fixes'] as $fix) { ?><div class="hops-note hops-post-fix"><?php echo esc_html($fix); ?></div><?php } ?>
							</div>
						<?php } ?>
						<?php if ($writing) {
						    $done = $stage === 'post_saved' ? 0 : self::steps_done($stage);
						    ?>
							<div class="hops-post is-active">
								<?php echo self::step_bar($done, true); // phpcs:ignore WordPress.Security.EscapeOutput?>
								<span class="hops-post-title">Writing draft <?php echo (int) (count($posts) + 1); ?> of <?php echo (int) $count; ?>…</span>
							</div>
						<?php } ?>
					</div>
				<?php } ?>
				</div>
			<?php } ?>
		</section>
		<?php
    }
}
