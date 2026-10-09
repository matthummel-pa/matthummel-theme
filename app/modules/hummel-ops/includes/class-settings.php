<?php
defined('ABSPATH') || exit;

class HOPS_Settings
{
    const OPT = 'hops_settings';

    const SECRETS = ['n8n_header_value', 'n8n_api_key', 'google_client_secret', 'notion_token', 'todoist_token', 'trello_key', 'trello_token'];

    const TEXT = ['n8n_header_name', 'google_client_id', 'notion_due_prop', 'notion_done_prop', 'notion_done_value', 'trello_board_id'];

    public static function init()
    {
        add_action('admin_init', [__CLASS__, 'register']);
    }

    public static function register()
    {
        register_setting('hops_group', self::OPT, [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize'],
            'default' => [],
        ]);
    }

    public static function defaults()
    {
        return [
            'workflows' => [],
            'n8n_header_name' => '',
            'n8n_header_value' => '',
            'n8n_api_url' => '',
            'n8n_api_key' => '',
            'google_client_id' => '',
            'google_client_secret' => '',
            'notion_token' => '',
            'notion_database_id' => '',
            'notion_due_prop' => 'Due',
            'notion_done_prop' => 'Done',
            'notion_done_value' => 'Done',
            'todoist_token' => '',
            'trello_key' => '',
            'trello_token' => '',
            'trello_board_id' => '',
            'todo_default' => 'local',
            'wprel_auto' => 1,
            'wprel_workflow' => -1,
        ];
    }

    /** Secrets are encrypted at rest with a key derived from the site salts. */
    public static function encrypt($plain)
    {
        if ($plain === '' || ! function_exists('openssl_encrypt')) {
            return $plain;
        }
        $key = hash('sha256', wp_salt('auth'), true);
        $iv = random_bytes(16);
        $ct = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return 'enc:'.base64_encode($iv.$ct);
    }

    public static function decrypt($stored)
    {
        if (! is_string($stored) || strpos($stored, 'enc:') !== 0) {
            return (string) $stored;
        }
        $raw = base64_decode(substr($stored, 4), true);
        if ($raw === false || strlen($raw) < 17) {
            return '';
        }
        $key = hash('sha256', wp_salt('auth'), true);
        $out = openssl_decrypt(substr($raw, 16), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, substr($raw, 0, 16));

        return $out === false ? '' : $out;
    }

    public static function raw()
    {
        return wp_parse_args(get_option(self::OPT, []), self::defaults());
    }

    /** Decrypted settings for server-side use. */
    public static function get()
    {
        $s = self::raw();
        foreach (self::SECRETS as $k) {
            $s[$k] = self::decrypt($s[$k]);
        }

        return $s;
    }

    public static function sanitize($in)
    {
        $old = self::raw();
        $def = self::defaults();
        $out = ['workflows' => []];

        if (! empty($in['workflows']) && is_array($in['workflows'])) {
            foreach ($in['workflows'] as $wf) {
                $name = isset($wf['name']) ? sanitize_text_field($wf['name']) : '';
                $url = isset($wf['url']) ? esc_url_raw(trim($wf['url'])) : '';
                if ($name !== '' && $url !== '') {
                    $out['workflows'][] = ['name' => $name, 'url' => $url];
                }
            }
        }

        foreach (self::TEXT as $k) {
            $v = isset($in[$k]) ? sanitize_text_field($in[$k]) : '';
            $out[$k] = ($v === '' && $def[$k] !== '') ? $def[$k] : $v;
        }

        // Accept a pasted Notion URL or ID; keep the 32-character database ID.
        $db = isset($in['notion_database_id']) ? str_replace('-', '', (string) $in['notion_database_id']) : '';
        $out['notion_database_id'] = preg_match('/[0-9a-f]{32}/i', $db, $m) ? strtolower($m[0]) : '';

        // Keep only scheme and host of the n8n address. Plain http is allowed for localhost only.
        $api = wp_parse_url(isset($in['n8n_api_url']) ? esc_url_raw(trim($in['n8n_api_url'])) : '');
        $ok = ! empty($api['host']) && (($api['scheme'] ?? '') === 'https' || (($api['scheme'] ?? '') === 'http' && in_array($api['host'], ['localhost', '127.0.0.1'], true)));
        $out['n8n_api_url'] = $ok ? $api['scheme'].'://'.$api['host'].(! empty($api['port']) ? ':'.$api['port'] : '') : '';

        $out['todo_default'] = (isset($in['todo_default']) && in_array($in['todo_default'], ['local', 'notion', 'todoist', 'trello'], true)) ? $in['todo_default'] : 'local';

        $out['wprel_auto'] = isset($in['wprel_auto']) ? (! empty($in['wprel_auto']) ? 1 : 0) : (int) $old['wprel_auto'];
        $wf_index = isset($in['wprel_workflow']) ? (int) $in['wprel_workflow'] : -1;
        $out['wprel_workflow'] = ($wf_index >= 0 && $wf_index < count($out['workflows'])) ? $wf_index : -1;

        // Blank secret fields keep the stored value.
        foreach (self::SECRETS as $k) {
            $v = isset($in[$k]) ? trim($in[$k]) : '';
            $out[$k] = $v === '' ? $old[$k] : self::encrypt($v);
        }

        delete_transient('hops_notion_schema');
        delete_transient(HOPS_N8nViz::CACHE);

        return $out;
    }

    private static function badge($on, $on_text = 'Connected', $off_text = 'Not set up')
    {
        return HOPS_UI::pill($on ? $on_text : $off_text, $on ? 'ok' : 'neutral');
    }

    /** One collapsible service section. $status_html is a pill. */
    private static function open_svc($id, $title, $status_html, $intro = '')
    {
        echo '<details class="hops-svc" id="hops-sec-'.esc_attr($id).'"><summary><span class="hops-svc-title"><span>'.esc_html($title).'</span> '.$status_html.'</span></summary><div class="hops-svc-body">'; // phpcs:ignore WordPress.Security.EscapeOutput
        if ($intro) {
            echo '<p>'.wp_kses_post($intro).'</p>';
        }
        echo self::guide($id); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside
    }

    /**
     * Step-by-step connection guide for one service: steps plus the pages you need.
     */
    private static function guide($id)
    {
        $redir = admin_url('admin.php?page=hops-drive');
        $guides = [
            'n8n' => [
                'steps' => [
                    'In n8n, create a workflow and add a <strong>Webhook</strong> node as the trigger. Set HTTP Method to <strong>POST</strong> and Respond to <strong>Immediately</strong> (or When Last Node Finishes to see results in the Run log).',
                    'Open the node and copy the <strong>Production URL</strong>. The Test URL only answers once, while you click Listen for test event in the editor.',
                    'Build the rest of the workflow, then switch the <strong>Active</strong> toggle on. A Production URL returns 404 while the workflow is inactive.',
                    'Click <strong>Add a workflow</strong> below, name the button, paste the Production URL, and save. The button appears on Today.',
                    'Optional security: in n8n create a <strong>Header Auth</strong> credential (name such as <code>X-Hummel-Key</code>, a long random value) and select it on the Webhook node. Enter the same name and value in the Auth header fields below. Every workflow here then sends that header.',
                    'Test: press the new button on Today. A 2xx result means n8n accepted it; open <strong>Executions</strong> in n8n to see the data. The default payload is JSON with <code>source</code> (hummel-ops), <code>triggered_by</code>, and <code>triggered_at</code>.',
                    'Optional, see workflows here: in n8n open <strong>Settings, n8n API</strong>, create a key with read access (<code>workflow:read</code> and <code>execution:read</code>), then enter the key and your n8n address below. The <a href="'.esc_url(HOPS_UI::url('hops-workflows')).'">Workflows</a> screen then shows each workflow as a diagram with its Active state and last run.',
                    'Troubleshooting: 404 means the workflow is inactive or you pasted the Test URL. 401 or 403 means the header name or value does not match. A timeout means the workflow runs longer than 45 seconds, so set Respond to Immediately.',
                ],
                'links' => [
                    'Webhook node docs' => 'https://docs.n8n.io/integrations/builtin/core-nodes/n8n-nodes-base.webhook/',
                    'Header Auth credential' => 'https://docs.n8n.io/integrations/builtin/credentials/httprequest/',
                    'Executions' => 'https://docs.n8n.io/workflows/executions/',
                    'n8n API keys' => 'https://docs.n8n.io/api/authentication/',
                    'n8n Cloud sign-in' => 'https://app.n8n.cloud/login',
                ],
            ],
            'google' => [
                'steps' => [
                    'In Google Cloud, create or pick a project, then enable the <strong>Drive</strong>, <strong>Calendar</strong>, and <strong>Docs</strong> APIs.',
                    'Set up the OAuth consent screen (External is fine). Add yourself as a test user.',
                    'Create credentials, OAuth client ID, type <strong>Web application</strong>. Add the redirect URI <code>'.esc_html($redir).'</code>.',
                    'Paste the Client ID and Client secret below, save, then connect from <a href="'.esc_url($redir).'">Files</a>.',
                ],
                'links' => [
                    'Create OAuth client' => 'https://console.cloud.google.com/apis/credentials',
                    'Consent screen' => 'https://console.cloud.google.com/apis/credentials/consent',
                    'Enable Drive API' => 'https://console.cloud.google.com/apis/library/drive.googleapis.com',
                    'Enable Calendar API' => 'https://console.cloud.google.com/apis/library/calendar-json.googleapis.com',
                    'Enable Docs API' => 'https://console.cloud.google.com/apis/library/docs.googleapis.com',
                ],
            ],
            'notion' => [
                'steps' => [
                    'Create an <strong>internal integration</strong> and copy its token (it starts with <code>ntn_</code>).',
                    'Open your tasks database in Notion, choose the <strong>...</strong> menu, Connections, and add the integration. Without this step the database stays hidden.',
                    'Paste the token and the database URL below. Match the property names to your database.',
                ],
                'links' => [
                    'Your integrations' => 'https://www.notion.so/profile/integrations',
                    'Integration guide' => 'https://developers.notion.com/docs/create-a-notion-integration',
                ],
            ],
            'todoist' => [
                'steps' => [
                    'In Todoist open Settings, Integrations, Developer.',
                    'Copy the <strong>API token</strong> and paste it below.',
                ],
                'links' => [
                    'Todoist developer settings' => 'https://app.todoist.com/app/settings/integrations/developer',
                    'API docs' => 'https://developer.todoist.com/api/v1/',
                ],
            ],
            'trello' => [
                'steps' => [
                    'Open the Trello Power-Ups admin, create a Power-Up (any name), and generate an <strong>API key</strong>.',
                    'On the same page choose <strong>Token</strong> to authorize the key, then copy the token.',
                    'Open your board. Add <code>.json</code> to the board URL and copy the <code>id</code> value, or use the short link from the URL.',
                ],
                'links' => [
                    'Power-Ups admin (API key)' => 'https://trello.com/power-ups/admin',
                    'REST API guide' => 'https://developer.atlassian.com/cloud/trello/guides/rest-api/api-introduction/',
                ],
            ],
            'wprel' => [
                'steps' => [
                    'Turn on the twice-daily check. It needs no key.',
                    'To get an announcement draft on each release, add an n8n workflow above (Webhook trigger), save, then pick it here.',
                ],
                'links' => [
                    'WordPress release news' => 'https://wordpress.org/news/category/releases/',
                    'Core release schedule' => 'https://make.wordpress.org/core/',
                ],
            ],
        ];
        if (empty($guides[$id])) {
            return '';
        }
        $g = $guides[$id];
        $out = '<div class="hops-guide"><strong>How to connect</strong><ol>';
        foreach ($g['steps'] as $step) {
            $out .= '<li>'.wp_kses($step, ['a' => ['href' => []], 'code' => [], 'strong' => []]).'</li>';
        }
        $out .= '</ol><p class="hops-guide-links">';
        $links = [];
        foreach ($g['links'] as $label => $url) {
            $links[] = '<a href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer">'.esc_html($label).' ↗</a>';
        }
        $out .= implode(' · ', $links).'</p></div>';

        return $out;
    }

    private static function close_svc()
    {
        echo '</div></details>';
    }

    private static function row($s, $key, $label, $type = 'text', $desc = '', $placeholder = '')
    {
        $secret = in_array($key, self::SECRETS, true);
        $id = 'hops-f-'.$key;
        $name = self::OPT.'['.$key.']';
        echo '<tr><th scope="row"><label for="'.esc_attr($id).'">'.esc_html($label).'</label></th><td>';
        if ($type === 'checkbox') {
            echo '<input type="hidden" name="'.esc_attr($name).'" value="0"><label><input id="'.esc_attr($id).'" type="checkbox" name="'.esc_attr($name).'" value="1"'.checked(! empty($s[$key]), true, false).'> Enabled</label>';
        } elseif ($type === 'select-wf') {
            echo '<select id="'.esc_attr($id).'" name="'.esc_attr($name).'"><option value="-1">Do not notify n8n</option>';
            foreach ($s['workflows'] as $i => $wf) {
                echo '<option value="'.(int) $i.'"'.selected((int) $s[$key], (int) $i, false).'>'.esc_html($wf['name']).'</option>';
            }
            echo '</select>';
        } elseif ($type === 'select-todo') {
            echo '<select id="'.esc_attr($id).'" name="'.esc_attr($name).'">';
            foreach (['local' => 'WordPress (local)', 'notion' => 'Notion', 'todoist' => 'Todoist', 'trello' => 'Trello'] as $v => $l) {
                echo '<option value="'.esc_attr($v).'"'.selected($s[$key], $v, false).'>'.esc_html($l).'</option>';
            }
            echo '</select>';
        } elseif ($secret) {
            $ph = $s[$key] !== '' ? 'Saved (leave blank to keep)' : $placeholder;
            echo '<input id="'.esc_attr($id).'" type="password" class="regular-text" autocomplete="new-password" name="'.esc_attr($name).'" value="" placeholder="'.esc_attr($ph).'">';
        } else {
            echo '<input id="'.esc_attr($id).'" type="text" class="regular-text" name="'.esc_attr($name).'" value="'.esc_attr($s[$key]).'" placeholder="'.esc_attr($placeholder).'">';
        }
        if ($desc) {
            echo '<p class="description">'.wp_kses_post($desc).'</p>';
        }
        echo '</td></tr>';
    }

    public static function render()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        $s = self::raw();
        $redir = admin_url('admin.php?page=hops-drive');

        $n8n_on = ! empty($s['workflows']);
        $google_on = HOPS_Google::connected();
        $notion_on = $s['notion_token'] !== '' && $s['notion_database_id'] !== '';
        $todo_on = $s['todoist_token'] !== '';
        $trello_on = $s['trello_key'] !== '' && $s['trello_token'] !== '' && $s['trello_board_id'] !== '';
        $rel_on = ! empty($s['wprel_auto']);

        $cards = [
            ['n8n', 'n8n workflows', $n8n_on, count($s['workflows']).' added', 'Run your automations from a button.', 'Add a workflow'],
            ['google', 'Google', $google_on, 'Connected', 'Drive files, today’s calendar, and the release documents.', HOPS_Google::configured() ? 'Connect Google' : 'Add OAuth client'],
            ['notion', 'Notion', $notion_on, 'Connected', 'Show and complete tasks from a Notion database.', 'Connect Notion'],
            ['todoist', 'Todoist', $todo_on, 'Connected', 'Bring your Todoist tasks into one list.', 'Connect Todoist'],
            ['trello', 'Trello', $trello_on, 'Connected', 'Pull cards from one Trello board.', 'Connect Trello'],
            ['wprel', 'WordPress releases', $rel_on, 'Automatic', 'Security releases and what is coming next.', 'Turn on checks', 'Checks off'],
        ];

        $done = 0;
        foreach ($cards as $c) {
            $done += $c[2] ? 1 : 0;
        }

        echo '<div class="wrap hops">';
        HOPS_UI::head(
            'Integrations',
            $done.' of '.count($cards).' connected. Connect the tools you use and Hummel Ops brings them into one place.'
        );
        ?>
		<div class="hops-svc-grid">
			<?php foreach ($cards as $c) { ?>
				<div class="hops-svc-card">
					<h3><?php echo esc_html($c[1]); ?> <?php echo self::badge($c[2], $c[3], isset($c[6]) ? $c[6] : 'Not set up'); // phpcs:ignore WordPress.Security.EscapeOutput?></h3>
					<p><?php echo esc_html($c[4]); ?></p>
					<a class="button<?php echo $c[2] ? '' : ' button-primary'; ?>" href="#hops-sec-<?php echo esc_attr($c[0]); ?>" data-hops-open="hops-sec-<?php echo esc_attr($c[0]); ?>"><?php echo esc_html($c[2] ? 'Edit settings' : $c[5]); ?></a>
				</div>
			<?php } ?>
		</div>

		<form method="post" action="options.php">
			<?php settings_fields('hops_group'); ?>

			<?php self::open_svc('n8n', 'n8n workflows', self::badge($n8n_on, count($s['workflows']).' added', 'None added'), 'One row per workflow. Copy the <strong>Production URL</strong> from the n8n Webhook node and keep the workflow active.'); ?>
				<div class="hops-table-wrap">
				<table class="widefat hops-table" id="hops-rows">
					<thead><tr><th>Button label</th><th>Webhook URL</th><th><span class="screen-reader-text">Remove</span></th></tr></thead>
					<tbody>
					<?php foreach ($s['workflows'] as $i => $wf) { ?>
						<tr>
							<td><input type="text" class="regular-text" aria-label="Button label" name="<?php echo esc_attr(self::OPT); ?>[workflows][<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr($wf['name']); ?>"></td>
							<td><input type="url" class="large-text" aria-label="Webhook URL" name="<?php echo esc_attr(self::OPT); ?>[workflows][<?php echo (int) $i; ?>][url]" value="<?php echo esc_attr($wf['url']); ?>"></td>
							<td><button type="button" class="button hops-remove">Remove</button></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				</div>
				<p><button type="button" class="button" id="hops-add" data-opt="<?php echo esc_attr(self::OPT); ?>" data-next="<?php echo (int) count($s['workflows']); ?>">Add a workflow</button></p>
				<table class="form-table" role="presentation">
					<?php
                    self::row($s, 'n8n_header_name', 'Auth header name', 'text', 'Optional. Matches a Header Auth credential on your Webhook nodes.', 'X-Hummel-Key');
        self::row($s, 'n8n_header_value', 'Auth header value', 'password');
        self::row($s, 'n8n_api_url', 'n8n address', 'text', 'Optional. Shows workflow status and diagrams on Workflows. Use the address you open n8n at, without a path.', 'https://your-name.app.n8n.cloud');
        self::row($s, 'n8n_api_key', 'n8n API key', 'password', 'Optional. Create it in n8n under Settings, n8n API. Read access is enough.');
        ?>
				</table>
			<?php self::close_svc(); ?>

			<?php self::open_svc('google', 'Google (Drive, Calendar, Docs)', self::badge($google_on), 'Create an OAuth client (Web application) in Google Cloud, enable the Drive, Calendar, and Google Docs APIs, and add this redirect URI: <code>'.esc_html($redir).'</code>. After you save, connect from <a href="'.esc_url($redir).'">Files</a>. Drive and Calendar access is read-only. The Docs permission lets WP Releases create and edit its two documents.'); ?>
				<table class="form-table" role="presentation">
					<?php
        self::row($s, 'google_client_id', 'Client ID');
        self::row($s, 'google_client_secret', 'Client secret', 'password');
        ?>
				</table>
			<?php self::close_svc(); ?>

			<?php self::open_svc('notion', 'Notion', self::badge($notion_on), 'Create an internal integration at notion.so/profile/integrations, then share your tasks database with it (database menu, Connections).'); ?>
				<table class="form-table" role="presentation">
					<?php
        self::row($s, 'notion_token', 'Integration token', 'password', '', 'ntn_...');
        self::row($s, 'notion_database_id', 'Tasks database', 'text', 'Paste the database URL or ID.', 'https://www.notion.so/...');
        self::row($s, 'notion_due_prop', 'Due date property', 'text', 'A Date property. Default: Due.');
        self::row($s, 'notion_done_prop', 'Done property', 'text', 'A Checkbox, Status, or Select property. Default: Done.');
        self::row($s, 'notion_done_value', 'Done value', 'text', 'The option name that means finished. Used for Status and Select. Default: Done.');
        ?>
				</table>
			<?php self::close_svc(); ?>

			<?php self::open_svc('todoist', 'Todoist', self::badge($todo_on)); ?>
				<table class="form-table" role="presentation">
					<?php self::row($s, 'todoist_token', 'API token', 'password', 'Find it in Todoist under Settings, Integrations, Developer.'); ?>
				</table>
			<?php self::close_svc(); ?>

			<?php self::open_svc('trello', 'Trello', self::badge($trello_on)); ?>
				<table class="form-table" role="presentation">
					<?php
        self::row($s, 'trello_key', 'API key', 'password', 'From trello.com/power-ups/admin.');
        self::row($s, 'trello_token', 'API token', 'password');
        self::row($s, 'trello_board_id', 'Board ID', 'text', 'The ID or short link from the board URL. New tasks land in the first open list.');
        ?>
				</table>
			<?php self::close_svc(); ?>

			<?php self::open_svc('wprel', 'WordPress releases', self::badge($rel_on, 'Checking twice a day', 'Automatic checks off'), 'Tracks WordPress.org security releases and the release schedule. Results appear on <a href="'.esc_url(HOPS_UI::url('hops-wp')).'">WP Releases</a>.'); ?>
				<table class="form-table" role="presentation">
					<?php
        self::row($s, 'wprel_auto', 'Check twice a day', 'checkbox', 'Runs in the background and updates the two Google Docs when something changes.');
        self::row($s, 'wprel_workflow', 'Run this workflow on a new release', 'select-wf', 'Pick the n8n workflow that writes the announcement post. It receives the release details and a ready-made post title and body. Add the workflow above and save first.');
        ?>
				</table>
			<?php self::close_svc(); ?>

			<?php self::open_svc('tasks', 'Tasks', HOPS_UI::pill('Default list', 'info')); ?>
				<table class="form-table" role="presentation">
					<?php self::row($s, 'todo_default', 'Default list for quick add', 'select-todo', 'The Add box on the Today screen saves here.'); ?>
				</table>
			<?php self::close_svc(); ?>

			<div class="hops-savebar">
				<p>Secrets stay encrypted. Leave a secret field blank to keep what is saved.</p>
				<?php submit_button('Save changes', 'primary', 'submit', false); ?>
			</div>
		</form>
		</div>
		<?php
    }
}
