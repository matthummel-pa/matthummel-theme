<?php

/**
 * Menu item icons + "mega" columns dropdown, both driven by CSS classes you add
 * in Appearance -> Menus (the item's "CSS Classes" field):
 *   - mh-ic-<icon>   e.g. "mh-ic-si-github" or "mh-ic-heroicon-o-rocket" prepends that Blade icon.
 *   - mh-mega        makes that item's submenu a wide, multi-column "mega" panel.
 */

namespace App;

add_filter('nav_menu_item_title', function ($title, $item) {
    if (empty($item->classes) || ! is_array($item->classes)) {
        return $title;
    }
    foreach ($item->classes as $cls) {
        if (strpos($cls, 'mh-ic-') === 0) {
            $name = substr($cls, 6);
            $svg  = mh_icon($name, 'mh-menu-ic');
            if ($svg) {
                return '<span class="mh-menu-ic-wrap">' . $svg . '</span>' . $title;
            }
        }
    }
    return $title;
}, 10, 2);

add_action('mh_head_end', function () {
    echo "\n<style id=\"mh-menu-icons\">"
        . '.mh-menu-ic-wrap{display:inline-flex;vertical-align:-2px;margin-right:7px;}'
        . '.mh-menu-ic-wrap svg{width:16px;height:16px;fill:currentColor;}'
        . '.nav li.mh-mega{position:static;}'
        . '.nav li.mh-mega > .sub-menu{display:flex;flex-wrap:wrap;gap:6px 32px;min-width:min(680px,90vw);padding:20px 24px;}'
        . '.nav li.mh-mega > .sub-menu > li{flex:0 0 auto;}'
        . "</style>\n";
}, 16);
