<!-- VIEW: application/views/document_types/bs_tableView.php -->
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
 * Vue table pour les types de documents
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('document_types');

echo '<div id="body" class="body container-fluid">';

echo heading("document_types_title", 3);
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array('code', 'name', 'section_name', 'scope', 'required', 'has_expiration', 'alert_days_before', 'active', 'display_order'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "datatable table table-striped"
);

// Filter form
$filter_scope      = isset($filter_scope)      ? $filter_scope      : '';
$filter_section_id = isset($filter_section_id) ? $filter_section_id : '';
?>
<form method="get" class="mb-3">
    <input type="hidden" name="filter_submitted" value="1">
    <div class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label for="scope" class="form-label"><?= $this->lang->line('document_types_scope') ?></label>
            <?= form_dropdown('scope', isset($scope_selector) ? $scope_selector : array(), $filter_scope, 'class="form-select" id="scope"') ?>
        </div>
        <div class="col-sm-3">
            <label for="section_id" class="form-label"><?= $this->lang->line('document_types_section') ?></label>
            <?= form_dropdown('section_id', isset($section_selector) ? $section_selector : array(), $filter_section_id, 'class="form-select" id="section_id"') ?>
        </div>
        <div class="col-sm-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary" title="<?= $this->lang->line('document_types_filter_apply') ?>">
                <i class="fas fa-filter"></i>
            </button>
            <button type="button" id="clear-filters" class="btn btn-outline-secondary" title="<?= $this->lang->line('document_types_filter_clear') ?>">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</form>
<script>
document.getElementById('clear-filters').addEventListener('click', function() {
    ['scope', 'section_id'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });
    try {
        if (typeof $ !== 'undefined' && $.fn.dataTable) {
            $('.datatable').dataTable().fnFilter('');
        }
    } catch(e) {}
    document.querySelector('form input[name="filter_submitted"]').closest('form').submit();
});
</script>
<?php

// Create button above the table
echo '<div class="mb-3">'
    . '<a href="' . site_url('document_types/create') . '" class="btn btn-sm btn-success">'
    . '<i class="fas fa-plus" aria-hidden="true"></i> '
    . $this->lang->line('gvv_button_create')
    . '</a> '
    . '<a href="' . site_url('archived_documents/page') . '" class="btn btn-sm btn-outline-secondary ms-2">'
    . '<i class="fas fa-archive" aria-hidden="true"></i> '
    . $this->lang->line('document_types_manage_documents')
    . '</a>'
    . '</div>';

echo $this->gvvmetadata->table("vue_document_types", $attrs, "");

echo '</div>';
