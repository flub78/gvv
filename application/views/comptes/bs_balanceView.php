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
/* Accordéons de balance hiérarchique - seulement pour #balanceAccordion */
#balanceAccordion .accordion-item {
    border: 2px solid #dee2e6;
    margin-bottom: 0.75rem;
    border-radius: 0.375rem;
    overflow: hidden;
}

#balanceAccordion .accordion-button {
    padding: 0;
    background-color: #d3e5ff;
    border: 2px solid #0d6efd;
    box-shadow: none;
    cursor: pointer;
}

#balanceAccordion .accordion-button:not(.collapsed) {
    background-color: #d3e5ff;
    color: #0d6efd;
    border: 2px solid #0d6efd;
}

#balanceAccordion .accordion-button:hover {
    background-color: #d1d5db;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

#balanceAccordion .accordion-button:not(.collapsed):hover {
    background-color: #d1d5db;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

#balanceAccordion .accordion-button table {
    margin: 0;
    width: 100%;
    pointer-events: none;
}

/* Forcer la couleur de fond du thead à être transparente pour hériter du bouton */
#balanceAccordion .accordion-button table thead,
#balanceAccordion .accordion-button table thead tr,
#balanceAccordion .accordion-button table thead.table-light {
    background-color: transparent !important;
}

#balanceAccordion .accordion-button table thead th {
    background-color: transparent !important;
}

#balanceAccordion .accordion-button table tbody tr {
    background-color: transparent !important;
}

#balanceAccordion .accordion-button table tbody td {
    background-color: transparent !important;
}

/* Changement de couleur de tous les éléments du titre au survol */
#balanceAccordion .accordion-button:hover table thead tr,
#balanceAccordion .accordion-button:hover table tbody tr,
#balanceAccordion .accordion-button:hover table thead,
#balanceAccordion .accordion-button:hover table tbody {
    background-color: inherit !important;
}

#balanceAccordion .accordion-button:hover table th,
#balanceAccordion .accordion-button:hover table td {
    color: inherit;
    background-color: transparent !important;
}

/* Surcharge du style table-light au survol */
#balanceAccordion .accordion-button:hover .table-light {
    background-color: transparent !important;
}

#balanceAccordion .accordion-button::after {
    font-size: 1.5rem;
    font-weight: bold;
    margin-left: 1rem;
}

#balanceAccordion .accordion-body {
    padding: 0 !important;
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

.balance-header-table thead {
    background-color: transparent !important;
}

.balance-header-table thead tr {
    background-color: transparent !important;
}

.balance-header-table td {
    font-size: 1rem;
}

/* Animation visuelle pour l'ouverture/fermeture */
#balanceAccordion .accordion-collapse {
    transition: all 0.3s ease-in-out;
}

/* Style pour les datatables dans les accordéons */
#balanceAccordion .accordion-body .table {
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

	// Boutons développer/réduire tout et recherche
	echo '<div class="mb-3 d-flex align-items-center">';
	echo '<div class="me-auto">';
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
	echo '<button type="button" class="btn btn-sm btn-secondary me-2" id="collapse-all">'
		. '<i class="fas fa-chevron-up" aria-hidden="true"></i> '
		. $this->lang->line('gvv_comptes_collapse_all')
		. '</button>';
	echo '</div>';
	echo '<div class="d-flex align-items-center">';
	echo '<label for="accordion-search" class="me-2 mb-0">Rechercher:</label>';
	echo '<input type="text" id="accordion-search" class="form-control form-control-sm" placeholder="Rechercher..." style="width: 250px;">';
	echo '</div>';
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
		// Gérer le bouton "Développer tout"
		var expandBtn = document.getElementById('expand-all');
		if (expandBtn) {
			// Retirer les anciens listeners si présents
			expandBtn.replaceWith(expandBtn.cloneNode(true));
			expandBtn = document.getElementById('expand-all');

			expandBtn.addEventListener('click', function(e) {
				e.preventDefault();
				// Trouver TOUS les collapse (peu importe leur état actuel)
				var collapses = document.querySelectorAll('#balanceAccordion .accordion-collapse');
				collapses.forEach(function(collapseElement) {
					// Forcer l'ouverture: ajouter la classe 'show' directement
					if (!collapseElement.classList.contains('show')) {
						collapseElement.classList.add('collapsing');
						collapseElement.classList.add('show');
						// Mettre à jour le bouton correspondant
						var button = document.querySelector('[data-bs-target="#' + collapseElement.id + '"]');
						if (button) {
							button.classList.remove('collapsed');
							button.setAttribute('aria-expanded', 'true');
						}
						// Retirer la classe 'collapsing' après l'animation
						setTimeout(function() {
							collapseElement.classList.remove('collapsing');
						}, 350);
					}
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
				// Trouver TOUS les collapse (peu importe leur état actuel)
				var collapses = document.querySelectorAll('#balanceAccordion .accordion-collapse');
				collapses.forEach(function(collapseElement) {
					// Forcer la fermeture: retirer la classe 'show' directement
					if (collapseElement.classList.contains('show')) {
						collapseElement.classList.add('collapsing');
						collapseElement.classList.remove('show');
						// Mettre à jour le bouton correspondant
						var button = document.querySelector('[data-bs-target="#' + collapseElement.id + '"]');
						if (button) {
							button.classList.add('collapsed');
							button.setAttribute('aria-expanded', 'false');
						}
						// Retirer la classe 'collapsing' après l'animation
						setTimeout(function() {
							collapseElement.classList.remove('collapsing');
						}, 350);
					}
				});
			});
		}
	}

	// Fonction de recherche dans les accordéons
	function initializeAccordionSearch() {
		var searchInput = document.getElementById('accordion-search');
		if (searchInput) {
			searchInput.addEventListener('input', function(e) {
				var searchTerm = e.target.value.toLowerCase().trim();
				var accordionItems = document.querySelectorAll('#balanceAccordion .accordion-item');

				accordionItems.forEach(function(item) {
					// Rechercher dans la seconde ligne du titre (tbody tr)
					var dataRow = item.querySelector('.accordion-button table tbody tr');
					if (dataRow) {
						// Extraire le texte de toutes les cellules de la ligne de données
						var cells = dataRow.querySelectorAll('td');
						var textContent = '';
						cells.forEach(function(cell) {
							textContent += cell.textContent.toLowerCase() + ' ';
						});

						// Afficher ou masquer l'accordéon selon le résultat de la recherche
						if (searchTerm === '' || textContent.indexOf(searchTerm) !== -1) {
							item.style.display = '';
						} else {
							item.style.display = 'none';
						}
					}
				});
			});
		}
	}

	// Essayer plusieurs méthodes pour s'assurer que le code s'exécute
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			initializeBalanceAccordion();
			initializeAccordionSearch();
		});
	} else {
		initializeBalanceAccordion();
		initializeAccordionSearch();
	}
	</script>

	<script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>
