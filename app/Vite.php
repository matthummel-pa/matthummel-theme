<?php

namespace App;

/**
 * Vite asset management for matthummel-theme.
 *
 * Handles both development (HMR via dev server) and production
 * (hashed files via Vite manifest) asset loading in WordPress.
 *
 * Usage in setup.php:
 *   Vite::enqueue('resources/scripts/app.js');
 *   Vite::enqueue('resources/scripts/editor.js', 'matthummel-editor');
 */
class Vite
{
    protected static string $buildDir = 'public';
    protected static string $hotFile  = 'hot';
    protected static ?array $manifest = null;

    /** True when the Vite dev server is running. */
    public static function isRunning(): bool
    {
        return is_file(static::hotFilePath());
    }

    protected static function hotFilePath(): string
    {
        return get_template_directory() . '/' . static::$buildDir . '/' . static::$hotFile;
    }

    /** Base URL read from the hot file (e.g. http://localhost:3000). */
    public static function devUrl(): string
    {
        return rtrim((string) file_get_contents(static::hotFilePath()), '/');
    }

    /** Base URL for production-built assets. */
    public static function assetUrl(): string
    {
        return rtrim(get_template_directory_uri() . '/' . static::$buildDir, '/');
    }

    /** Read and cache the Vite manifest (Vite 5: .vite/manifest.json). */
    public static function manifest(): array
    {
        if (static::$manifest !== null) {
            return static::$manifest;
        }

        foreach ([
            get_template_directory() . '/' . static::$buildDir . '/.vite/manifest.json',
            get_template_directory() . '/' . static::$buildDir . '/manifest.json',
        ] as $path) {
            if (file_exists($path)) {
                return static::$manifest = (array) json_decode(
                    (string) file_get_contents($path),
                    true
                );
            }
        }

        return static::$manifest = [];
    }

    /**
     * Enqueue a Vite entry point (works in both dev and prod).
     *
     * @param string $entry  Relative path from theme root, e.g. 'resources/scripts/app.js'
     * @param string $handle WP script handle (defaults to 'matthummel-<basename>')
     * @param array  $deps   Additional WP script dependencies
     */
    public static function enqueue(string $entry, string $handle = '', array $deps = []): void
    {
        if (! $handle) {
            $handle = 'matthummel-' . pathinfo($entry, PATHINFO_FILENAME);
        }

        if (static::isRunning()) {
            static::enqueueDev($entry, $handle, $deps);
        } else {
            static::enqueueProd($entry, $handle, $deps);
        }
    }

    /** Dev mode: inject Vite HMR client + entry as ES module. */
    protected static function enqueueDev(string $entry, string $handle, array $deps): void
    {
        $devUrl = static::devUrl();

        if (! wp_script_is('vite-client', 'registered')) {
            wp_register_script('vite-client', "{$devUrl}/@vite/client", [], null);
            add_filter('script_loader_tag', [static::class, 'addModuleType'], 10, 2);
        }

        wp_enqueue_script(
            $handle,
            "{$devUrl}/{$entry}",
            array_merge(['vite-client'], $deps),
            null,
            ['in_footer' => true]
        );
    }

    /** Prod mode: enqueue hashed files from the Vite manifest. */
    protected static function enqueueProd(string $entry, string $handle, array $deps): void
    {
        $manifest = static::manifest();
        $baseUrl  = static::assetUrl();

        if (! isset($manifest[$entry])) {
            return;
        }

        $item = $manifest[$entry];

        wp_enqueue_script($handle, "{$baseUrl}/{$item['file']}", $deps, null, ['in_footer' => true]);

        if (! empty($item['css'])) {
            foreach ($item['css'] as $i => $cssFile) {
                wp_enqueue_style("{$handle}-css-{$i}", "{$baseUrl}/{$cssFile}", [], null);
            }
        }

        add_filter('script_loader_tag', [static::class, 'addModuleType'], 10, 2);
    }

    /**
     * Adds type="module" to Vite-managed script tags.
     * Hooked via add_filter('script_loader_tag', ...).
     */
    public static function addModuleType(string $tag, string $handle): string
    {
        static $vitHandles = ['vite-client', 'matthummel-app', 'matthummel-editor'];

        if (in_array($handle, $vitHandles, true)) {
            return str_replace('<script ', '<script type="module" ', $tag);
        }

        return $tag;
    }
}
