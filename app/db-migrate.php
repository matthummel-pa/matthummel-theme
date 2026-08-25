<?php

/**
 * WP-CLI database migration — pull prod → local, push local → prod via SSH.
 *
 * Credential resolution (first match wins):
 *   1. WP-CLI --ssh-* flags on the command
 *   2. MH_SSH_* constants in wp-config.php
 *   3. SITEGROUND_* / SERVER_* env vars — same names as deploy.yml secrets
 *
 * Commands:
 *   wp mh db-pull   Export prod DB via SSH, import locally, search-replace URLs.
 *   wp mh db-push   Export local DB, send to prod via SSH, import + search-replace.
 *                   Requires --yes and --remote-url flags; very destructive.
 */

namespace App;

if (! defined('WP_CLI') || ! WP_CLI) {
    return;
}

/**
 * Resolve one SSH credential: CLI flag → wp-config constant → env var(s).
 *
 * @since 3.1.0
 *
 * @param  string  $flag  Already-parsed CLI assoc arg value (may be empty).
 * @param  string  $const  wp-config.php constant name, e.g. 'MH_SSH_HOST'.
 * @param  string  ...$envKeys  Environment variable names tried left-to-right.
 * @return string Resolved credential, or empty string when none is found.
 */
function mh_db_cred(string $flag, string $const, string ...$envKeys): string
{
    if ($flag !== '') {
        return $flag;
    }
    if (defined($const)) {
        return (string) constant($const);
    }
    foreach ($envKeys as $key) {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
    }

    return '';
}

/**
 * Run a shell command via proc_open, stream stderr to WP-CLI debug, and return exit code + output.
 *
 * @since 3.1.0
 *
 * @param  string  $cmd  Shell command to execute.
 * @param  bool  $quiet  When true, suppress stderr forwarding to WP-CLI debug.
 * @return array{0: int, 1: string, 2: string} Tuple of [exit code, stdout, stderr].
 */
function mh_db_exec(string $cmd, bool $quiet = false): array
{
    $desc = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = proc_open($cmd, $desc, $pipes);
    if (! is_resource($proc)) {
        return [1, '', 'proc_open failed'];
    }
    fclose($pipes[0]);
    $out = (string) stream_get_contents($pipes[1]);
    $err = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    if (! $quiet && $err !== '') {
        \WP_CLI::debug($err, 'db-migrate');
    }

    return [$code, $out, $err];
}

/**
 * Build the ssh base command string with port, optional identity file, and StrictHostKeyChecking=accept-new.
 *
 * @since 3.1.0
 *
 * @param  string  $user  SSH username.
 * @param  string  $host  SSH hostname or IP address.
 * @param  string  $port  SSH port; defaults to 22 when empty.
 * @param  string  $identity  Absolute path to a private key file (optional).
 * @return string Escaped ssh command prefix.
 */
function mh_db_ssh_base(string $user, string $host, string $port, string $identity = ''): string
{
    $p = escapeshellarg($port !== '' ? $port : '22');
    $u = escapeshellarg($user);
    $h = escapeshellarg($host);
    $idOpts = '';
    if ($identity !== '' && is_readable($identity)) {
        $idOpts = ' -i '.escapeshellarg($identity).' -o IdentitiesOnly=yes';
    }

    return "ssh -p {$p}{$idOpts} -o StrictHostKeyChecking=accept-new {$u}@{$h}";
}

/**
 * Resolve an SSH private-key path for db-pull / db-push.
 *
 * Order: --ssh-identity flag → MH_SSH_IDENTITY_FILE → SERVER_SSH_IDENTITY_FILE →
 * common Cloud Agent key paths → default ~/.ssh/id_ed25519 / id_rsa when present.
 *
 * @since 3.1.4
 */
function mh_db_identity_file(string $flag = ''): string
{
    $candidates = array_filter([
        $flag,
        mh_db_cred('', 'MH_SSH_IDENTITY_FILE', 'SERVER_SSH_IDENTITY_FILE', 'SSH_IDENTITY_FILE'),
        getenv('HOME') !== false ? rtrim((string) getenv('HOME'), '/').'/.ssh/id_ed25519_sg' : '',
        getenv('HOME') !== false ? rtrim((string) getenv('HOME'), '/').'/.ssh/id_ed25519' : '',
        getenv('HOME') !== false ? rtrim((string) getenv('HOME'), '/').'/.ssh/id_rsa' : '',
    ], static fn ($p) => is_string($p) && $p !== '');

    foreach ($candidates as $path) {
        if (is_readable($path)) {
            return $path;
        }
    }

    return '';
}

/**
 * Load a passphrase-protected identity into ssh-agent when a passphrase secret is available.
 *
 * Uses SSH_ASKPASS so the passphrase never appears on the command line argv.
 *
 * @since 3.1.4
 */
function mh_db_load_identity(string $identity): void
{
    if ($identity === '' || ! is_readable($identity)) {
        return;
    }

    $pass = mh_db_cred(
        '',
        'MH_SSH_KEY_PASSPHRASE',
        'SERVER_SSH_PRIVATE_KEY_PASSPHRASE',
        'SSH_KEY_PASSPHRASE'
    );

    // Unencrypted keys need no agent warm-up.
    if ($pass === '') {
        return;
    }

    $askpass = sys_get_temp_dir().'/mh-ssh-askpass-'.getmypid().'.sh';
    $script = "#!/bin/sh\nprintf '%s\\n' ".escapeshellarg($pass)."\n";
    if (file_put_contents($askpass, $script) === false) {
        \WP_CLI::warning('Could not write SSH askpass helper; passphrase-protected keys may fail.');

        return;
    }
    chmod($askpass, 0700);

    if (getenv('SSH_AUTH_SOCK') === false || getenv('SSH_AUTH_SOCK') === '') {
        [$code, $out] = mh_db_exec('ssh-agent -s', true);
        if ($code === 0) {
            foreach (preg_split('/\r\n|\r|\n/', $out) ?: [] as $line) {
                if (preg_match('/^(SSH_AUTH_SOCK|SSH_AGENT_PID)=(.*?);/', $line, $m)) {
                    putenv($m[1].'='.$m[2]);
                    $_ENV[$m[1]] = $m[2];
                }
            }
        }
    }

    $cmd = 'SSH_ASKPASS_REQUIRE=force SSH_ASKPASS='.escapeshellarg($askpass)
        .' DISPLAY=:0 ssh-add '.escapeshellarg($identity).' </dev/null';
    [$code, , $err] = mh_db_exec($cmd, true);
    wp_delete_file($askpass);

    if ($code !== 0) {
        \WP_CLI::warning('ssh-add failed for identity file — check SERVER_SSH_PRIVATE_KEY_PASSPHRASE. '.$err);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// wp mh db-pull
// ─────────────────────────────────────────────────────────────────────────────

\WP_CLI::add_command(
    'mh db-pull',
    function (array $args, array $assoc_args): void {
        $host = mh_db_cred(
            (string) ($assoc_args['ssh-host'] ?? ''),
            'MH_SSH_HOST',
            'SITEGROUND_HOST', 'SERVER_IP'
        );
        $port = mh_db_cred(
            (string) ($assoc_args['ssh-port'] ?? ''),
            'MH_SSH_PORT',
            'SITEGROUND_PORT', 'SERVER_SSH_PORT'
        );
        $user = mh_db_cred(
            (string) ($assoc_args['ssh-user'] ?? ''),
            'MH_SSH_USER',
            'SITEGROUND_USER', 'SERVER_USER'
        );
        $remotePath = mh_db_cred(
            (string) ($assoc_args['ssh-path'] ?? ''),
            'MH_SSH_WP_PATH',
            'SERVER_DESTINATION_PATH',
            'LIVE_WP_PATH'
        );
        // Strip trailing theme folder so we land in the WP root.
        $wpRoot = rtrim($remotePath, '/');
        if (str_ends_with($wpRoot, '/wp-content/themes/matthummel')) {
            $wpRoot = substr($wpRoot, 0, -strlen('/wp-content/themes/matthummel'));
        }

        $remoteUrl = (string) ($assoc_args['remote-url'] ?? 'https://matthummel.com');
        $localUrl = (string) ($assoc_args['local-url'] ?? home_url());
        $identity = mh_db_identity_file((string) ($assoc_args['ssh-identity'] ?? ''));

        if ($host === '' || $user === '') {
            \WP_CLI::error(
                'SSH host/user not set. Pass --ssh-host and --ssh-user, or define '
                .'MH_SSH_HOST / MH_SSH_USER in wp-config.php, or set '
                .'SITEGROUND_HOST / SITEGROUND_USER env vars.'
            );
        }
        if ($wpRoot === '') {
            \WP_CLI::error(
                'Remote WordPress root not set. Pass --ssh-path (theme path or WP root), '
                .'or define MH_SSH_WP_PATH in wp-config.php, or set SERVER_DESTINATION_PATH / LIVE_WP_PATH.'
            );
        }

        if ($identity !== '') {
            mh_db_load_identity($identity);
            \WP_CLI::log('Using SSH identity: '.$identity);
        } else {
            \WP_CLI::warning('No SSH identity file found; relying on ssh-agent / default keys.');
        }

        $stamp = gmdate('Ymd-His');
        $remoteFile = "/tmp/mh-db-pull-{$stamp}.sql";
        $localFile = sys_get_temp_dir()."/mh-db-pull-{$stamp}.sql";

        $ssh = mh_db_ssh_base($user, $host, $port, $identity);

        // 1. Export prod DB on the remote server.
        \WP_CLI::log("Exporting production database on {$host}…");
        $exportCmd = "{$ssh} "
            .escapeshellarg(
                'cd '.escapeshellarg($wpRoot).' && '
                .'wp db export --add-drop-table '.escapeshellarg($remoteFile).' --quiet'
            );
        [$code, , $err] = mh_db_exec($exportCmd);
        if ($code !== 0) {
            $hint = str_contains($err, 'Permission denied')
                ? ' Permission denied usually means the key is not authorized, or it is passphrase-protected without SERVER_SSH_PRIVATE_KEY_PASSPHRASE.'
                : '';
            \WP_CLI::error("Remote wp db export failed (exit {$code}). Is WP-CLI installed on the server?{$hint}");
        }
        \WP_CLI::log('  Remote export complete.');

        // 2. Download the dump.
        \WP_CLI::log('Downloading dump via scp…');
        $p = $port !== '' ? $port : '22';
        $idOpts = ($identity !== '' && is_readable($identity))
            ? ' -i '.escapeshellarg($identity).' -o IdentitiesOnly=yes'
            : '';
        $scpCmd = sprintf(
            'scp -P %s%s -o StrictHostKeyChecking=accept-new %s@%s:%s %s',
            escapeshellarg($p),
            $idOpts,
            escapeshellarg($user),
            escapeshellarg($host),
            escapeshellarg($remoteFile),
            escapeshellarg($localFile)
        );
        [$code] = mh_db_exec($scpCmd);
        if ($code !== 0 || ! is_readable($localFile)) {
            \WP_CLI::error("scp download failed (exit {$code}).");
        }
        \WP_CLI::log('  Downloaded to '.$localFile);

        // 3. Clean up temp file on remote.
        mh_db_exec("{$ssh} ".escapeshellarg('rm -f '.escapeshellarg($remoteFile)), true);

        // 4. Import locally.
        \WP_CLI::log('Importing into local database…');
        \WP_CLI::runcommand('db import '.escapeshellarg($localFile), ['launch' => true]);
        wp_delete_file($localFile);

        // 5. Search-replace URLs if they differ.
        if (rtrim($remoteUrl, '/') !== rtrim($localUrl, '/')) {
            \WP_CLI::log("Search-replacing {$remoteUrl} → {$localUrl}…");
            \WP_CLI::runcommand(
                'search-replace '.escapeshellarg($remoteUrl).' '.escapeshellarg($localUrl)
                .' --skip-columns=guid --report-changed-only',
                ['launch' => true]
            );
        }

        \WP_CLI::success('db-pull complete. Local database now mirrors production.');
    },
    [
        'shortdesc' => 'Pull the production database to local via SSH + search-replace.',
        'synopsis' => [
            [
                'type' => 'assoc',
                'name' => 'ssh-host',
                'description' => 'SSH hostname (default: MH_SSH_HOST → SITEGROUND_HOST → SERVER_IP).',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'ssh-port',
                'description' => 'SSH port (default: MH_SSH_PORT → SITEGROUND_PORT → SERVER_SSH_PORT, else 22).',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'ssh-user',
                'description' => 'SSH username (default: MH_SSH_USER → SITEGROUND_USER → SERVER_USER).',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'ssh-path',
                'description' => 'Path to WordPress root on remote (default: MH_SSH_WP_PATH → SERVER_DESTINATION_PATH → LIVE_WP_PATH). Theme path is auto-trimmed.',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'ssh-identity',
                'description' => 'Path to SSH private key (default: MH_SSH_IDENTITY_FILE → ~/.ssh/id_ed25519_sg → id_ed25519 → id_rsa).',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'remote-url',
                'description' => 'Production site URL to replace (default: https://matthummel.com).',
                'optional' => true,
                'default' => 'https://matthummel.com',
            ],
            [
                'type' => 'assoc',
                'name' => 'local-url',
                'description' => 'Local site URL to insert (default: home_url()).',
                'optional' => true,
            ],
        ],
        'when' => 'before_wp_load',
        'longdesc' => <<<'HELP'
## EXAMPLES

    # Pull using env vars (set SITEGROUND_HOST, SITEGROUND_USER, SERVER_DESTINATION_PATH)
    wp mh db-pull

    # Explicit flags
    wp mh db-pull --ssh-host=sg-host.example.com --ssh-user=sguser \
        --ssh-path=/home/customer/www/example.com/public_html \
        --remote-url=https://matthummel.com --local-url=http://localhost:8080

## CREDENTIAL CONSTANTS (wp-config.php)

    define( 'MH_SSH_HOST',    'sg-server.example.com' );
    define( 'MH_SSH_PORT',    '18765' );
    define( 'MH_SSH_USER',    'sguser' );
    define( 'MH_SSH_WP_PATH', '/home/customer/www/example.com/public_html' );

## NOTES

- SSH key must be readable (pass --ssh-identity or place at ~/.ssh/id_ed25519_sg).
- Passphrase-protected keys: set SERVER_SSH_PRIVATE_KEY_PASSPHRASE (or MH_SSH_KEY_PASSPHRASE).
- Local SQLite installs: the SQLite integration translates most MySQL syntax on import.
- Runs wp db export on remote; requires WP-CLI installed there.
HELP,
    ]
);

// ─────────────────────────────────────────────────────────────────────────────
// wp mh db-push
// ─────────────────────────────────────────────────────────────────────────────

\WP_CLI::add_command(
    'mh db-push',
    function (array $args, array $assoc_args): void {
        if (empty($assoc_args['yes'])) {
            \WP_CLI::error(
                "db-push overwrites the LIVE production database.\n"
                .'Re-run with --yes to confirm. Back up production first.'
            );
        }

        $host = mh_db_cred(
            (string) ($assoc_args['ssh-host'] ?? ''),
            'MH_SSH_HOST',
            'SITEGROUND_HOST', 'SERVER_IP'
        );
        $port = mh_db_cred(
            (string) ($assoc_args['ssh-port'] ?? ''),
            'MH_SSH_PORT',
            'SITEGROUND_PORT', 'SERVER_SSH_PORT'
        );
        $user = mh_db_cred(
            (string) ($assoc_args['ssh-user'] ?? ''),
            'MH_SSH_USER',
            'SITEGROUND_USER', 'SERVER_USER'
        );
        $remotePath = mh_db_cred(
            (string) ($assoc_args['ssh-path'] ?? ''),
            'MH_SSH_WP_PATH',
            'SERVER_DESTINATION_PATH',
            'LIVE_WP_PATH'
        );
        $wpRoot = rtrim($remotePath, '/');
        if (str_ends_with($wpRoot, '/wp-content/themes/matthummel')) {
            $wpRoot = substr($wpRoot, 0, -strlen('/wp-content/themes/matthummel'));
        }

        $remoteUrl = (string) ($assoc_args['remote-url'] ?? '');
        $localUrl = (string) ($assoc_args['local-url'] ?? home_url());
        $identity = mh_db_identity_file((string) ($assoc_args['ssh-identity'] ?? ''));

        if ($remoteUrl === '') {
            \WP_CLI::error('--remote-url is required for db-push (e.g. --remote-url=https://matthummel.com).');
        }
        if ($host === '' || $user === '') {
            \WP_CLI::error('SSH host/user not set. See `wp help mh db-push`.');
        }
        if ($wpRoot === '') {
            \WP_CLI::error('Remote WP root not set. Pass --ssh-path or set MH_SSH_WP_PATH / SERVER_DESTINATION_PATH / LIVE_WP_PATH.');
        }

        if ($identity !== '') {
            mh_db_load_identity($identity);
            \WP_CLI::log('Using SSH identity: '.$identity);
        }

        $stamp = gmdate('Ymd-His');
        $localFile = sys_get_temp_dir()."/mh-db-push-{$stamp}.sql";
        $remoteFile = "/tmp/mh-db-push-{$stamp}.sql";

        $ssh = mh_db_ssh_base($user, $host, $port, $identity);

        // 1. Export local DB.
        \WP_CLI::log('Exporting local database…');
        \WP_CLI::runcommand('db export --add-drop-table '.escapeshellarg($localFile), ['launch' => true]);
        if (! is_readable($localFile)) {
            \WP_CLI::error('Local wp db export failed.');
        }

        // 2. Upload via scp.
        \WP_CLI::log('Uploading to production via scp…');
        $p = $port !== '' ? $port : '22';
        $idOpts = ($identity !== '' && is_readable($identity))
            ? ' -i '.escapeshellarg($identity).' -o IdentitiesOnly=yes'
            : '';
        $scpCmd = sprintf(
            'scp -P %s%s -o StrictHostKeyChecking=accept-new %s %s@%s:%s',
            escapeshellarg($p),
            $idOpts,
            escapeshellarg($localFile),
            escapeshellarg($user),
            escapeshellarg($host),
            escapeshellarg($remoteFile)
        );
        [$code] = mh_db_exec($scpCmd);
        wp_delete_file($localFile);
        if ($code !== 0) {
            \WP_CLI::error("scp upload failed (exit {$code}).");
        }

        // 3. Import on remote + search-replace.
        \WP_CLI::log('Importing into production database…');
        $replaceCmd = rtrim($localUrl, '/') !== rtrim($remoteUrl, '/')
            ? ' && wp search-replace '.escapeshellarg($localUrl).' '.escapeshellarg($remoteUrl)
              .' --skip-columns=guid --report-changed-only'
            : '';
        $remoteImportCmd = "{$ssh} "
            .escapeshellarg(
                'cd '.escapeshellarg($wpRoot).' && '
                .'wp db import '.escapeshellarg($remoteFile).' --quiet'
                .$replaceCmd
                .' && rm -f '.escapeshellarg($remoteFile)
            );
        [$code] = mh_db_exec($remoteImportCmd);
        if ($code !== 0) {
            \WP_CLI::error("Remote import failed (exit {$code}). The remote dump is at {$remoteFile} for manual recovery.");
        }

        \WP_CLI::success('db-push complete. Production database now mirrors local.');
    },
    [
        'shortdesc' => 'Push the local database to production via SSH + search-replace. DESTRUCTIVE.',
        'synopsis' => [
            [
                'type' => 'flag',
                'name' => 'yes',
                'description' => 'Required confirmation flag — this overwrites the live database.',
                'optional' => false,
            ],
            [
                'type' => 'assoc',
                'name' => 'remote-url',
                'description' => 'Production URL to insert after import (required, e.g. https://matthummel.com).',
                'optional' => false,
            ],
            [
                'type' => 'assoc',
                'name' => 'ssh-host',
                'description' => 'SSH hostname.',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'ssh-port',
                'description' => 'SSH port.',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'ssh-user',
                'description' => 'SSH username.',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'ssh-path',
                'description' => 'Path to WP root on remote.',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'ssh-identity',
                'description' => 'Path to SSH private key.',
                'optional' => true,
            ],
            [
                'type' => 'assoc',
                'name' => 'local-url',
                'description' => 'Local site URL to replace (default: home_url()).',
                'optional' => true,
            ],
        ],
        'when' => 'before_wp_load',
        'longdesc' => <<<'HELP'
## EXAMPLES

    wp mh db-push --yes --remote-url=https://matthummel.com

## WARNING

This completely replaces the production database. Always take a SiteGround
backup first (Site Tools → Security → Backups).
HELP,
    ]
);
