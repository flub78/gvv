<!-- VIEW: application/views/comptes/bs_formView.php -->
<?php

/**
 * 
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
 *    Formulaire de saisie d'un compte
 *    @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('comptes');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
	echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo heading('gvv_comptes_title', 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
echo form_hidden('actif', $actif, '');

// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

echo validation_errors();
echo ($this->gvvmetadata->form('comptes', array(
	'nom' => $nom,
	'codec' => $codec,
	'desc' => $desc,
	'debit' => $debit,		// Support pour les champs readonly et hidden ???
	'credit' => $credit,    // Dans ce cas le concept est cachée ou readonly seulement à la création ...
	'saisie_par' => $saisie_par,
	'pilote' => $pilote,
	'masked' => $masked
)));

// Display message about masked field and balance
if ($action == MODIFICATION && isset($compte_solde)) {
	echo '<div class="mt-3">';
	if ($can_mask) {
		echo '<div class="alert alert-info">';
		echo '<i class="bi bi-info-circle"></i> ' . $this->lang->line('gvv_comptes_can_mask');
		echo '</div>';
	} else {
		echo '<div class="alert alert-warning">';
		$solde_formatted = number_format($compte_solde, 2, ',', ' ');
		echo '<i class="bi bi-exclamation-triangle"></i> ';
		echo sprintf($this->lang->line('gvv_comptes_masked_warning'), $solde_formatted);
		echo '</div>';
	}
	echo '</div>';
}

echo validation_button($action);
echo form_close();

// JavaScript to enable/disable pilote selector based on codec selection
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Function to check codec and enable/disable pilote selector
    function updatePiloteSelector() {
        var codecValue = $('#codec').val();
        var piloteSelect = $('#pilote');
        
        console.log('Codec selected:', codecValue);
        
        // Enable pilote selector only if codec is 411 (Clients)
        if (codecValue == '411') {
            piloteSelect.prop('disabled', false);
            piloteSelect.closest('tr').show(); // Show the row
            console.log('Pilote selector enabled (codec 411)');
        } else {
            piloteSelect.prop('disabled', true);
            piloteSelect.val(''); // Clear selection
            piloteSelect.closest('tr').hide(); // Hide the row
            console.log('Pilote selector disabled (codec not 411)');
        }
        
        // Trigger Select2 update if using Select2
        if (piloteSelect.hasClass('select2-hidden-accessible')) {
            piloteSelect.trigger('change.select2');
        }
    }
    
    // Run on page load
    updatePiloteSelector();
    
    // Run when codec selector changes
    $('#codec').on('change', function() {
        updatePiloteSelector();
    });
    
    // Also handle Select2 change events
    $('#codec').on('select2:select', function() {
        updatePiloteSelector();
    });
});
</script>
<?php

echo '</div>';
