<!-- VIEW: application/views/bs_message.php -->
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
 * Simple vue pour afficher un message à l'utilisateur
 * 
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

echo '<div id="body" class="body container-fluid">';
if (isset($title))
	echo heading($title, 3);

if (isset($popup)) echo checkalert($this->session, $popup);

if (isset($text)) {
	echo(p($text));
}

if (isset($table)) {
    echo br();
    echo table_from_array($table, $attrs);
    echo br(2);

	$bar = array(
		array('label' => "Excel", 'url' =>"reports/export/csv/$request", 'role' => 'ca'),
		array('label' => "Pdf", 'url' =>"reports/export/pdf/$request", 'role' => 'ca'),
		);
	echo button_bar4($bar);

} 

echo '</div>';
?>