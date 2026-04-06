<!-- VIEW: application/views/vols_decouverte/bs_tableView.php -->
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
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Vue table pour les vols de découverte avec filtres
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('vols_decouverte');
$this->lang->load('briefing_passager');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_vols_decouverte_title", 3);
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// Display flash message if exists
if ($this->session->flashdata('message')) {
    echo $this->session->flashdata('message');
}

// Display filter error if exists
if (isset($filter_error)) {
    echo '<div class="alert alert-danger">' . $filter_error . '</div>';
}

// Year selector
echo '<div class="mb-3">';
if (isset($year_selector) && isset($year) && isset($controller)) {
    echo year_selector($controller, $year, $year_selector);
}
echo '</div>';

?>

<!-- Filter accordion -->
<div class="accordion accordion-flush collapsed mb-3" id="accordionPanelsStayOpenExample">
    <div class="accordion-item">
        <h2 class="accordion-header" id="panelsStayOpen-headingOne">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                <?= $this->lang->line("gvv_str_filter") ?>
            </button>
        </h2>
        <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse <?= isset($filter_active) && $filter_active ? 'show' : '' ?>" aria-labelledby="panelsStayOpen-headingOne">
            <div class="accordion-body">
                <div>
                    <form action="<?= controller_url($controller) . "/filter" ?>" method="post" accept-charset="utf-8" name="saisie">
                        <input type="hidden" name="return_url" value="<?= current_url() ?>" />
                        
                        <div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="startDate" class="form-label">Date début</label>
                                    <input type="date" class="form-control" id="startDate" name="startDate" value="<?= isset($startDate) ? $startDate : '' ?>">
                                </div>
                                <div class="col">
                                    <label for="endDate" class="form-label">Date fin</label>
                                    <input type="date" class="form-control" id="endDate" name="endDate" value="<?= isset($endDate) ? $endDate : '' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Afficher</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_all" value="all" <?= (!isset($filter_type) || $filter_type == 'all') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_all"><?= $this->lang->line("gvv_vols_decouverte_filter_all") ?></label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_done" value="done" <?= (isset($filter_type) && $filter_type == 'done') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_done"><?= $this->lang->line("gvv_vols_decouverte_filter_done") ?></label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_todo" value="todo" <?= (isset($filter_type) && $filter_type == 'todo') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_todo"><?= $this->lang->line("gvv_vols_decouverte_filter_todo") ?></label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_cancelled" value="cancelled" <?= (isset($filter_type) && $filter_type == 'cancelled') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_cancelled"><?= $this->lang->line("gvv_vols_decouverte_filter_cancelled") ?></label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_expired" value="expired" <?= (isset($filter_type) && $filter_type == 'expired') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_expired"><?= $this->lang->line("gvv_vols_decouverte_filter_expired") ?></label>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="mb-2 mt-2">
                                <?= filter_buttons() ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

$is_pilot_only = !$has_modification_rights && (isset($has_pilot_rights) && $has_pilot_rights);
if ($has_modification_rights) {
    $table_actions = array('edit', 'delete', 'print_vd', 'email_vd', 'action', 'briefing_vd');
} elseif ($is_pilot_only) {
    $table_actions = array('print_vd', 'email_vd', 'action', 'briefing_vd');
} else {
    $table_actions = array();
}
$attrs = array(
    'controller' => $controller,
    'actions' => $table_actions,
    'fields' => array('id', 'validite', 'product', 'beneficiaire', 'urgence', 'date_vol',  'pilote', 'airplane_immat', 'cancelled', 'paiement', 'participation'),
    'mode' => ($has_modification_rights || $is_pilot_only) ? "rw" : "ro",
    'class' => "datatable table table-striped"
);

// Create button above the table
if ($has_modification_rights) {
    $btn_bar = '<div class="d-flex gap-2 mb-3">';
    $btn_bar .= '<a href="' . site_url('vols_decouverte/create') . '" class="btn btn-sm btn-success">'
        . '<i class="fas fa-plus" aria-hidden="true"></i> '
        . $this->lang->line('gvv_button_create')
        . '</a>';

    if (!empty($vd_par_cb_enabled)) {
        $btn_bar .= ' <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#shareModal">'
            . '<i class="fas fa-share-alt me-1"></i>'
            . $this->lang->line('gvv_vd_share_link_btn')
            . '</button>';
    }

    $btn_bar .= '</div>';
    echo $btn_bar;
}

echo $this->gvvmetadata->table("vue_vols_decouverte", $attrs, "");


if ($has_modification_rights) {
    $bar = array(
        array('label' => "Excel", 'url' => "$controller/export/csv"),
        array('label' => "Pdf",   'url' => "$controller/export/pdf", 'target' => '_blank'),
    );
    echo button_bar4($bar);
}

echo '</div>';

?>

<?php if (!empty($vd_par_cb_enabled)): ?>
<!-- Modale de partage de la page publique VD -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="shareModalLabel">
          <i class="fas fa-share-alt me-2"></i><?= $this->lang->line('gvv_vd_share_link_btn') ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <?php
        $public_url = site_url('vols_decouverte/public_vd?section=' . (int) $current_section_id);
        $qr_url     = site_url('vols_decouverte/qrcode/' . (int) $current_section_id);
        ?>

        <!-- URL publique -->
        <div class="mb-3">
          <label class="form-label fw-semibold"><?= $this->lang->line('gvv_vd_public_title') ?></label>
          <div class="input-group">
            <input type="text" id="share-url" class="form-control font-monospace small"
                   value="<?= htmlspecialchars($public_url) ?>" readonly>
            <button type="button" class="btn btn-outline-secondary" onclick="copyShareUrl()" title="Copier">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        </div>

        <!-- QR Code -->
        <div class="mb-3">
          <a href="<?= $qr_url ?>" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="fas fa-qrcode me-1"></i><?= $this->lang->line('gvv_vols_decouverte_field_qr_code') ?>
          </a>
        </div>

        <hr>

        <!-- Envoi par email -->
        <?= form_open('vols_decouverte/send_public_link', array('id' => 'share-form')) ?>
        <input type="hidden" name="section_id" value="<?= (int) $current_section_id ?>">
        <div class="mb-3">
          <label class="form-label" for="share-email">
            <?= $this->lang->line('gvv_vd_share_link_email_label') ?>
          </label>
          <input type="email" name="to" id="share-email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="share-custom-msg">
            <?= $this->lang->line('gvv_vd_share_link_custom_msg_label') ?>
          </label>
          <textarea name="custom_message" id="share-custom-msg" class="form-control" rows="3"
                    placeholder="<?= htmlspecialchars($this->lang->line('gvv_vd_share_link_custom_msg_placeholder'), ENT_QUOTES, 'UTF-8') ?>"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fas fa-paper-plane me-1"></i><?= $this->lang->line('gvv_vd_share_link_send_btn') ?>
        </button>
        <?= form_close() ?>

      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function new_year() {
    var year = document.getElementById('year_selector').value;
    var url = document.querySelector('input[name="controller_url"]').value + '/page/' + year;
    window.location.href = url;
}

function copyShareUrl() {
    var el = document.getElementById('share-url');
    el.select();
    document.execCommand('copy');
    var btn = el.nextElementSibling;
    btn.innerHTML = '<i class="fas fa-check text-success"></i>';
    setTimeout(function() { btn.innerHTML = '<i class="fas fa-copy"></i>'; }, 2000);
}
</script>
