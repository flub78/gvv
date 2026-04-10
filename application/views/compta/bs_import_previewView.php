<!-- VIEW: application/views/compta/bs_import_previewView.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

<h3><?= $this->lang->line('gvv_import_preview_title') ?></h3>

<?= checkalert($this->session) ?>

<form method="post" action="<?= controller_url('compta/confirm_import') ?>">

<div class="mb-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-select-all">
        <?= $this->lang->line('gvv_import_select_all') ?>
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary ms-1" id="btn-deselect-all">
        <?= $this->lang->line('gvv_import_deselect_all') ?>
    </button>
</div>

<table class="table table-sm table-bordered table-hover">
    <thead class="table-light">
        <tr>
            <th style="width:36px"></th>
            <th><?= $this->lang->line('gvv_import_col_date') ?></th>
            <th><?= $this->lang->line('gvv_import_col_emploi') ?></th>
            <th><?= $this->lang->line('gvv_import_col_ressource') ?></th>
            <th class="text-end"><?= $this->lang->line('gvv_import_col_montant') ?></th>
            <th><?= $this->lang->line('gvv_import_col_description') ?></th>
            <th><?= $this->lang->line('gvv_import_col_status') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($validated as $idx => $row):
            $entry  = $row['entry'];
            $valid  = $row['valid'];
            $rowclass = $valid ? '' : 'table-danger';
        ?>
        <tr class="<?= $rowclass ?>">
            <td class="text-center">
                <?php if ($valid): ?>
                    <input type="checkbox" name="selected_indices[]"
                           value="<?= $idx ?>" checked class="form-check-input entry-cb">
                <?php else: ?>
                    <input type="checkbox" disabled class="form-check-input">
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars(date_db2ht($entry['date_op'])) ?></td>
            <td><?= htmlspecialchars($entry['compte1_codec'] . ' ' . $entry['compte1_nom']) ?></td>
            <td><?= htmlspecialchars($entry['compte2_codec'] . ' ' . $entry['compte2_nom']) ?></td>
            <td class="text-end"><?= euro($entry['montant'], ',', 'csv') ?></td>
            <td><?= htmlspecialchars($entry['description'] ?? '') ?></td>
            <td>
                <?php if ($valid): ?>
                    <span class="text-success"><i class="fas fa-check"></i></span>
                <?php else: ?>
                    <span class="text-danger">
                        <?php foreach ($row['errors'] as $err): ?>
                            <div><i class="fas fa-times"></i> <?= htmlspecialchars($err) ?></div>
                        <?php endforeach; ?>
                    </span>
                    <?php if (!empty($row['missing_accounts'])): ?>
                        <div class="mt-2">
                            <?php foreach ($row['missing_accounts'] as $missing):
                                $create_url = controller_url('compta/create_missing_compte') . '?' . http_build_query([
                                    'codec' => $missing['codec'] ?? '',
                                    'nom' => $missing['nom'] ?? '',
                                    'desc' => $missing['desc'] ?? '',
                                ]);
                            ?>
                                <a class="btn btn-sm btn-outline-warning me-1 mb-1" href="<?= htmlspecialchars($create_url) ?>">
                                    <i class="fas fa-plus-circle me-1"></i><?= $this->lang->line('gvv_import_create_missing_account') ?>
                                    (<?= htmlspecialchars(($missing['codec'] ?? '') . ' ' . ($missing['nom'] ?? '')) ?>)
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<button type="submit" class="btn btn-primary mt-2">
    <i class="fas fa-file-import me-1"></i><?= $this->lang->line('gvv_import_confirm') ?>
</button>
<a href="<?= controller_url('compta/import_ecritures') ?>" class="btn btn-secondary mt-2 ms-2">
    <i class="fas fa-arrow-left me-1"></i><?= $this->lang->line('gvv_str_cancel') ?>
</a>

</form>

</div>

<script>
document.getElementById('btn-select-all').addEventListener('click', function () {
    document.querySelectorAll('.entry-cb').forEach(function (cb) { cb.checked = true; });
});
document.getElementById('btn-deselect-all').addEventListener('click', function () {
    document.querySelectorAll('.entry-cb').forEach(function (cb) { cb.checked = false; });
});
</script>
