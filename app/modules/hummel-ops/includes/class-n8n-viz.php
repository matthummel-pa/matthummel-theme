<?php
defined('ABSPATH') || exit;

/**
 * Reads workflows from the n8n public API and shows them in the admin:
 * a status card and node diagram per workflow, plus a plain-language visual guide.
 */
class HOPS_N8nViz
{
    const CACHE = 'hops_n8n_viz';

    public static function configured()
    {
        $s = HOPS_Settings::get();

        return $s['n8n_api_url'] !== '' && $s['n8n_api_key'] !== '';
    }

    private static function api($path, $query = [])
    {
        $s = HOPS_Settings::get();
        $res = wp_remote_get(add_query_arg($query, $s['n8n_api_url'].'/api/v1/'.$path), [
            'timeout' => 20,
            'headers' => ['X-N8N-API-KEY' => $s['n8n_api_key'], 'Accept' => 'application/json'],
        ]);
        if (is_wp_error($res)) {
            return new WP_Error('hops_n8n_net', 'Could not reach n8n: '.$res->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        if ($code === 401 || $code === 403) {
            return new WP_Error('hops_n8n_auth', 'n8n rejected the API key. Create a new key in n8n under Settings, n8n API, and save it on Integrations.');
        }
        if ($code < 200 || $code >= 300) {
            return new WP_Error('hops_n8n_http', 'n8n answered HTTP '.$code.'. Check the n8n address on Integrations.');
        }
        $json = json_decode(wp_remote_retrieve_body($res), true);

        return is_array($json) ? $json : new WP_Error('hops_n8n_json', 'n8n returned a reply this screen could not read.');
    }

    /** Workflows with status, trimmed to what the diagram needs. Cached for five minutes. */
    public static function load($force = false)
    {
        if (! self::configured()) {
            return new WP_Error('hops_n8n_off', 'Add your n8n address and API key on Integrations to see workflow status and diagrams.');
        }
        if (! $force) {
            $cached = get_transient(self::CACHE);
            if (is_array($cached)) {
                return $cached;
            }
        }
        $list = self::api('workflows', ['limit' => 100]);
        if (is_wp_error($list)) {
            return $list;
        }

        $last = [];
        $ex = self::api('executions', ['limit' => 100]);
        if (! is_wp_error($ex)) {
            foreach ((array) ($ex['data'] ?? []) as $e) {
                $id = (string) ($e['workflowId'] ?? '');
                if ($id !== '' && ! isset($last[$id])) {
                    $last[$id] = [
                        'status' => (string) ($e['status'] ?? ''),
                        'time' => strtotime((string) ($e['startedAt'] ?? '')) ?: 0,
                    ];
                }
            }
        }

        $out = [];
        foreach ((array) ($list['data'] ?? []) as $w) {
            if (! empty($w['isArchived'])) {
                continue;
            }
            $nodes = [];
            foreach ((array) ($w['nodes'] ?? []) as $n) {
                $pos = (array) ($n['position'] ?? [0, 0]);
                $nodes[] = [
                    'name' => (string) ($n['name'] ?? ''),
                    'type' => (string) ($n['type'] ?? ''),
                    'x' => (float) ($pos[0] ?? 0),
                    'y' => (float) ($pos[1] ?? 0),
                    'off' => ! empty($n['disabled']),
                    // Newer webhook nodes leave the path empty and use their webhookId as the Production URL slug.
                    'path' => (string) (($n['parameters']['path'] ?? '') !== '' ? $n['parameters']['path'] : ($n['webhookId'] ?? '')),
                ];
            }
            $id = (string) ($w['id'] ?? '');
            $out[] = [
                'id' => $id,
                'name' => (string) ($w['name'] ?? 'Untitled'),
                'active' => ! empty($w['active']),
                'updated' => strtotime((string) ($w['updatedAt'] ?? '')) ?: 0,
                'nodes' => $nodes,
                'connections' => (array) ($w['connections'] ?? []),
                'last' => $last[$id] ?? null,
            ];
        }
        $data = ['at' => time(), 'runs_ok' => ! is_wp_error($ex), 'workflows' => $out];
        set_transient(self::CACHE, $data, 5 * MINUTE_IN_SECONDS);

        return $data;
    }

    private static function kind($type)
    {
        $t = strtolower(preg_replace('/^.*\./', '', $type));
        if (strpos($t, 'trigger') !== false || $t === 'webhook' || $t === 'manualtrigger' || $t === 'cron') {
            return ['trigger', 'Trigger'];
        }
        if (strpos($type, 'langchain') !== false || preg_match('/openai|anthropic|agent|gemini/', $t)) {
            return ['ai', 'AI'];
        }
        if (in_array($t, ['if', 'switch', 'merge', 'filter', 'wait', 'splitinbatches', 'loop'], true)) {
            return ['logic', 'Logic'];
        }
        if (in_array($t, ['code', 'function', 'functionitem', 'set', 'editfields', 'aggregate', 'splitout', 'summarize'], true)) {
            return ['data', 'Data'];
        }

        return ['action', 'Action'];
    }

    /** Group nearby canvas coordinates into 0-based grid slots: [coordinate string => slot]. */
    private static function snap($values, $gap)
    {
        $values = array_values(array_unique(array_map('floatval', $values)));
        sort($values);
        $map = [];
        $slot = -1;
        $prev = null;
        foreach ($values as $v) {
            if ($prev === null || $v - $prev >= $gap) {
                $slot++;
            }
            $map[(string) $v] = $slot;
            $prev = $v;
        }

        return $map;
    }

    private static function short($text, $max)
    {
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1).'…' : $text;
    }

    /** Inline SVG of the workflow: boxes at their n8n positions, arrows along the connections. */
    public static function diagram($wf)
    {
        if (empty($wf['nodes'])) {
            return '<p class="hops-note">This workflow has no nodes yet.</p>';
        }
        $w = 156;
        $h = 48;
        $pad = 14;
        $gx = 44;
        $gy = 34;
        // Snap n8n's free-form canvas positions to a tidy grid so boxes never overlap.
        $col = self::snap(array_column($wf['nodes'], 'x'), 100);
        $row = self::snap(array_column($wf['nodes'], 'y'), 60);
        $pos = [];
        foreach ($wf['nodes'] as $n) {
            $pos[$n['name']] = [$pad + $col[(string) $n['x']] * ($w + $gx), $pad + $row[(string) $n['y']] * ($h + $gy)];
        }
        $vw = (int) ($pad * 2 + (max($col) + 1) * ($w + $gx) - $gx);
        $vh = (int) ($pad * 2 + (max($row) + 1) * ($h + $gy) - $gy);
        $uid = 'hops-arr-'.substr(md5($wf['id'].$wf['name']), 0, 8);
        $label = sprintf('Diagram of %s: %d nodes.', $wf['name'], count($wf['nodes']));

        $svg = '<svg viewBox="0 0 '.$vw.' '.$vh.'" style="min-width:'.(int) min($vw, max(520, $vw * 0.75)).'px" role="img" aria-label="'.esc_attr($label).'" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<defs><marker id="'.esc_attr($uid).'" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" fill="currentColor"/></marker></defs>';

        foreach ((array) $wf['connections'] as $from => $types) {
            if (! isset($pos[$from])) {
                continue;
            }
            foreach ((array) $types as $type => $outputs) {
                foreach ((array) $outputs as $targets) {
                    foreach ((array) $targets as $t) {
                        $to = $t['node'] ?? '';
                        if (! isset($pos[$to])) {
                            continue;
                        }
                        if ($type === 'main') {
                            $sx = $pos[$from][0] + $w;
                            $sy = $pos[$from][1] + $h / 2;
                            $tx = $pos[$to][0];
                            $ty = $pos[$to][1] + $h / 2;
                            $d = max(30, abs($tx - $sx) / 2);
                            $path = sprintf('M%.1f %.1fC%.1f %.1f %.1f %.1f %.1f %.1f', $sx, $sy, $sx + $d, $sy, $tx - $d, $ty, $tx, $ty);
                        } else {
                            // Sub-node links (AI model, tool, parser) join the parent from below, as n8n draws them.
                            $below = $pos[$from][1] >= $pos[$to][1];
                            $sx = $pos[$from][0] + $w / 2;
                            $sy = $pos[$from][1] + ($below ? 0 : $h);
                            $tx = $pos[$to][0] + $w / 2;
                            $ty = $pos[$to][1] + ($below ? $h : 0);
                            $d = max(18, abs($ty - $sy) / 2);
                            $dir = $below ? -1 : 1;
                            $path = sprintf('M%.1f %.1fC%.1f %.1f %.1f %.1f %.1f %.1f', $sx, $sy, $sx, $sy + $dir * $d, $tx, $ty - $dir * $d, $tx, $ty);
                        }
                        $svg .= '<path class="hops-edge'.($type === 'main' ? '' : ' is-aux').'" d="'.$path.'" marker-end="url(#'.esc_attr($uid).')"/>';
                    }
                }
            }
        }

        foreach ($wf['nodes'] as $n) {
            [$cls, $kind] = self::kind($n['type']);
            $x = $pos[$n['name']][0];
            $y = $pos[$n['name']][1];
            $svg .= sprintf('<g class="hops-n is-%s%s" transform="translate(%.1f %.1f)">', $cls, $n['off'] ? ' is-off' : '', $x, $y);
            $svg .= '<title>'.esc_html($n['name'].' ('.preg_replace('/^.*\./', '', $n['type']).($n['off'] ? ', disabled' : '').')').'</title>';
            $svg .= '<rect width="'.$w.'" height="'.$h.'" rx="8"/>';
            $svg .= '<text class="hops-n-kind" x="10" y="17">'.esc_html($kind.($n['off'] ? ' · off' : '')).'</text>';
            $svg .= '<text class="hops-n-name" x="10" y="35">'.esc_html(self::short($n['name'], 20)).'</text>';
            $svg .= '</g>';
        }

        return $svg.'</svg>';
    }

    /** Run buttons whose webhook URL ends in one of this workflow's webhook paths. */
    private static function buttons_for($wf, $buttons)
    {
        $found = [];
        foreach ($wf['nodes'] as $n) {
            if ($n['path'] === '') {
                continue;
            }
            $tail = '/'.ltrim($n['path'], '/');
            foreach ($buttons as $b) {
                $p = (string) wp_parse_url($b['url'], PHP_URL_PATH);
                if ($p !== '' && substr(rtrim($p, '/'), -strlen($tail)) === $tail) {
                    $found[$b['name']] = true;
                }
            }
        }

        return array_keys($found);
    }

    private static function status_pill($last)
    {
        if (! $last) {
            return HOPS_UI::pill('No runs yet', 'neutral');
        }
        $map = [
            'success' => ['Last run succeeded', 'ok'],
            'error' => ['Last run failed', 'bad'],
            'crashed' => ['Last run crashed', 'bad'],
            'running' => ['Running now', 'info'],
            'waiting' => ['Waiting', 'warn'],
            'canceled' => ['Last run canceled', 'warn'],
        ];
        [$text, $tone] = $map[$last['status']] ?? [ucfirst($last['status']) ?: 'Unknown', 'neutral'];

        return HOPS_UI::pill($text, $tone).' <span class="hops-muted">'.esc_html(HOPS_UI::when($last['time'])).'</span>';
    }

    private static function card($wf, $buttons, $base)
    {
        $linked = self::buttons_for($wf, $buttons);
        $wide = count($wf['nodes']) > 5;
        ?>
		<article class="hops-nwf<?php echo $wide ? ' is-wide' : ''; ?>">
			<div class="hops-wf-top">
				<h3><?php echo esc_html($wf['name']); ?></h3>
				<?php echo HOPS_UI::pill($wf['active'] ? 'Active' : 'Inactive', $wf['active'] ? 'ok' : 'neutral'); // phpcs:ignore WordPress.Security.EscapeOutput?>
			</div>
			<div class="hops-wf-meta"><?php echo self::status_pill($wf['last']); // phpcs:ignore WordPress.Security.EscapeOutput?></div>
			<div class="hops-diagram"><?php echo self::diagram($wf); // phpcs:ignore WordPress.Security.EscapeOutput?></div>
			<p class="hops-nwf-foot">
				<span><?php echo esc_html(count($wf['nodes']).' nodes'); ?></span>
				<span><?php echo $linked ? esc_html('Run button: '.implode(', ', $linked)) : esc_html('No Run button yet'); ?></span>
				<?php if (! $wf['active']) { ?><span>Inactive workflows ignore Run now. Switch it on in n8n.</span><?php } ?>
				<a href="<?php echo esc_url($base.'/workflow/'.rawurlencode($wf['id'])); ?>" target="_blank" rel="noopener noreferrer">Open in n8n ↗</a>
			</p>
		</article>
		<?php
    }

    /** The visual guide plus the live workflow cards. Printed on the Workflows screen. */
    public static function panels()
    {
        $s = HOPS_Settings::get();
        $refresh = wp_nonce_url(HOPS_UI::url('hops-workflows', '&hops_n8n_refresh=1'), 'hops_n8n_refresh');
        ?>
		<section class="hops-panel hops-wide" id="hops-n8n-guide">
			<div class="hops-panel-head"><h2>How n8n works here</h2></div>
			<ol class="hops-flow">
				<li><strong>Press Run now</strong><span>On Today or Workflows, in WordPress.</span></li>
				<li><strong>WordPress sends a POST</strong><span>JSON saying who triggered it and when, plus your auth header if you set one.</span></li>
				<li><strong>The Webhook node answers</strong><span>The Production URL receives it. The workflow must be Active.</span></li>
				<li><strong>Your nodes run in order</strong><span>Each node passes its data to the next one. The diagrams below show the path.</span></li>
				<li><strong>The result comes back</strong><span>The HTTP status lands in Recent runs. n8n keeps the full data under Executions.</span></li>
			</ol>
			<p class="hops-note">WP Releases uses the same path. A new WordPress release triggers one POST with a ready-made post title and body, and the n8n workflow drafts the announcement.</p>
			<ul class="hops-key" aria-label="Diagram key">
				<li><span class="hops-key-dot is-trigger"></span>Trigger starts the workflow</li>
				<li><span class="hops-key-dot is-action"></span>Action talks to another app</li>
				<li><span class="hops-key-dot is-logic"></span>Logic branches or waits</li>
				<li><span class="hops-key-dot is-data"></span>Data reshapes the items</li>
				<li><span class="hops-key-dot is-ai"></span>AI calls a model</li>
			</ul>
		</section>

		<section class="hops-panel hops-wide" id="hops-n8n-live">
			<div class="hops-panel-head">
				<h2>Workflows in n8n</h2>
				<?php if (self::configured()) { ?><a class="button" href="<?php echo esc_url($refresh); ?>">Refresh from n8n</a><?php } ?>
			</div>
			<?php
            $data = self::load();
        if (is_wp_error($data)) {
            $off = $data->get_error_code() === 'hops_n8n_off';
            echo '<p class="hops-note">'.($off ? '' : HOPS_UI::pill('Could not load', 'bad').' ').esc_html($data->get_error_message()).'</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
            echo '<p><a class="button button-primary" href="'.esc_url(HOPS_UI::url('hops-settings', '#hops-sec-n8n')).'">'.esc_html($off ? 'Connect n8n' : 'Check n8n settings').'</a></p>';
        } else {
            $workflows = $data['workflows'];
            $active = count(array_filter($workflows, function ($w) {
                return $w['active'];
            }));
            echo '<p class="hops-note">'.esc_html(sprintf('%d workflows, %d active. Updated %s.', count($workflows), $active, HOPS_UI::when($data['at']))).'</p>';
            if (! $data['runs_ok']) {
                echo '<p class="hops-note">Run history was not available. Give the API key the <code>execution:read</code> scope to see last-run status.</p>';
            }
            if (! $workflows) {
                echo '<p class="hops-note">No workflows found on this n8n account yet.</p>';
            } else {
                echo '<div class="hops-nwfs">';
                foreach ($workflows as $wf) {
                    self::card($wf, $s['workflows'], $s['n8n_api_url']);
                }
                echo '</div>';
            }
        }
        ?>
		</section>
		<?php
    }

    /** Handle the Refresh button before any output. */
    public static function maybe_refresh()
    {
        if (! empty($_GET['hops_n8n_refresh']) && current_user_can('manage_options') && check_admin_referer('hops_n8n_refresh')) {
            delete_transient(self::CACHE);
        }
    }
}
