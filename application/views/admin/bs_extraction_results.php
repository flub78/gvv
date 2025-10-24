<!-- VIEW: application/views/admin/bs_extraction_results.php -->
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
 *    Vue pour les résultats d'extraction de données de test
 *
 *    @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

echo '<div id="body" class="body container-fluid">';

// Title
echo heading($title ?? 'Extraction de données de test', 3);

// Success message
if (!empty($message)) {
    $alert_class = empty($errors) ? 'alert-success' : 'alert-danger';
    $icon_class = empty($errors) ? 'fa-check-circle' : 'fa-exclamation-triangle';
    echo '<div class="alert ' . $alert_class . '">';
    echo '<i class="fas ' . $icon_class . '"></i> ' . htmlspecialchars($message);
    echo '</div>';
}

// Results table
echo '<div class="card mt-3">';
echo '<div class="card-header bg-info text-white">';
echo '<h4 class="mb-0"><i class="fas fa-database"></i> Données extraites</h4>';
echo '</div>';
echo '<div class="card-body">';

echo '<table class="table table-striped table-hover">';
echo '<thead class="table-dark">';
echo '<tr>';
echo '<th>Type de données</th>';
echo '<th class="text-end">Éléments extraits</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($results as $result) {
    $badge_class = $result['extracted'] > 0 ? 'success' : 'warning';

    echo '<tr>';
    echo '<td>' . htmlspecialchars($result['routine']) . '</td>';
    echo '<td class="text-end">';
    echo '<span class="badge bg-' . $badge_class . '">' . $result['extracted'] . '</span>';
    echo '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '<tfoot class="table-secondary">';
echo '<tr>';
echo '<th>Total</th>';
echo '<th class="text-end"><strong>' . $total_extracted . '</strong></th>';
echo '</tr>';
echo '</tfoot>';
echo '</table>';

echo '</div>'; // card-body
echo '</div>'; // card

// Output file information
if (!empty($output_file)) {
    echo '<div class="card mt-3">';
    echo '<div class="card-header bg-secondary text-white">';
    echo '<h5 class="mb-0"><i class="fas fa-file-code"></i> Fichier de sortie</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<dl class="row mb-0">';
    echo '<dt class="col-sm-3">Fichier :</dt>';
    echo '<dd class="col-sm-9"><code>' . htmlspecialchars($output_file) . '</code></dd>';
    echo '<dt class="col-sm-3">Taille :</dt>';
    echo '<dd class="col-sm-9">' . number_format($file_size) . ' octets</dd>';
    echo '</dl>';
    echo '</div>';
    echo '</div>';
}

// Additional information if errors exist
if (!empty($errors)) {
    echo '<div class="alert alert-danger mt-3">';
    echo '<h5><i class="fas fa-exclamation-circle"></i> Erreurs</h5>';
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
echo '<a href="' . controller_url('admin/extract_test_data') . '" class="btn btn-info">';
echo '<i class="fas fa-sync-alt"></i> Relancer l\'extraction';
echo '</a>';
echo '</div>';

echo '</div>'; // body container
?>
