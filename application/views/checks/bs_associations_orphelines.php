<!-- VIEW: application/views/checks/bs_associations_orphelines.php -->
<?php
// ----------------------------------------------------------------------------------------
//    GVV Gestion vol à voile
//    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
// ----------------------------------------------------------------------------------------
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

echo '<div id="body" class="body container-fluid">';
echo '<h3 class="text-center">Vérification de la cohérence des rapprochements bancaires</h3>';

echo '<div class="alert alert-info mb-4">';
echo '<h5><i class="fas fa-info-circle"></i> Que vérifie cette page ?</h5>';
echo '<p class="mb-2">Cette page identifie les <strong>associations orphelines</strong> dans la table <code>associations_ecriture</code>.</p>';
echo '<p class="mb-2">Une association orpheline est un rapprochement bancaire qui pointe vers une écriture comptable qui n\'existe plus dans la base de données. Cela peut se produire lorsque :</p>';
echo '<ul class="mb-2">';
echo '<li>Une écriture comptable a été supprimée après avoir été rapprochée</li>';
echo '<li>Une importation de données a créé des associations invalides</li>';
echo '<li>Une restauration de sauvegarde partielle a créé des incohérences</li>';
echo '</ul>';
echo '<p class="mb-0"><strong>Impact :</strong> Ces associations orphelines n\'affectent pas le fonctionnement du système mais encombrent la base de données. Il est recommandé de les supprimer pour maintenir l\'intégrité de la base.</p>';
echo '</div>';

if (empty($associations)) {
    echo '<div class="alert alert-success" role="alert">';
    echo '<i class="fas fa-check-circle"></i> ';
    echo 'Aucun rapprochement orphelin détecté. Toutes les associations pointent vers des écritures existantes.';
    echo '</div>';
} else {
    echo '<div class="alert alert-warning" role="alert">';
    echo '<i class="fas fa-exclamation-triangle"></i> ';
    echo '<strong>' . count($associations) . '</strong> rapprochement(s) orphelin(s) détecté(s).';
    echo '</div>';

    echo '<h4 class="text-center">Rapprochements orphelins</h4>';
    echo form_open_multipart('dbchecks/delete_associations');

    echo table_from_array($associations, array(
        'fields' => array('', 'ID', 'Identifiant relevé', 'ID écriture GVV (supprimée)'),
        'align' => array('left', 'left', 'left', 'left'),
        'class' => 'datatable table'
    ));

    echo br();
    ?>
    <div class="actions mb-3">
        <button type="button" class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
        <button type="button" class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
    </div>
    <?php

    echo form_input(array(
        'type' => 'submit',
        'name' => 'button',
        'value' => "Supprimer la sélection",
        'class' => 'btn btn-danger mb-4',
        'onclick' => "return confirm('Êtes-vous sûr de vouloir supprimer ces rapprochements orphelins ?');"
    ));
    echo form_close('</div>');
}

echo '<div class="mb-3">';
echo '<a href="' . controller_url('admin/page') . '" class="btn btn-secondary">Retour à l\'administration</a>';
echo '</div>';

echo '</div>';
?>
