<?php
/**
 * Vue : liste des types de séances de formation
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-tags" aria-hidden="true"></i>
            <?= $this->lang->line('formation_types_seances_title') ?>
        </h3>
        <a href="<?= controller_url($controller) ?>/create" class="btn btn-success">
            <i class="fas fa-plus" aria-hidden="true"></i>
            <?= $this->lang->line('formation_types_seances_create') ?>
        </a>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <?= $this->session->flashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <?= $this->session->flashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th><?= $this->lang->line('formation_type_seance_nom') ?></th>
                        <th><?= $this->lang->line('formation_type_seance_nature') ?></th>
                        <th><?= $this->lang->line('formation_type_seance_periodicite') ?></th>
                        <th><?= $this->lang->line('formation_type_seance_description') ?></th>
                        <th><?= $this->lang->line('formation_type_seance_actif') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($types)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            Aucun type de séance défini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($types as $type): ?>
                    <tr class="<?= $type['actif'] ? '' : 'text-muted' ?>">
                        <td>
                            <?= htmlspecialchars($type['nom']) ?>
                        </td>
                        <td>
                            <?php if ($type['nature'] === 'vol'): ?>
                                <span class="badge bg-primary">
                                    <i class="fas fa-plane" aria-hidden="true"></i>
                                    <?= $this->lang->line('formation_nature_vol') ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-chalkboard" aria-hidden="true"></i>
                                    <?= $this->lang->line('formation_nature_theorique') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($type['periodicite_max_jours'])): ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-calendar-check" aria-hidden="true"></i>
                                    <?= sprintf($this->lang->line('formation_type_seance_periodicite_jours'), $type['periodicite_max_jours']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small"><?= $this->lang->line('formation_type_seance_no_periodicite') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?= htmlspecialchars($type['description'] ?? '') ?>
                        </td>
                        <td>
                            <?php if ($type['actif']): ?>
                                <span class="badge bg-success"><?= $this->lang->line('formation_type_seance_actif') ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="<?= controller_url($controller) ?>/edit/<?= $type['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="<?= $this->lang->line('formation_types_seances_edit') ?>">
                                <i class="fas fa-edit" aria-hidden="true"></i>
                            </a>
                            <?php if ($type['actif']): ?>
                            <a href="<?= controller_url($controller) ?>/deactivate/<?= $type['id'] ?>"
                               class="btn btn-sm btn-outline-warning"
                               title="<?= $this->lang->line('formation_types_seances_deactivate') ?>"
                               onclick="return confirm('Désactiver ce type de séance ?')">
                                <i class="fas fa-ban" aria-hidden="true"></i>
                            </a>
                            <?php endif; ?>
                            <a href="<?= controller_url($controller) ?>/delete/<?= $type['id'] ?>"
                               class="btn btn-sm btn-outline-danger" title="<?= $this->lang->line('formation_types_seances_delete') ?>"
                               onclick="return confirm('Supprimer ce type de séance ? Cette action est irréversible.')">
                                <i class="fas fa-trash" aria-hidden="true"></i>
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
