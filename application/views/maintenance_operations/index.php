<?php
/**
 * Vue : liste des operations de maintenance recentes (tout dossier)
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-tools" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_operations_title') ?>
        </h3>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th><?= $this->lang->line('maintenance_operation_date') ?></th>
                        <th><?= $this->lang->line('maintenance_dossier_entite') ?></th>
                        <th><?= $this->lang->line('maintenance_dossier_programme') ?></th>
                        <th><?= $this->lang->line('maintenance_dossier_mecano') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($operations)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            <?= $this->lang->line('maintenance_operations_aucune') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($operations as $operation): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($operation['date_operation'])) ?></td>
                        <td>
                            <?= htmlspecialchars($operation['entite_label']) ?>
                            <?php if ($operation['mode_saisie'] === 'compte_rendu'): ?>
                                <span class="badge bg-info"><i class="fas fa-paperclip" aria-hidden="true"></i></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($operation['programme_code']) ?> - <?= htmlspecialchars($operation['programme_titre']) ?></td>
                        <td><?= htmlspecialchars($operation['mecano_prenom'] . ' ' . $operation['mecano_nom']) ?></td>
                        <td class="text-end">
                            <a href="<?= controller_url($controller) ?>/edit/<?= $operation['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit" aria-hidden="true"></i> <?= $this->lang->line('maintenance_equipements_edit') ?>
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
