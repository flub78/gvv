<!-- VIEW: application/views/admin/bs_anonymization_results.php -->
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
 *    Vue pour les résultats d'anonymisation
 *
 *    @package vues
 */
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-warning">
            <h2><i class="fas fa-user-secret"></i> <?= $title ?></h2>
        </div>
        <div class="card-body">
            <?php if (!empty($message)) : ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <?= $message ?>
                </div>
            <?php endif; ?>

            <h3>Résultats par routine</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Routine</th>
                        <th>Mis à jour</th>
                        <th>Total</th>
                        <th>Pourcentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $key => $result) : ?>
                        <tr>
                            <td><?= $result['routine'] ?></td>
                            <td class="text-success"><strong><?= $result['updated'] ?></strong></td>
                            <td><?= $result['total'] ?></td>
                            <td>
                                <?php
                                $percentage = $result['total'] > 0 ? round(($result['updated'] / $result['total']) * 100, 2) : 0;
                                $badge_class = $percentage == 100 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
                                ?>
                                <span class="badge bg-<?= $badge_class ?>"><?= $percentage ?>%</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-info">
                        <th>Total</th>
                        <th class="text-success"><strong><?= $total_updated ?></strong></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>

            <?php if (!empty($errors)) : ?>
                <div class="alert alert-info mt-3">
                    <h4><i class="fas fa-info-circle"></i> Informations complémentaires</h4>
                    <ul>
                        <?php foreach ($errors as $error) : ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="mt-3">
                <a href="<?= controller_url('admin/page') ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Retour à l'administration
                </a>
            </div>
        </div>
    </div>
</div>
