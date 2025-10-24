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

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

echo '<div id="body" class="body container-fluid">';

// Title
echo heading($title ?? 'Anonymisation globale des données', 3);

// Success message
if (!empty($message)) {
    echo '<div class="alert alert-success">';
    echo '<i class="fas fa-check-circle"></i> ' . htmlspecialchars($message);
    echo '</div>';
}

// Results table
echo '<div class="card mt-3">';
echo '<div class="card-header bg-warning text-dark">';
echo '<h4 class="mb-0"><i class="fas fa-list"></i> Résultats par routine</h4>';
echo '</div>';
echo '<div class="card-body">';

echo '<table class="table table-striped table-hover">';
echo '<thead class="table-dark">';
echo '<tr>';
echo '<th>Routine</th>';
echo '<th class="text-end">Mis à jour</th>';
echo '<th class="text-end">Total</th>';
echo '<th class="text-center">Pourcentage</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($results as $key => $result) {
    $percentage = $result['total'] > 0 ? round(($result['updated'] / $result['total']) * 100, 2) : 0;
    $badge_class = $percentage == 100 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');

    echo '<tr>';
    echo '<td>' . htmlspecialchars($result['routine']) . '</td>';
    echo '<td class="text-end text-success"><strong>' . $result['updated'] . '</strong></td>';
    echo '<td class="text-end">' . $result['total'] . '</td>';
    echo '<td class="text-center">';
    echo '<span class="badge bg-' . $badge_class . '">' . $percentage . '%</span>';
    echo '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '<tfoot class="table-secondary">';
echo '<tr>';
echo '<th>Total</th>';
echo '<th class="text-end text-success"><strong>' . $total_updated . '</strong></th>';
echo '<th colspan="2"></th>';
echo '</tr>';
echo '</tfoot>';
echo '</table>';

echo '</div>'; // card-body
echo '</div>'; // card

// Additional information if errors exist
if (!empty($errors)) {
    echo '<div class="alert alert-info mt-3">';
    echo '<h5><i class="fas fa-info-circle"></i> Informations complémentaires</h5>';
    echo '<ul class="mb-0">';
    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

// Action buttons
echo '<div class="mt-3">';
echo '<a href="' . controller_url('admin/page') . '" class="btn btn-primary me-2">';
echo '<i class="fas fa-arrow-left"></i> Retour à l\'administration';
echo '</a>';
echo '<a href="' . controller_url('admin/anonymize_all_data') . '" class="btn btn-warning">';
echo '<i class="fas fa-sync-alt"></i> Relancer l\'anonymisation';
echo '</a>';
echo '</div>';

echo '</div>'; // body container
?>
