<?php
/**
 * Vue : detail d'un dossier d'entretien
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
            <?= htmlspecialchars($dossier['entite_label']) ?>
            <span class="badge <?= $statut_badges[$dossier['statut']] ?>"><?= $statut_labels[$dossier['statut']] ?></span>
        </h3>
        <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line('maintenance_btn_retour') ?>
        </a>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <?= htmlspecialchars($this->session->flashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3"><?= $this->lang->line('maintenance_dossier_programme') ?></dt>
                <dd class="col-sm-9">
                    <a href="<?= controller_url('maintenance_programmes') ?>/view/<?= $dossier['programme_id'] ?>">
                        <?= htmlspecialchars($dossier['programme_code']) ?> - <?= htmlspecialchars($dossier['programme_titre']) ?>
                    </a>
                </dd>

                <dt class="col-sm-3"><?= $this->lang->line('maintenance_dossier_date_ouverture') ?></dt>
                <dd class="col-sm-9"><?= date('d/m/Y', strtotime($dossier['date_ouverture'])) ?></dd>

                <?php if (!empty($dossier['date_suspension'])): ?>
                <dt class="col-sm-3"><?= $this->lang->line('maintenance_dossier_date_suspension') ?></dt>
                <dd class="col-sm-9"><?= date('d/m/Y', strtotime($dossier['date_suspension'])) ?></dd>
                <?php endif; ?>

                <?php if (!empty($dossier['date_cloture'])): ?>
                <dt class="col-sm-3"><?= $this->lang->line('maintenance_dossier_date_cloture') ?></dt>
                <dd class="col-sm-9"><?= date('d/m/Y', strtotime($dossier['date_cloture'])) ?></dd>
                <?php endif; ?>

                <?php if (!empty($dossier['mecano_nom'])): ?>
                <dt class="col-sm-3"><?= $this->lang->line('maintenance_dossier_mecano') ?></dt>
                <dd class="col-sm-9"><?= htmlspecialchars($dossier['mecano_prenom'] . ' ' . $dossier['mecano_nom']) ?></dd>
                <?php endif; ?>

                <?php if (!empty($dossier['commentaire'])): ?>
                <dt class="col-sm-3"><?= $this->lang->line('maintenance_equipement_description') ?></dt>
                <dd class="col-sm-9"><?= nl2br(htmlspecialchars($dossier['commentaire'])) ?></dd>
                <?php endif; ?>
            </dl>
        </div>
        <div class="card-footer text-end">
            <?php if ($dossier['statut'] === 'ouvert'): ?>
                <a href="<?= controller_url($controller) ?>/suspend/<?= $dossier['id'] ?>" class="btn btn-outline-warning"
                   onclick="return confirm('<?= $this->lang->line('maintenance_dossier_suspend_confirm') ?>')">
                    <i class="fas fa-pause" aria-hidden="true"></i> <?= $this->lang->line('maintenance_dossier_suspendre') ?>
                </a>
                <a href="<?= controller_url($controller) ?>/close/<?= $dossier['id'] ?>" class="btn btn-outline-secondary"
                   onclick="return confirm('<?= $this->lang->line('maintenance_dossier_close_confirm') ?>')">
                    <i class="fas fa-check" aria-hidden="true"></i> <?= $this->lang->line('maintenance_dossier_cloturer') ?>
                </a>
                <a href="<?= controller_url($controller) ?>/abandon/<?= $dossier['id'] ?>" class="btn btn-outline-danger"
                   onclick="return confirm('<?= $this->lang->line('maintenance_dossier_abandon_confirm') ?>')">
                    <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line('maintenance_dossier_abandonner') ?>
                </a>
            <?php elseif ($dossier['statut'] === 'suspendu'): ?>
                <a href="<?= controller_url($controller) ?>/reactivate/<?= $dossier['id'] ?>" class="btn btn-outline-success">
                    <i class="fas fa-play" aria-hidden="true"></i> <?= $this->lang->line('maintenance_dossier_reactiver') ?>
                </a>
                <a href="<?= controller_url($controller) ?>/abandon/<?= $dossier['id'] ?>" class="btn btn-outline-danger"
                   onclick="return confirm('<?= $this->lang->line('maintenance_dossier_abandon_confirm') ?>')">
                    <i class="fas fa-times" aria-hidden="true"></i> <?= $this->lang->line('maintenance_dossier_abandonner') ?>
                </a>
            <?php else: ?>
                <span class="text-muted"><?= $this->lang->line('maintenance_dossier_termine') ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <?= $this->lang->line('maintenance_dossier_operations') ?>
            <a href="<?= controller_url('maintenance_operations') ?>/create/<?= $dossier['id'] ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-plus" aria-hidden="true"></i> <?= $this->lang->line('maintenance_operation_create') ?>
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($operations)): ?>
                <p class="text-muted mb-0"><?= $this->lang->line('maintenance_dossier_aucune_operation') ?></p>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($operations as $operation): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <?= date('d/m/Y', strtotime($operation['date_operation'])) ?>
                                — <?= htmlspecialchars($operation['mecano_nom'] . ' ' . $operation['mecano_prenom']) ?>
                                <?php if ($operation['mode_saisie'] === 'compte_rendu'): ?>
                                    <span class="badge bg-info"><i class="fas fa-paperclip" aria-hidden="true"></i> <?= $this->lang->line('maintenance_operation_compte_rendu') ?></span>
                                <?php endif; ?>
                            </span>
                            <a href="<?= controller_url('maintenance_operations') ?>/edit/<?= $operation['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit" aria-hidden="true"></i> <?= $this->lang->line('maintenance_equipements_edit') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php $this->load->view('bs_footer'); ?>
