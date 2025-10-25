<!-- VIEW: application/views/comptes/bs_check_results.php -->
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
 * Vue pour afficher les résultats de la vérification de cohérence des comptes
 *
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid py-3">
    <?php if (isset($title)): ?>
        <h3><?= $title ?></h3>
    <?php endif; ?>

    <?php if (isset($text)): ?>
        <?= $text ?>
    <?php endif; ?>

    <?php if (isset($table) && !empty($table)): ?>
        <h4 class="mt-4">Détails des corrections :</h4>
        <div class="table-responsive">
            <table id="coherence-check-table" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <?php foreach (array_keys($table[0]) as $header): ?>
                            <th><?= $header ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($table as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= $cell ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (isset($enable_datatable) && $enable_datatable): ?>
<script>
$(document).ready(function() {
    $('#coherence-check-table').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json"
        },
        "pageLength": 25,
        "order": [[4, "desc"]], // Sort by Différence column (descending)
        "columnDefs": [
            {
                "targets": [2, 3, 4], // Solde enregistré, Solde calculé, Différence
                "className": "text-end"
            }
        ]
    });
});
</script>
<?php endif; ?>
