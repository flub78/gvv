<?php
/**
 * Vue : liste des dossiers d'entretien (historique)
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$statut_badges = array(
    'ouvert'    => 'bg-success',
    'suspendu'  => 'bg-warning text-dark',
    'cloture'   => 'bg-secondary',
    'abandonne' => 'bg-danger',
);
$statut_labels = array(
    'ouvert'    => $this->lang->line('maintenance_dossier_statut_ouvert'),
    'suspendu'  => $this->lang->line('maintenance_dossier_statut_suspendu'),
    'cloture'   => $this->lang->line('maintenance_dossier_statut_cloture'),
    'abandonne' => $this->lang->line('maintenance_dossier_statut_abandonne'),
);
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-folder-open" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_dossiers_title') ?>
            <?php if ($entite_type && $entite_id): ?>
                <small class="text-muted fs-6">— <?= htmlspecialchars($this->maintenance_dossier_model->entite_label($entite_type, $entite_id)) ?></small>
            <?php endif; ?>
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>/ouvrir_form/aeronef" class="btn btn-success">
                <i class="fas fa-plus" aria-hidden="true"></i> <?= $this->lang->line('maintenance_dossier_ouvrir_aeronef') ?>
            </a>
            <a href="<?= controller_url($controller) ?>/ouvrir_form/equipement" class="btn btn-success">
                <i class="fas fa-plus" aria-hidden="true"></i> <?= $this->lang->line('maintenance_dossier_ouvrir_equipement') ?>
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <?= htmlspecialchars($this->session->flashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th><?= $this->lang->line('maintenance_dossier_entite') ?></th>
                        <th><?= $this->lang->line('maintenance_dossier_programme') ?></th>
                        <th><?= $this->lang->line('maintenance_dossier_date_ouverture') ?></th>
                        <th><?= $this->lang->line('maintenance_equipement_actif') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($dossiers)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            <?= $this->lang->line('maintenance_dossiers_aucun') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dossiers as $dossier): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($dossier['entite_label']) ?>
                            <span class="badge bg-info"><?= $dossier['entite_type'] === 'aeronef' ? $this->lang->line('maintenance_dossier_type_aeronef') : $this->lang->line('maintenance_dossier_type_equipement') ?></span>
                        </td>
                        <td><?= htmlspecialchars($dossier['programme_code']) ?> - <?= htmlspecialchars($dossier['programme_titre']) ?></td>
                        <td><?= date('d/m/Y', strtotime($dossier['date_ouverture'])) ?></td>
                        <td><span class="badge <?= $statut_badges[$dossier['statut']] ?>"><?= $statut_labels[$dossier['statut']] ?></span></td>
                        <td class="text-end">
                            <a href="<?= controller_url($controller) ?>/view/<?= $dossier['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye" aria-hidden="true"></i> <?= $this->lang->line('maintenance_programmes_view') ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<?php $this->load->view('bs_footer'); ?>
