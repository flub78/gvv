<?php
/**
 * Vue : liste des bulletins de service d'un aeronef
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$statut_badges = array(
    'a_traiter'      => 'bg-danger',
    'traite'         => 'bg-success',
    'non_applicable' => 'bg-secondary',
);
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-bell" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_bulletins_title') ?>
        </h3>
        <?php if ($machine_immat): ?>
        <a href="<?= controller_url($controller) ?>/upload_form/<?= htmlspecialchars($machine_immat) ?>" class="btn btn-success">
            <i class="fas fa-plus" aria-hidden="true"></i> <?= $this->lang->line('maintenance_bulletins_deposer') ?>
        </a>
        <?php endif; ?>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <?= htmlspecialchars($this->session->flashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <?= htmlspecialchars($this->session->flashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="<?= controller_url($controller) ?>" class="row g-2 align-items-end" id="aeronef-filter-form">
                <div class="col-md-4">
                    <label for="machine_immat_select" class="form-label"><?= $this->lang->line('maintenance_equipement_aeronef') ?></label>
                    <?= form_dropdown('machine_immat_select', $aeronef_selector, $machine_immat, 'class="form-select" id="machine_immat_select"') ?>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary" id="btn-voir-aeronef">
                        <i class="fas fa-search" aria-hidden="true"></i> <?= $this->lang->line('maintenance_programmes_view') ?>
                    </button>
                </div>
            </form>
            <script>
                document.getElementById('btn-voir-aeronef').addEventListener('click', function () {
                    var immat = document.getElementById('machine_immat_select').value;
                    if (immat) {
                        window.location.href = '<?= controller_url($controller) ?>/index/' + encodeURIComponent(immat);
                    }
                });
            </script>
        </div>
    </div>

    <?php if (!$machine_immat): ?>
        <p class="text-muted"><?= $this->lang->line('maintenance_bulletins_selectionner_aeronef') ?></p>
    <?php else: ?>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th><?= $this->lang->line('maintenance_bulletin_fichier') ?></th>
                        <th><?= $this->lang->line('maintenance_bulletin_depose_le') ?></th>
                        <th><?= $this->lang->line('maintenance_bulletin_statut') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($bulletins)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            <?= $this->lang->line('maintenance_bulletins_aucun') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bulletins as $bulletin): ?>
                    <tr>
                        <td>
                            <a href="<?= site_url('archived_documents/preview/' . $bulletin['id']) ?>" target="_blank">
                                <i class="fas fa-file-alt" aria-hidden="true"></i> <?= htmlspecialchars($bulletin['original_filename']) ?>
                            </a>
                            <?php if (!empty($bulletin['description'])): ?>
                                <div class="text-muted small"><?= htmlspecialchars($bulletin['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($bulletin['uploaded_at'])) ?></td>
                        <td>
                            <span class="badge <?= $statut_badges[$bulletin['statut']] ?? 'bg-secondary' ?>">
                                <?= htmlspecialchars($statuts[$bulletin['statut']] ?? $bulletin['statut']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?= form_open(controller_url($controller) . '/set_statut/' . $bulletin['id'], array('class' => 'd-inline-flex gap-1')) ?>
                            <?= form_dropdown('statut', $statuts, $bulletin['statut'], 'class="form-select form-select-sm"') ?>
                            <button type="submit" class="btn btn-sm btn-outline-primary"><?= $this->lang->line('maintenance_btn_enregistrer') ?></button>
                            <?= form_close() ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php $this->load->view('bs_footer'); ?>
