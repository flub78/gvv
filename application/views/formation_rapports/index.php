<!-- VIEW: application/views/formation_rapports/index.php -->
<?php
/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 * Rapports de formation - Vue synthétique par année
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
            <i class="fas fa-chart-bar" aria-hidden="true"></i>
            <?= $this->lang->line("formation_rapports_title") ?>
        </h3>
        <div class="d-flex align-items-center gap-3">
            <?= year_selector('formation_rapports', $year, $year_selector) ?>
        </div>
    </div>

    <script>
    function new_year() {
        var year = document.getElementById('year_selector').value;
        var url = document.querySelector('input[name="controller_url"]').value + '/new_year/' + year;
        window.location.href = url;
    }
    </script>

    <!-- ============================================ -->
    <!-- SECTION: FORMATIONS                          -->
    <!-- ============================================ -->

    <div class="accordion mb-4" id="rapportFormationsAccordion">

        <!-- 1. Formations clôturées avec succès -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingCloturees">
                <button class="accordion-button collapsed py-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseCloturees"
                        aria-expanded="false" aria-controls="collapseCloturees">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <?= $this->lang->line("formation_rapports_cloturees_succes") ?>
                    <span class="badge bg-success ms-2"><?= count($formations['cloturees']) ?></span>
                </button>
            </h2>
            <div id="collapseCloturees" class="accordion-collapse collapse" aria-labelledby="headingCloturees">
                <div class="accordion-body p-2">
                    <?php if (empty($formations['cloturees'])): ?>
                        <p class="text-muted mb-0"><?= $this->lang->line("formation_rapports_aucune") ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= $this->lang->line("formation_inscription_pilote") ?></th>
                                        <th><?= $this->lang->line("formation_inscription_programme") ?></th>
                                        <th><?= $this->lang->line("formation_inscription_instructeur") ?></th>
                                        <th><?= $this->lang->line("formation_rapports_date_cloture") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($formations['cloturees'] as $f): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= controller_url('formation_inscriptions') ?>/detail/<?= $f['id'] ?>">
                                                    <?= htmlspecialchars(($f['pilote_prenom'] ?? '') . ' ' . ($f['pilote_nom'] ?? '')) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($f['programme_titre'] ?? '') ?></td>
                                            <td><?= htmlspecialchars(($f['instructeur_prenom'] ?? '') . ' ' . ($f['instructeur_nom'] ?? '')) ?></td>
                                            <td><?= !empty($f['date_cloture']) ? date('d/m/Y', strtotime($f['date_cloture'])) : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2. Formations abandonnées -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingAbandonnees">
                <button class="accordion-button collapsed py-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseAbandonnees"
                        aria-expanded="false" aria-controls="collapseAbandonnees">
                    <i class="fas fa-times-circle text-danger me-2"></i>
                    <?= $this->lang->line("formation_rapports_abandonnees") ?>
                    <span class="badge bg-danger ms-2"><?= count($formations['abandonnees']) ?></span>
                </button>
            </h2>
            <div id="collapseAbandonnees" class="accordion-collapse collapse" aria-labelledby="headingAbandonnees">
                <div class="accordion-body p-2">
                    <?php if (empty($formations['abandonnees'])): ?>
                        <p class="text-muted mb-0"><?= $this->lang->line("formation_rapports_aucune") ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= $this->lang->line("formation_inscription_pilote") ?></th>
                                        <th><?= $this->lang->line("formation_inscription_programme") ?></th>
                                        <th><?= $this->lang->line("formation_rapports_date_cloture") ?></th>
                                        <th><?= $this->lang->line("formation_rapports_motif") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($formations['abandonnees'] as $f): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= controller_url('formation_inscriptions') ?>/detail/<?= $f['id'] ?>">
                                                    <?= htmlspecialchars(($f['pilote_prenom'] ?? '') . ' ' . ($f['pilote_nom'] ?? '')) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($f['programme_titre'] ?? '') ?></td>
                                            <td><?= !empty($f['date_cloture']) ? date('d/m/Y', strtotime($f['date_cloture'])) : '-' ?></td>
                                            <td><?= htmlspecialchars($f['motif_cloture'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 3. Formations suspendues -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingSuspendues">
                <button class="accordion-button collapsed py-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseSuspendues"
                        aria-expanded="false" aria-controls="collapseSuspendues">
                    <i class="fas fa-pause-circle text-warning me-2"></i>
                    <?= $this->lang->line("formation_rapports_suspendues") ?>
                    <span class="badge bg-warning ms-2"><?= count($formations['suspendues']) ?></span>
                </button>
            </h2>
            <div id="collapseSuspendues" class="accordion-collapse collapse" aria-labelledby="headingSuspendues">
                <div class="accordion-body p-2">
                    <?php if (empty($formations['suspendues'])): ?>
                        <p class="text-muted mb-0"><?= $this->lang->line("formation_rapports_aucune") ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= $this->lang->line("formation_inscription_pilote") ?></th>
                                        <th><?= $this->lang->line("formation_inscription_programme") ?></th>
                                        <th><?= $this->lang->line("formation_rapports_date_suspension") ?></th>
                                        <th><?= $this->lang->line("formation_rapports_motif") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($formations['suspendues'] as $f): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= controller_url('formation_inscriptions') ?>/detail/<?= $f['id'] ?>">
                                                    <?= htmlspecialchars(($f['pilote_prenom'] ?? '') . ' ' . ($f['pilote_nom'] ?? '')) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($f['programme_titre'] ?? '') ?></td>
                                            <td><?= !empty($f['date_suspension']) ? date('d/m/Y', strtotime($f['date_suspension'])) : '-' ?></td>
                                            <td><?= htmlspecialchars($f['motif_suspension'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 4. Formations ouvertes dans l'année -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingOuvertes">
                <button class="accordion-button collapsed py-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseOuvertes"
                        aria-expanded="false" aria-controls="collapseOuvertes">
                    <i class="fas fa-folder-open text-secondary me-2"></i>
                    <?= $this->lang->line("formation_rapports_ouvertes") ?>
                    <span class="badge bg-secondary ms-2"><?= count($formations['ouvertes']) ?></span>
                </button>
            </h2>
            <div id="collapseOuvertes" class="accordion-collapse collapse" aria-labelledby="headingOuvertes">
                <div class="accordion-body p-2">
                    <?php if (empty($formations['ouvertes'])): ?>
                        <p class="text-muted mb-0"><?= $this->lang->line("formation_rapports_aucune") ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= $this->lang->line("formation_inscription_pilote") ?></th>
                                        <th><?= $this->lang->line("formation_inscription_programme") ?></th>
                                        <th><?= $this->lang->line("formation_inscription_instructeur") ?></th>
                                        <th><?= $this->lang->line("formation_inscription_date_ouverture") ?></th>
                                        <th><?= $this->lang->line("formation_inscription_statut") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($formations['ouvertes'] as $f): ?>
                                        <?php
                                        $statut_classes = array(
                                            'ouverte' => 'bg-success',
                                            'suspendue' => 'bg-warning',
                                            'cloturee' => 'bg-primary',
                                            'abandonnee' => 'bg-danger'
                                        );
                                        $badge_class = $statut_classes[$f['statut']] ?? 'bg-secondary';
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?= controller_url('formation_inscriptions') ?>/detail/<?= $f['id'] ?>">
                                                    <?= htmlspecialchars(($f['pilote_prenom'] ?? '') . ' ' . ($f['pilote_nom'] ?? '')) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($f['programme_titre'] ?? '') ?></td>
                                            <td><?= htmlspecialchars(($f['instructeur_prenom'] ?? '') . ' ' . ($f['instructeur_nom'] ?? '')) ?></td>
                                            <td><?= !empty($f['date_ouverture']) ? date('d/m/Y', strtotime($f['date_ouverture'])) : '-' ?></td>
                                            <td><span class="badge <?= $badge_class ?>"><?= $this->lang->line('formation_inscription_statut_' . $f['statut']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 5. Formations en cours -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingEnCours">
                <button class="accordion-button collapsed py-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseEnCours"
                        aria-expanded="false" aria-controls="collapseEnCours">
                    <i class="fas fa-spinner text-primary me-2"></i>
                    <?= $this->lang->line("formation_rapports_en_cours") ?>
                    <span class="badge bg-primary ms-2"><?= count($formations['en_cours']) ?></span>
                </button>
            </h2>
            <div id="collapseEnCours" class="accordion-collapse collapse" aria-labelledby="headingEnCours">
                <div class="accordion-body p-2">
                    <?php if (empty($formations['en_cours'])): ?>
                        <p class="text-muted mb-0"><?= $this->lang->line("formation_rapports_aucune") ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= $this->lang->line("formation_inscription_pilote") ?></th>
                                        <th><?= $this->lang->line("formation_inscription_programme") ?></th>
                                        <th><?= $this->lang->line("formation_inscription_instructeur") ?></th>
                                        <th><?= $this->lang->line("formation_inscription_date_ouverture") ?></th>
                                        <th style="width: 250px"><?= $this->lang->line("formation_rapports_progression") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($formations['en_cours'] as $f): ?>
                                        <?php
                                        $pct = isset($f['progression']) ? $f['progression']['pourcentage'] : 0;
                                        $bar_class = $formation_progression->get_progress_bar_class($pct);
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?= controller_url('formation_inscriptions') ?>/detail/<?= $f['id'] ?>">
                                                    <?= htmlspecialchars(($f['pilote_prenom'] ?? '') . ' ' . ($f['pilote_nom'] ?? '')) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($f['programme_titre'] ?? '') ?></td>
                                            <td><?= htmlspecialchars(($f['instructeur_prenom'] ?? '') . ' ' . ($f['instructeur_nom'] ?? '')) ?></td>
                                            <td><?= !empty($f['date_ouverture']) ? date('d/m/Y', strtotime($f['date_ouverture'])) : '-' ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1" style="height: 20px;">
                                                        <div class="progress-bar <?= $bar_class ?>" role="progressbar"
                                                             style="width: <?= $pct ?>%"
                                                             aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <span class="ms-2 text-nowrap"><strong><?= $pct ?>%</strong></span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 5. Séances de ré-entrainement -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingReentrainement">
                <button class="accordion-button collapsed py-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseReentrainement"
                        aria-expanded="false" aria-controls="collapseReentrainement">
                    <i class="fas fa-plane text-info me-2"></i>
                    <?= $this->lang->line("formation_rapports_reentrainement") ?>
                    <span class="badge bg-info ms-2"><?= count($seances_libres) ?></span>
                </button>
            </h2>
            <div id="collapseReentrainement" class="accordion-collapse collapse" aria-labelledby="headingReentrainement">
                <div class="accordion-body p-2">
                    <?php if (empty($seances_libres)): ?>
                        <p class="text-muted mb-0"><?= $this->lang->line("formation_rapports_aucune") ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= $this->lang->line("formation_seance_date") ?></th>
                                        <th><?= $this->lang->line("formation_seance_pilote") ?></th>
                                        <th><?= $this->lang->line("formation_seance_instructeur") ?></th>
                                        <th><?= $this->lang->line("formation_seance_programme") ?></th>
                                        <th><?= $this->lang->line("formation_seance_duree") ?></th>
                                        <th><?= $this->lang->line("formation_seance_nb_atterrissages") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($seances_libres as $s): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($s['date_seance'])) ?></td>
                                            <td><?= htmlspecialchars(($s['pilote_prenom'] ?? '') . ' ' . ($s['pilote_nom'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars(($s['instructeur_prenom'] ?? '') . ' ' . ($s['instructeur_nom'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars($s['programme_titre'] ?? '') ?></td>
                                            <td><?= substr($s['duree'], 0, 5) ?></td>
                                            <td><?= $s['nb_atterrissages'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- ============================================ -->
    <!-- SECTION: PAR INSTRUCTEUR                     -->
    <!-- ============================================ -->

    <h4 class="mt-4 mb-3">
        <i class="fas fa-user-tie" aria-hidden="true"></i>
        <?= $this->lang->line("formation_rapports_par_instructeur") ?>
    </h4>

    <?php if (empty($instructeurs)): ?>
        <p class="text-muted"><?= $this->lang->line("formation_rapports_aucune") ?></p>
    <?php else: ?>
        <div class="accordion mb-4" id="rapportInstructeursAccordion">
            <?php foreach ($instructeurs as $idx => $inst): ?>
                <?php
                $inst_name = htmlspecialchars(($inst['prenom'] ?? '') . ' ' . ($inst['nom'] ?? ''));
                $total_seances = $inst['nb_seances_libres'];
                foreach ($inst['formations'] as $form) {
                    $total_seances += $form['nb_seances'];
                }
                ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingInst<?= $idx ?>">
                        <button class="accordion-button collapsed py-2" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapseInst<?= $idx ?>"
                                aria-expanded="false" aria-controls="collapseInst<?= $idx ?>">
                            <i class="fas fa-user text-secondary me-2"></i>
                            <?= $inst_name ?>
                            <span class="badge bg-secondary ms-2"><?= $total_seances ?> <?= $this->lang->line("formation_rapports_nb_seances") ?></span>
                        </button>
                    </h2>
                    <div id="collapseInst<?= $idx ?>" class="accordion-collapse collapse" aria-labelledby="headingInst<?= $idx ?>">
                        <div class="accordion-body p-2">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?= $this->lang->line("formation_seance_type") ?></th>
                                            <th><?= $this->lang->line("formation_inscription_pilote") ?></th>
                                            <th><?= $this->lang->line("formation_inscription_programme") ?></th>
                                            <th class="text-center"><?= $this->lang->line("formation_rapports_nb_seances") ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($inst['formations'])): ?>
                                            <?php foreach ($inst['formations'] as $form): ?>
                                                <tr>
                                                    <td><span class="badge bg-primary"><?= $this->lang->line("formation_seance_type_formation") ?></span></td>
                                                    <td><?= htmlspecialchars(($form['pilote_prenom'] ?? '') . ' ' . ($form['pilote_nom'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars($form['programme_titre'] ?? '') ?></td>
                                                    <td class="text-center"><strong><?= $form['nb_seances'] ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php if ($inst['nb_seances_libres'] > 0): ?>
                                            <tr class="table-info">
                                                <td><span class="badge bg-info"><?= $this->lang->line("formation_seance_type_libre") ?></span></td>
                                                <td colspan="2"><em><?= $this->lang->line("formation_rapports_nb_seances_libre") ?></em></td>
                                                <td class="text-center"><strong><?= $inst['nb_seances_libres'] ?></strong></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php $this->load->view('bs_footer'); ?>
