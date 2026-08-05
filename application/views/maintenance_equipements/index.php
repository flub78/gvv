<?php
/**
 * Vue : liste des equipements maintenables
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-cogs" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_equipements_title') ?>
        </h3>
        <a href="<?= controller_url($controller) ?>/create" class="btn btn-success">
            <i class="fas fa-plus" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_equipements_create') ?>
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
                        <th><?= $this->lang->line('maintenance_equipement_nom') ?></th>
                        <th><?= $this->lang->line('maintenance_equipement_aeronef') ?></th>
                        <th><?= $this->lang->line('maintenance_equipement_description') ?></th>
                        <th><?= $this->lang->line('maintenance_equipement_actif') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($equipements)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            <?= $this->lang->line('maintenance_equipements_aucun') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equipements as $equipement): ?>
                    <tr class="<?= $equipement['actif'] ? '' : 'text-muted' ?>">
                        <td><?= htmlspecialchars($equipement['nom']) ?></td>
                        <td>
                            <?= htmlspecialchars($equipement['aeronef_modele'] ?? '') ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars($equipement['aeronef_id']) ?></span>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($equipement['description'] ?? '') ?></td>
                        <td>
                            <?php if ($equipement['actif']): ?>
                                <span class="badge bg-success"><?= $this->lang->line('maintenance_equipement_actif') ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= $this->lang->line('maintenance_equipement_inactif') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="<?= controller_url($controller) ?>/edit/<?= $equipement['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="<?= $this->lang->line('maintenance_equipements_edit') ?>">
                                <i class="fas fa-edit" aria-hidden="true"></i>
                            </a>
                            <a href="<?= controller_url($controller) ?>/transfer/<?= $equipement['id'] ?>"
                               class="btn btn-sm btn-outline-info" title="<?= $this->lang->line('maintenance_equipements_transfer') ?>">
                                <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                            </a>
                            <?php if ($equipement['actif']): ?>
                            <a href="<?= controller_url($controller) ?>/deactivate/<?= $equipement['id'] ?>"
                               class="btn btn-sm btn-outline-warning"
                               title="<?= $this->lang->line('maintenance_equipements_deactivate') ?>"
                               onclick="return confirm('<?= $this->lang->line('maintenance_equipement_deactivate_confirm') ?>')">
                                <i class="fas fa-ban" aria-hidden="true"></i>
                            </a>
                            <?php else: ?>
                            <a href="<?= controller_url($controller) ?>/reactivate/<?= $equipement['id'] ?>"
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
