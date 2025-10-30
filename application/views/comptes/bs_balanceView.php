<!-- VIEW: application/views/comptes/bs_balanceView.php -->
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
 * Vue balance hiérarchique des comptes
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('comptes');
?>

<style>
/* Accordéons de balance hiérarchique */
.accordion-item {
    border: 2px solid #dee2e6;
    margin-bottom: 0.75rem;
    border-radius: 0.375rem;
    overflow: hidden;
}

.accordion-button {
    padding: 0;
    background-color: #f8f9fa;
    border: 2px solid #0d6efd;
    box-shadow: none;
    cursor: pointer;
}

.accordion-button:not(.collapsed) {
    background-color: #e7f1ff;
    color: #0d6efd;
    border: 2px solid #0d6efd;
}

.accordion-button:hover {
    background-color: #e9ecef;
}

.accordion-button:not(.collapsed):hover {
    background-color: #d3e5ff;
}

.accordion-button table {
    margin: 0;
    width: 100%;
    pointer-events: none;
}

/* Changement de couleur de tous les éléments du titre au survol */
.accordion-button:hover table thead tr,
.accordion-button:hover table tbody tr,
.accordion-button:hover table thead,
.accordion-button:hover table tbody {
    background-color: inherit !important;
}

.accordion-button:hover table th,
.accordion-button:hover table td {
    color: inherit;
    background-color: transparent !important;
}

/* Surcharge du style table-light au survol */
.accordion-button:hover .table-light {
    background-color: transparent !important;
}

.accordion-button::after {
    font-size: 1.5rem;
    font-weight: bold;
    margin-left: 1rem;
}

.accordion-body {
    padding: 0;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

.balance-header-table {
    width: 100%;
    margin-bottom: 0;
}

.balance-header-table th,
.balance-header-table td {
    padding: 0.75rem;
    border: none;
}

.balance-header-table th {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
    text-transform: uppercase;
}

.balance-header-table td {
    font-size: 1rem;
}

/* Animation visuelle pour l'ouverture/fermeture */
.accordion-collapse {
    transition: all 0.3s ease-in-out;
}

/* Style pour les datatables dans les accordéons */
.accordion-body .table {
    background-color: white;
    margin-bottom: 0;
}

/* Liens vers les opérations des comptes */
.balance-datatable-wrapper a:not(.btn) {
    color: #0d6efd;
    text-decoration: none;
}

.balance-datatable-wrapper a:not(.btn):hover {
    text-decoration: underline;
}

/* Wrapper avec bordure colorée autour de la datatable */
.balance-datatable-wrapper {
    border: 2px solid #0d6efd;
    border-radius: 0;
    padding: 0;
    background-color: white;
    margin: 0;
}

/* Supprimer tout padding du body de l'accordéon */
.accordion-body {
    padding: 0 !important;
}
</style>

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

							<div class="d-md-flex flex-row  mb-2">
								<div class="me-3 mb-2">
									<?= $this->lang->line("comptes_label_soldes") . ": " . enumerate_radio_fields($this->lang->line("comptes_filter_active_select"), 'filter_solde', $filter_solde) ?>
								</div>

								<div class="me-3 mb-2">
								</div>
							</div>

							<div class="d-md-flex flex-row  mb-2">
								<div class="me-3 mb-2">
									<?= $this->lang->line("gvv_comptes_field_masked") . ": " . enumerate_radio_fields($this->lang->line("gvv_comptes_filter_masked"), 'filter_masked', $filter_masked) ?>
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
	$footer[] = ['', $this->lang->line("comptes_label_balance"), $solde_deb, $solde_cred, '', ''];

	// Chargement du helper balance
	$this->load->helper('balance');

	// Boutons développer/réduire tout
	echo '<div class="mb-3">';
	if ($has_modification_rights && $section) {
		echo '<a href="' . site_url('comptes/create') . '" class="btn btn-sm btn-success me-2">'
			. '<i class="fas fa-plus" aria-hidden="true"></i> '
			. $this->lang->line('gvv_button_create')
			. '</a>';
	}
	echo '<button type="button" class="btn btn-sm btn-primary me-2" id="expand-all">'
		. '<i class="fas fa-chevron-down" aria-hidden="true"></i> '
		. $this->lang->line('gvv_comptes_expand_all')
		. '</button>';
	echo '<button type="button" class="btn btn-sm btn-secondary" id="collapse-all">'
		. '<i class="fas fa-chevron-up" aria-hidden="true"></i> '
		. $this->lang->line('gvv_comptes_collapse_all')
		. '</button>';
	echo '</div>';

	// Accordéon Bootstrap pour la balance hiérarchique
	?>
	<div class="accordion" id="balanceAccordion">
		<?php
		$index = 0;
		foreach ($result_general as $general_row):
			$codec = $general_row['codec'];
			$details = isset($details_by_codec[$codec]) ? $details_by_codec[$codec] : array();
			echo balance_accordion_item($general_row, $details, $index, $this->gvvmetadata, $controller, $has_modification_rights, $section);
			$index++;
		endforeach;
		?>
	</div>

	<!-- Totaux -->
	<div class="card mt-3">
		<div class="card-body">
			<table class="table table-sm mb-0">
				<thead>
					<tr>
						<th style="width: 50%"><?= $this->lang->line("comptes_label_balance") ?></th>
						<th style="width: 25%" class="text-end"><?= $this->gvvmetadata->label('vue_comptes', 'solde_debit') ?></th>
						<th style="width: 25%" class="text-end"><?= $this->gvvmetadata->label('vue_comptes', 'solde_credit') ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong><?= $this->lang->line("comptes_label_balance") ?></strong></td>
						<td class="text-end"><strong><?= $footer[0][2] ?></strong></td>
						<td class="text-end"><strong><?= $footer[0][3] ?></strong></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<?php
	$csv_url = "$controller/balance_hierarchical_csv";
	$pdf_url = "$controller/balance_hierarchical_pdf";
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
	echo '</div>';

	?>

	<script type="text/javascript">
	function initializeBalanceAccordion() {
		// D'ABORD: Gérer les boutons expand/collapse (priorité!)
		// Gérer le bouton "Développer tout"
		var expandBtn = document.getElementById('expand-all');
		if (expandBtn) {
			// Retirer les anciens listeners si présents
			expandBtn.replaceWith(expandBtn.cloneNode(true));
			expandBtn = document.getElementById('expand-all');

			expandBtn.addEventListener('click', function(e) {
				e.preventDefault();
				var buttons = document.querySelectorAll('#balanceAccordion .accordion-button.collapsed');
				if (buttons.length === 0) {
					buttons = document.querySelectorAll('#balanceAccordion .accordion-button');
				}
				buttons.forEach(function(button) {
					button.click();
				});
			});
		}

		// Gérer le bouton "Réduire tout"
		var collapseBtn = document.getElementById('collapse-all');
		if (collapseBtn) {
			// Retirer les anciens listeners si présents
			collapseBtn.replaceWith(collapseBtn.cloneNode(true));
			collapseBtn = document.getElementById('collapse-all');

			collapseBtn.addEventListener('click', function(e) {
				e.preventDefault();
				var buttons = document.querySelectorAll('#balanceAccordion .accordion-button:not(.collapsed)');
				buttons.forEach(function(button) {
					button.click();
				});
			});
		}

		// ENSUITE: Initialiser les datatables (après les boutons)
		var datatables = document.querySelectorAll('.searchable_datatable');

		// Vérifier si jQuery et DataTables sont chargés
		if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
			datatables.forEach(function(table) {
				try {
					if ($.fn.DataTable.isDataTable(table)) {
						$(table).DataTable().destroy();
					}
					$(table).DataTable({
						"paging": true,
						"pageLength": 10,
						"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tous"]],
						"searching": true,
						"ordering": true,
						"info": true,
						"autoWidth": false,
						"language": {
							"url": "<?= base_url('assets/js/datatables/French.json') ?>"
						}
					});
				} catch (e) {
					console.error('Error initializing DataTable:', e);
				}
			});
		}
	}

	// Essayer plusieurs méthodes pour s'assurer que le code s'exécute
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializeBalanceAccordion);
	} else {
		initializeBalanceAccordion();
	}
	</script>

	<script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>
