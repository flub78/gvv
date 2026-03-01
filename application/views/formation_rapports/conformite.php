<?php
/**
 * Vue : rapport de conformité – pilotes non conformes aux contraintes de périodicité.
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-exclamation-circle text-warning" aria-hidden="true"></i>
            <?= $this->lang->line('formation_rapports_conformite_title') ?>
        </h3>
        <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <?= $this->lang->line('formation_rapports_title') ?>
        </a>
    </div>

    <?php if (empty($rapport)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <?= $this->lang->line('formation_rapports_conformite_aucun_type') ?>
        </div>
    <?php else: ?>
        <?php foreach ($rapport as $item): ?>
            <?php
            $type          = $item['type'];
            $non_conformes = $item['non_conformes'];
            $nb_nc         = count($non_conformes);
            ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center
                            <?= $nb_nc > 0 ? 'bg-danger text-white' : 'bg-success text-white' ?>">
                    <span>
                        <i class="fas fa-<?= $nb_nc > 0 ? 'exclamation-triangle' : 'check-circle' ?> me-2" aria-hidden="true"></i>
                        <strong><?= htmlspecialchars($type['nom']) ?></strong>
                        &nbsp;—&nbsp;
                        <?= $this->lang->line('formation_rapports_conformite_periodicite') ?>
                        <?= sprintf($this->lang->line('formation_type_seance_periodicite_jours'), (int)$type['periodicite_max_jours']) ?>
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($nb_nc > 0): ?>
                            <span class="badge bg-light text-danger">
                                <?= $nb_nc ?> <?= $this->lang->line('formation_rapports_conformite_non_conformes') ?>
                            </span>
                            <a href="<?= controller_url($controller) ?>/export_conformite_csv/<?= $type['id'] ?>"
                               class="btn btn-sm btn-light">
                                <i class="fas fa-file-csv" aria-hidden="true"></i>
                                <?= $this->lang->line('formation_rapports_conformite_export_csv') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if ($nb_nc === 0): ?>
                        <p class="text-muted p-3 mb-0">
                            <i class="fas fa-check-circle text-success me-1" aria-hidden="true"></i>
                            <?= $this->lang->line('formation_rapports_conformite_aucun') ?>
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= $this->lang->line('formation_rapports_conformite_pilote') ?></th>
                                        <th class="text-center"><?= $this->lang->line('formation_rapports_conformite_derniere_seance') ?></th>
                                        <th class="text-center"><?= $this->lang->line('formation_rapports_conformite_jours_ecoules') ?></th>
                                        <th class="text-center"><?= $this->lang->line('formation_rapports_conformite_periodicite') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($non_conformes as $p): ?>
                                        <?php
                                        $jours     = $p['jours_ecoules'];
                                        $max_jours = (int) $p['periodicite_max_jours'];
                                        $overdue   = ($jours === null) || ((int)$jours > $max_jours);
                                        ?>
                                        <tr class="<?= $overdue ? 'table-danger' : '' ?>">
                                            <td>
                                                <i class="fas fa-user me-1" aria-hidden="true"></i>
                                                <?= htmlspecialchars(trim($p['mprenom'] . ' ' . $p['mnom'])) ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($p['derniere_seance'])): ?>
                                                    <?= date('d/m/Y', strtotime($p['derniere_seance'])) ?>
                                                <?php else: ?>
                                                    <em class="text-muted"><?= $this->lang->line('formation_rapports_conformite_jamais') ?></em>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($jours !== null): ?>
                                                    <span class="badge <?= (int)$jours > $max_jours ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                                        <?= $jours ?> j
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">
                                                        <?= $this->lang->line('formation_rapports_conformite_jamais') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?= sprintf($this->lang->line('formation_type_seance_periodicite_jours'), $max_jours) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
<?php $this->load->view('bs_footer'); ?>
