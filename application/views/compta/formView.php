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
 * Formulaire de passage d'écritures
 * @package vues
 */
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');
$this->lang->load('compta');
echo '<div id="body" class="body ui-widget-content">';

echo checkalert($this->session, isset($popup) ? $popup : "");
echo heading($this->lang->line($title_key), 3);


if (isset($message)) {
    echo p($message) .br();
}

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie') );

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
echo form_hidden('saisie_par', $saisie_par, '');
echo form_hidden('annee_exercise', $annee_exercise, '');


// On affiche tous les champs dans un tableau. C'est plus simple de remplir d'abbord le tableau
// et de l'afficher ensuite, surtout pour modifier l'affichage

echo form_hidden('id', $id);
echo form_hidden('date_creation', $date_creation);
echo form_hidden('title', $this->lang->line($title_key));
echo form_hidden('categorie', 0);

echo validation_errors();
echo ($this->gvvmetadata->form('ecritures', array(
	'date_op' => $date_op,
//	'annee_exercise' => $annee_exercise,
	'compte1' => $compte1,
	'compte2' => $compte2,
    'montant' => $montant,
    'description' => $description,
    'num_cheque' => $num_cheque,
//     'categorie' => $categorie,
	'gel' => $gel
)));

echo validation_button ($action);
echo form_close();

echo '</div>';
?>
<script type="text/javascript" src="<?php echo js_url('form_ecriture'); ?>"></script>
<?php

?>
