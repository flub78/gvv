<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Renders one dashboard_shortcuts card. Single source of truth for the card
 * markup — used both to merge a shortcut into an existing dashboard section
 * (render_dashboard_shortcut_cards(), called inline from bs_sub_dashboard.php)
 * and by the leftover-section fallback in welcome/_dashboard_shortcuts.php.
 */
if (!function_exists('render_dashboard_shortcut_card')) {
    function render_dashboard_shortcut_card(array $s) {
        $CI = &get_instance();
        $CI->lang->load('tableaux_de_bord');

        $title = ($s['title_key'] && $CI->lang->line($s['title_key']) !== false)
            ? $CI->lang->line($s['title_key'])
            : $s['title'];
        $description = ($s['description_key'] && $CI->lang->line($s['description_key']) !== false)
            ? $CI->lang->line($s['description_key'])
            : $s['description'];

        $is_external = (bool) preg_match('#^https?://#i', $s['url']);
        $href        = $is_external ? $s['url'] : site_url($s['url']);
        $icon_class  = $s['icon'] ?: 'fa-link';
        $color_class = $s['color'] ?: 'text-secondary';

        ob_start();
        ?>
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <div class="sub-card text-center">
            <i class="fas <?= html_escape($icon_class) ?> <?= html_escape($color_class) ?>"></i>
            <div class="card-title"><?= html_escape($title) ?></div>
            <?php if ($description): ?>
                <div class="card-text text-muted"><?= html_escape($description) ?></div>
            <?php endif; ?>
            <a href="<?= html_escape($href) ?>" class="btn btn-primary btn-sm"<?= $is_external ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= $CI->lang->line('db_btn_acceder') ?></a>
        </div>
    </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Echoes the cards queued under $section_title in $shortcuts_by_section (a
 * dashboard's shortcuts grouped by their 'section' field, built in
 * Welcome::section()) and removes them from the pool. Call right after the
 * last card of an existing dashboard section, inside any surrounding
 * if/endif — a no-op when nothing matches, so it's safe to sprinkle after
 * every named section in bs_sub_dashboard.php, including ones that don't
 * always render.
 */
if (!function_exists('render_dashboard_shortcut_cards')) {
    function render_dashboard_shortcut_cards(array &$shortcuts_by_section, $section_title) {
        $key = trim((string) $section_title);
        if ($key === '' || empty($shortcuts_by_section[$key])) {
            return;
        }
        foreach ($shortcuts_by_section[$key] as $s) {
            echo render_dashboard_shortcut_card($s);
        }
        unset($shortcuts_by_section[$key]);
    }
}
