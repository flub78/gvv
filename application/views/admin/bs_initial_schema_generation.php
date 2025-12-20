<!-- VIEW: application/views/admin/bs_initial_schema_generation.php -->
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
 * Vue génération schéma initial
 * @package vues
 * @filesource bs_initial_schema_generation.php
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid py-3">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-3">
                <i class="fas fa-file-code text-primary"></i>
                <?= isset($title) ? $title : 'Génération du schéma initial' ?>
            </h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5><i class="fas fa-exclamation-triangle"></i> Erreurs</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($show_form) && $show_form): ?>
                <!-- Form to trigger generation -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">Cette opération va :</p>
                        <ol>
                            <li>Sauvegarder le fichier <code>install/gvv_init.sql</code> existant</li>
                            <li>Générer le schéma complet de la base de données (structure uniquement)</li>
                            <li>Ajouter des données de test minimales :
                                <ul>
                                    <li>Utilisateur admin (login: admin, password: admin)</li>
                                    <li>Membre de test</li>
                                    <li>2 terrains de test (LFOR, LFHV)</li>
                                    <li>Plan comptable de base (101, 411, 512, 530, 706, 707)</li>
                                    <li>Comptes de test (Banque, Caisse, compte pilote)</li>
                                    <li>1 planeur de test (ASK-21)</li>
                                    <li>1 avion remorqueur de test (DR400)</li>
                                    <li>Sections et rôles de base</li>
                                </ul>
                            </li>
                        </ol>

                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i>
                            <strong>Utilisation :</strong> Ce fichier permet de déployer GVV avec le schéma à jour et juste assez de données pour démarrer.
                        </div>

                        <form method="post" action="<?= controller_url('admin/generate_initial_schema') ?>" class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-file-code"></i> Générer le schéma initial
                            </button>
                            <a href="<?= controller_url('admin') ?>" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left"></i> Retour
                            </a>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($results)): ?>
                <!-- Results table -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Résultats de l'opération</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th style="width: 200px;">Étape</th>
                                        <th style="width: 100px;">Statut</th>
                                        <th>Détails</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 1; foreach ($results as $result): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($result['step']) ?></td>
                                            <td>
                                                <?php
                                                $badge_class = 'bg-secondary';
                                                $icon = 'fa-info-circle';
                                                
                                                if ($result['status'] === 'OK') {
                                                    $badge_class = 'bg-success';
                                                    $icon = 'fa-check-circle';
                                                } elseif ($result['status'] === 'WARNING') {
                                                    $badge_class = 'bg-warning text-dark';
                                                    $icon = 'fa-exclamation-triangle';
                                                } elseif ($result['status'] === 'ERROR') {
                                                    $badge_class = 'bg-danger';
                                                    $icon = 'fa-times-circle';
                                                } elseif ($result['status'] === 'SKIPPED') {
                                                    $badge_class = 'bg-secondary';
                                                    $icon = 'fa-minus-circle';
                                                }
                                                ?>
                                                <span class="badge <?= $badge_class ?>">
                                                    <i class="fas <?= $icon ?>"></i>
                                                    <?= htmlspecialchars($result['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($result['details']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <a href="<?= controller_url('admin') ?>" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
                            </a>
                            <a href="<?= controller_url('admin/generate_initial_schema') ?>" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Générer à nouveau
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php $this->load->view('bs_footer'); ?>
