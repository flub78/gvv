<!-- VIEW: application/views/comptes/bs_bilanView.php -->
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
 * Bilan
 * 
 */
$this->load->library('table');
$this->load->library('DataTable');
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('comptes');

echo '<div id="body" class="body container-fluid">';

$title = $this->lang->line('gvv_comptes_title_bilan');
if ($section) {
	$title .= " section " . $section['nom'];
}

echo heading($title, 2, "");
echo year_selector($controller, $year, $year_selector);
echo br(2);
echo heading("date: $date", 3, "");
echo br();

if ((bool) $this->config->item('former_bilan_layout')) {
	$table = new DataTable(array(
			'title' => "",
			'values' => $bilan_table,
			'controller' => $controller,
			'class' => "sql_table fixed_datatable table",
			'create' => '',
			'count' => '',
			'first' => '',
			'align' => array('left', 'right', 'right', 'right', 'right', 'center', 'left', 'right', 'right')
	));
	$table->display();
}

if (isset($actif_detail_n) && isset($actif_detail_n1)) {
	echo heading('Bilan Actif', 3, '');

	$year_n = (int)$year;
	$year_n1 = $year_n - 1;

	$non_zero = function ($value) {
		return abs((float)$value) >= 0.005;
	};

	$show_line = function ($line_n, $line_n1) use ($non_zero) {
		return $non_zero($line_n['brut']) || $non_zero($line_n['amort']) || $non_zero($line_n['net']) || $non_zero($line_n1['net']);
	};

	echo '<table class="table table-sm table-bordered table-hover">';
	echo '<thead class="table-light">';
	echo '<tr>';
	echo '<th rowspan="2">Actif</th>';
	echo '<th class="text-center" colspan="3">31/12/' . $year_n . '</th>';
	echo '<th class="text-center" rowspan="1" colspan="1">31/12/' . $year_n1 . '</th>';
	echo '</tr>';
	echo '<tr>';
	echo '<th class="text-end">Brut</th>';
	echo '<th class="text-end">Amort. et depr.</th>';
	echo '<th class="text-end">Net</th>';
	echo '<th class="text-end">Net</th>';
	echo '</tr>';
	echo '</thead><tbody>';

	echo '<tr class="fw-bold table-secondary"><td colspan="5">Actif immobilise</td></tr>';

	if ($show_line($actif_detail_n['immobilisations_corporelles'], $actif_detail_n1['immobilisations_corporelles'])) {
		echo '<tr>';
		echo '<td>' . anchor(controller_url('comptes/balance/2/28'), 'Immobilisations corporelles') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['immobilisations_corporelles']['brut'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['immobilisations_corporelles']['amort'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['immobilisations_corporelles']['net'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n1['immobilisations_corporelles']['net'], ',', 'html') . '</td>';
		echo '</tr>';
	}

	if ($show_line($actif_detail_n['immobilisations_financieres'], $actif_detail_n1['immobilisations_financieres'])) {
		echo '<tr>';
		echo '<td>' . anchor(controller_url('comptes/balance/274/275'), 'Immobilisations financieres') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['immobilisations_financieres']['brut'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['immobilisations_financieres']['amort'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['immobilisations_financieres']['net'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n1['immobilisations_financieres']['net'], ',', 'html') . '</td>';
		echo '</tr>';
	}

	echo '<tr class="fw-bold table-secondary">';
	echo '<td>' . anchor(controller_url('comptes/balance/2/29'), 'Total actif immobilise') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif_immobilise']['brut'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif_immobilise']['amort'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif_immobilise']['net'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n1['total_actif_immobilise']['net'], ',', 'html') . '</td>';
	echo '</tr>';

	echo '<tr class="fw-bold table-secondary"><td colspan="5">Actif circulant</td></tr>';

	if ($show_line($actif_detail_n['creances_tiers'], $actif_detail_n1['creances_tiers'])) {
		echo '<tr>';
		echo '<td>' . anchor(controller_url('comptes/balance/4/5/1'), 'Creances de tiers') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['creances_tiers']['brut'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['creances_tiers']['amort'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['creances_tiers']['net'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n1['creances_tiers']['net'], ',', 'html') . '</td>';
		echo '</tr>';
	}

	if ($show_line($actif_detail_n['disponibilites'], $actif_detail_n1['disponibilites'])) {
		echo '<tr>';
		echo '<td>' . anchor(controller_url('comptes/balance/5/6/1'), 'Disponibilites') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['disponibilites']['brut'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['disponibilites']['amort'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['disponibilites']['net'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n1['disponibilites']['net'], ',', 'html') . '</td>';
		echo '</tr>';
	}

	echo '<tr class="fw-bold table-secondary">';
	echo '<td>' . anchor(controller_url('comptes/balance/4/6/1'), 'Total actif circulant') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif_circulant']['brut'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif_circulant']['amort'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif_circulant']['net'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n1['total_actif_circulant']['net'], ',', 'html') . '</td>';
	echo '</tr>';

	echo '<tr class="fw-bold table-primary">';
	echo '<td>' . anchor(controller_url('comptes/bilan'), 'Total actif') . '</td>';
	echo '<td class="text-end"></td>';
	echo '<td class="text-end"></td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n1['total_actif'], ',', 'html') . '</td>';
	echo '</tr>';

	echo '</tbody></table>';
}

if (isset($passif_detail_n) && isset($passif_detail_n1)) {
	echo br(2);
	echo heading('Bilan Passif', 3, '');

	$year_n = (int)$year;
	$year_n1 = $year_n - 1;

	$rows = [
		[anchor(controller_url('comptes/balance/102/103'), 'Fonds propres sans droit de reprise'), $passif_detail_n['fonds_propres_sans_droit_reprise'], $passif_detail_n1['fonds_propres_sans_droit_reprise'], false],
		[anchor(controller_url('comptes/balance/106/107'), 'Reserves'), $passif_detail_n['reserves'], $passif_detail_n1['reserves'], false],
		[anchor(controller_url('comptes/resultat'), 'Resultat'), $passif_detail_n['resultat'], $passif_detail_n1['resultat'], false],
		[anchor(controller_url('comptes/balance/13/14'), 'Subventions d\'investissement'), $passif_detail_n['subventions_investissement'], $passif_detail_n1['subventions_investissement'], false],
		[anchor(controller_url('comptes/balance/1/14'), 'Total des fonds reportes et dedies'), $passif_detail_n['total_fonds_reportes_dedies'], $passif_detail_n1['total_fonds_reportes_dedies'], true],
		[anchor(controller_url('comptes/balance/151/156'), 'Provisions pour risques'), $passif_detail_n['provisions_risques'], $passif_detail_n1['provisions_risques'], false],
		[anchor(controller_url('comptes/balance/157/159'), 'Provisions pour charges'), $passif_detail_n['provisions_charges'], $passif_detail_n1['provisions_charges'], false],
		[anchor(controller_url('comptes/balance/15/16'), 'Total des provisions'), $passif_detail_n['total_provisions'], $passif_detail_n1['total_provisions'], true],
		[anchor(controller_url('comptes/balance/4/5/1'), 'Dettes envers des tiers'), $passif_detail_n['avances_membres'], $passif_detail_n1['avances_membres'], false],
		[anchor(controller_url('comptes/balance/16/17/1'), 'Dettes financieres'), $passif_detail_n['dettes_financieres'], $passif_detail_n1['dettes_financieres'], false],
		[anchor(controller_url('comptes/balance/4/5/1'), 'Total des dettes'), $passif_detail_n['total_dettes'], $passif_detail_n1['total_dettes'], true],
		[anchor(controller_url('comptes/bilan'), 'Total du passif'), $passif_detail_n['total_passif'], $passif_detail_n1['total_passif'], true],
	];

	echo '<table class="table table-sm table-bordered table-hover">';
	echo '<colgroup>';
	echo '<col style="width:46%">';
	echo '<col style="width:13%">';
	echo '<col style="width:13%">';
	echo '<col style="width:14%">';
	echo '<col style="width:14%">';
	echo '</colgroup>';
	echo '<thead class="table-light"><tr>';
	echo '<th colspan="3">Passif</th>';
	echo '<th class="text-end">31/12/' . $year_n . '</th>';
	echo '<th class="text-end">31/12/' . $year_n1 . '</th>';
	echo '</tr></thead><tbody>';

	foreach ($rows as $r) {
		$class = $r[3] ? ' class="fw-bold table-secondary"' : '';
		echo '<tr' . $class . '>';
		echo '<td colspan="3">' . $r[0] . '</td>';
		echo '<td class="text-end">' . euro($r[1], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($r[2], ',', 'html') . '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
}

$bar = array(
	array('label' => "Excel", 'url' =>"comptes/export_bilan/csv", 'role' => 'ca'),
	array('label' => "Pdf", 'url' => "comptes/export_bilan/pdf", 'role' => 'ca'),
);
if (has_role('super-tresorier')) {
	$bar[] = array('label' => $this->lang->line('comptes_button_cloture'), 'url' => "comptes/cloture");
}
echo button_bar4($bar);

echo '</div>';
?>
