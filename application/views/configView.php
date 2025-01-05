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
//
// Formulaires pour la configuration
//
// ----------------------------------------------------------------------------------------

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

$this->lang->load('config');

echo '<div id="body" class="body ui-widget-content">';

if (isset($message)) {
	echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");
echo heading("gvv_config_title", 3);

echo form_hidden('logo_club', $logo_club);

echo form_open_multipart(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

$table = array();
$row = 0;
$table[$row][] = $this->lang->line("gvv_config_field_acronym") . ": ";
$table[$row][] = input_field('sigle_club', $sigle_club, array('type'  => 'text', 'size' => '40'));
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_name") . ": ";
$table[$row][] = input_field('nom_club', $nom_club, array('type'  => 'text', 'size' => '100'));
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_id") . ": ";
$table[$row][] = input_field('code_club', $code_club, array('type'  => 'text', 'size' => '20'));
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_adresse") . ": ";
$table[$row][] = input_field('adresse_club', $adresse_club, array('type'  => 'text', 'size' => '100'));
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_zip") . ": ";
$table[$row][] = input_field('cp_club', $cp_club, array('type'  => 'text', 'size' => '10'));
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_city") . ": ";
$table[$row][] = input_field('ville_club', $ville_club, array('type'  => 'text', 'size' => '100'));
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_tel") . ": ";
$table[$row][] = input_field('tel_club', $tel_club, array('type'  => 'text'));
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_email") . ": ";
$table[$row][] = input_field('email_club', $email_club, array('type'  => 'text', 'size' => 50));
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_web") . ": ";
$table[$row][] = input_field('url_club', $url_club, array('type'  => 'text', 'size' => 50));
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_gcalendar") . ": ";
$table[$row][] = input_field('calendar_id', $calendar_id, array('type'  => 'text', 'size' => 50));
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_theme_graphic") . ": ";
$options = $fields['theme']['options'];
$table[$row][] = dropdown_field('theme', $theme, $options, "");
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_palette") . ": ";
$table[$row][] = dropdown_field('palette', $palette, $colors, "");
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_facturation") . ": ";
$options = array('accabs'  => 'accabs', 'aces' => 'aces');
$table[$row][] = input_field('club', $club, array('type'  => 'text', 'size' => 32));
// dropdown_field('club', $club, $options, "");
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_gcalendar_url") . ": ";
$table[$row][] = input_field('url_gcalendar', $url_gcalendar, array('type'  => 'text', 'size' => '100'));
$row++;


$table[$row][] = $this->lang->line("gvv_config_field_planche_auto") . ": ";
$table[$row][] = input_field('url_planche_auto', $url_planche_auto, array('type'  => 'text', 'size' => '100'));
$row++;


$table[$row][] = nbs();
$row++;

$table[$row][] = $this->lang->line("gvv_config_field_logo") . ": ";
$table[$row][] = img($logo_club) . '<br><input type="file" name="userfile" size="20" />';
$row++;

$table[$row][] = $this->lang->line("gvv_config_mod") . ": ";
$table[$row][] = form_textarea(array(
	'name' => 'mod',
	'value' => $mod,
	'rows' => '10',
	'cols' => '80'
));
$row++;

$table[$row][] = $this->lang->line("gvv_config_ffvv_id") . ": ";
$table[$row][] = input_field('ffvv_id', $ffvv_id, array('type'  => 'text', 'size' => '8'));
$row++;

$table[$row][] = $this->lang->line("gvv_config_ffvv_pwd") . ": ";
$table[$row][] = input_field('ffvv_pwd', $ffvv_pwd, array('type'  => 'text', 'size' => '36'));
$row++;


$table[$row][] = $this->lang->line("gvv_config_ffvv_product") . ": ";
$table[$row][] = dropdown_field('ffvv_product', $ffvv_product, $product_selector, "");
// input_field('ffvv_product', $ffvv_product, array('type'  => 'text', 'size' => '36'));
$row++;

$table[$row][] = $this->lang->line("gvv_config_gesasso") . ": ";
$table[$row][] = checkbox_field('gesasso', $gesasso, '');
$row++;

$table[$row][] = nbs();
$row++;

display_form_table($table);

// Le boutton de validation
$retour = new Button(array(
	'label' => "Retour",
	'controller' => $controller,
	'action' => 'page',
	'param' => ''
));
echo "<table><tr><td>\n";
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_button_validate")));
echo form_close();
echo "</td><td>";
echo "</td></tr></table>\n";

echo '</div>';
