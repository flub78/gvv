<!-- VIEW: application/views/bs_alarmes.php -->
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

/**
 * display in different colors
 * @param unknown $assert
 * @param unknown $str
 */
function check_that($assert, $str) {
	$class = ($assert) ? 'success' : 'error';
	echo '<div class="' . $class . '">';
	echo $str;
	echo '</div>';	
}

echo '<div id="body" class="body container-fluid">';
if (isset($title))
	echo heading($title, 3);

if (isset($popup)) echo checkalert($this->session, $popup);

if (isset($text)) {
	echo(p($text));
}

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

if (true || $this->dx_auth->is_role('ca', true, true)) {
echo 'Pilote: ' 
		. dropdown_field('mlogin', $mlogin, $pilote_selector, "id='selector' onchange='new_alarm();'")
		.br(2);
} else {
	echo $pilot_name . br(2);
}

echo form_fieldset('Licence assurance', array(
		'title' => $this->lang->line("gvv_str_filter_tooltip")));

# Licence fédérale
if ($licence_ok) {
	echo '<div class="success">';
	echo p("Vous avez une licence/assurance pour l'année " . $year); 
	echo p($licence_type); 
	echo '</div>';
} else {
	echo '<div class="error">';
	echo p("Vous n'avez pas de licence/assurance pour l'année " . $year);
	echo p($licence_type); 
	echo '</div>';
}
echo form_fieldset_close();

# Visite médicale
echo form_fieldset('Visite médicale', array(
		'title' => $this->lang->line("gvv_str_filter_tooltip")));
check_that($medical_ok, p($medical_message));

echo form_fieldset_close();


echo form_fieldset('Renouvellement licence', array(
		'title' => $this->lang->line("gvv_str_filter_tooltip")));

echo heading("Si vous avez un brevet de pilote de planeur (licence française)", 4);

if ($brevet) {
	if ($depuis_test < 2) {
		echo '<div class="success">';
		echo p('Brevet de pilote de planeur du ' . date_db2ht($brevet));
		echo p("Dernier test en vol le "
				. date_db2ht($date_test)
				. " (moins de 2 ans), pas de conditions d'expérience exigées.");
		echo '</div>';
	} elseif ($depuis_test < 6) {
		echo '<div class="success">';
		echo p('Dernier contrôle en vol du ' . date_db2ht($date_test) . ' (moins de 6 ans)');
		echo '</div>';
	} else {
		echo '<div class="error">';
		echo p('Dernier contrôle en vol du ' . date_db2ht($date_test) . ' (plus de 6 ans)');
		echo '</div>';
	}
	
} else {
	echo p('Pas de brevet de pilote de planeur');	
}
$ok = (($heures_cdb_2_ans >= 6) && ($vols_cdb_2_ans >= 10))
 	|| (($heures_cdb_2_ans >= 3) && ($vols_cdb_2_ans >= 5) && ($vols_en_double_depuis_2_ans >= 2));
$exp = "Heures CDB = $heures_cdb_2_ans, Vols CDB = $vols_cdb_2_ans, Vols en double = $vols_en_double_depuis_2_ans";
echo "Expérience des 24 derniers mois";
check_that($ok, $exp);
echo br();
echo p("Brevet ou test en vol depuis moins de 2 ans, vous pouvez voler.");
echo p("Sinon, il faut satisfaire aux conditions d'expérience: 6 heures et 10 décollage Cdb dans les 24 derniers mois ou 3 heures, 5 décollage et deux vols en double commande avec un instructeur.");

echo heading("Si vous avez une SPL (licence européenne)", 4);
echo "Expérience des 24 derniers mois";
$ok = (($heures_cdb_2_ans >= 5) && ($vols_cdb_2_ans >= 15));
$exp = "Heures CDB = $heures_cdb_2_ans, Vols CDB = $vols_cdb_2_ans";
check_that($ok, $exp);
$ok = ($vols_en_double_depuis_2_ans >= 2);
$exp = "Vols en double = $vols_en_double_depuis_2_ans";
check_that($ok, $exp);
check_that($autonome_2_ans >= 5, "Décollage autonome = " . $autonome_2_ans);
check_that($treuille_2_ans >= 5, "Décollage treuil = " . $treuille_2_ans);
check_that($rem_2_ans >= 5, "Décollage remorquage = " . $rem_2_ans);
echo br();

echo p("Brevet ou test en vol depuis moins de 2 ans, vous pouvez voler.");
echo p("Sinon, il faut satisfaire aux conditions d'expérience: 5 heures et 15 décollage Cdb dans les 24 derniers mois et deux vols en double commande avec un instructeur.");
echo p("De plus il vous faut 5 vols sur votre moyen de lancement.");
echo form_fieldset_close();

echo form_fieldset('Emport passager', array(
		'title' => $this->lang->line("gvv_str_filter_tooltip")));
if ($emport_passager) {
	echo p("Autorisation d'emport passager du " . date_db2ht($emport_passager) );
	
	check_that($vols_cdb_depuis_90_jours > 3, "Vols CDB depuis 90 jours = " . $vols_cdb_depuis_90_jours);
	
} else {
	echo p("Pas d'autorisation d'emport passager.");
	
}
echo br();
echo p("Vous devez avoir l'emport passager et 3 décollages commandant de bord dans les trois derniers mois pour pouvoir emmener un passager.");
echo form_fieldset_close();
echo form_fieldset_close();

echo form_fieldset('Instructeur', array(
		'title' => $this->lang->line("gvv_str_filter_tooltip")));
		
if ($validity_inst) {
	if ($inst_valid) {
		echo '<div class="success">';
		echo p("Qualification instructeur valide jusqu'au " . date_db2ht($validity_inst));
		echo '</div>';
	} else {
		echo '<div class="error">';
		$str = "Qualification instructeur périmée";
		if ($validity_inst != '0000-00-00') {
			$str .= " le " . date_db2ht($validity_inst);
		}
		echo p($str);
		echo '</div>';
	}
} else {
	echo p ("Non instructeur.");
}
echo form_fieldset_close();

echo br();
echo "Seul le commandant de bord est responsable de savoir s'il remplit les conditions pour voler. Cette page n'est qu'une aide fournie à titre indicatif. " 
		. " Elle n'engage pas la responsabilité du club et ne prend pas en compte les vols que vous pouvez avoir fait dans d'autres clubs.";
echo p("Fiches pratiques sur le site " . anchor('http://www.ato.cnvv.net/', 'ATO-CNVV'));
echo '</div>';
?>