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
 */

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

$this->lang->load('welcome');

$gcalendar = $this->config->item('url_gcalendar');

echo '<div id="body" class="body ui-widget-content">';
$club = $this->config->item('club');

echo heading("welcome_title", 3);

echo p($this->lang->line("welcome_intro1"));
echo mailto($this->config->item('email_club'), $this->lang->line("welcome_admin")) . br(2);

if ($display_presences) {

    $options = $this->lang->line("welcome_options");
    $role = form_dropdown("role", $options, "", 'id="role"');
    $table = array(
        array($this->lang->line("welcome_attendance")),
        array($this->lang->line("welcome_date") . ": ", '<input type="text" name="date_ajout" value="" size="10" class="datepicker" title="JJ/MM/AAAA"  />' . nbs(3) .
            $this->lang->line("welcome_absent") . ": " . nbs() . '<input type="checkbox" name="absent" value="1" id="absent"   />'),
        array($this->lang->line("welcome_intent") . ": ", $role),
        array($this->lang->line("welcome_comment") . ": ", '<input type="text" name="commentaire" value="" size="32"  />'),
        array(form_input(array(
            'type' => 'submit',
            'name' => 'button',
            'value' => $this->lang->line("gvv_button_new")
        ))),
        array(nbs())
    );
    foreach ($mes_presences as $event) {
        $delete = $event['id'];
        $delete = anchor(base_url() . 'index.php' . '/presences/delete/' . $event['id'], 'Supprimer');
        $image = theme() . "/images/delete.png";
        $image_properties = array(
            'src' => $image,
            'class' => 'icon',
            'title' => 'delete'
        );
        $label = img($image_properties);
        $delete = anchor(base_url() . 'index.php' . '/presences/delete/' . $event['id'], $label);


        $date = date_db2ht($event['start']);
        $table[] = array($date, $delete);
    }
} else {
    $table = array();
}

echo "<table>\n";
echo "<tr><td>";
echo "<CENTER>";
echo '<iframe src="' . $gcalendar . '" style="border: 0" width="800" height="600" frameborder="0" scrolling="no"></iframe>';
echo '</CENTER></td><td style="vertical-align: top">';

$controller = 'presences';
echo form_open(controller_url($controller) . "/ajout/", array('name' => 'saisie'));
$CI = &get_instance();
echo form_hidden('mlogin', $CI->dx_auth->get_username());

display_form_table($table);

echo form_close();

echo "</td></tr>";
echo "</table>\n";

# $deconnexion = "https://www.google.com/calendar/logout";
# $compte = 'aeroclubdelasomme@free.fr';

echo br();

if ($club == 'accabs') {
    echo p('<div class="error">Les paiements par chèque doivent être remis exclusivement à Guillaume Pruvost,</div>');
    echo p('<div class="error">ceux en liquide à Mathieu Caudrelier.</div>');
    echo p(" Les chèques sont à rédiger à l'ordre de l'aéroclub d'Abbeville - section vol à voile.");
    echo br(2);
}

echo p($this->lang->line("welcome_intro2"));

echo '</div>';
