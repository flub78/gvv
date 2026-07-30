<!-- VIEW: application/views/bs_dashboard.php -->
<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Dashboard principal - Page d'accueil responsive
 * @package vues
 * @filesource bs_dashboard.php
 */

$this->load->view('bs_header');
$this->load->view('bs_menu', array('is_planchiste' => $is_planchiste, 'is_auto_planchiste' => $is_auto_planchiste));
$this->load->view('bs_banner');

$this->lang->load('welcome');
$this->lang->load('tableaux_de_bord');
$this->lang->load('motd');

$show_planeurs = empty($section) || !empty($section['gestion_planeurs']);
$show_avions   = empty($section) || !empty($section['gestion_avions']);
?>

<style>
.section-tile {
    border-radius: 8px;
    padding: 2rem 1rem;
    text-align: center;
    transition: all 0.2s ease;
    height: 100%;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: inherit;
    background: #fff;
    border: 2px solid #e0e0e0;
    border-left-width: 5px;
}

.section-tile:hover {
    transform: translateY(-4px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    text-decoration: none;
    color: inherit;
}

.section-tile.user       { border-left-color: #0d6efd; }
.section-tile.flights    { border-left-color: #198754; }
.section-tile.treasurer  { border-left-color: #ffc107; }
.section-tile.formation  { border-left-color: #0d6efd; }
.section-tile.maintenance{ border-left-color: #6c757d; }
.section-tile.admin      { border-left-color: #dc3545; }

.section-tile i {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
}

.section-tile .tile-title {
    font-size: 1rem;
    font-weight: 700;
}
</style>

<div id="body" class="body container-fluid py-3">

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-home text-primary"></i>
                <?= $this->lang->line("welcome_title") ?>
            </h2>
        </div>
    </div>

    <!-- Message du jour -->
    <?php if ($mod): ?>
    <div id="mod_dialog" style="display:none" title="<?= $this->lang->line("gvv_config_mod") ?>">
        <div class="markdown-content">
            <?= markdown($mod) ?>
        </div>
        <div class="mt-3">
            <label>
                <input type="checkbox" name="no_mod" value="0" id="no_mod" class="form-check-input" />
                <small class="text-muted ms-1"><?= $this->lang->line("gvv_no_more_mod") ?></small>
            </label>
            <input type="hidden" name="mod_title" value="<?= $this->lang->line("gvv_config_mod") ?>" />
        </div>
    </div>
    <?php endif; ?>

    <!-- Messages du jour (nouvelle section repliable) -->
    <?php if (!empty($motd_messages)): ?>
    <div class="card mb-4" id="motdSectionCard">
        <div class="card-header bg-primary text-white d-flex flex-wrap align-items-center gap-2"
             data-bs-toggle="collapse" data-bs-target="#motdSectionBody"
             style="cursor: pointer;" aria-expanded="<?= $motd_section_expanded ? 'true' : 'false' ?>">
            <h5 class="mb-0 flex-grow-1">
                <i class="fas fa-bullhorn" aria-hidden="true"></i>
                <?= $this->lang->line('motd_title') ?>
                <span class="badge bg-light text-dark ms-1"><?= count($motd_messages) ?></span>
            </h5>
            <select id="motdSortSelect" class="form-select form-select-sm me-2" style="width: auto;">
                <option value="priority" <?= $motd_sort_by === 'priority' ? 'selected' : '' ?>><?= $this->lang->line('motd_sort_priority') ?></option>
                <option value="date" <?= $motd_sort_by === 'date' ? 'selected' : '' ?>><?= $this->lang->line('motd_sort_date') ?></option>
            </select>
            <button type="button" id="motdHideAllBtn" class="btn btn-sm btn-light me-2">
                <i class="fas fa-eye-slash" aria-hidden="true"></i> <?= $this->lang->line('motd_action_hide_all') ?>
            </button>
            <i class="fas fa-chevron-down collapse-indicator" aria-hidden="true"></i>
        </div>
        <div id="motdSectionBody" class="collapse <?= $motd_section_expanded ? 'show' : '' ?>">
            <div class="card-body" style="max-height: 480px; overflow-y: auto;">
                <?php $this->load->view('motd/_message_accordion', array('motd_messages' => $motd_messages, 'is_admin' => $is_admin)); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="mb-2">
        <a href="<?= controller_url('motd/mine') ?>" class="btn btn-outline-primary btn-sm" id="motdMineLink">
            <i class="fas fa-inbox" aria-hidden="true"></i> <?= $this->lang->line('motd_my_messages_link') ?>
            <?php if (!empty($motd_unread_count)): ?>
            <span class="badge rounded-pill bg-danger" id="motdMineUnreadBadge"><?= $motd_unread_count ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Section tiles grid -->
    <div class="row g-3">

        <!-- Mon espace personnel (always visible) -->
        <div class="col-6 col-md-4 col-lg-3">
            <a class="section-tile user" href="<?= controller_url('welcome/section/user') ?>">
                <i class="fas fa-user text-primary"></i>
                <div class="tile-title"><?= $this->lang->line('db_section_personal') ?></div>
            </a>
        </div>

        <!-- Gestion des vols -->
        <?php if ($show_planeurs || $show_avions): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a class="section-tile flights" href="<?= controller_url('welcome/section/flights') ?>">
                <i class="fas fa-clipboard-list text-success"></i>
                <div class="tile-title"><?= $this->lang->line('db_section_flights') ?></div>
            </a>
        </div>
        <?php endif; ?>

        <!-- Trésorerie -->
        <?php if ($is_admin || $is_bureau || ($this->config->item('tresorers_can_access_others_sections') ? $is_treasurer : $is_treasurer_in_section)): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a class="section-tile treasurer" href="<?= controller_url('welcome/section/treasurer') ?>">
                <i class="fas fa-euro-sign text-warning"></i>
                <div class="tile-title"><?= $this->lang->line('db_section_treasury') ?></div>
            </a>
        </div>
        <?php endif; ?>

        <!-- Formation -->
        <?php if ($this->config->item('gestion_formations') && (isset($can_view_formation) ? $can_view_formation : ($is_ca || $is_admin || $is_instructeur))): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a class="section-tile formation" href="<?= controller_url('welcome/section/formation') ?>">
                <i class="fas fa-graduation-cap text-primary"></i>
                <div class="tile-title"><?= $this->lang->line('db_section_training') ?></div>
            </a>
        </div>
        <?php endif; ?>

        <!-- Maintenance -->
        <?php if ($is_mecano || $is_admin): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a class="section-tile maintenance" href="<?= controller_url('welcome/section/maintenance') ?>">
                <i class="fas fa-wrench text-secondary"></i>
                <div class="tile-title"><?= $this->lang->line('db_section_maintenance') ?></div>
            </a>
        </div>
        <?php endif; ?>

        <!-- Administration Club -->
        <?php if (isset($is_ca_any_section) ? $is_ca_any_section : $is_ca): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a class="section-tile admin" href="<?= controller_url('welcome/section/admin_club') ?>">
                <i class="fas fa-cogs text-danger"></i>
                <div class="tile-title"><?= $this->lang->line('db_section_admin_club') ?></div>
            </a>
        </div>
        <?php endif; ?>

        <!-- Administration Système -->
        <?php if ($is_admin || $is_backup_db): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a class="section-tile admin" href="<?= controller_url('welcome/section/admin_sys') ?>">
                <i class="fas fa-server text-danger"></i>
                <div class="tile-title"><?= $this->lang->line('db_section_admin_sys') ?></div>
            </a>
        </div>
        <?php endif; ?>

        <!-- Développement / Test -->
        <?php if (isset($is_dev_authorized) && $is_dev_authorized): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a class="section-tile admin" href="<?= controller_url('welcome/section/dev') ?>">
                <i class="fas fa-flask text-warning"></i>
                <div class="tile-title"><?= $this->lang->line('db_section_dev') ?></div>
            </a>
        </div>
        <?php endif; ?>

    </div>

</div>

<!-- CSS and JS for MOD dialog -->
<style>
.ui-dialog { max-width: 90vw !important; box-sizing: border-box; }
#mod_dialog { max-width: 100%; overflow-x: hidden; word-wrap: break-word; }
#mod_dialog img { display: block !important; max-width: 100% !important; height: auto !important; margin: 10px auto !important; visibility: visible !important; }
#mod_dialog a, #mod_dialog .markdown-content a { color: #007bff !important; text-decoration: underline !important; cursor: pointer !important; font-weight: 500 !important; }
#mod_dialog a:hover, #mod_dialog .markdown-content a:hover { color: #0056b3 !important; font-weight: 600 !important; }
.ui-dialog { z-index: 9999 !important; }
.ui-widget-overlay { z-index: 9998 !important; }
@media (max-width: 768px) {
    .ui-dialog { margin: 10px !important; top: 70px !important; max-height: calc(100vh - 80px) !important; }
    .ui-dialog .ui-dialog-content { padding: 10px !important; max-height: calc(100vh - 180px) !important; overflow-y: auto !important; }
    .ui-dialog .ui-dialog-buttonpane { padding: 5px 10px !important; }
    .ui-dialog .ui-dialog-titlebar { padding: 8px 10px !important; }
}
</style>

<?php if ($mod): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function handleDontShowAgain() {
        const noModCheckbox = document.getElementById('no_mod');
        if (noModCheckbox && noModCheckbox.checked) {
            fetch('<?= controller_url("welcome/set_cookie") ?>')
                .then(response => response.json())
                .catch(error => console.error('Error setting MOD cookie:', error));
        }
    }

    function getResponsiveWidth() {
        return Math.min(600, window.innerWidth * 0.9);
    }

    $('#mod_dialog').dialog({
        modal: true,
        width: getResponsiveWidth(),
        height: 'auto',
        resizable: true,
        draggable: true,
        closeOnEscape: true,
        buttons: {
            "OK": function() { handleDontShowAgain(); $(this).dialog('close'); }
        },
        close: function() { handleDontShowAgain(); },
        position: { my: "center", at: "center", of: window }
    });

    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if ($('#mod_dialog').dialog('isOpen')) {
                $('#mod_dialog').dialog('option', 'width', getResponsiveWidth());
                $('#mod_dialog').dialog('option', 'position', { my: "center", at: "center", of: window });
            }
        }, 250);
    });

    $('#mod_dialog').dialog('open');

    $('#mod_dialog a').each(function() {
        const href = $(this).attr('href');
        if (href && (href.startsWith('http://') || href.startsWith('https://'))) {
            $(this).attr('target', '_blank').attr('rel', 'noopener noreferrer');
        }
    });
});
</script>
<?php endif; ?>

<?php if (!empty($motd_messages)): ?>
<?= html_script(array('type' => "text/javascript", 'src' => js_url('motd'))) ?>
<script>
$(function() {
    motd_init_dashboard_section(
        '<?= controller_url('motd') ?>/toggle_section',
        '<?= controller_url('motd') ?>/set_sort'
    );
    motd_init_dashboard_actions({
        hideUrl: '<?= controller_url('motd') ?>/hide_message',
        hideAllUrl: '<?= controller_url('motd') ?>/hide_all',
        ackUrl: '<?= controller_url('motd') ?>/acknowledge_message',
        replyUrl: '<?= controller_url('motd') ?>/reply',
        confirmHideAll: <?= json_encode($this->lang->line('motd_confirm_hide_all')) ?>,
        errorFallback: <?= json_encode($this->lang->line('motd_error_action_failed')) ?>,
        ackBadgeLabel: <?= json_encode($this->lang->line('motd_acknowledged_badge')) ?>,
        repliesTitle: <?= json_encode($this->lang->line('motd_replies_title')) ?>
    });
});
</script>
<?php endif; ?>
