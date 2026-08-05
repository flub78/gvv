<?php
/**
 * Vue : liste des programmes d'entretien
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-clipboard-list" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_programmes_title') ?>
        </h3>
        <a href="<?= controller_url($controller) ?>/create" class="btn btn-success">
            <i class="fas fa-plus" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_programmes_create') ?>
        </a>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <?= nl2br(htmlspecialchars($this->session->flashdata('success'))) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <?= nl2br(htmlspecialchars($this->session->flashdata('error'))) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th><?= $this->lang->line('maintenance_programme_code') ?></th>
                        <th><?= $this->lang->line('maintenance_programme_titre') ?></th>
                        <th><?= $this->lang->line('maintenance_programme_section') ?></th>
                        <th><?= $this->lang->line('maintenance_programme_regle') ?></th>
                        <th><?= $this->lang->line('maintenance_programme_structure') ?></th>
                        <th><?= $this->lang->line('maintenance_equipement_actif') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($programmes)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            <?= $this->lang->line('maintenance_programmes_aucun') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($programmes as $programme): ?>
                    <tr class="<?= $programme['statut'] === 'actif' ? '' : 'text-muted' ?>">
                        <td><code><?= htmlspecialchars($programme['code']) ?></code></td>
                        <td>
                            <a href="<?= controller_url($controller) ?>/view/<?= $programme['id'] ?>">
                                <?= htmlspecialchars($programme['titre']) ?>
                            </a>
                        </td>
                        <td>
                            <?= $programme['section_id']
                                ? htmlspecialchars($programme['section_nom'] ?? $programme['section_id'])
                                : '<span class="badge bg-info">' . $this->lang->line('maintenance_programme_section_globale') . '</span>' ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if ($programme['regle_butee_date']): ?>
                                <span class="badge bg-primary"><i class="fas fa-calendar" aria-hidden="true"></i> <?= $programme['periodicite_mois'] ?: '?' ?> <?= $this->lang->line('maintenance_programme_mois') ?></span>
                            <?php endif; ?>
                            <?php if ($programme['regle_butee_heures']): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half" aria-hidden="true"></i> <?= $programme['seuil_heures'] ?: '?' ?> h</span>
                            <?php endif; ?>
                            <?php if (!$programme['regle_butee_date'] && !$programme['regle_butee_heures']): ?>
                                <span class="text-muted small"><?= $this->lang->line('maintenance_programme_aucune_regle') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?= sprintf($this->lang->line('maintenance_programme_nb_structure'), $programme['nb_sections'], $programme['nb_taches']) ?>
                        </td>
                        <td>
                            <?php if ($programme['statut'] === 'actif'): ?>
                                <span class="badge bg-success"><?= $this->lang->line('maintenance_equipement_actif') ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= $this->lang->line('maintenance_equipement_inactif') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="<?= controller_url($controller) ?>/view/<?= $programme['id'] ?>"
                               class="btn btn-sm btn-outline-secondary" title="<?= $this->lang->line('maintenance_programmes_view') ?>">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </a>
                            <a href="<?= controller_url($controller) ?>/edit/<?= $programme['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="<?= $this->lang->line('maintenance_equipements_edit') ?>">
                                <i class="fas fa-edit" aria-hidden="true"></i>
                            </a>
                            <?php if ($programme['statut'] === 'actif'): ?>
                            <a href="<?= controller_url($controller) ?>/deactivate/<?= $programme['id'] ?>"
                               class="btn btn-sm btn-outline-warning" title="<?= $this->lang->line('maintenance_programmes_deactivate') ?>"
                               onclick="return confirm('<?= $this->lang->line('maintenance_programme_deactivate_confirm') ?>')">
                                <i class="fas fa-ban" aria-hidden="true"></i>
                            </a>
                            <?php else: ?>
                            <a href="<?= controller_url($controller) ?>/reactivate/<?= $programme['id'] ?>"
                               class="btn btn-sm btn-outline-success" title="<?= $this->lang->line('maintenance_equipements_reactivate') ?>">
                                <i class="fas fa-undo" aria-hidden="true"></i>
                            </a>
                            <?php endif; ?>
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
