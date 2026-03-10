<?php
/**
 * Vue : détail d'une séance théorique
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-chalkboard" aria-hidden="true"></i>
            <?= $this->lang->line('formation_seance_theorique_detail') ?>
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>/edit/<?= $seance['id'] ?>"
               class="btn btn-warning me-2">
                <i class="fas fa-edit" aria-hidden="true"></i> Modifier
            </a>
            <a href="<?= controller_url($controller) ?>/delete/<?= $seance['id'] ?>"
               class="btn btn-danger me-2"
               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette séance ?')">
                <i class="fas fa-trash" aria-hidden="true"></i> Supprimer cette séance
            </a>
            <a href="<?= controller_url($controller) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Retour
            </a>
        </div>
    </div>

    <?php
    if ($this->session->flashdata('success')) {
        echo '<div class="alert alert-success alert-dismissible fade show">'
           . '<i class="fas fa-check-circle" aria-hidden="true"></i> '
           . $this->session->flashdata('success')
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    ?>

    <div class="row">

        <!-- Informations générales -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-info-circle" aria-hidden="true"></i> Informations
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5"><?= $this->lang->line('formation_seance_date') ?></dt>
                        <dd class="col-sm-7"><?= date('d/m/Y', strtotime($seance['date_seance'])) ?></dd>

                        <dt class="col-sm-5"><?= $this->lang->line('formation_type_seance_nom') ?></dt>
                        <dd class="col-sm-7">
                            <?= htmlspecialchars($type['nom'] ?? '—') ?>
                            <span class="badge bg-success ms-1">
                                <i class="fas fa-chalkboard" aria-hidden="true"></i>
                                <?= $this->lang->line('formation_nature_theorique') ?>
                            </span>
                        </dd>

                        <dt class="col-sm-5"><?= $this->lang->line('formation_seance_instructeur') ?></dt>
                        <dd class="col-sm-7">
                            <?= htmlspecialchars(
                                trim(($seance['instructeur_prenom'] ?? '') . ' ' . ($seance['instructeur_nom'] ?? ''))
                            ) ?>
                        </dd>

                        <?php if (!empty($seance['programme_titre'])): ?>
                        <dt class="col-sm-5"><?= $this->lang->line('formation_seance_programme') ?></dt>
                        <dd class="col-sm-7">
                            <a href="<?= site_url('programmes/view/' . $seance['programme_id']) ?>">
                                <?= htmlspecialchars($seance['programme_titre']) ?>
                            </a>
                        </dd>
                        <?php endif; ?>

                        <?php if (!empty($seance['lieu'])): ?>
                        <dt class="col-sm-5"><?= $this->lang->line('formation_seance_lieu') ?></dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($seance['lieu']) ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($seance['duree'])): ?>
                        <dt class="col-sm-5"><?= $this->lang->line('formation_seance_duree_cours') ?></dt>
                        <dd class="col-sm-7"><?= substr($seance['duree'], 0, 5) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <?php if (!empty($seance['commentaires'])): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-comment" aria-hidden="true"></i>
                    <?= $this->lang->line('formation_seance_commentaires') ?>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($seance['commentaires'])) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Liste des participants -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-users" aria-hidden="true"></i>
                        <?= $this->lang->line('formation_seance_participants') ?>
                    </span>
                    <span class="badge bg-secondary"><?= count($participants) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($participants)): ?>
                        <p class="text-muted p-3 mb-0">
                            <?= $this->lang->line('formation_seance_participants_aucun') ?>
                        </p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($participants as $p): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-user me-2" aria-hidden="true"></i>
                                    <?= htmlspecialchars(trim(($p['mnom'] ?? '') . ' ' . ($p['mprenom'] ?? ''))) ?>
                                    <?php if (!empty($p['memail'])): ?>
                                        <small class="text-muted ms-2"><?= htmlspecialchars($p['memail']) ?></small>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>


</div>
<?php $this->load->view('bs_footer'); ?>
