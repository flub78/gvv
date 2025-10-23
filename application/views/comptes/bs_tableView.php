<!-- VIEW: application/views/comptes/bs_tableView.php -->
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
 * Vue balance des comptes
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('comptes');
?>

<div id="body" class="body container-fluid">
	<?= checkalert($this->session) ?>
	<?php
	$title = $this->lang->line($title_key);
	if ($codec) {
		$title .= nbs() . $this->lang->line('comptes_label_class') . nbs() . $codec;
	}
	if ($codec2) {
		$title .= nbs() . $this->lang->line('comptes_label_to') . nbs() . $codec2;
	}
	if ($section) {
		$title .= " section " . $section['nom'];
	}
	?>
	<h3><?= $title ?></h3>
	<input type="hidden" name="controller_url" id="controller_url" value="<?= controller_url($controller) ?>" />


	<?php
	$values = $this->lang->line("comptes_balance_general");
	echo $this->lang->line("comptes_label_date") . ': <input type="text" name="balance_date" id="balance_date" value="' . $balance_date . '" size="15" title="JJ/MM/AAAA" class="datepicker" onchange=new_balance_date(); />';
	echo br(2);

	// --------------------------------------------------------------------------------------------------
	// Filtre
	?>
	<input type="hidden" name="filter_active" value="<?= $filter_active ?>" />
	<div class="accordion accordion-flush collapsed mb-3" id="accordionPanelsStayOpenExample">
		<div class="accordion-item">
			<h2 class="accordion-header" id="panelsStayOpen-headingOne">
				<button class="accordion-button" type="button" id="filter_button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
					<?= $this->lang->line("gvv_str_filter") ?>
				</button>
			</h2>
			<div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse  <?= $filter_active ? 'show' : '' ?>" aria-labelledby="panelsStayOpen-headingOne">
				<div class="accordion-body">
					<div>
						<form action="<?= controller_url($controller) . "/filterValidation/" ?>" method="post" accept-charset="utf-8" name="saisie">

							<div class="d-md-flex flex-row mb-2">
								<!-- Détaillée / générale-->
								<div class="me-3 mb-2">
									<?= $this->lang->line("comptes_label_balance") . " : " . radio_field('general', $general, $values, 'onchange=balance_general();') ?>
								</div>
							</div>

							<div class="d-md-flex flex-row  mb-2">
								<div class="me-3 mb-2">
									<?= $this->lang->line("comptes_label_soldes") . ": " . enumerate_radio_fields($this->lang->line("comptes_filter_active_select"), 'filter_solde', $filter_solde) ?>
								</div>

								<div class="me-3 mb-2">
								</div>
							</div>

							<div class="d-md-flex flex-row  mb-2">
								<div class="me-3 mb-2">
								</div>
							</div>

							<div class="d-md-flex flex-row  mb-2">
								<div class="me-3 mb-2">
								</div>
							</div>

							<div class="d-md-flex flex-row">
								<?= filter_buttons() ?>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

	</div>

	<?php
	// --------------------------------------------------------------------------------------------------
	// Data

	$solde_deb = '';
	$solde_cred = '';
	$total_debit = $total['debit'];
	$total_credit = $total['credit'];
	if ($total_debit > $total_credit) {
		$solde_deb = euro($total_debit - $total_credit);
	} else {
		$solde_cred = euro($total_credit - $total_debit);
	}

	$footer = [];


	if ($detail) {
		// $footer[] = ['', $this->lang->line("comptes_label_totals"), euro($total['debit']), euro($total['credit']), euro($total['solde_debit']), euro($total['solde_credit']), '', ''];
		$footer[] = ['', $this->lang->line("comptes_label_totals_balance"), '', euro($total['solde_debit']), euro($total['solde_credit']), ''];
	}

	if ($general) {
		$footer[] = ['', $this->lang->line("comptes_label_balance"), $solde_deb, $solde_cred, '', '', ''];

		$fields = array('codec', 'nom', 'solde_debit', 'solde_credit');
		$res = array();
		foreach ($select_result as $row) {
			//var_dump($row);
			$row['nom'] = anchor(site_url() . '/comptes/page/' . $row['codec'], $row['nom']);
			$res[] = $row;
		}
		$select_result = $res;
	} else {
		$footer[] = ['', $this->lang->line("comptes_label_balance"), '', $solde_deb, $solde_cred, '', ''];

		$fields = array('codec', 'id', 'section_name', 'solde_debit', 'solde_credit');
	}
	$attrs = array(
		'controller' => $controller,
		'actions' => array('edit', 'delete'),
		'mode' => ($has_modification_rights && $section && ! $general) ? "rw" : "ro",
		'footer' => $footer,
		'fields' => $fields,
		'class' => "datatable  table table-striped"
	);

	// Create button above the table
	echo '<div class="mb-3">'
		. '<a href="' . site_url('comptes/create') . '" class="btn btn-sm btn-success">'
		. '<i class="fas fa-plus" aria-hidden="true"></i> '
		. $this->lang->line('gvv_button_create')
		. '</a>'
		. '</div>';
	echo $this->gvvmetadata->table("vue_comptes", $attrs, $select_result, "");

	$csv_url = "$controller/balance_csv";
	$pdf_url = "$controller/balance_pdf";
	if (isset($codec)) {
		$csv_url .= "/$codec";
		$pdf_url .= "/$codec";
		if (isset($codec2)) {
			$csv_url .= "/$codec2";
			$pdf_url .= "/$codec2";
		}
	}
	$bar = array(
		array('label' => "Excel", 'url' => $csv_url, 'role' => 'ca'),
		array('label' => "Pdf", 'url' => $pdf_url, 'role' => 'ca'),
	);
	echo button_bar4($bar);

	echo br(2);
	echo p($this->lang->line("comptes_warning"));
	//echo "? " . $this->session->userdata('return_url');
	echo '</div>';

	?>

	<script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>