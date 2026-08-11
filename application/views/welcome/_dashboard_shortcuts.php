<?php
/**
 * Leftover dashboard_shortcuts cards for a section of the welcome.php
 * dashboard — $shortcuts_by_section grouped by their 'section' field
 * (built in Welcome::section()), minus whatever bs_sub_dashboard.php already
 * merged into an existing section via render_dashboard_shortcut_cards().
 * Each remaining group gets its own titled block; the uncategorized group
 * (key '') keeps the historical "Raccourcis" heading.
 */
$this->lang->load('tableaux_de_bord');
$this->lang->load('shortcuts');
$this->load->helper('dashboard_shortcuts');

foreach ($shortcuts_by_section as $section_title => $group):
    if (empty($group)):
        continue;
    endif;
?>
<div class="col-12 mt-3">
    <h6 class="text-muted mb-2">
    <?php if ($section_title !== ''): ?>
        <?= html_escape($section_title) ?>
    <?php else: ?>
        <i class="fas fa-thumbtack"></i> <?= $this->lang->line('shortcuts_dashboard_title') ?>
    <?php endif; ?>
    </h6>
</div>

<?php foreach ($group as $s): ?>
    <?= render_dashboard_shortcut_card($s) ?>
<?php endforeach; ?>
<?php endforeach; ?>
