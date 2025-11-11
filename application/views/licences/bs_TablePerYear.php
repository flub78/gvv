<!-- VIEW: application/views/licences/bs_TablePerYear.php -->
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
 * Vue table pour les licences
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->load->library('DataTable');

echo '<div id="body" class="body container-fluid">';

echo heading("Licences", 3);

// echo year_selector($controller, $year, $year_selector);
echo licence_selector($controller, $type);
echo br(2)
;
$table = new DataTable(array(
	'title' => "",
	'values' => $table,
	'controller' => '',
	'class' => "datatable table table-striped",
	'create' => "",
    'first' => 0));

$table->display();

?>
<script>
$(document).ready(function() {
    // Gestionnaire pour les changements de checkboxes
    $('.licence-checkbox').on('change', function() {
        var checkbox = $(this);
        var pilote = checkbox.data('pilote');
        var year = checkbox.data('year');
        var type = checkbox.data('type');
        var isChecked = checkbox.is(':checked');

        // Désactiver la checkbox pendant le traitement
        checkbox.prop('disabled', true);

        // Déterminer l'URL en fonction de l'état de la checkbox
        var url;
        if (isChecked) {
            // Cocher = créer la licence
            url = '<?php echo base_url(); ?>licences/set/' + pilote + '/' + year + '/' + type;
        } else {
            // Décocher = supprimer la licence
            url = '<?php echo base_url(); ?>licences/switch_it/' + pilote + '/' + year + '/' + type;
        }

        // Envoyer la requête AJAX
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function(response) {
                // Réactiver la checkbox
                checkbox.prop('disabled', false);

                // Vérifier si l'opération a réussi
                if (response.success) {
                    // Succès silencieux - pas de message
                } else {
                    console.error('Licence error:', response.error);
                    alert('Erreur: ' + response.error);
                    checkbox.prop('checked', !isChecked);
                }
            },
            error: function(xhr, status, error) {
                console.error('Licence AJAX error:', error);
                // En cas d'erreur, remettre la checkbox dans son état précédent
                checkbox.prop('checked', !isChecked);
                checkbox.prop('disabled', false);

                // Afficher un message d'erreur
                var errorMsg = 'Erreur lors de la mise à jour de la licence: ' + error;
                if (xhr.responseText && xhr.responseText.length < 500) {
                    errorMsg += '\n\nRéponse: ' + xhr.responseText;
                }
                alert(errorMsg);
            }
        });
    });
});
</script>
<?php

echo '</div>';

?>
