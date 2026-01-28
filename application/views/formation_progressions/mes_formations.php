<!-- VIEW: application/views/formation_progressions/mes_formations.php -->
<?php
/**
 * Vue élève pour consulter ses formations et sa progression
 * Mode read-only - accès depuis le dashboard "Mon espace personnel"
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('formation');
$this->lang->load('gvv');

?>
<div id="body" class="body container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-graduation-cap" aria-hidden="true"></i>
            <?= $this->lang->line("formation_mes_formations_title") ?>
        </h3>
        <div>
            <a href="<?= controller_url('welcome') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $this->lang->line("gvv_str_back") ?>
            </a>
        </div>
    </div>

    <div class="alert alert-info mb-3">
        <i class="fas fa-info-circle" aria-hidden="true"></i> 
        <?= $this->lang->line("formation_mes_formations_info") ?>
    </div>

    <!-- Liste des formations -->
    <?php if (empty($formations)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> 
            <?= $this->lang->line("formation_mes_formations_empty") ?>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($formations as $formation): ?>
                <?php
                // Définir la classe de badge selon le statut
                $badge_class = 'secondary';
                switch ($formation['statut']) {
                    case 'ouverte':
                        $badge_class = 'success';
                        break;
                    case 'suspendue':
                        $badge_class = 'warning';
                        break;
                    case 'cloturee':
                        $badge_class = 'primary';
                        break;
                    case 'abandonnee':
                        $badge_class = 'danger';
                        break;
                }

                // Calculer la progression
                $this->load->library('formation_progression');
                $progression_data = $this->formation_progression->calculer($formation['id']);
                $pourcentage_acquis = $progression_data ? $progression_data['stats']['pourcentage_acquis'] : 0;
                ?>

                <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-book" aria-hidden="true"></i>
                                    <?= htmlspecialchars($formation['programme_titre']) ?>
                                </h5>
                                <span class="badge bg-<?= $badge_class ?>">
                                    <?= $this->lang->line('formation_inscription_statut_' . $formation['statut']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <p class="mb-2">
                                    <strong><i class="fas fa-calendar-alt" aria-hidden="true"></i> 
                                    <?= $this->lang->line("formation_inscription_date_ouverture") ?> :</strong>
                                    <?= date('d/m/Y', strtotime($formation['date_ouverture'])) ?>
                                </p>
                                <?php if ($formation['statut'] === 'cloturee' && !empty($formation['date_cloture'])): ?>
                                <p class="mb-2">
                                    <strong><i class="fas fa-check-circle" aria-hidden="true"></i> 
                                    <?= $this->lang->line("formation_inscription_date_cloture") ?> :</strong>
                                    <?= date('d/m/Y', strtotime($formation['date_cloture'])) ?>
                                </p>
                                <?php endif; ?>
                            </div>

                            <!-- Barre de progression -->
                            <?php if ($progression_data): ?>
                            <div class="mb-3">
                                <p class="mb-2">
                                    <strong><?= $this->lang->line("formation_progression_titre") ?> :</strong>
                                    <?= $pourcentage_acquis ?>% 
                                    (<?= $progression_data['stats']['nb_sujets_acquis'] ?>/<?= $progression_data['stats']['nb_sujets_total'] ?>)
                                </p>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar <?= $this->formation_progression->get_progress_bar_class($pourcentage_acquis) ?>" 
                                         role="progressbar" 
                                         style="width: <?= $pourcentage_acquis ?>%"
                                         aria-valuenow="<?= $pourcentage_acquis ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?= $pourcentage_acquis ?>%
                                    </div>
                                </div>
                            </div>

                            <!-- Statistiques rapides -->
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="border rounded p-2 bg-light">
                                        <h5 class="text-primary mb-0"><?= $progression_data['stats']['nb_seances'] ?></h5>
                                        <small class="text-muted"><?= $this->lang->line("formation_progression_nb_seances") ?></small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2 bg-light">
                                        <h5 class="text-primary mb-0"><?= $progression_data['stats']['heures_totales'] ?></h5>
                                        <small class="text-muted"><?= $this->lang->line("formation_progression_heures_vol") ?></small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2 bg-light">
                                        <h5 class="text-primary mb-0"><?= $progression_data['stats']['atterrissages_totaux'] ?></h5>
                                        <small class="text-muted"><?= $this->lang->line("formation_progression_atterrissages") ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-light">
                            <div class="d-grid gap-2">
                                <a href="<?= controller_url('formation_inscriptions') ?>/detail/<?= $formation['id'] ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-chart-line" aria-hidden="true"></i> 
                                    <?= $this->lang->line('formation_voir_ma_progression') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
