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
 * Page d'administration
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('welcome');

$attributes = array(
	'class' => 'boldlist',
	'id'    => 'mylist'
);

echo '<div id="body" class="body container-fluid">';

$title = $this->lang->line("welcome_accounts_title");
$title = 'A propos de GVV';

?>
<h2> <?= $title ?> </h2>
<?php
$list = [];

$list[] = '<a href="https://github.com/flub78/gvv/blob/main/README.md" target="_blank" >
Site du projet GVV</a>';

$list[] = "Site URL : " . $site_url;
$list[] = "Base URL : " . $base_url;
$list[] = "Version du : " . $commit_date;
$list[] = "Identifiant de version : " . $commit;
$list[] = "Dernier message git : " . $commit_message;
$list[] = "Répertoire d'installation : " . getcwd();
$list[] = "Who am I  : " . exec('whoami');
$list[] = '<a href="https://github.com/flub78/gvv/commits/main/" target="_blank" >
Liste des modifications</a>';
echo ul($list, $attributes);

echo '<hr>';

echo "<table class='table'>";
echo "<tr><th>Section</th><th>Date de clôture</th></tr>";
foreach ($dates_gel as $sect) {
	echo "<tr><td>" . $sect->nom . "</td><td>" . $sect->latest_cloture_date . "</td></tr>";
}
echo "</table>";


echo '<hr>';
$list = [];
$list[] = "Version de schéma requise par le programme : " . $program_level;
$list[] = "Version du schéma de la base de données : " . $base_level;

echo ul($list, $attributes);
?>
</div>