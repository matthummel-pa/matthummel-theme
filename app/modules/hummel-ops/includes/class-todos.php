<?php
defined('ABSPATH') || exit;

abstract class HOPS_Todo_Provider
{
    abstract public function id();

    abstract public function label();

    abstract public function configured();

    abstract public function fetch();

    abstract public function add($title, $due);

    abstract public function complete($id);

    protected function item($id, $title, $due, $url = '', $extra = [])
    {
        return array_merge([
            'id' => (string) $id,
            'provider' => $this->id(),
            'provider_label' => $this->label(),
            'title' => $title,
            'due' => $due ? substr($due, 0, 10) : null,
            'url' => $url,
            'priority' => null,
            'type' => null,
            'status' => 'todo',
            'can_edit' => false,
        ], $extra);
    }

    /** Add with optional priority and type. Apps without those fields ignore them. */
    public function add_with($title, $due, $extra = [])
    {
        return $this->add($title, $due);
    }

    /** Change priority, status (todo, progress, done) or due date. */
    public function update($id, $fields)
    {
        return new WP_Error('hops_todo', $this->label().' tasks cannot be edited from here yet.');
    }

    /** Recently finished tasks. */
    public function fetch_done()
    {
        return [];
    }

    /** Choices for the priority and type pickers. */
    public function options()
    {
        return ['priority' => [], 'types' => []];
    }

    /** JSON request helper. Returns decoded array or WP_Error. */
    protected function req($method, $url, $headers = [], $body = null)
    {
        $args = ['method' => $method, 'timeout' => 20, 'headers' => $headers];
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }
        $res = wp_remote_request($url, $args);
        if (is_wp_error($res)) {
            return $res;
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        $raw = wp_remote_retrieve_body($res);
        $json = json_decode($raw, true);
        if ($code < 200 || $code >= 300) {
            $msg = is_array($json) && isset($json['message']) ? $json['message'] : (is_array($json) && isset($json['error']) && is_string($json['error']) ? $json['error'] : substr(wp_strip_all_tags($raw), 0, 160));

            return new WP_Error('hops_http', 'HTTP '.$code.': '.$msg);
        }

        return is_array($json) ? $json : [];
    }
}

/** Tasks stored in WordPress itself. */
class HOPS_Todo_Local extends HOPS_Todo_Provider
{
    const OPT = 'hops_local_todos';

    const PRIORITIES = ['Critical', 'High', 'Medium', 'Low'];

    const KEEP_DONE = 50;

    public function id()
    {
        return 'local';
    }

    public function label()
    {
        return 'WordPress';
    }

    public function configured()
    {
        return true;
    }

    public function options()
    {
        return ['priority' => self::PRIORITIES, 'types' => []];
    }

    private function row($t)
    {
        return $this->item($t['id'], $t['title'], $t['due'] ?? null, '', [
            'priority' => $t['priority'] ?? null,
            'type' => $t['type'] ?? null,
            'status' => $t['status'] ?? 'todo',
            'can_edit' => true,
        ]);
    }

    public function fetch()
    {
        $out = [];
        foreach (get_option(self::OPT, []) as $t) {
            if (($t['status'] ?? 'todo') !== 'done') {
                $out[] = $this->row($t);
            }
        }

        return $out;
    }

    public function fetch_done()
    {
        $done = array_filter(get_option(self::OPT, []), function ($t) {
            return ($t['status'] ?? 'todo') === 'done';
        });
        usort($done, function ($a, $b) {
            return ($b['done_at'] ?? 0) <=> ($a['done_at'] ?? 0);
        });

        return array_map([$this, 'row'], array_slice($done, 0, 20));
    }

    public function add($title, $due)
    {
        return $this->add_with($title, $due, []);
    }

    public function add_with($title, $due, $extra = [])
    {
        $all = get_option(self::OPT, []);
        $pri = $extra['priority'] ?? '';
        $all[] = [
            'id' => wp_generate_uuid4(), 'title' => $title, 'due' => $due,
            'priority' => in_array($pri, self::PRIORITIES, true) ? $pri : '',
            'status' => 'todo', 'created' => time(),
        ];
        update_option(self::OPT, $all, false);

        return true;
    }

    private function change($id, $fn)
    {
        $all = get_option(self::OPT, []);
        $found = false;
        foreach ($all as $i => $t) {
            if ($t['id'] === $id) {
                $all[$i] = $fn($t);
                $found = true;
            }
        }
        if (! $found) {
            return new WP_Error('hops_todo', 'That task no longer exists.');
        }
        // Keep the list of finished tasks short.
        $done = array_keys(array_filter($all, function ($t) {
            return ($t['status'] ?? 'todo') === 'done';
        }));
        if (count($done) > self::KEEP_DONE) {
            usort($done, function ($a, $b) use ($all) {
                return ($all[$a]['done_at'] ?? 0) <=> ($all[$b]['done_at'] ?? 0);
            });
            foreach (array_slice($done, 0, count($done) - self::KEEP_DONE) as $i) {
                unset($all[$i]);
            }
        }
        update_option(self::OPT, array_values($all), false);

        return true;
    }

    public function complete($id)
    {
        return $this->change($id, function ($t) {
            $t['status'] = 'done';
            $t['done_at'] = time();

            return $t;
        });
    }

    public function update($id, $fields)
    {
        return $this->change($id, function ($t) use ($fields) {
            if (isset($fields['priority'])) {
                $t['priority'] = in_array($fields['priority'], self::PRIORITIES, true) ? $fields['priority'] : '';
            }
            if (isset($fields['due'])) {
                $t['due'] = $fields['due'] !== '' ? $fields['due'] : null;
            }
            if (isset($fields['status']) && in_array($fields['status'], ['todo', 'progress', 'done'], true)) {
                $t['status'] = $fields['status'];
                $t['done_at'] = $fields['status'] === 'done' ? time() : 0;
            }

            return $t;
        });
    }
}

class HOPS_Todo_Notion extends HOPS_Todo_Provider
{
    const API = 'https://api.notion.com/v1/';

    public function id()
    {
        return 'notion';
    }

    public function label()
    {
        return 'Notion';
    }

    public function configured()
    {
        $s = HOPS_Settings::get();

        return $s['notion_token'] !== '' && $s['notion_database_id'] !== '';
    }

    private function headers()
    {
        $s = HOPS_Settings::get();

        return [
            'Authorization' => 'Bearer '.$s['notion_token'],
            'Notion-Version' => '2022-06-28',
        ];
    }

    private static function first_match($names, $re)
    {
        foreach ($names as $n) {
            if (preg_match($re, $n)) {
                return $n;
            }
        }

        return '';
    }

    /**
     * Reads the database once and works out which property is which. The names saved in
     * Integrations win when they exist; otherwise the first fitting property is used.
     * Cached 10 minutes.
     */
    private function schema()
    {
        $cached = get_transient('hops_notion_schema');
        if ($cached && ($cached['v'] ?? 0) === 2) {
            return $cached;
        }
        $s = HOPS_Settings::get();
        $res = $this->req('GET', self::API.'databases/'.$s['notion_database_id'], $this->headers());
        if (is_wp_error($res)) {
            return $res;
        }
        $types = [];
        $opts = [];
        $title = 'Name';
        foreach ($res['properties'] ?? [] as $name => $p) {
            $types[$name] = $p['type'];
            if ($p['type'] === 'title') {
                $title = $name;
            }
            if (in_array($p['type'], ['select', 'status'], true)) {
                $opts[$name] = array_values(array_map(function ($o) {
                    return $o['name'];
                }, $p[$p['type']]['options'] ?? []));
            }
        }
        $pick = function ($want, $type, $re) use ($types) {
            if ($want !== '' && ($types[$want] ?? '') === $type) {
                return $want;
            }
            $names = array_keys(array_filter($types, function ($t) use ($type) {
                return $t === $type;
            }));
            $hit = $re !== '' ? self::first_match($names, $re) : '';

            return $hit !== '' ? $hit : ($want === '' && $names ? $names[0] : '');
        };
        $due = $pick($s['notion_due_prop'], 'date', '/due|deadline/i');
        if ($due === '') {
            $due = $pick('', 'date', '');
        }
        $done = '';
        if (in_array($types[$s['notion_done_prop']] ?? '', ['checkbox', 'status', 'select'], true)) {
            $done = $s['notion_done_prop'];
        } else {
            $done = $pick('', 'status', '');
            if ($done === '') {
                $done = $pick('', 'checkbox', '/done|complete/i');
            }
        }
        $names = $opts[$done] ?? [];
        $done_value = in_array($s['notion_done_value'], $names, true)
            ? $s['notion_done_value']
            : (self::first_match($names, '/^(done|complete|completed|finished)$/i') ?: $s['notion_done_value']);
        $schema = [
            'v' => 2,
            'types' => $types,
            'options' => $opts,
            'title' => $title,
            'due' => $due,
            'done' => $done,
            'done_type' => $done !== '' ? $types[$done] : '',
            'done_value' => $done_value,
            'progress' => self::first_match($names, '/progress|doing|started|active/i'),
            'todo' => self::first_match($names, '/^(to ?do|not started|backlog|open)$/i'),
            'priority' => $pick('', 'select', '/priorit/i'),
            'type' => $pick('', 'select', '/type|category/i'),
        ];
        // A Select that is not a priority or type should not be taken for one.
        if ($schema['priority'] !== '' && ! preg_match('/priorit/i', $schema['priority'])) {
            $schema['priority'] = '';
        }
        if ($schema['type'] !== '' && (! preg_match('/type|category/i', $schema['type']) || $schema['type'] === $schema['done'])) {
            $schema['type'] = '';
        }
        set_transient('hops_notion_schema', $schema, 10 * MINUTE_IN_SECONDS);

        return $schema;
    }

    public function options()
    {
        $schema = $this->schema();
        if (is_wp_error($schema)) {
            return ['priority' => [], 'types' => []];
        }

        return [
            'priority' => $schema['priority'] !== '' ? ($schema['options'][$schema['priority']] ?? []) : [],
            'types' => $schema['type'] !== '' ? ($schema['options'][$schema['type']] ?? []) : [],
        ];
    }

    private function page_item($page, $schema, $status = null)
    {
        $props = $page['properties'] ?? [];
        $title = '';
        foreach ($props[$schema['title']]['title'] ?? [] as $seg) {
            $title .= $seg['plain_text'] ?? '';
        }
        $date = $schema['due'] !== '' ? ($props[$schema['due']]['date']['start'] ?? null) : null;
        $name = $schema['done'] !== '' && in_array($schema['done_type'], ['status', 'select'], true)
            ? ($props[$schema['done']][$schema['done_type']]['name'] ?? '')
            : '';
        if ($status === null) {
            $status = ($schema['progress'] !== '' && $name === $schema['progress']) ? 'progress' : 'todo';
        }

        return $this->item($page['id'], $title !== '' ? $title : '(untitled)', $date, $page['url'] ?? '', [
            'priority' => $schema['priority'] !== '' ? ($props[$schema['priority']]['select']['name'] ?? null) : null,
            'type' => $schema['type'] !== '' ? ($props[$schema['type']]['select']['name'] ?? null) : null,
            'status' => $status,
            'can_edit' => true,
        ]);
    }

    private function done_filter($schema, $finished)
    {
        $d = $schema['done'];
        if ($schema['done_type'] === 'checkbox') {
            return ['property' => $d, 'checkbox' => ['equals' => $finished]];
        }
        if (in_array($schema['done_type'], ['status', 'select'], true)) {
            return ['property' => $d, $schema['done_type'] => [$finished ? 'equals' : 'does_not_equal' => $schema['done_value']]];
        }

        return null;
    }

    public function fetch()
    {
        $schema = $this->schema();
        if (is_wp_error($schema)) {
            return $schema;
        }
        $s = HOPS_Settings::get();
        $body = ['page_size' => 100];
        if ($f = $this->done_filter($schema, false)) {
            $body['filter'] = $f;
        }
        if ($schema['due'] !== '') {
            $body['sorts'] = [['property' => $schema['due'], 'direction' => 'ascending']];
        }
        $res = $this->req('POST', self::API.'databases/'.$s['notion_database_id'].'/query', $this->headers(), $body);
        if (is_wp_error($res)) {
            return $res;
        }

        return array_map(function ($page) use ($schema) {
            return $this->page_item($page, $schema);
        }, $res['results'] ?? []);
    }

    public function fetch_done()
    {
        $schema = $this->schema();
        if (is_wp_error($schema) || ! ($f = $this->done_filter($schema, true))) {
            return [];
        }
        $s = HOPS_Settings::get();
        $res = $this->req('POST', self::API.'databases/'.$s['notion_database_id'].'/query', $this->headers(), [
            'page_size' => 20,
            'filter' => $f,
            'sorts' => [['timestamp' => 'last_edited_time', 'direction' => 'descending']],
        ]);
        if (is_wp_error($res)) {
            return [];
        }

        return array_map(function ($page) use ($schema) {
            return $this->page_item($page, $schema, 'done');
        }, $res['results'] ?? []);
    }

    public function add($title, $due)
    {
        return $this->add_with($title, $due, []);
    }

    public function add_with($title, $due, $extra = [])
    {
        $schema = $this->schema();
        if (is_wp_error($schema)) {
            return $schema;
        }
        $s = HOPS_Settings::get();
        $props = [$schema['title'] => ['title' => [['text' => ['content' => $title]]]]];
        if ($due && $schema['due'] !== '') {
            $props[$schema['due']] = ['date' => ['start' => $due]];
        }
        foreach (['priority' => 'priority', 'type' => 'type'] as $key => $field) {
            $v = $extra[$key] ?? '';
            if ($v !== '' && $schema[$field] !== '' && in_array($v, $schema['options'][$schema[$field]] ?? [], true)) {
                $props[$schema[$field]] = ['select' => ['name' => $v]];
            }
        }
        $res = $this->req('POST', self::API.'pages', $this->headers(), [
            'parent' => ['database_id' => $s['notion_database_id']],
            'properties' => $props,
        ]);

        return is_wp_error($res) ? $res : true;
    }

    private function patch($id, $props)
    {
        $res = $this->req('PATCH', self::API.'pages/'.rawurlencode($id), $this->headers(), ['properties' => $props]);

        return is_wp_error($res) ? $res : true;
    }

    private function status_value($schema, $name, $finished)
    {
        if ($schema['done_type'] === 'checkbox') {
            return ['checkbox' => $finished];
        }

        return [$schema['done_type'] => ['name' => $name]];
    }

    public function complete($id)
    {
        $schema = $this->schema();
        if (is_wp_error($schema)) {
            return $schema;
        }
        if ($schema['done'] === '') {
            return new WP_Error('hops_notion', 'No Done or Status property was found in the database. Check Integrations.');
        }

        return $this->patch($id, [$schema['done'] => $this->status_value($schema, $schema['done_value'], true)]);
    }

    public function update($id, $fields)
    {
        $schema = $this->schema();
        if (is_wp_error($schema)) {
            return $schema;
        }
        $props = [];
        if (isset($fields['priority'])) {
            if ($schema['priority'] === '') {
                return new WP_Error('hops_notion', 'The database has no Priority property.');
            }
            $props[$schema['priority']] = ['select' => $fields['priority'] !== '' ? ['name' => $fields['priority']] : null];
        }
        if (isset($fields['due']) && $schema['due'] !== '') {
            $props[$schema['due']] = ['date' => $fields['due'] !== '' ? ['start' => $fields['due']] : null];
        }
        if (isset($fields['status'])) {
            if ($schema['done'] === '') {
                return new WP_Error('hops_notion', 'No Done or Status property was found in the database. Check Integrations.');
            }
            if ($fields['status'] === 'done') {
                $props[$schema['done']] = $this->status_value($schema, $schema['done_value'], true);
            } elseif ($schema['done_type'] === 'checkbox') {
                if ($fields['status'] === 'progress') {
                    return new WP_Error('hops_notion', 'The Done property is a checkbox, so there is no In progress state. Use a Status property.');
                }
                $props[$schema['done']] = ['checkbox' => false];
            } else {
                $name = $fields['status'] === 'progress' ? $schema['progress'] : $schema['todo'];
                if ($name === '') {
                    return new WP_Error('hops_notion', 'The Status property has no '.($fields['status'] === 'progress' ? 'In progress' : 'To Do').' option.');
                }
                $props[$schema['done']] = $this->status_value($schema, $name, false);
            }
        }

        return $props ? $this->patch($id, $props) : true;
    }
}

class HOPS_Todo_Todoist extends HOPS_Todo_Provider
{
    const API = 'https://api.todoist.com/api/v1/';

    public function id()
    {
        return 'todoist';
    }

    public function label()
    {
        return 'Todoist';
    }

    public function configured()
    {
        $s = HOPS_Settings::get();

        return $s['todoist_token'] !== '';
    }

    private function headers()
    {
        $s = HOPS_Settings::get();

        return ['Authorization' => 'Bearer '.$s['todoist_token']];
    }

    public function fetch()
    {
        $res = $this->req('GET', self::API.'tasks?limit=200', $this->headers());
        if (is_wp_error($res)) {
            return $res;
        }
        $list = isset($res['results']) ? $res['results'] : $res;
        $out = [];
        foreach ($list as $t) {
            if (! is_array($t) || ! isset($t['id'])) {
                continue;
            }
            $due = isset($t['due']['date']) ? $t['due']['date'] : null;
            $out[] = $this->item($t['id'], isset($t['content']) ? $t['content'] : '(untitled)', $due, 'https://app.todoist.com/app/task/'.$t['id']);
        }

        return $out;
    }

    public function add($title, $due)
    {
        $body = ['content' => $title];
        if ($due) {
            $body['due_date'] = $due;
        }
        $res = $this->req('POST', self::API.'tasks', $this->headers(), $body);

        return is_wp_error($res) ? $res : true;
    }

    public function complete($id)
    {
        $res = $this->req('POST', self::API.'tasks/'.rawurlencode($id).'/close', $this->headers());

        return is_wp_error($res) ? $res : true;
    }
}

class HOPS_Todo_Trello extends HOPS_Todo_Provider
{
    const API = 'https://api.trello.com/1/';

    public function id()
    {
        return 'trello';
    }

    public function label()
    {
        return 'Trello';
    }

    public function configured()
    {
        $s = HOPS_Settings::get();

        return $s['trello_key'] !== '' && $s['trello_token'] !== '' && $s['trello_board_id'] !== '';
    }

    private function auth($extra = [])
    {
        $s = HOPS_Settings::get();

        return http_build_query(array_merge(['key' => $s['trello_key'], 'token' => $s['trello_token']], $extra), '', '&', PHP_QUERY_RFC3986);
    }

    public function fetch()
    {
        $s = HOPS_Settings::get();
        $res = $this->req('GET', self::API.'boards/'.rawurlencode($s['trello_board_id']).'/cards?'.$this->auth(['fields' => 'name,due,dueComplete,url']));
        if (is_wp_error($res)) {
            return $res;
        }
        $out = [];
        foreach ($res as $c) {
            if (! empty($c['dueComplete'])) {
                continue;
            }
            $out[] = $this->item($c['id'], $c['name'], isset($c['due']) ? $c['due'] : null, isset($c['url']) ? $c['url'] : '');
        }

        return $out;
    }

    public function add($title, $due)
    {
        $s = HOPS_Settings::get();
        $lists = $this->req('GET', self::API.'boards/'.rawurlencode($s['trello_board_id']).'/lists?'.$this->auth(['filter' => 'open', 'fields' => 'name']));
        if (is_wp_error($lists)) {
            return $lists;
        }
        if (empty($lists[0]['id'])) {
            return new WP_Error('hops_trello', 'No open lists on that Trello board.');
        }
        $params = ['idList' => $lists[0]['id'], 'name' => $title];
        if ($due) {
            $params['due'] = $due;
        }
        $res = $this->req('POST', self::API.'cards?'.$this->auth($params));

        return is_wp_error($res) ? $res : true;
    }

    /** Completing archives the card. */
    public function complete($id)
    {
        $res = $this->req('PUT', self::API.'cards/'.rawurlencode($id).'?'.$this->auth(['closed' => 'true']));

        return is_wp_error($res) ? $res : true;
    }
}

class HOPS_Todos
{
    public static function init()
    {
        add_action('wp_ajax_hops_todos_list', [__CLASS__, 'ajax_list']);
        add_action('wp_ajax_hops_todo_add', [__CLASS__, 'ajax_add']);
        add_action('wp_ajax_hops_todo_done', [__CLASS__, 'ajax_done']);
        add_action('wp_ajax_hops_todo_update', [__CLASS__, 'ajax_update']);
        add_action('wp_ajax_hops_todos_done_list', [__CLASS__, 'ajax_done_list']);
    }

    /** Add a provider here to bring another to-do app into the hub. */
    public static function providers()
    {
        static $p = null;
        if ($p === null) {
            $all = [new HOPS_Todo_Local, new HOPS_Todo_Notion, new HOPS_Todo_Todoist, new HOPS_Todo_Trello];
            $p = [];
            foreach ($all as $prov) {
                $p[$prov->id()] = $prov;
            }
        }

        return $p;
    }

    public static function configured_providers()
    {
        return array_filter(self::providers(), function ($p) {
            return $p->configured();
        });
    }

    /** Open tasks from every configured provider. */
    public static function collect()
    {
        $items = [];
        $errors = [];
        foreach (self::configured_providers() as $p) {
            $r = $p->fetch();
            if (is_wp_error($r)) {
                $errors[$p->label()] = $r->get_error_message();
            } else {
                $items = array_merge($items, $r);
            }
        }
        $rank = array_flip(['Critical', 'High', 'Medium', 'Low']);
        usort($items, function ($a, $b) use ($rank) {
            $c = (isset($a['due']) ? $a['due'] : '9999-99-99') <=> (isset($b['due']) ? $b['due'] : '9999-99-99');
            if ($c === 0) {
                $c = ($rank[$a['priority']] ?? 9) <=> ($rank[$b['priority']] ?? 9);
            }

            return $c !== 0 ? $c : strcasecmp($a['title'], $b['title']);
        });

        return [$items, $errors];
    }

    private static function guard()
    {
        check_ajax_referer('hops');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Not allowed.'], 403);
        }
    }

    /** Priority and type choices for each app, for the Add box and filters. */
    private static function option_sets()
    {
        $out = [];
        foreach (self::configured_providers() as $id => $p) {
            $out[$id] = $p->options();
        }

        return $out;
    }

    public static function ajax_list()
    {
        self::guard();
        [$items, $errors] = self::collect();
        wp_send_json_success(['items' => $items, 'errors' => $errors, 'today' => wp_date('Y-m-d'), 'options' => self::option_sets()]);
    }

    public static function ajax_done_list()
    {
        self::guard();
        $items = [];
        foreach (self::configured_providers() as $p) {
            $items = array_merge($items, $p->fetch_done());
        }
        wp_send_json_success(['items' => $items]);
    }

    public static function ajax_update()
    {
        self::guard();
        $prov = isset($_POST['provider']) ? sanitize_key(wp_unslash($_POST['provider'])) : '';
        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        $all = self::configured_providers();
        if (! isset($all[$prov]) || $id === '') {
            wp_send_json_error(['message' => 'Unknown task.'], 400);
        }
        $fields = [];
        foreach (['priority', 'status', 'due'] as $k) {
            if (isset($_POST[$k])) {
                $fields[$k] = sanitize_text_field(wp_unslash($_POST[$k]));
            }
        }
        if (isset($fields['status']) && ! in_array($fields['status'], ['todo', 'progress', 'done'], true)) {
            wp_send_json_error(['message' => 'Unknown status.'], 400);
        }
        if (isset($fields['due']) && $fields['due'] !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fields['due'])) {
            wp_send_json_error(['message' => 'Due date must be YYYY-MM-DD.'], 400);
        }
        $r = $all[$prov]->update($id, $fields);
        if (is_wp_error($r)) {
            wp_send_json_error(['message' => $r->get_error_message()]);
        }
        wp_send_json_success();
    }

    public static function ajax_add()
    {
        self::guard();
        $title = isset($_POST['title']) ? trim(sanitize_text_field(wp_unslash($_POST['title']))) : '';
        $due = isset($_POST['due']) ? sanitize_text_field(wp_unslash($_POST['due'])) : '';
        $prov = isset($_POST['provider']) ? sanitize_key(wp_unslash($_POST['provider'])) : '';
        if ($title === '') {
            wp_send_json_error(['message' => 'Enter a task title.'], 400);
        }
        if ($due !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) {
            wp_send_json_error(['message' => 'Due date must be YYYY-MM-DD.'], 400);
        }
        $all = self::configured_providers();
        if (! isset($all[$prov])) {
            $s = HOPS_Settings::get();
            $prov = isset($all[$s['todo_default']]) ? $s['todo_default'] : 'local';
        }
        $extra = [
            'priority' => isset($_POST['priority']) ? sanitize_text_field(wp_unslash($_POST['priority'])) : '',
            'type' => isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '',
        ];
        $r = $all[$prov]->add_with($title, $due ?: null, $extra);
        if (is_wp_error($r)) {
            wp_send_json_error(['message' => $r->get_error_message()]);
        }
        wp_send_json_success(['provider' => $prov]);
    }

    public static function ajax_done()
    {
        self::guard();
        $prov = isset($_POST['provider']) ? sanitize_key(wp_unslash($_POST['provider'])) : '';
        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        $all = self::configured_providers();
        if (! isset($all[$prov]) || $id === '') {
            wp_send_json_error(['message' => 'Unknown task.'], 400);
        }
        $r = $all[$prov]->complete($id);
        if (is_wp_error($r)) {
            wp_send_json_error(['message' => $r->get_error_message()]);
        }
        wp_send_json_success();
    }

    public static function render()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        $provs = self::configured_providers();
        $s = HOPS_Settings::get();
        echo '<div class="wrap hops">';
        HOPS_UI::head(
            'Tasks',
            'Your to-do list across WordPress, Notion, Todoist, and Trello: priorities, due dates, and in-progress work, with changes saved back to the app.',
            '<a class="button" href="'.esc_url(HOPS_UI::url('hops-settings', '#hops-sec-tasks')).'">Connect an app</a>'
        );
        ?>
			<div id="hops-tasks">
				<?php if (count($provs) < 2) { ?>
					<div class="notice notice-info inline"><p>You are seeing WordPress tasks only. <a href="<?php echo esc_url(HOPS_UI::url('hops-settings', '#hops-sec-tasks')); ?>">Connect Notion, Todoist, or Trello</a> to bring those lists in.</p></div>
				<?php } ?>
				<dl class="hops-stats hops-task-stats">
					<div class="hops-stat"><dt>Open</dt><dd class="hops-stat-value" data-stat="open">–</dd></div>
					<div class="hops-stat"><dt>Overdue</dt><dd class="hops-stat-value" data-stat="overdue">–</dd></div>
					<div class="hops-stat"><dt>Due today</dt><dd class="hops-stat-value" data-stat="today">–</dd></div>
					<div class="hops-stat"><dt>In progress</dt><dd class="hops-stat-value" data-stat="progress">–</dd></div>
				</dl>
				<section class="hops-panel">
					<div class="hops-panel-head"><h2>Add a task</h2></div>
					<form class="hops-task-form hops-form">
						<label class="grow">Task
							<input type="text" name="title" placeholder="Send the Q4 campaign brief" required>
						</label>
						<label>Due date
							<input type="date" name="due">
						</label>
						<label>Priority
							<select name="priority"><option value="">None</option></select>
						</label>
						<label class="hops-type-wrap" hidden>Type
							<select name="type"><option value="">None</option></select>
						</label>
						<label>Save to
							<select name="provider">
								<?php foreach ($provs as $id => $p) { ?>
									<option value="<?php echo esc_attr($id); ?>"<?php selected($s['todo_default'], $id); ?>><?php echo esc_html($p->label()); ?></option>
								<?php } ?>
							</select>
						</label>
						<button class="button button-primary">Add task</button>
					</form>
				</section>
				<div class="hops-chips hops-seg" role="group" aria-label="View">
					<button type="button" class="button hops-chip hops-view" data-view="open" aria-pressed="true">Open <span class="n"></span></button>
					<button type="button" class="button hops-chip hops-view" data-view="today" aria-pressed="false">Today <span class="n"></span></button>
					<button type="button" class="button hops-chip hops-view" data-view="overdue" aria-pressed="false">Overdue <span class="n"></span></button>
					<button type="button" class="button hops-chip hops-view" data-view="progress" aria-pressed="false">In progress <span class="n"></span></button>
					<button type="button" class="button hops-chip hops-view" data-view="done" aria-pressed="false">Done</button>
				</div>
				<div class="hops-toolbar">
					<label class="screen-reader-text" for="hops-q">Search tasks</label>
					<input type="search" id="hops-q" class="hops-q" placeholder="Search tasks">
					<label class="screen-reader-text" for="hops-f-pri">Filter by priority</label>
					<select id="hops-f-pri" class="hops-f-pri"><option value="">Any priority</option></select>
					<label class="screen-reader-text" for="hops-f-type">Filter by type</label>
					<select id="hops-f-type" class="hops-f-type" hidden><option value="">Any type</option></select>
					<label class="screen-reader-text" for="hops-sort">Group by</label>
					<select id="hops-sort" class="hops-sort"><option value="due">Group by due date</option><option value="priority">Group by priority</option></select>
				</div>
				<div class="hops-chips hops-apps" role="group" aria-label="Filter by app">
					<button type="button" class="button hops-chip hops-app" data-filter="" aria-pressed="true">All apps</button>
					<?php foreach ($provs as $id => $p) { ?>
						<button type="button" class="button hops-chip hops-app" data-filter="<?php echo esc_attr($id); ?>" aria-pressed="false"><?php echo esc_html($p->label()); ?> <span class="n"></span></button>
					<?php } ?>
				</div>
				<div class="hops-errors"></div>
				<div class="hops-status" role="status" aria-live="polite"></div>
				<section class="hops-panel hops-groups" hidden></section>
			</div>
		</div>
		<?php
    }
}
