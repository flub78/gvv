<!-- VIEW: application/views/comptes/bs_decloture.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
echo '<div id="body" class="body container-fluid">';

$title = $this->lang->line("comptes_decloture_title");
if ($section) {
    $title .= " — section " . htmlspecialchars($section['nom']);
}
echo heading($title, 2, "");

// Message de succès
if ($this->session->flashdata('success')) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<i class="fas fa-check-circle me-2"></i>';
    echo nl2br(htmlspecialchars($this->session->flashdata('success')));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

if ($error) {
    echo '<div class="alert alert-warning">' . $error . '</div>';
    echo '</div>';
    return;
}

$freeze_only = !empty($freeze_only);
?>

<div class="card border-danger mb-4">
    <div class="card-header bg-danger text-white">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong><?= $this->lang->line("comptes_decloture_warning_title") ?></strong>
    </div>
    <div class="card-body">
        <dl class="row mb-3">
            <dt class="col-sm-3"><?= $this->lang->line("comptes_decloture_section") ?></dt>
            <dd class="col-sm-9"><?= htmlspecialchars($section['nom']) ?></dd>

            <dt class="col-sm-3"><?= $this->lang->line("comptes_decloture_freeze_date") ?></dt>
            <dd class="col-sm-9"><strong><?= htmlspecialchars($freeze_date) ?></strong></dd>

            <?php if ($cloture_description): ?>
            <dt class="col-sm-3"><?= $this->lang->line("comptes_decloture_description") ?></dt>
            <dd class="col-sm-9"><?= htmlspecialchars($cloture_description) ?></dd>
            <?php endif; ?>

            <dt class="col-sm-3"><?= $this->lang->line("comptes_decloture_entries_count") ?></dt>
            <dd class="col-sm-9">
                <?php if ($freeze_only): ?>
                    <?= $this->lang->line("comptes_decloture_entries_none") ?>
                <?php else: ?>
                    <?= count($ecritures) ?> écriture(s) à supprimer
                <?php endif; ?>
            </dd>
        </dl>

        <?php if (!$freeze_only): ?>
        <h5><?= $this->lang->line("comptes_decloture_entries_title") ?> <?= htmlspecialchars($year) ?></h5>
        <?php endif; ?>

        <?php if (!empty($ecritures)): ?>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th><?= $this->lang->line("comptes_decloture_col_date") ?></th>
                        <th><?= $this->lang->line("comptes_decloture_col_compte1") ?></th>
                        <th><?= $this->lang->line("comptes_decloture_col_compte2") ?></th>
                        <th class="text-end"><?= $this->lang->line("comptes_decloture_col_montant") ?></th>
                        <th><?= $this->lang->line("comptes_decloture_col_description") ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ecritures as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars(date_db2ht($e['date_op'])) ?></td>
                        <td><?= htmlspecialchars($e['compte1']) ?></td>
                        <td><?= htmlspecialchars($e['compte2']) ?></td>
                        <td class="text-end"><?= number_format((float)$e['montant'], 2, ',', ' ') ?></td>
                        <td><?= htmlspecialchars($e['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= $freeze_only
                ? $this->lang->line("comptes_decloture_warning_text_freeze_only")
                : $this->lang->line("comptes_decloture_warning_text") ?>
        </div>

        <?php echo form_open(controller_url($controller) . "/decloture"); ?>
        <div class="d-flex gap-2">
            <?php echo form_hidden('confirm_decloture', '1'); ?>
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-unlock me-1"></i>
                <?= $this->lang->line("comptes_decloture_btn_confirm") ?>
            </button>
            <a href="<?= controller_url('comptes/cloture') ?>" class="btn btn-secondary">
                <i class="fas fa-times me-1"></i>
                <?= $this->lang->line("comptes_decloture_btn_cancel") ?>
            </a>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>

<?php
echo '</div>';
?>
