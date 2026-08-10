<?php
/**
 * Cartes de raccourcis dynamiques (dashboard_shortcuts) pour une section
 * du dashboard welcome.php. $shortcuts est déjà filtré (actif, club, rôle)
 * et trié (section, sort_order) par Dashboard_shortcuts_model::get_for_dashboard().
 */
$this->lang->load('tableaux_de_bord');
$this->lang->load('shortcuts');
?>
<div class="col-12 mt-3">
    <h6 class="text-muted mb-2"><i class="fas fa-thumbtack"></i> <?= $this->lang->line('shortcuts_dashboard_title') ?></h6>
</div>

<?php
$current_section = null;
foreach ($shortcuts as $s):
    $title = ($s['title_key'] && $this->lang->line($s['title_key']) !== false)
        ? $this->lang->line($s['title_key'])
        : $s['title'];
    $description = ($s['description_key'] && $this->lang->line($s['description_key']) !== false)
        ? $this->lang->line($s['description_key'])
        : $s['description'];

    $is_external = (bool) preg_match('#^https?://#i', $s['url']);
    $href = $is_external ? $s['url'] : site_url($s['url']);
    $icon_class = $s['icon'] ?: 'fa-link';
    $color_class = $s['color'] ?: 'text-secondary';

    if ($s['section'] !== $current_section):
        $current_section = $s['section'];
        if ($current_section):
?>
        <div class="col-12 mt-2">
            <h6 class="text-muted mb-2"><?= html_escape($current_section) ?></h6>
        </div>
<?php
        endif;
    endif;
?>
    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <div class="sub-card text-center">
            <i class="fas <?= html_escape($icon_class) ?> <?= html_escape($color_class) ?>"></i>
            <div class="card-title"><?= html_escape($title) ?></div>
            <?php if ($description): ?>
                <div class="card-text text-muted"><?= html_escape($description) ?></div>
            <?php endif; ?>
            <a href="<?= html_escape($href) ?>" class="btn btn-primary btn-sm"<?= $is_external ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= $this->lang->line('db_btn_acceder') ?></a>
        </div>
    </div>
<?php endforeach; ?>
