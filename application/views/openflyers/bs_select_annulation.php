<!-- VIEW: application/views/openflyers/bs_select_annulation.php -->
<?php
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
//
//    base restauration view

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('openflyers');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_of_annulation", 3);

if (!empty($error)) {
	echo '<div class="alert alert-danger">' . $error . '</div>';
}
echo p($this->lang->line("gvv_of_explain_annulation"));

?>
<p>Cependant il peut arriver qu'un administrateur modifie une opération après la synchronisation, en remontant parfois plusieurs semaines en arrière et introduise une désynchronisation. Si la modification est une modification de valeur, il suffit de resynchroniser les opérations concernées. S'il s'agit d'un ajout, il suffit de synchroniser les opérations ajoutées. S'il s'agit d'une suppression il faut alors supprimer toutes les opérations synchronisées dans GVV pour la période concernée, puis resynchroniser la période.</p>

<a href="https://openflyers.com/abbeville/index.php?menuAction=admin_view_favorite_generic_report&menuParameter=141">Balance des comptes utilisateurs</a>
<?php
echo p($this->lang->line("gvv_of_warning2"), 'class="error"');

echo form_open_multipart('openflyers/cancel_operations');
echo "Date de debut: " . '<input type="date" name="start_date" size="50" class="mt-4"/><br>';
echo "Date de fin: " . '<input type="date" name="end_date" size="50" class="mt-4"/><br>';
echo "Supprime aussi les écritures non issues d'OpenFlyers: " . '<input type="checkbox"'
                        . ' name="cb_all"'
                        . ' ><br><br>';


echo form_input(array(
	'type' => 'submit',
	'name' => 'button',
	'value' => $this->lang->line("gvv_button_validate"),
	'class' => 'btn btn-primary'
));
echo form_close('</div>');
