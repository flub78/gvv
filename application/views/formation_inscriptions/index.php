<!-- VIEW: application/views/formation_inscriptions/index.php -->
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
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Vue liste des inscriptions aux formations
 *
 * @package vues
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
            <i class="fas fa-user-graduate" aria-hidden="true"></i>
            <?= $this->lang->line("formation_inscriptions_title") ?>
        </h3>
        <div>
            <a href="<?= controller_url($controller) ?>/ouvrir" class="btn btn-primary">
                <i class="fas fa-plus" aria-hidden="true"></i> <?= $this->lang->line("formation_inscriptions_ouvrir") ?>
            </a>
        </div>
    </div>

    <?php
    // Display flash messages
    if ($this->session->flashdata('success')) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-check-circle" aria-hidden="true"></i> ' . $this->session->flashdata('success');
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    if ($this->session->flashdata('error')) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i> ' . $this->session->flashdata('error');
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    ?>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="<?= controller_url($controller) ?>" class="row g-3">
                <div class="col-md-3">
                    <label for="pilote_id" class="form-label"><?= $this->lang->line("formation_inscription_pilote") ?></label>
                    <select class="form-select" id="pilote_id" name="pilote_id">
                        <option value="">-- Tous --</option>
                        <?php foreach ($pilotes as $id => $nom): ?>
                            <option value="<?= $id ?>" <?= isset($filters['pilote_id']) && $filters['pilote_id'] == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nom) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="programme_id" class="form-label"><?= $this->lang->line("formation_inscription_programme") ?></label>
                    <select class="form-select" id="programme_id" name="programme_id">
                        <option value="">-- Tous --</option>
                        <?php foreach ($programmes as $id => $titre): ?>
                            <option value="<?= $id ?>" <?= isset($filters['programme_id']) && $filters['programme_id'] == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($titre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="statut" class="form-label"><?= $this->lang->line("formation_inscription_statut") ?></label>
                    <select class="form-select" id="statut" name="statut">
                        <option value="">-- Tous --</option>
                        <option value="ouverte" <?= isset($filters['statut']) && $filters['statut'] == 'ouverte' ? 'selected' : '' ?>>Ouverte</option>
                        <option value="suspendue" <?= isset($filters['statut']) && $filters['statut'] == 'suspendue' ? 'selected' : '' ?>>Suspendue</option>
                        <option value="cloturee" <?= isset($filters['statut']) && $filters['statut'] == 'cloturee' ? 'selected' : '' ?>>Clôturée</option>
                        <option value="abandonnee" <?= isset($filters['statut']) && $filters['statut'] == 'abandonnee' ? 'selected' : '' ?>>Abandonnée</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="instructeur_referent_id" class="form-label"><?= $this->lang->line("formation_inscription_instructeur") ?></label>
                    <select class="form-select" id="instructeur_referent_id" name="instructeur_referent_id">
                        <option value="">-- Tous --</option>
                        <?php foreach ($instructeurs as $id => $nom): ?>
                            <option value="<?= $id ?>" <?= isset($filters['instructeur_referent_id']) && $filters['instructeur_referent_id'] == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nom) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Inscriptions list -->
    <?php if (empty($inscriptions)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle" aria-hidden="true"></i> <?= $this->lang->line("formation_inscriptions_empty") ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th><?= $this->lang->line("formation_inscription_pilote") ?></th>
                        <th><?= $this->lang->line("formation_inscription_programme") ?></th>
                        <th><?= $this->lang->line("formation_inscription_instructeur") ?></th>
                        <th><?= $this->lang->line("formation_inscription_date_ouverture") ?></th>
                        <th><?= $this->lang->line("formation_inscription_statut") ?></th>
                        <th class="text-center"><?= $this->lang->line("gvv_str_actions") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscriptions as $inscription): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($inscription['pilote_prenom'] . ' ' . $inscription['pilote_nom']) ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($inscription['programme_code']) ?></strong> -
                                <?= htmlspecialchars($inscription['programme_titre']) ?>
                            </td>
                            <td>
                                <?php if (!empty($inscription['instructeur_nom'])): ?>
                                    <?= htmlspecialchars($inscription['instructeur_prenom'] . ' ' . $inscription['instructeur_nom']) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($inscription['date_ouverture'])) ?></td>
                            <td>
                                <?php
                                $badge_class = 'secondary';
                                $statut_label = $inscription['statut'];
                                switch ($inscription['statut']) {
                                    case 'ouverte':
                                        $badge_class = 'success';
                                        $statut_label = 'Ouverte';
                                        break;
                                    case 'suspendue':
                                        $badge_class = 'warning';
                                        $statut_label = 'Suspendue';
                                        break;
                                    case 'cloturee':
                                        $badge_class = 'primary';
                                        $statut_label = 'Clôturée';
                                        break;
                                    case 'abandonnee':
                                        $badge_class = 'danger';
                                        $statut_label = 'Abandonnée';
                                        break;
                                }
                                ?>
                                <span class="badge bg-<?= $badge_class ?>"><?= $statut_label ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= controller_url($controller) ?>/detail/<?= $inscription['id'] ?>" 
                                   class="btn btn-sm btn-info" title="Détails">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </a>
                                
                                <?php if ($inscription['statut'] === 'ouverte'): ?>
                                    <a href="<?= controller_url($controller) ?>/suspendre/<?= $inscription['id'] ?>" 
                                       class="btn btn-sm btn-warning" title="Suspendre">
                                        <i class="fas fa-pause" aria-hidden="true"></i>
                                    </a>
                                    <a href="<?= controller_url($controller) ?>/cloturer/<?= $inscription['id'] ?>" 
                                       class="btn btn-sm btn-success" title="Clôturer">
                                        <i class="fas fa-check" aria-hidden="true"></i>
                                    </a>
                                <?php elseif ($inscription['statut'] === 'suspendue'): ?>
                                    <a href="<?= controller_url($controller) ?>/reactiver/<?= $inscription['id'] ?>" 
                                       class="btn btn-sm btn-success" title="Réactiver">
                                        <i class="fas fa-play" aria-hidden="true"></i>
                                    </a>
                                    <a href="<?= controller_url($controller) ?>/cloturer/<?= $inscription['id'] ?>" 
                                       class="btn btn-sm btn-danger" title="Abandonner">
                                        <i class="fas fa-times" aria-hidden="true"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-2">
            <small class="text-muted">
                <?= count($inscriptions) ?> <?= $this->lang->line("formation_inscriptions_count") ?>
            </small>
        </div>
    <?php endif; ?>
</div>

<?php $this->load->view('bs_footer'); ?>
