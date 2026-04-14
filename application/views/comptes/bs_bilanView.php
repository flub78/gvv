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

// Show success message (e.g. after clôture)
if ($this->session->flashdata('success')) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-check-circle"></i></strong> ';
    echo nl2br(htmlspecialchars($this->session->flashdata('success')));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

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
	$lbl_title_actif = $this->lang->line('comptes_bilan_title_actif');
	$lbl_actif = $this->lang->line('comptes_bilan_actif');
	$lbl_brut = $this->lang->line('comptes_bilan_valeur_brute');
	$lbl_amort_depr = $this->lang->line('comptes_bilan_amort_depr');
	$lbl_net = $this->lang->line('comptes_bilan_valeur_nette');
	$lbl_actif_immobilise = $this->lang->line('comptes_bilan_actif_immobilise');
	$lbl_immobilisations_corp = $this->lang->line('comptes_bilan_immobilisations_corp');
	$lbl_immobilisations_financieres = $this->lang->line('comptes_bilan_immobilisations_financieres');
	$lbl_total_actif_immobilise = $this->lang->line('comptes_bilan_total_actif_immobilise');
	$lbl_actif_circulant = $this->lang->line('comptes_bilan_actif_circulant');
	$lbl_stocks = $this->lang->line('comptes_bilan_stocks');
	$lbl_creances_tiers = $this->lang->line('comptes_bilan_creances_tiers');
	$lbl_disponibilites = $this->lang->line('comptes_bilan_dispo');
	$lbl_total_actif_circulant = $this->lang->line('comptes_bilan_total_actif_circulant');
	$lbl_total_actif = $this->lang->line('comptes_bilan_total_actif');

	echo heading($lbl_title_actif, 3, '');

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
	echo '<th rowspan="2">' . $lbl_actif . '</th>';
	echo '<th class="text-center" colspan="3">31/12/' . $year_n . '</th>';
	echo '<th class="text-center" rowspan="1" colspan="1">31/12/' . $year_n1 . '</th>';
	echo '</tr>';
	echo '<tr>';
	echo '<th class="text-end">' . $lbl_brut . '</th>';
	echo '<th class="text-end">' . $lbl_amort_depr . '</th>';
	echo '<th class="text-end">' . $lbl_net . '</th>';
	echo '<th class="text-end">' . $lbl_net . '</th>';
	echo '</tr>';
	echo '</thead><tbody>';

	echo '<tr class="fw-bold table-secondary"><td colspan="5">' . $lbl_actif_immobilise . '</td></tr>';

	if ($show_line($actif_detail_n['immobilisations_corporelles'], $actif_detail_n1['immobilisations_corporelles'])) {
		echo '<tr>';
		echo '<td>' . anchor(controller_url('comptes/balance/2/28'), $lbl_immobilisations_corp) . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['immobilisations_corporelles']['brut'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . anchor(controller_url('comptes/balance/281'), euro($actif_detail_n['immobilisations_corporelles']['amort'], ',', 'html')) . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['immobilisations_corporelles']['net'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n1['immobilisations_corporelles']['net'], ',', 'html') . '</td>';
		echo '</tr>';
	}

	if ($show_line($actif_detail_n['immobilisations_financieres'], $actif_detail_n1['immobilisations_financieres'])) {
		echo '<tr>';
		echo '<td>' . anchor(controller_url('comptes/balance/274/275'), $lbl_immobilisations_financieres) . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['immobilisations_financieres']['brut'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['immobilisations_financieres']['amort'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['immobilisations_financieres']['net'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n1['immobilisations_financieres']['net'], ',', 'html') . '</td>';
		echo '</tr>';
	}

	echo '<tr class="fw-bold table-secondary">';
	echo '<td>' . anchor(controller_url('comptes/balance/2/29'), $lbl_total_actif_immobilise) . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif_immobilise']['brut'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif_immobilise']['amort'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif_immobilise']['net'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n1['total_actif_immobilise']['net'], ',', 'html') . '</td>';
	echo '</tr>';

	echo '<tr class="fw-bold table-secondary"><td colspan="5">' . $lbl_actif_circulant . '</td></tr>';

	if ($show_line($actif_detail_n['stocks'], $actif_detail_n1['stocks'])) {
		echo '<tr>';
		echo '<td>' . anchor(controller_url('comptes/balance/37/38'), $lbl_stocks) . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['stocks']['brut'], ',', 'html') . '</td>';
		echo '<td class="text-end"></td>';
		echo '<td class="text-end">' . euro($actif_detail_n['stocks']['net'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n1['stocks']['net'], ',', 'html') . '</td>';
		echo '</tr>';
	}

	if ($show_line($actif_detail_n['creances_tiers'], $actif_detail_n1['creances_tiers'])) {
		echo '<tr>';
		echo '<td>' . anchor(controller_url('comptes/balance/4/5/1'), $lbl_creances_tiers) . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['creances_tiers']['brut'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['creances_tiers']['amort'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['creances_tiers']['net'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n1['creances_tiers']['net'], ',', 'html') . '</td>';
		echo '</tr>';
	}

	if ($show_line($actif_detail_n['disponibilites'], $actif_detail_n1['disponibilites'])) {
		echo '<tr>';
		echo '<td>' . anchor(controller_url('comptes/balance/5/6/1'), $lbl_disponibilites) . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['disponibilites']['brut'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['disponibilites']['amort'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n['disponibilites']['net'], ',', 'html') . '</td>';
		echo '<td class="text-end">' . euro($actif_detail_n1['disponibilites']['net'], ',', 'html') . '</td>';
		echo '</tr>';
	}

	echo '<tr class="fw-bold table-secondary">';
	echo '<td>' . anchor(controller_url('comptes/balance/4/6/1'), $lbl_total_actif_circulant) . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif_circulant']['brut'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif_circulant']['amort'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif_circulant']['net'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n1['total_actif_circulant']['net'], ',', 'html') . '</td>';
	echo '</tr>';

	echo '<tr class="fw-bold table-primary">';
	echo '<td>' . anchor(controller_url('comptes/bilan'), $lbl_total_actif) . '</td>';
	echo '<td class="text-end"></td>';
	echo '<td class="text-end"></td>';
	echo '<td class="text-end">' . euro($actif_detail_n['total_actif'], ',', 'html') . '</td>';
	echo '<td class="text-end">' . euro($actif_detail_n1['total_actif'], ',', 'html') . '</td>';
	echo '</tr>';

	echo '</tbody></table>';
}

if (isset($passif_detail_n) && isset($passif_detail_n1)) {
	$lbl_title_passif = $this->lang->line('comptes_bilan_title_passif');
	$lbl_passif = $this->lang->line('comptes_bilan_passif');
	$lbl_section_fonds_propres = $this->lang->line('comptes_bilan_section_fonds_propres');
	$lbl_fonds_propres_sans_droit_reprise = $this->lang->line('comptes_bilan_fonds_propres_sans_droit_reprise');
	$lbl_reserves = $this->lang->line('comptes_bilan_reserves');
	$lbl_resultat = $this->lang->line('comptes_bilan_resultat');
	$lbl_subventions_investissement = $this->lang->line('comptes_bilan_subventions_investissement');
	$lbl_total_fonds_reportes_dedies = $this->lang->line('comptes_bilan_total_fonds_reportes_dedies');
	$lbl_provisions_risques = $this->lang->line('comptes_bilan_provisions_risques');
	$lbl_provisions_charges = $this->lang->line('comptes_bilan_provisions_charges');
	$lbl_total_provisions = $this->lang->line('comptes_bilan_total_provisions');
	$lbl_dettes = $this->lang->line('comptes_bilan_dettes');
	$lbl_section_dettes_financieres = $this->lang->line('comptes_bilan_section_dettes_financieres');
	$lbl_dettes_tiers = $this->lang->line('comptes_bilan_dettes_tiers');
	$lbl_dettes_financieres = $this->lang->line('comptes_bilan_dettes_financieres');
	$lbl_dettes_exploitation = $this->lang->line('comptes_bilan_dettes_exploitation');
	$lbl_dettes_fournisseurs = $this->lang->line('comptes_bilan_dettes_fournisseurs');
	$lbl_dettes_fiscales_sociales = $this->lang->line('comptes_bilan_dettes_fiscales_sociales');
	$lbl_dettes_diverses = $this->lang->line('comptes_bilan_dettes_diverses');
	$lbl_autres_crediteurs = $this->lang->line('comptes_bilan_autres_crediteurs');
	$lbl_total_dettes = $this->lang->line('comptes_bilan_total_dettes');
	$lbl_total_passif = $this->lang->line('comptes_bilan_total_passif');

	echo br(2);
	echo heading($lbl_title_passif, 3, '');

	$year_n = (int)$year;
	$year_n1 = $year_n - 1;

	$rows = [
		[$lbl_section_fonds_propres, null, null, false, true],
		[anchor(controller_url('comptes/balance/102/103'), $lbl_fonds_propres_sans_droit_reprise), $passif_detail_n['fonds_propres_sans_droit_reprise'], $passif_detail_n1['fonds_propres_sans_droit_reprise'], false],
		[anchor(controller_url('comptes/balance/106/107'), $lbl_reserves), $passif_detail_n['reserves'], $passif_detail_n1['reserves'], false],
		[anchor(controller_url('comptes/resultat'), $lbl_resultat), $passif_detail_n['resultat'], $passif_detail_n1['resultat'], false],
		[anchor(controller_url('comptes/balance/13/14'), $lbl_subventions_investissement), $passif_detail_n['subventions_investissement'], $passif_detail_n1['subventions_investissement'], false],
		[anchor(controller_url('comptes/balance/1/14'), $lbl_total_fonds_reportes_dedies), $passif_detail_n['total_fonds_reportes_dedies'], $passif_detail_n1['total_fonds_reportes_dedies'], true],
		[anchor(controller_url('comptes/balance/151/156'), $lbl_provisions_risques), $passif_detail_n['provisions_risques'], $passif_detail_n1['provisions_risques'], false],
		[anchor(controller_url('comptes/balance/157/159'), $lbl_provisions_charges), $passif_detail_n['provisions_charges'], $passif_detail_n1['provisions_charges'], false],
		[anchor(controller_url('comptes/balance/15/16'), $lbl_total_provisions), $passif_detail_n['total_provisions'], $passif_detail_n1['total_provisions'], true],
		[$lbl_dettes, null, null, false, true],
		[$lbl_section_dettes_financieres, null, null, false, true],
		[anchor(controller_url('comptes/balance/411/412'), $lbl_dettes_tiers), $passif_detail_n['avances_membres'], $passif_detail_n1['avances_membres'], false],
		[anchor(controller_url('comptes/balance/16/17/1'), $lbl_dettes_financieres), $passif_detail_n['dettes_financieres'], $passif_detail_n1['dettes_financieres'], false],
		[$lbl_dettes_exploitation, null, null, false, true],
		[anchor(controller_url('comptes/balance/40/41'), $lbl_dettes_fournisseurs), $passif_detail_n['dettes_fournisseurs'], $passif_detail_n1['dettes_fournisseurs'], false],
		[anchor(controller_url('comptes/balance/42/44'), $lbl_dettes_fiscales_sociales), $passif_detail_n['dettes_fiscales_sociales'], $passif_detail_n1['dettes_fiscales_sociales'], false],
		[$lbl_dettes_diverses, null, null, false, true],
		[anchor(controller_url('comptes/balance/44/47'), $lbl_autres_crediteurs), $passif_detail_n['autres_crediteurs'], $passif_detail_n1['autres_crediteurs'], false],
		[anchor(controller_url('comptes/balance/4/5/1'), $lbl_total_dettes), $passif_detail_n['total_dettes'], $passif_detail_n1['total_dettes'], true],
		[anchor(controller_url('comptes/bilan'), $lbl_total_passif), $passif_detail_n['total_passif'], $passif_detail_n1['total_passif'], true, 'primary'],
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
	echo '<th colspan="3">' . $lbl_passif . '</th>';
	echo '<th class="text-end">31/12/' . $year_n . '</th>';
	echo '<th class="text-end">31/12/' . $year_n1 . '</th>';
	echo '</tr></thead><tbody>';

	foreach ($rows as $r) {
		if (isset($r[4]) && $r[4] === true) {
			echo '<tr class="fw-bold table-secondary"><td colspan="5">' . $r[0] . '</td></tr>';
			continue;
		}
		$row_color = (isset($r[4]) && $r[4] === 'primary') ? 'table-primary' : 'table-secondary';
		$class = $r[3] ? ' class="fw-bold ' . $row_color . '"' : '';
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
