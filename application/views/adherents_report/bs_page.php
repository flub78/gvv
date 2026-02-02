<!-- VIEW: application/views/adherents_report/bs_page.php -->
<?php
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Vue pour le rapport des adhérents par année et classe d'âge
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid">
    <h3><?= translation('gvv_adherents_report_title') ?> - <?= $year ?></h3>

    <!-- Sélecteur d'année -->
    <div class="row mb-4">
        <div class="col-md-4">
            <label for="year_selector" class="form-label"><?= translation('gvv_adherents_report_select_year') ?></label>
            <select class="form-select" id="year_selector" name="year">
                <?php foreach ($year_selector as $y => $label): ?>
                    <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Tableau des statistiques -->
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th></th>
                            <?php foreach ($sections as $section): ?>
                                <th class="text-center"><?= htmlspecialchars($section['nom']) ?></th>
                            <?php endforeach; ?>
                            <th class="text-center table-primary"><?= translation('gvv_adherents_report_club_total') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Moins de 25 ans -->
                        <tr>
                            <td><strong><?= translation('gvv_adherents_report_under_25') ?></strong></td>
                            <?php foreach ($sections as $section): ?>
                                <td class="text-center"><?= $stats['under_25']['section_' . $section['id']] ?></td>
                            <?php endforeach; ?>
                            <td class="text-center table-primary"><strong><?= $stats['under_25']['club_total'] ?></strong></td>
                        </tr>
                        <!-- 25 à 59 ans -->
                        <tr>
                            <td><strong><?= translation('gvv_adherents_report_25_to_59') ?></strong></td>
                            <?php foreach ($sections as $section): ?>
                                <td class="text-center"><?= $stats['25_to_59']['section_' . $section['id']] ?></td>
                            <?php endforeach; ?>
                            <td class="text-center table-primary"><strong><?= $stats['25_to_59']['club_total'] ?></strong></td>
                        </tr>
                        <!-- 60 ans et plus -->
                        <tr>
                            <td><strong><?= translation('gvv_adherents_report_60_and_over') ?></strong></td>
                            <?php foreach ($sections as $section): ?>
                                <td class="text-center"><?= $stats['60_and_over']['section_' . $section['id']] ?></td>
                            <?php endforeach; ?>
                            <td class="text-center table-primary"><strong><?= $stats['60_and_over']['club_total'] ?></strong></td>
                        </tr>
                    </tbody>
                    <tfoot class="table-secondary">
                        <!-- Total -->
                        <tr>
                            <td><strong><?= translation('gvv_adherents_report_total') ?></strong></td>
                            <?php foreach ($sections as $section): ?>
                                <td class="text-center"><strong><?= $stats['total']['section_' . $section['id']] ?></strong></td>
                            <?php endforeach; ?>
                            <td class="text-center table-primary"><strong><?= $stats['total']['club_total'] ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Note explicative -->
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <?= translation('gvv_adherents_report_note') ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Gestionnaire pour le changement d'année
    $('#year_selector').on('change', function() {
        var selectedYear = $(this).val();

        $.ajax({
            url: '<?= site_url('adherents_report/set_year') ?>/' + selectedYear,
            type: 'GET',
            dataType: 'json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function(response) {
                if (response.success) {
                    // Recharger la page pour afficher les nouvelles statistiques
                    window.location.reload();
                } else {
                    console.error('Erreur lors du changement d\'année:', response.error);
                    alert('Erreur: ' + response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
                alert('Erreur lors du changement d\'année');
            }
        });
    });
});
</script>
