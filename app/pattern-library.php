<?php

/**
 * Pattern Library admin page.
 *
 * Adds Appearance → Pattern Library that shows:
 *  - All registered patterns in the 'matthummel' category
 *  - A link to the Synced Patterns (wp_block) editor
 *  - Block inserter tips
 *
 * Mirrors the UX of Kadence's "Design Library" but lives entirely
 * within WordPress with no external API calls.
 */

namespace App;

add_action('admin_menu', function () {
    add_theme_page(
        __('Pattern Library', 'matthummel'),
        __('Pattern Library', 'matthummel'),
        'edit_posts',
        'mh-pattern-library',
        __NAMESPACE__ . '\\mh_pattern_library_page'
    );
});

/**
 * Renders the Pattern Library admin page.
 */
function mh_pattern_library_page(): void
{
    $all_patterns      = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
    $theme_patterns    = array_filter($all_patterns, function ($p) {
        return in_array('matthummel', $p['categories'] ?? [], true);
    });

    $synced_url = admin_url('edit.php?post_type=wp_block');
    ?>
    <div class="wrap mh-pattern-library">

        <h1 class="wp-heading-inline">
            <?php esc_html_e('Pattern Library', 'matthummel'); ?>
        </h1>
        <a href="<?php echo esc_url($synced_url); ?>" class="page-title-action">
            <?php esc_html_e('Manage Synced Patterns', 'matthummel'); ?>
        </a>
        <hr class="wp-header-end">

        <p class="description" style="margin-bottom:1.5rem;">
            <?php esc_html_e(
                'Pre-built block patterns for the matthummel theme. Open any page in the block editor, click the + Inserter, then choose Patterns → Matthummel to insert these layouts.',
                'matthummel'
            ); ?>
        </p>

        <?php if (empty($theme_patterns)) : ?>
            <div class="notice notice-warning inline">
                <p><?php esc_html_e('No matthummel patterns registered yet.', 'matthummel'); ?></p>
            </div>
        <?php else : ?>

        <div class="mh-pl-grid" style="
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(300px,1fr));
            gap:1.5rem;
            margin-top:1.5rem;">

            <?php foreach ($theme_patterns as $pattern) :
                $name  = $pattern['name']  ?? '';
                $title = $pattern['title'] ?? $name;
                $desc  = $pattern['description'] ?? '';
                $kws   = implode(', ', $pattern['keywords'] ?? []);
            ?>
            <div class="mh-pl-card" style="
                background:#fff;
                border:1px solid #e0e0e0;
                border-radius:8px;
                overflow:hidden;
                display:flex;
                flex-direction:column;">

                <!-- Preview area -->
                <div class="mh-pl-preview" style="
                    background:#f6f7f7;
                    padding:1rem;
                    min-height:120px;
                    border-bottom:1px solid #e0e0e0;
                    display:flex;
                    align-items:center;
                    justify-content:center;">
                    <span style="font-size:2.5rem;">🧩</span>
                </div>

                <!-- Info -->
                <div style="padding:1rem 1.25rem 1.25rem;flex:1;display:flex;flex-direction:column;gap:.35rem;">
                    <strong style="font-size:.95rem;"><?php echo esc_html($title); ?></strong>
                    <?php if ($desc) : ?>
                        <p style="margin:0;font-size:.8rem;color:#666;"><?php echo esc_html($desc); ?></p>
                    <?php endif; ?>
                    <?php if ($kws) : ?>
                        <p style="margin:.25rem 0 0;font-size:.7rem;color:#999;">
                            <?php echo esc_html($kws); ?>
                        </p>
                    <?php endif; ?>

                    <!-- Copy button -->
                    <button
                        class="button button-secondary mh-copy-pattern"
                        data-pattern="<?php echo esc_attr($name); ?>"
                        style="margin-top:auto;width:100%;">
                        <?php esc_html_e('Copy pattern name', 'matthummel'); ?>
                    </button>
                </div>

            </div>
            <?php endforeach; ?>

        </div>
        <?php endif; ?>

        <!-- Synced patterns section -->
        <hr style="margin:2.5rem 0 1.5rem;">
        <h2><?php esc_html_e('Synced Patterns (Reusable Blocks)', 'matthummel'); ?></h2>
        <p class="description">
            <?php esc_html_e(
                'Synced Patterns let you save any block layout and reuse it across multiple pages. Editing the original updates it everywhere it appears — perfect for headers, CTAs, and footers.',
                'matthummel'
            ); ?>
        </p>
        <p>
            <a href="<?php echo esc_url($synced_url); ?>" class="button button-primary">
                <?php esc_html_e('Manage Synced Patterns →', 'matthummel'); ?>
            </a>
        </p>

        <!-- Quick tips -->
        <hr style="margin:2.5rem 0 1.5rem;">
        <h2><?php esc_html_e('How to use patterns', 'matthummel'); ?></h2>
        <ol style="max-width:640px;line-height:1.8;">
            <li><?php esc_html_e('Open any page or post in the block editor.', 'matthummel'); ?></li>
            <li><?php esc_html_e('Click the blue + button (top-left or in the document body).', 'matthummel'); ?></li>
            <li><?php esc_html_e('Switch to the "Patterns" tab.', 'matthummel'); ?></li>
            <li><?php esc_html_e('Choose "Matthummel" from the category list.', 'matthummel'); ?></li>
            <li><?php esc_html_e('Click any pattern to insert it, then customise the content.', 'matthummel'); ?></li>
        </ol>

    </div>

    <script>
    (function () {
        document.querySelectorAll('.mh-copy-pattern').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var name = this.getAttribute('data-pattern');
                navigator.clipboard.writeText(name).then(function () {
                    btn.textContent = '✓ Copied!';
                    setTimeout(function () {
                        btn.textContent = '<?php echo esc_js(__('Copy pattern name', 'matthummel')); ?>';
                    }, 1800);
                });
            });
        });
    })();
    </script>
    <?php
}
