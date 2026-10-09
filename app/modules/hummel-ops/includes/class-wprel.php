<?php
defined('ABSPATH') || exit;

/**
 * WordPress release tracker.
 *
 * Pulls security releases and the upcoming release schedule from WordPress.org, keeps a running
 * history, rewrites two Google Docs when something changes, and starts an n8n workflow when a new
 * release ships.
 */
class HOPS_WPRel
{
    const OPT = 'hops_wprel';

    const CRON = 'hops_wprel_check';

    const FEED = 'https://wordpress.org/news/category/security/feed/';

    const STABLE = 'https://api.wordpress.org/core/stable-check/1.0/';

    const MAKE = 'https://make.wordpress.org/core/wp-json/wp/v2/';

    const DOC_TITLES = [
        'security' => 'WordPress Security Releases',
        'upcoming' => 'WordPress Upcoming Changes',
    ];

    public static function init()
    {
        add_action('wp_ajax_hops_wprel_run', [__CLASS__, 'ajax']);
        add_action(self::CRON, [__CLASS__, 'cron']);
        add_action('admin_init', [__CLASS__, 'schedule']);
        add_action('rest_api_init', [__CLASS__, 'register_rest']);
    }

    /* ------------------------------------------------------------------ state */

    public static function defaults()
    {
        return [
            'security' => [],
            'branches' => [],
            'latest' => '',
            'upcoming' => null,
            'minor' => [],
            'checked' => 0,
            'error' => '',
            'warn' => [],
            'docs' => ['security' => [], 'upcoming' => []],
            'docs_error' => '',
            'notified' => [],
            'last_post' => '',
        ];
    }

    public static function state()
    {
        $st = wp_parse_args(get_option(self::OPT, []), self::defaults());
        $st['docs'] = wp_parse_args((array) $st['docs'], ['security' => [], 'upcoming' => []]);

        return $st;
    }

    private static function save($st)
    {
        update_option(self::OPT, $st, false);
    }

    /* ------------------------------------------------------------------ cron */

    public static function schedule()
    {
        $s = HOPS_Settings::raw();
        $next = wp_next_scheduled(self::CRON);
        if (! empty($s['wprel_auto']) && ! $next) {
            wp_schedule_event(time() + 120, 'twicedaily', self::CRON);
        } elseif (empty($s['wprel_auto']) && $next) {
            wp_clear_scheduled_hook(self::CRON);
        }
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook(self::CRON);
    }

    public static function cron()
    {
        $s = HOPS_Settings::raw();
        if (! empty($s['wprel_auto'])) {
            self::check(true, false);
        }
    }

    /* ------------------------------------------------------------------ fetching */

    private static function http($url)
    {
        $res = wp_remote_get($url, [
            'timeout' => 20,
            'user-agent' => 'HummelOps/'.HOPS_VERSION.'; '.home_url(),
        ]);
        if (is_wp_error($res)) {
            return $res;
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        if ($code !== 200) {
            $host = wp_parse_url($url, PHP_URL_HOST);

            return new WP_Error('hops_http', $host.' answered HTTP '.$code.'.', ['status' => $code]);
        }

        return wp_remote_retrieve_body($res);
    }

    private static function clean($html)
    {
        $t = wp_strip_all_tags((string) $html);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $t));
    }

    private static function shorten($t, $max)
    {
        if (mb_strlen($t) <= $max) {
            return $t;
        }

        return rtrim(mb_substr($t, 0, $max - 1), " ,;:.\t").'…';
    }

    /** Parse an HTML fragment. Returns array( DOMDocument, DOMXPath ) with glossary tooltips removed. */
    private static function dom($html)
    {
        $d = new DOMDocument;
        libxml_use_internal_errors(true);
        $d->loadHTML('<?xml encoding="utf-8" ?><div id="hops-root">'.$html.'</div>', LIBXML_NONET);
        libxml_clear_errors();
        $x = new DOMXPath($d);
        foreach (iterator_to_array($x->query("//*[contains(@class,'glossary-item-hidden-content')]")) as $n) {
            $n->parentNode->removeChild($n);
        }

        return [$d, $x];
    }

    /** Security releases from the WordPress.org news feed, keyed by version. */
    private static function fetch_security($pages)
    {
        $out = [];
        for ($p = 1; $p <= $pages; $p++) {
            $body = self::http(self::FEED.($p > 1 ? '?paged='.$p : ''));
            if (is_wp_error($body)) {
                if ($p === 1) {
                    return $body;
                }
                break;
            }
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
            libxml_clear_errors();
            if (! $xml || ! isset($xml->channel->item)) {
                if ($p === 1) {
                    return new WP_Error('hops_feed', 'The WordPress.org security feed could not be read.');
                }
                break;
            }
            foreach ($xml->channel->item as $it) {
                $title = trim((string) $it->title);
                if (! preg_match('/^WordPress\s+(\d+(?:\.\d+){1,2})\b/', $title, $m)) {
                    continue;
                }
                $content = (string) $it->children('http://purl.org/rss/1.0/modules/content/')->encoded;
                [$summary, $fixes] = self::parse_announcement($content);
                if ($summary === '') {
                    $summary = preg_replace('/\s*\[…\]\s*$/u', '', self::clean((string) $it->description));
                }
                $out[$m[1]] = [
                    'version' => $m[1],
                    'ts' => (int) strtotime((string) $it->pubDate),
                    'kind' => stripos($title, 'maintenance and security') !== false ? 'Maintenance and security' : 'Security',
                    'title' => $title,
                    'url' => esc_url_raw(trim((string) $it->link)),
                    'summary' => self::shorten($summary, 400),
                    'fixes' => $fixes,
                ];
            }
        }

        return $out;
    }

    /** First paragraph and the "Security updates included" list from an announcement. */
    private static function parse_announcement($html)
    {
        if (trim($html) === '') {
            return ['', []];
        }
        [$d, $x] = self::dom($html);
        $summary = '';
        $para = $x->query('//p')->item(0);
        if ($para) {
            $summary = self::clean($para->textContent);
        }
        $fixes = [];
        $on = false;
        $root = $d->getElementById('hops-root');
        foreach ($root->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            if ($node->nodeName === 'h2') {
                $on = stripos($node->textContent, 'security update') !== false;
            } elseif ($on && $node->nodeName === 'ul') {
                foreach ($node->getElementsByTagName('li') as $li) {
                    $t = self::clean($li->textContent);
                    $t = preg_replace('/,?\s*(reported|discovered|found|disclosed)\s+(by|to)\b.*$/iu', '', $t);
                    $t = trim($t, ' ,;.');
                    if ($t !== '' && count($fixes) < 12) {
                        $fixes[] = self::shorten($t, 240);
                    }
                }
            }
        }

        return [$summary, $fixes];
    }

    /** Latest version and the newest version on each recent branch, from the WordPress.org version API. */
    private static function fetch_status()
    {
        $body = self::http(self::STABLE);
        if (is_wp_error($body)) {
            return $body;
        }
        $map = json_decode($body, true);
        if (! is_array($map) || ! $map) {
            return new WP_Error('hops_status', 'The WordPress.org version list could not be read.');
        }
        $latest = '';
        $best = [];
        foreach ($map as $v => $status) {
            $v = (string) $v;
            if (strpos($v, '-') !== false || ! preg_match('/^(\d+\.\d+)/', $v, $m)) {
                continue;
            }
            if ($status === 'latest') {
                $latest = $v;
            }
            if (! isset($best[$m[1]]) || version_compare($v, $best[$m[1]]['version'], '>')) {
                $best[$m[1]] = ['branch' => $m[1], 'version' => $v, 'status' => (string) $status];
            }
        }
        uasort($best, function ($a, $b) {
            return version_compare($b['branch'], $a['branch']);
        });

        return ['latest' => $latest, 'branches' => array_slice(array_values($best), 0, 4)];
    }

    /** Next release cycle: schedule table, roadmap items. Null when no cycle page exists yet. */
    private static function fetch_upcoming($latest)
    {
        if (! preg_match('/^(\d+)\.(\d+)/', $latest, $m)) {
            return new WP_Error('hops_up', 'No current version to plan from.');
        }
        $page = null;
        $ver = '';
        foreach ([$m[1].'.'.((int) $m[2] + 1), ((int) $m[1] + 1).'.0'] as $cand) {
            $body = self::http(self::MAKE.'pages?slug='.str_replace('.', '-', $cand).'&_fields=id,link,modified,content');
            if (is_wp_error($body)) {
                return $body;
            }
            $arr = json_decode($body, true);
            if (! empty($arr[0]['content']['rendered'])) {
                $page = $arr[0];
                $ver = $cand;
                break;
            }
        }
        if (! $page) {
            return null;
        }

        [$d, $x] = self::dom($page['content']['rendered']);
        $schedule = [];
        foreach ($x->query('//tr') as $tr) {
            $tds = $tr->getElementsByTagName('td');
            if ($tds->length < 2) {
                continue;
            }
            $when = DateTime::createFromFormat('!j F Y', trim(self::clean($tds->item(0)->textContent)), new DateTimeZone('UTC'));
            if (! $when) {
                continue;
            }
            $strong = $tds->item(1)->getElementsByTagName('strong')->item(0);
            $label = $strong ? self::clean($strong->textContent) : self::shorten(self::clean($tds->item(1)->textContent), 60);
            $schedule[] = ['date' => $when->format('Y-m-d'), 'label' => rtrim($label, '. ')];
        }
        $release_date = '';
        foreach ($schedule as $row) {
            if (preg_match('/released/i', $row['label'])) {
                $release_date = $row['date'];
            }
        }
        if ($release_date === '' && $schedule) {
            $release_date = end($schedule)['date'];
        }

        $roadmap_url = '';
        $items = [];
        $intro = '';
        $link = $x->query("//a[contains(@href,'roadmap-to')]")->item(0);
        if ($link) {
            $roadmap_url = esc_url_raw($link->getAttribute('href'));
            $slug = basename(untrailingslashit((string) wp_parse_url($roadmap_url, PHP_URL_PATH)));
            $rb = self::http(self::MAKE.'posts?slug='.rawurlencode($slug).'&_fields=link,content');
            if (! is_wp_error($rb)) {
                $ra = json_decode($rb, true);
                if (! empty($ra[0]['content']['rendered'])) {
                    $roadmap_url = esc_url_raw($ra[0]['link']);
                    [$intro, $items] = self::parse_roadmap($ra[0]['content']['rendered']);
                }
            }
        }

        return [
            'version' => $ver,
            'cycle_url' => esc_url_raw($page['link']),
            'schedule' => $schedule,
            'release_date' => $release_date,
            'roadmap_url' => $roadmap_url,
            'intro' => $intro,
            'items' => $items,
        ];
    }

    /** Roadmap post: headline paragraph plus a flat list of planned changes. */
    private static function parse_roadmap($html)
    {
        [$d, $x] = self::dom($html);
        $intro = '';
        foreach ($x->query('//p') as $p) {
            $t = self::clean($p->textContent);
            if (mb_strlen($t) > 120) {
                $intro = self::shorten($t, 600);
                break;
            }
        }
        $skip = ['table of contents', 'open to contributors', 'how to contribute'];
        $items = [];
        $group = '';
        $pending = null; // An h2 that may turn out to be an item itself.
        $flush = function () use (&$pending, &$items) {
            if ($pending && ! $pending['kids']) {
                $items[] = ['group' => '', 'title' => $pending['title']];
            }
            $pending = null;
        };
        foreach ($x->query('//h2 | //h3') as $h) {
            $t = rtrim(self::clean($h->textContent), ': ');
            if ($t === '') {
                continue;
            }
            if ($h->nodeName === 'h2') {
                $flush();
                if (in_array(strtolower($t), $skip, true)) {
                    $group = '';

                    continue;
                }
                $group = $t;
                $pending = ['title' => $t, 'kids' => 0];
            } elseif ($pending) {
                $items[] = ['group' => $group, 'title' => $t];
                $pending['kids']++;
            }
        }
        $flush();

        return [$intro, $items];
    }

    /** Minor releases announced on make.wordpress.org in the last 60 days that are newer than the latest version. */
    private static function fetch_minor($latest)
    {
        $found = [];
        $after = gmdate('Y-m-d\TH:i:s', time() - 60 * DAY_IN_SECONDS);
        foreach (['release planning', 'upcoming maintenance release'] as $q) {
            $body = self::http(self::MAKE.'posts?search='.rawurlencode($q).'&after='.$after.'&per_page=10&_fields=title,link,date');
            if (is_wp_error($body)) {
                return $body;
            }
            foreach ((array) json_decode($body, true) as $p) {
                $title = self::clean(isset($p['title']['rendered']) ? $p['title']['rendered'] : '');
                if (preg_match('/WordPress\s+(\d+\.\d+\.\d+)/', $title, $m) && version_compare($m[1], $latest, '>')) {
                    $found[$m[1]] = ['version' => $m[1], 'title' => $title, 'url' => esc_url_raw($p['link']), 'date' => substr((string) $p['date'], 0, 10)];
                }
            }
        }
        uksort($found, function ($a, $b) {
            return version_compare($a, $b);
        });

        return array_values($found);
    }

    /* ------------------------------------------------------------------ check */

    /**
     * Refresh everything, update the Google Docs if content changed, notify n8n of new releases.
     *
     * @return array{ok:bool,message:string}
     */
    public static function check($notify = true, $force_docs = false)
    {
        $st = self::state();
        $first = ! $st['checked'];
        $msgs = [];
        $warn = [];

        $sec = self::fetch_security(($first || count($st['security']) < 5) ? 3 : 1);
        if (is_wp_error($sec)) {
            $st['error'] = $sec->get_error_message();
            self::save($st);

            return ['ok' => false, 'message' => $st['error']];
        }
        $st['error'] = '';
        $new = [];
        foreach ($sec as $v => $item) {
            if (! isset($st['security'][$v])) {
                $new[] = $v;
            }
            $st['security'][$v] = $item;
        }
        uasort($st['security'], function ($a, $b) {
            return $b['ts'] <=> $a['ts'];
        });
        if ($first) {
            $st['notified'] = array_keys($st['security']); // History is not news.
        }

        $status = self::fetch_status();
        if (is_wp_error($status)) {
            $warn[] = $status->get_error_message();
        } else {
            $st['latest'] = $status['latest'];
            $st['branches'] = $status['branches'];
        }

        if ($st['latest']) {
            $up = self::fetch_upcoming($st['latest']);
            if (is_wp_error($up)) {
                $warn[] = 'Upcoming schedule: '.$up->get_error_message();
            } else {
                $st['upcoming'] = $up;
            }
            $minor = self::fetch_minor($st['latest']);
            if (is_wp_error($minor)) {
                $warn[] = 'Minor release notices: '.$minor->get_error_message();
            } else {
                $st['minor'] = $minor;
            }
        }

        $st['checked'] = time();
        $st['warn'] = $warn;

        $msgs[] = $new ? count($new).' new security release'.(count($new) > 1 ? 's' : '').' ('.implode(', ', $new).')' : 'No new security releases';

        self::sync_docs($st, $force_docs, $msgs);

        if ($notify) {
            self::notify($st, $msgs);
        }

        self::save($st);

        return ['ok' => true, 'message' => implode('. ', $msgs).'.'];
    }

    /* ------------------------------------------------------------------ n8n */

    private static function notify(&$st, &$msgs)
    {
        $s = HOPS_Settings::get();
        $index = (int) $s['wprel_workflow'];
        if ($index < 0 || ! isset($s['workflows'][$index])) {
            return;
        }
        $pending = [];
        foreach ($st['security'] as $v => $r) {
            if (! in_array($v, $st['notified'], true)) {
                $pending[] = $v;
            }
        }
        foreach (array_reverse($pending) as $v) { // Oldest first.
            $res = HOPS_N8n::send($s['workflows'][$index], self::payload($st, $v));
            if (is_wp_error($res) || $res['status'] < 200 || $res['status'] >= 300) {
                $msgs[] = 'n8n did not accept the '.$v.' notice ('.(is_wp_error($res) ? $res->get_error_message() : 'HTTP '.$res['status']).'); it will retry on the next check';

                return;
            }
            $st['notified'][] = $v;
            $msgs[] = 'Started "'.$s['workflows'][$index]['name'].'" for '.$v;
        }
    }

    private static function payload($st, $version)
    {
        $r = $st['security'][$version];

        return [
            'event' => 'wordpress.release',
            'source' => 'hummel-ops',
            'site' => home_url(),
            'checked_at' => gmdate('c'),
            'release' => self::export_release($r),
            'post' => self::post_for($r, $st),
            'latest' => $st['latest'],
            'upcoming' => self::export_upcoming($st),
            'docs' => self::doc_urls($st),
        ];
    }

    /** A ready-to-publish post (block markup) so the n8n workflow only has to create it. */
    private static function post_for($r, $st)
    {
        $e = function ($html) {
            return $html; // Already escaped by the callers below.
        };
        $p = function ($inner) use ($e) {
            return "<!-- wp:paragraph -->\n<p>".$e($inner)."</p>\n<!-- /wp:paragraph -->\n\n";
        };
        $h = function ($text) {
            return "<!-- wp:heading -->\n<h2>".esc_html($text)."</h2>\n<!-- /wp:heading -->\n\n";
        };
        $ul = function ($rows) {
            $o = "<!-- wp:list -->\n<ul>";
            foreach ($rows as $row) {
                $o .= "<!-- wp:list-item -->\n<li>".$row."</li>\n<!-- /wp:list-item -->";
            }

            return $o."</ul>\n<!-- /wp:list -->\n\n";
        };

        $ver = $r['version'];
        $date = wp_date('F j, Y', $r['ts']);
        $body = $p(esc_html($r['summary']));
        if ($r['fixes']) {
            $body .= $h('What changed').$ul(array_map('esc_html', $r['fixes']));
        }
        $body .= $h('What to do');
        $body .= $p('Update to WordPress '.esc_html($ver).' from Dashboard, Updates. Released '.esc_html($date).'. Read the <a href="'.esc_url($r['url']).'">official announcement</a> for the full notes.');

        $up = self::export_upcoming($st);
        if ($up && $up['release_date']) {
            $body .= $h('Coming next');
            $body .= $p('WordPress '.esc_html($up['version']).' is scheduled for '.esc_html(self::fmt_ymd($up['release_date'])).'.');
            $sec_items = [];
            foreach ($up['items'] as $it) {
                if ($it['group'] === 'Security') {
                    $sec_items[] = esc_html($it['title']);
                }
            }
            if ($sec_items) {
                $body .= $p('Security work on the roadmap:').$ul($sec_items);
            }
        }

        $title = $r['kind'] === 'Security' ? 'WordPress '.$ver.' Security Release: What Changed' : 'WordPress '.$ver.' Maintenance and Security Release: What Changed';

        return [
            'title' => $title,
            'content' => trim($body),
            'excerpt' => $r['summary'],
        ];
    }

    /* ------------------------------------------------------------------ export */

    private static function export_release($r)
    {
        return [
            'version' => $r['version'],
            'date' => gmdate('Y-m-d', $r['ts']),
            'kind' => $r['kind'],
            'title' => $r['title'],
            'url' => $r['url'],
            'summary' => $r['summary'],
            'fixes' => $r['fixes'],
            'security' => true,
        ];
    }

    /** Upcoming cycle with past schedule rows removed. */
    private static function export_upcoming($st)
    {
        $up = $st['upcoming'];
        if (! is_array($up)) {
            return null;
        }
        $today = wp_date('Y-m-d');
        $up['schedule'] = array_values(array_filter($up['schedule'], function ($row) use ($today) {
            return $row['date'] >= $today;
        }));
        if ($up['release_date'] && $up['release_date'] < $today) {
            return null; // The cycle has shipped; the next check will find the following one.
        }

        return $up;
    }

    private static function doc_urls($st)
    {
        $out = [];
        foreach (['security', 'upcoming'] as $k) {
            $out[$k.'_url'] = ! empty($st['docs'][$k]['id']) ? 'https://docs.google.com/document/d/'.$st['docs'][$k]['id'].'/edit' : '';
        }

        return $out;
    }

    public static function export()
    {
        $st = self::state();

        return [
            'latest' => $st['latest'],
            'checked' => $st['checked'] ? gmdate('c', $st['checked']) : '',
            'branches' => $st['branches'],
            'security' => array_values(array_map([__CLASS__, 'export_release'], $st['security'])),
            'upcoming' => self::export_upcoming($st),
            'minor' => $st['minor'],
            'docs' => self::doc_urls($st),
        ];
    }

    public static function register_rest()
    {
        register_rest_route('hummel-ops/v1', '/wp-releases', [
            'methods' => 'GET',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'callback' => function () {
                return rest_ensure_response(HOPS_WPRel::export());
            },
        ]);
    }

    /* ------------------------------------------------------------------ Google Docs */

    private static function fmt_ymd($ymd)
    {
        $d = DateTime::createFromFormat('!Y-m-d', $ymd, wp_timezone());

        return $d ? $d->format('M j, Y') : $ymd;
    }

    private static function ln($type, ...$segs)
    {
        $out = [];
        foreach ($segs as $s) {
            $out[] = is_array($s) ? $s : [$s];
        }

        return ['t' => $type, 'seg' => $out];
    }

    private static function lines_security($st)
    {
        $L = [];
        $sec = array_values($st['security']);
        $up = self::export_upcoming($st);

        $L[] = self::ln('title', 'WordPress Security Releases');
        $L[] = self::ln('stamp', 'Updated '.wp_date('M j, Y').'. Source: wordpress.org. Kept current by Hummel Ops.');

        $L[] = self::ln('h2', 'Current');
        if ($st['latest']) {
            $L[] = self::ln('bullet', ['Latest WordPress version: '.$st['latest'], true]);
        }
        if ($sec) {
            $r = $sec[0];
            $L[] = self::ln('bullet', ['Latest security release: '.$r['version'].' ('.wp_date('M j, Y', $r['ts']).')', true], ' '.$r['summary'].' ', ['Announcement', false, $r['url']]);
            foreach ($r['fixes'] as $fix) {
                $L[] = self::ln('bullet', 'Fixed: '.$fix);
            }
        }
        if ($st['branches']) {
            $L[] = self::ln('bullet', 'Newest version on recent branches: '.implode(', ', wp_list_pluck($st['branches'], 'version')));
        }

        $L[] = self::ln('h2', 'Upcoming');
        foreach ($st['minor'] as $m) {
            $L[] = self::ln('bullet', ['WordPress '.$m['version'], true], ' announced '.self::fmt_ymd($m['date']).'. ', ['Release planning', false, $m['url']]);
        }
        if ($up) {
            $sec_items = [];
            foreach ($up['items'] as $it) {
                if ($it['group'] === 'Security') {
                    $sec_items[] = $it['title'];
                }
            }
            $line = ' Scheduled '.($up['release_date'] ? self::fmt_ymd($up['release_date']) : 'date to be announced').'.';
            if ($sec_items) {
                $line .= ' Security work on the roadmap: '.implode('; ', $sec_items).'.';
            }
            $L[] = self::ln('bullet', ['WordPress '.$up['version'], true], $line);
        } elseif (! $st['minor']) {
            $L[] = self::ln('bullet', 'No future release is scheduled yet.');
        }
        $L[] = self::ln('p', 'WordPress does not announce security releases in advance. This list is updated when one ships.');

        $L[] = self::ln('h2', 'Release history');
        foreach ($sec as $r) {
            $L[] = self::ln('bullet', [$r['version'], true], ' ('.wp_date('M j, Y', $r['ts']).', '.strtolower($r['kind']).') '.$r['summary'].' ', ['Announcement', false, $r['url']]);
        }

        return $L;
    }

    private static function lines_upcoming($st)
    {
        $L = [];
        $up = self::export_upcoming($st);
        $L[] = self::ln('title', 'WordPress Upcoming Changes');
        $L[] = self::ln('stamp', 'Updated '.wp_date('M j, Y').'. Source: make.wordpress.org. Kept current by Hummel Ops.');

        if ($st['minor']) {
            $L[] = self::ln('h2', 'Planned minor releases');
            foreach ($st['minor'] as $m) {
                $L[] = self::ln('bullet', ['WordPress '.$m['version'], true], ' ', [$m['title'], false, $m['url']]);
            }
        }

        if (! $up) {
            $L[] = self::ln('p', 'No upcoming major release is scheduled yet.');

            return $L;
        }

        $L[] = self::ln('h2', 'WordPress '.$up['version']);
        $L[] = self::ln('p', 'Target release: '.($up['release_date'] ? self::fmt_ymd($up['release_date']) : 'to be announced').'. ', ['Release cycle', false, $up['cycle_url']], $up['roadmap_url'] ? ' · ' : '', $up['roadmap_url'] ? ['Roadmap', false, $up['roadmap_url']] : '');

        if ($up['schedule']) {
            $L[] = self::ln('h2', 'Schedule');
            foreach ($up['schedule'] as $row) {
                $L[] = self::ln('bullet', [self::fmt_ymd($row['date']), true], ': '.$row['label']);
            }
        }
        if ($up['items']) {
            $L[] = self::ln('h2', 'What is coming');
            foreach ($up['items'] as $it) {
                $L[] = self::ln('bullet', $it['group'] ? [$it['group'].': ', true] : '', $it['title']);
            }
            $L[] = self::ln('p', 'Roadmap items are being pursued; not every item is guaranteed to land in the release.');
        }

        return $L;
    }

    private static function u16($s)
    {
        return (int) (strlen(mb_convert_encoding($s, 'UTF-16LE', 'UTF-8')) / 2);
    }

    /** Build the batchUpdate requests that replace the whole document body with $lines. */
    private static function requests($lines, $end)
    {
        $reqs = [];
        $had = $end > 2;
        if ($had) {
            $reqs[] = ['deleteContentRange' => ['range' => ['startIndex' => 1, 'endIndex' => $end - 1]]];
        }
        $texts = [];
        $pos = 1;
        $paras = [];
        $bold = [];
        $links = [];
        $last = count($lines) - 1;
        foreach ($lines as $i => $ln) {
            $start = $pos;
            $text = '';
            foreach ($ln['seg'] as $seg) {
                $t = $seg[0];
                if ($t === '') {
                    continue;
                }
                $len = self::u16($t);
                if (! empty($seg[1])) {
                    $bold[] = [$pos, $pos + $len];
                }
                if (! empty($seg[2])) {
                    $links[] = [$pos, $pos + $len, $seg[2]];
                }
                $text .= $t;
                $pos += $len;
            }
            if ($i < $last) {
                $text .= "\n";
                $pos++;
            }
            $texts[] = $text;
            $paras[] = [$ln['t'], $start, max($pos, $start + 1)];
        }
        $total = $pos;

        $reqs[] = ['insertText' => ['location' => ['index' => 1], 'text' => implode('', $texts)]];
        if ($had) {
            $reqs[] = ['deleteParagraphBullets' => ['range' => ['startIndex' => 1, 'endIndex' => $total]]];
            $reqs[] = ['updateTextStyle' => ['range' => ['startIndex' => 1, 'endIndex' => $total], 'textStyle' => new stdClass, 'fields' => 'bold,link']];
        }
        $reqs[] = ['updateParagraphStyle' => ['range' => ['startIndex' => 1, 'endIndex' => $total], 'paragraphStyle' => ['namedStyleType' => 'NORMAL_TEXT'], 'fields' => 'namedStyleType']];
        foreach ($paras as $p) {
            if ($p[0] === 'title' || $p[0] === 'h2') {
                $reqs[] = ['updateParagraphStyle' => ['range' => ['startIndex' => $p[1], 'endIndex' => $p[2]], 'paragraphStyle' => ['namedStyleType' => $p[0] === 'title' ? 'TITLE' : 'HEADING_2'], 'fields' => 'namedStyleType']];
            }
        }
        // One bullet request per run of consecutive bullet lines.
        $run = null;
        foreach ($paras as $p) {
            if ($p[0] === 'bullet') {
                $run = $run ? [$run[0], $p[2]] : [$p[1], $p[2]];
            } elseif ($run) {
                $reqs[] = ['createParagraphBullets' => ['range' => ['startIndex' => $run[0], 'endIndex' => $run[1]], 'bulletPreset' => 'BULLET_DISC_CIRCLE_SQUARE']];
                $run = null;
            }
        }
        if ($run) {
            $reqs[] = ['createParagraphBullets' => ['range' => ['startIndex' => $run[0], 'endIndex' => $run[1]], 'bulletPreset' => 'BULLET_DISC_CIRCLE_SQUARE']];
        }
        foreach ($bold as $b) {
            $reqs[] = ['updateTextStyle' => ['range' => ['startIndex' => $b[0], 'endIndex' => $b[1]], 'textStyle' => ['bold' => true], 'fields' => 'bold']];
        }
        foreach ($links as $l) {
            $reqs[] = ['updateTextStyle' => ['range' => ['startIndex' => $l[0], 'endIndex' => $l[1]], 'textStyle' => ['link' => ['url' => $l[2]]], 'fields' => 'link']];
        }

        return $reqs;
    }

    /** Create the document if needed, then replace its content. $d is updated in place so a new ID is never lost. */
    private static function write_doc(&$d, $title, $lines, $retry = true)
    {
        if (empty($d['id'])) {
            $c = HOPS_Google::api_post('https://docs.googleapis.com/v1/documents', ['title' => $title]);
            if (is_wp_error($c)) {
                return $c;
            }
            if (empty($c['documentId'])) {
                return new WP_Error('hops_doc', 'Google did not return a document ID.');
            }
            $d['id'] = $c['documentId'];
        }
        $base = 'https://docs.googleapis.com/v1/documents/'.rawurlencode($d['id']);
        $doc = HOPS_Google::api_get($base.'?fields='.rawurlencode('body(content(endIndex))'));
        if (is_wp_error($doc)) {
            $data = $doc->get_error_data();
            if ($retry && isset($data['status']) && (int) $data['status'] === 404) { // Deleted in Drive: start a fresh one.
                $d = [];

                return self::write_doc($d, $title, $lines, false);
            }

            return $doc;
        }
        $end = 2;
        if (! empty($doc['body']['content'])) {
            $tail = end($doc['body']['content']);
            $end = isset($tail['endIndex']) ? (int) $tail['endIndex'] : 2;
        }
        $r = HOPS_Google::api_post($base.':batchUpdate', ['requests' => self::requests($lines, $end)]);

        return is_wp_error($r) ? $r : true;
    }

    private static function sync_docs(&$st, $force, &$msgs)
    {
        $st['docs_error'] = '';
        if (! HOPS_Google::configured() || ! HOPS_Google::connected()) {
            $st['docs_error'] = 'Connect Google on the Files page to keep the Google Docs current.';

            return;
        }
        if (! HOPS_Google::has_scope(HOPS_Google::DOCS)) {
            $st['docs_error'] = 'Reconnect Google once to grant Google Docs access.';

            return;
        }
        $errors = [];
        foreach (['security', 'upcoming'] as $key) {
            $lines = $key === 'security' ? self::lines_security($st) : self::lines_upcoming($st);
            $body = array_values(array_filter($lines, function ($l) {
                return $l['t'] !== 'stamp';
            }));
            $hash = md5(wp_json_encode($body));
            $d = isset($st['docs'][$key]) ? (array) $st['docs'][$key] : [];
            if (! $force && ! empty($d['id']) && isset($d['hash']) && $d['hash'] === $hash) {
                continue;
            }
            $r = self::write_doc($d, self::DOC_TITLES[$key], $lines);
            if (is_wp_error($r)) {
                $st['docs'][$key] = $d; // Keep a new ID so the next try reuses the document.
                $errors[] = self::DOC_TITLES[$key].': '.$r->get_error_message();

                continue;
            }
            $d['hash'] = $hash;
            $d['updated'] = time();
            $st['docs'][$key] = $d;
            $msgs[] = 'Updated "'.self::DOC_TITLES[$key].'"';
        }
        if ($errors) {
            $st['docs_error'] = implode(' ', $errors);
            $msgs[] = 'Google Docs not updated';
        }
    }

    /* ------------------------------------------------------------------ ajax */

    public static function ajax()
    {
        check_ajax_referer('hops');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Not allowed.'], 403);
        }
        $force = isset($_POST['mode']) && $_POST['mode'] === 'docs';
        $r = self::check(true, $force);
        if ($r['ok']) {
            wp_send_json_success(['message' => $r['message']]);
        }
        wp_send_json_error(['message' => $r['message']]);
    }

    /* ------------------------------------------------------------------ screen */

    public static function render()
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        $st = self::state();
        $up = self::export_upcoming($st);
        $sec = array_values($st['security']);
        $docs = self::doc_urls($st);
        $s = HOPS_Settings::raw();
        $wf = ((int) $s['wprel_workflow'] >= 0 && isset($s['workflows'][(int) $s['wprel_workflow']])) ? $s['workflows'][(int) $s['wprel_workflow']] : null;
        $g_ok = HOPS_Google::configured() && HOPS_Google::connected() && HOPS_Google::has_scope(HOPS_Google::DOCS);

        echo '<div class="wrap hops" id="hops-wp">';
        HOPS_UI::head(
            'WordPress Releases',
            'Track WordPress security releases and what is coming next. Two Google Docs stay current, and n8n hears about every new release.',
            '<button type="button" class="button button-primary" data-hops-wp="check">'.($st['checked'] ? 'Check now' : 'Run the first check').'</button>'
            .'<button type="button" class="button" data-hops-wp="docs">Rewrite Google Docs</button>'
        );

        if ($st['checked']) {
            $tiles = HOPS_UI::stat('Latest WordPress', $st['latest'] ? $st['latest'] : 'Unknown', $st['error'] ? HOPS_UI::pill('Check failed', 'bad') : ($st['latest'] && version_compare(get_bloginfo('version'), $st['latest'], '<') ? HOPS_UI::pill('This site runs '.get_bloginfo('version'), 'warn') : HOPS_UI::pill('This site is current', 'ok')));
            $tiles .= HOPS_UI::stat('Next release', $up ? $up['version'] : 'None scheduled', $up ? esc_html($up['release_date'] ? self::fmt_ymd($up['release_date']) : 'Date to be announced') : '');
            $tiles .= HOPS_UI::stat('Last check', human_time_diff($st['checked']).' ago', ! empty($s['wprel_auto']) ? HOPS_UI::pill('Checks twice a day', 'info') : HOPS_UI::pill('Automatic checks off', 'warn'));
            $tiles .= HOPS_UI::stat('Google Docs', $g_ok ? 'Connected' : 'Not connected', $g_ok ? HOPS_UI::pill('Updating', 'ok') : '<a href="'.esc_url(HOPS_UI::url('hops-settings', '#hops-sec-google')).'">Set up Google</a>');
            HOPS_UI::stats($tiles);
        }
        ?>
			<?php if (! HOPS_Google::configured()) { ?>
				<div class="notice notice-info inline"><p>Add your Google OAuth client in <a href="<?php echo esc_url(HOPS_UI::url('hops-settings', '#hops-sec-google')); ?>">Integrations</a> to keep the two Google Docs current.</p></div>
			<?php } elseif (! HOPS_Google::connected()) { ?>
				<div class="notice notice-info inline"><p>Connect Google to create and update the two documents. <a class="button button-primary" href="<?php echo esc_url(HOPS_Google::auth_url()); ?>">Connect Google</a></p></div>
			<?php } elseif (! HOPS_Google::has_scope(HOPS_Google::DOCS)) { ?>
				<div class="notice notice-warning inline"><p>Google needs one more permission to write the documents. Enable the Google Docs API in your Google Cloud project, then <a class="button button-primary" href="<?php echo esc_url(HOPS_Google::auth_url()); ?>">Reconnect Google</a></p></div>
			<?php } ?>

			<?php if ($st['error']) { ?>
				<div class="notice notice-error inline"><p><?php echo esc_html($st['error']); ?></p></div>
			<?php } ?>
			<?php if ($st['docs_error'] && HOPS_Google::has_scope(HOPS_Google::DOCS)) { ?>
				<div class="notice notice-error inline"><p><?php echo esc_html($st['docs_error']); ?></p></div>
			<?php } ?>
			<?php foreach ($st['warn'] as $w) { ?>
				<div class="notice notice-warning inline"><p><?php echo esc_html($w); ?></p></div>
			<?php } ?>

			<div class="hops-status" role="status" aria-live="polite"></div>

			<?php if (! $st['checked']) { ?>
				<?php echo HOPS_UI::empty_state( // phpcs:ignore WordPress.Security.EscapeOutput
				    'Run your first check',
				    'The first check reads the WordPress.org security feed and the release schedule, then creates the two Google Docs. It takes a few seconds.'
				); ?>
			<?php } else { ?>
			<div class="hops-grid">
				<section class="hops-panel hops-wide">
					<div class="hops-panel-head"><h2>Security releases</h2></div>
					<h3>Current</h3>
					<?php if ($sec) {
					    $r = $sec[0]; ?>
						<p><strong><?php echo esc_html($r['version']); ?></strong>
							<span class="hops-muted"><?php echo esc_html(wp_date('M j, Y', $r['ts'])); ?></span>
							<?php echo HOPS_UI::pill($r['kind'], 'bad'); // phpcs:ignore WordPress.Security.EscapeOutput?><br>
							<?php echo esc_html($r['summary']); ?>
							<a href="<?php echo esc_url($r['url']); ?>" target="_blank" rel="noopener noreferrer">Announcement</a></p>
						<?php if ($r['fixes']) { ?>
							<ul class="hops-bullets">
								<?php foreach ($r['fixes'] as $fix) { ?><li><?php echo esc_html($fix); ?></li><?php } ?>
							</ul>
						<?php } ?>
					<?php } ?>
					<?php if ($st['branches']) { ?>
						<p class="hops-muted">Newest on recent branches: <?php echo esc_html(implode(', ', wp_list_pluck($st['branches'], 'version'))); ?></p>
					<?php } ?>

					<h3>Upcoming</h3>
					<ul class="hops-bullets">
						<?php foreach ($st['minor'] as $m) { ?>
							<li><strong>WordPress <?php echo esc_html($m['version']); ?></strong> announced <?php echo esc_html(self::fmt_ymd($m['date'])); ?>. <a href="<?php echo esc_url($m['url']); ?>" target="_blank" rel="noopener noreferrer">Release planning</a></li>
						<?php } ?>
						<?php if ($up) { ?>
							<li><strong>WordPress <?php echo esc_html($up['version']); ?></strong> scheduled <?php echo esc_html($up['release_date'] ? self::fmt_ymd($up['release_date']) : 'date to be announced'); ?>.
								<?php
					            $sec_items = [];
						    foreach ($up['items'] as $it) {
						        if ($it['group'] === 'Security') {
						            $sec_items[] = $it['title'];
						        }
						    }
						    echo $sec_items ? esc_html('Security work on the roadmap: '.implode('; ', $sec_items).'.') : '';
						    ?></li>
						<?php } elseif (! $st['minor']) { ?>
							<li>No future release is scheduled yet.</li>
						<?php } ?>
					</ul>
					<p class="hops-muted">WordPress does not announce security releases in advance.</p>

					<h3>History</h3>
					<table class="widefat striped hops-table hops-hist">
						<thead><tr><th>Version</th><th>Date</th><th>Type</th><th>What it covered</th></tr></thead>
						<tbody>
						<?php foreach ($sec as $r) { ?>
							<tr>
								<td><a href="<?php echo esc_url($r['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($r['version']); ?></a></td>
								<td><?php echo esc_html(wp_date('M j, Y', $r['ts'])); ?></td>
								<td><?php echo HOPS_UI::pill($r['kind'], 'neutral'); // phpcs:ignore WordPress.Security.EscapeOutput?></td>
								<td><?php echo esc_html($r['summary']); ?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				</section>

				<section class="hops-panel">
					<div class="hops-panel-head"><h2>Upcoming changes</h2></div>
					<?php if (! $up) { ?>
						<p class="hops-note">No upcoming major release is scheduled yet. The next check picks it up when WordPress.org publishes the schedule.</p>
					<?php } else { ?>
						<p><strong>WordPress <?php echo esc_html($up['version']); ?></strong>, target <?php echo esc_html($up['release_date'] ? self::fmt_ymd($up['release_date']) : 'to be announced'); ?>.
							<a href="<?php echo esc_url($up['cycle_url']); ?>" target="_blank" rel="noopener noreferrer">Release cycle</a>
							<?php if ($up['roadmap_url']) { ?> · <a href="<?php echo esc_url($up['roadmap_url']); ?>" target="_blank" rel="noopener noreferrer">Roadmap</a><?php } ?></p>
						<?php if ($up['schedule']) { ?>
							<h3>Schedule</h3>
							<ul class="hops-plain">
								<?php foreach ($up['schedule'] as $row) { ?>
									<li><span class="hops-time"><?php echo esc_html(self::fmt_ymd($row['date'])); ?></span> <?php echo esc_html($row['label']); ?></li>
								<?php } ?>
							</ul>
						<?php } ?>
						<?php if ($up['items']) { ?>
							<h3>What is coming</h3>
							<ul class="hops-bullets">
								<?php foreach ($up['items'] as $it) { ?>
									<li><?php echo $it['group'] ? '<strong>'.esc_html($it['group']).':</strong> ' : ''; ?><?php echo esc_html($it['title']); ?></li>
								<?php } ?>
							</ul>
						<?php } ?>
					<?php } ?>
				</section>

				<section class="hops-panel">
					<div class="hops-panel-head"><h2>Google Docs</h2></div>
					<ul class="hops-plain">
						<?php foreach (['security', 'upcoming'] as $k) {
						    $d = $st['docs'][$k];
						    ?>
							<li><strong><?php echo esc_html(self::DOC_TITLES[$k]); ?></strong><br>
								<?php if ($docs[$k.'_url']) { ?>
									<a href="<?php echo esc_url($docs[$k.'_url']); ?>" target="_blank" rel="noopener noreferrer">Open in Google Docs</a>
									<span class="hops-muted"><?php echo ! empty($d['updated']) ? esc_html('Updated '.human_time_diff($d['updated']).' ago') : ''; ?></span>
								<?php } else { ?>
									<?php echo HOPS_UI::pill('Not created yet', 'neutral'); // phpcs:ignore WordPress.Security.EscapeOutput?>
								<?php } ?></li>
						<?php } ?>
					</ul>
					<h3>n8n</h3>
					<p><?php echo $wf ? esc_html('A new release starts "'.$wf['name'].'" with the release details and a ready-made post.') : 'No workflow is set to run on a new release.'; ?>
						<a href="<?php echo esc_url(HOPS_UI::url('hops-settings', '#hops-sec-wprel')); ?>">Change in Integrations</a></p>
					<p class="hops-muted">JSON feed for n8n: <code><?php echo esc_html(rest_url('hummel-ops/v1/wp-releases')); ?></code> (log in with an Application Password).</p>
				</section>
			</div>
			<?php } ?>
		</div>
		<?php
    }
}
