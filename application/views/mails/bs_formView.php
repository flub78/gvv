<!-- VIEW: application/views/mails/bs_formView.php -->
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
 * Formulaire de saisie des mails
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('mails');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo heading("gvv_mails_title", 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
echo form_hidden('id', $id);

// echo validation_errors();
echo ($this->mailmetadata->form('mails', array(
    'titre' => $titre,
    'selection' => $selection,
    'destinataires' => $destinataires,
    'copie_a' => $copie_a,
    'individuel' => $individuel,
    'date_envoie' => $date_envoie,
    'texte' => $texte,
    'debut_facturation' => $debut_facturation,
    'fin_facturation' => $fin_facturation
)));
echo br();
echo button_bar2("$controller/buttons/$action", array('Valider' => "button", 'Envoyer' => "button"));

echo br();
echo p($this->lang->line("mails_help"));
echo form_close();

echo '</div>';
?>
<script type="text/javascript" src="<?php echo js_url('form_emails'); ?>"></script>