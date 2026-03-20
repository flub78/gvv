<!-- VIEW: application/views/rapprochements/bs_select_releve.php -->
<?php
//    GVV Gestion vol à voile
//    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
//    base restauration view

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('rapprochements', 'french');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_rapprochements_title", 3);
if (!empty($error)) {
	echo '<div class="alert alert-danger">' . $error . '</div>';
}

echo p($this->lang->line("gvv_rapprochements_explain"));
?>
<!-- Filtre -->


<?php

echo p($this->lang->line("gvv_of_select"));
?>
<form action="<?= site_url('rapprochements/import_releve') ?>" method="post" enctype="multipart/form-data" id="upload-form">
  <div id="drop-zone" class="border border-2 border-primary rounded p-5 text-center mb-3"
       style="cursor:pointer; transition: background .2s;">
    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-2"></i>
    <p class="mb-1 fw-bold"><?= $this->lang->line('gvv_rapprochements_drop_file') ?></p>
    <p class="text-muted small mb-2"><?= $this->lang->line('gvv_rapprochements_or_click') ?></p>
    <input type="file" name="userfile" id="userfile" class="d-none" accept=".csv,.txt,.ofx,.qif">
    <span id="file-name" class="text-muted small"></span>
  </div>
  <button type="submit" name="button" value="<?= $this->lang->line('gvv_button_validate') ?>" class="btn btn-primary" id="upload-btn" disabled>
    <i class="fas fa-upload me-1"></i><?= $this->lang->line('gvv_button_validate') ?>
  </button>
</form>

<script>
(function () {
  var zone    = document.getElementById('drop-zone');
  var input   = document.getElementById('userfile');
  var nameEl  = document.getElementById('file-name');
  var btn     = document.getElementById('upload-btn');

  function setFile(file) {
    var dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    nameEl.textContent = file.name;
    btn.disabled = false;
    zone.style.background = '#e8f4fd';
  }

  zone.addEventListener('click', function () { input.click(); });

  input.addEventListener('change', function () {
    if (input.files.length) setFile(input.files[0]);
  });

  zone.addEventListener('dragover', function (e) {
    e.preventDefault();
    zone.style.background = '#cce5ff';
  });

  zone.addEventListener('dragleave', function () {
    zone.style.background = '';
  });

  zone.addEventListener('drop', function (e) {
    e.preventDefault();
    zone.style.background = '#e8f4fd';
    var file = e.dataTransfer.files[0];
    if (file) setFile(file);
  });
}());
</script>
<?php

// ── Rapprochements existants ─────────────────────────────────────────────────
echo '<hr>';
echo '<h4>' . $this->lang->line('gvv_rapprochements_list_title') . '</h4>';
?>

<form method="post" action="<?= site_url('rapprochements/filter') ?>" class="row g-2 align-items-end mb-3">
  <input type="hidden" name="button" value="Filtrer">
  <input type="hidden" name="return_url" value="rapprochements/select_releve">
  <div class="col-auto">
    <label class="form-label"><?= $this->lang->line('gvv_filter_start_date') ?></label>
    <input type="date" name="startDate" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
  </div>
  <div class="col-auto">
    <label class="form-label"><?= $this->lang->line('gvv_filter_end_date') ?></label>
    <input type="date" name="endDate" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-secondary"><?= $this->lang->line('gvv_str_select') ?></button>
  </div>
</form>

<?php if (!empty($rapprochements)) : ?>
<form method="post" action="<?= site_url('rapprochements/delete_selected_rapprochements') ?>">
  <div class="mb-2 d-flex gap-2">
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="rapprSelectAll(true)"><?= $this->lang->line('gvv_rapprochements_select_all') ?></button>
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="rapprSelectAll(false)"><?= $this->lang->line('gvv_rapprochements_deselect_all') ?></button>
    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('<?= $this->lang->line('gvv_rapprochements_confirm_delete') ?>')"><?= $this->lang->line('gvv_rapprochements_delete_selected') ?></button>
  </div>
  <table id="rappr-table" class="datatable table table-striped table-sm table-hover">
    <thead>
      <tr>
        <th></th>
        <th><?= $this->lang->line('gvv_rapprochements_col_date') ?></th>
        <th><?= $this->lang->line('gvv_rapprochements_col_description') ?></th>
        <th><?= $this->lang->line('gvv_rapprochements_col_montant') ?></th>
        <th><?= $this->lang->line('gvv_sections_element') ?></th>
        <th><?= $this->lang->line('gvv_rapprochements_col_operation') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rapprochements as $row) : ?>
      <tr>
        <td><input type="checkbox" name="rapprochement_ids[]" value="<?= $row['id'] ?>" class="rappr-cb"></td>
        <td data-order="<?= $row['date_op'] ?>"><?= date_db2ht($row['date_op']) ?></td>
        <td><?= htmlspecialchars($row['description']) ?></td>
        <td data-order="<?= $row['montant'] ?>"><?= number_format($row['montant'], 2, ',', ' ') ?></td>
        <td><?= htmlspecialchars($row['nom_section']) ?></td>
        <td title="<?= htmlspecialchars($row['string_releve']) ?>"><?= htmlspecialchars(mb_strimwidth($row['string_releve'], 0, 60, '…')) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</form>
<?php else : ?>
<p class="text-muted"><?= $this->lang->line('gvv_rapprochements_no_results') ?></p>
<?php endif; ?>

<script>
function rapprSelectAll(checked) {
  // Couvre toutes les lignes DataTables (y compris les pages masquées)
  document.querySelectorAll('.rappr-cb').forEach(cb => cb.checked = checked);
}
</script>
<?php
echo '</div>';
