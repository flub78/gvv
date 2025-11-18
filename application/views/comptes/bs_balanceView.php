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

	<?php if ($this->session->flashdata('error')): ?>
		<div class="alert alert-danger alert-dismissible fade show" role="alert">
			<?= $this->session->flashdata('error') ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>
	<?php endif; ?>

	<?php if ($this->session->flashdata('success')): ?>
		<div class="alert alert-success alert-dismissible fade show" role="alert">
			<?= $this->session->flashdata('success') ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>
	<?php endif; ?>

	<?php if ($this->session->flashdata('message')): ?>
		<div class="alert alert-info alert-dismissible fade show" role="alert">
			<?= $this->session->flashdata('message') ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>
	<?php endif; ?>

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
			$row_codec = $general_row['codec'];
			$details = isset($details_by_codec[$row_codec]) ? $details_by_codec[$row_codec] : array();
			echo balance_accordion_item($general_row, $details, $index, $this->gvvmetadata, $controller, $has_modification_rights, $section, $start_expanded);
			$index++;
		endforeach;
		?>
	</div>

	<?php
	$csv_url = "$controller/balance_hierarchical_csv";
	$pdf_url = "$controller/balance_hierarchical_pdf";
	if (!empty($codec)) {
		$csv_url .= "/$codec";
		$pdf_url .= "/$codec";
		if (!empty($codec2)) {
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

	// Variable globale pour mémoriser l'état original des accordéons
	var originalAccordionStates = {};

	// Fonction de recherche dans les accordéons
	function initializeAccordionSearch() {
		var searchInput = document.getElementById('accordion-search');
		if (searchInput) {
			// Capturer l'état initial des accordéons au premier chargement
			captureOriginalAccordionStates();
			
			searchInput.addEventListener('input', function(e) {
				var originalValue = e.target.value.trim();
				var searchTerm = originalValue.toLowerCase();
				var accordionItems = document.querySelectorAll('#balanceAccordion .accordion-item');

				// Si la recherche est vide, restaurer l'état original
				if (searchTerm === '') {
					restoreOriginalAccordionStates();
					// Afficher tous les accordéons et effacer les filtres DataTable
					accordionItems.forEach(function(item) {
						item.style.display = '';
						clearDataTableSearch(item);
					});
					return;
				}

				var visibleCount = 0;

				accordionItems.forEach(function(item) {
					var shouldShow = false;
					var foundInChildren = false;
					
					// Rechercher dans la seconde ligne du titre (tbody tr) - header de l'accordéon
					var dataRow = item.querySelector('.accordion-button table tbody tr');
					if (dataRow) {
						// Extraire le texte de toutes les cellules de la ligne de données du header
						var cells = dataRow.querySelectorAll('td');
						var headerTextContent = '';
						cells.forEach(function(cell) {
							headerTextContent += (cell.textContent || cell.innerText || '').toLowerCase() + ' ';
						});
						
						// Nettoyer les espaces multiples et caractères spéciaux
						headerTextContent = headerTextContent.replace(/\s+/g, ' ').trim();
						
						// Vérifier si le terme de recherche est dans le header
						if (headerTextContent.indexOf(searchTerm) !== -1) {
							shouldShow = true;
							console.log('Recherche accordéon - Match dans header:', {
								searchTerm: originalValue,
								headerContent: headerTextContent.substring(0, 50),
								accordionElement: item.id || 'sans ID'
							});
						}
					}
					
					// Si pas trouvé dans le header, rechercher dans les comptes enfants (accordion body)
					if (!shouldShow && searchTerm !== '') {
						// Rechercher dans l'accordion-body, même si l'accordéon est fermé
						// Utiliser le sélecteur correct avec le wrapper balance-datatable-wrapper
						var accordionBody = item.querySelector('.accordion-collapse .accordion-body .balance-datatable-wrapper table tbody');
						
						// Fallback: essayer aussi sans .accordion-collapse
						if (!accordionBody) {
							accordionBody = item.querySelector('.accordion-body .balance-datatable-wrapper table tbody');
						}
						
						// Fallback ultime: essayer directement table tbody dans accordion-body
						if (!accordionBody) {
							accordionBody = item.querySelector('.accordion-body table tbody');
						}
						
						if (accordionBody) {
							var childRows = accordionBody.querySelectorAll('tr');
							var matchingChildren = [];
							
							// Vérifier qu'on a bien trouvé des lignes
							if (childRows.length > 0) {
								childRows.forEach(function(childRow) {
									var childCells = childRow.querySelectorAll('td');
									var childTextContent = '';
									childCells.forEach(function(cell) {
										childTextContent += (cell.textContent || cell.innerText || '').toLowerCase() + ' ';
									});
									
									// Nettoyer les espaces multiples et caractères spéciaux
									childTextContent = childTextContent.replace(/\s+/g, ' ').trim();
									
									// Si trouvé dans un compte enfant, afficher l'accordéon
									if (childTextContent.indexOf(searchTerm) !== -1) {
										shouldShow = true;
										foundInChildren = true;
										matchingChildren.push(childTextContent.substring(0, 30));
									}
								});
							} else {
								// Logging pour debug si aucune ligne trouvée
								console.warn('Accordéon search - Aucune ligne enfant trouvée:', {
									accordionElement: item.id || 'sans ID',
									accordionBody: !!accordionBody,
									searchTerm: originalValue
								});
							}
							
							if (foundInChildren) {
								console.log('Recherche accordéon - Match dans enfants:', {
									searchTerm: originalValue,
									matchingChildren: matchingChildren,
									accordionElement: item.id || 'sans ID',
									totalChildren: childRows.length
								});
							}
						} else {
							// Logging pour debug si accordion-body pas trouvé
							console.warn('Accordéon search - accordion-body non trouvé:', {
								accordionElement: item.id || 'sans ID',
								searchTerm: originalValue,
								itemHTML: item.outerHTML.substring(0, 200) + '...'
							});
						}
					}

					// Afficher ou masquer l'accordéon selon le résultat de la recherche
					if (shouldShow) {
						item.style.display = '';
						visibleCount++;

						// Développer l'accordéon et appliquer la recherche DataTable
						// (que ce soit un match dans le header ou dans les enfants)
						if (searchTerm !== '') {
							expandAccordionAndApplyDataTableSearch(item, searchTerm);
						}
					} else {
						item.style.display = 'none';
					}
				});
			});
		}
	}

	// Fonction pour capturer l'état original des accordéons
	function captureOriginalAccordionStates() {
		var accordionItems = document.querySelectorAll('#balanceAccordion .accordion-item');
		accordionItems.forEach(function(item, index) {
			var collapseElement = item.querySelector('.accordion-collapse');
			if (collapseElement) {
				var accordionId = collapseElement.id || 'accordion_' + index;
				originalAccordionStates[accordionId] = {
					isExpanded: collapseElement.classList.contains('show'),
					element: collapseElement,
					button: item.querySelector('.accordion-button')
				};
			}
		});
	}

	// Fonction pour restaurer l'état original des accordéons
	function restoreOriginalAccordionStates() {
		for (var accordionId in originalAccordionStates) {
			var state = originalAccordionStates[accordionId];
			var collapseElement = state.element;
			var button = state.button;
			
			if (collapseElement && button) {
				if (state.isExpanded) {
					// Était ouvert à l'origine, s'assurer qu'il reste ouvert
					if (!collapseElement.classList.contains('show')) {
						collapseElement.classList.add('show');
						button.classList.remove('collapsed');
						button.setAttribute('aria-expanded', 'true');
					}
				} else {
					// Était fermé à l'origine, s'assurer qu'il se ferme
					if (collapseElement.classList.contains('show')) {
						collapseElement.classList.add('collapsing');
						collapseElement.classList.remove('show');
						button.classList.add('collapsed');
						button.setAttribute('aria-expanded', 'false');
						
						// Retirer la classe 'collapsing' après l'animation
						setTimeout(function() {
							collapseElement.classList.remove('collapsing');
						}, 350);
					}
				}
			}
		}
	}

	// Fonction pour vérifier si un accordéon est actuellement développé
	function isAccordionExpanded(accordionItem) {
		var collapseElement = accordionItem.querySelector('.accordion-collapse');
		return collapseElement && collapseElement.classList.contains('show');
	}

	// Fonction pour développer un accordéon et appliquer la recherche DataTable
	function expandAccordionAndApplyDataTableSearch(accordionItem, searchTerm) {
		// Trouver le collapse element
		var collapseElement = accordionItem.querySelector('.accordion-collapse');
		var button = accordionItem.querySelector('.accordion-button');
		
		if (collapseElement && button) {
			var wasAlreadyExpanded = collapseElement.classList.contains('show');
			
			if (!wasAlreadyExpanded) {
				// Développer l'accordéon s'il n'était pas déjà ouvert
				collapseElement.classList.add('collapsing');
				collapseElement.classList.add('show');
				button.classList.remove('collapsed');
				button.setAttribute('aria-expanded', 'true');
				
				// Retirer la classe 'collapsing' après l'animation
				setTimeout(function() {
					collapseElement.classList.remove('collapsing');
				}, 350);
				
				// Attendre que l'accordéon soit complètement ouvert puis appliquer la recherche
				setTimeout(function() {
					applyDataTableSearch(accordionItem, searchTerm);
				}, 450); // Un peu plus de temps pour s'assurer que tout est prêt
			} else {
				// L'accordéon était déjà ouvert, appliquer la recherche immédiatement
				// mais avec un petit délai pour s'assurer que les DataTables sont initialisées
				setTimeout(function() {
					applyDataTableSearch(accordionItem, searchTerm);
				}, 100);
			}
		}
	}

	// Fonction pour appliquer la recherche dans la DataTable
	function applyDataTableSearch(accordionItem, searchTerm) {
		// Trouver la DataTable dans cet accordéon
		var dataTable = accordionItem.querySelector('.accordion-body table');
		if (dataTable) {
			var tableId = dataTable.getAttribute('id');
			
			// Obtenir le terme de recherche original (avec la casse originale)
			var originalSearchTerm = document.getElementById('accordion-search').value.trim();
			
			// Fonction helper pour appliquer la recherche DataTable
			var applyDataTableSearchInternal = function() {
				if ((dataTable.classList.contains('searchable_nosort_datatable') || dataTable.classList.contains('balance_searchable_datatable')) && typeof $ !== 'undefined') {
					try {
						// Vérifier si la DataTable est initialisée (compatible ancienne/nouvelle API)
						var isDataTable = false;
						if ($.fn.DataTable && $.fn.DataTable.isDataTable) {
							isDataTable = $.fn.DataTable.isDataTable('#' + tableId);
						} else if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable) {
							isDataTable = $.fn.dataTable.fnIsDataTable(dataTable);
						} else if ($(dataTable).hasClass('dataTable')) {
							isDataTable = true;
						}

						if (isDataTable) {
							// Utiliser l'ancienne API pour être compatible
							var dt = $('#' + tableId).dataTable();
							// Appliquer la recherche
							dt.fnFilter(originalSearchTerm);
							// Recalculer les totaux immédiatement après le draw
							setTimeout(function() {
								if (typeof recalculateGroupTotals === 'function') {
									recalculateGroupTotals('#' + tableId);
								}
							}, 100);
							return true; // Succès
						} else {
							// DataTable pas encore initialisée
							return false;
						}
					} catch (e) {
						console.warn('Erreur DataTable pour ' + tableId + ':', e);
						return false;
					}
				}
				return false; // Pas de DataTable
			};
			
			// Essayer d'appliquer la recherche DataTable
			var success = applyDataTableSearchInternal();
			
			if (!success) {
				// Première tentative échouée, essayer après un délai
				setTimeout(function() {
					var secondAttempt = applyDataTableSearchInternal();
					if (!secondAttempt) {
						// Fallback définitif: utiliser la recherche manuelle
						console.log('Fallback vers recherche manuelle pour table:', tableId);
						applyManualTableFilter(dataTable, originalSearchTerm);
					}
				}, 300);
			}
		}
	}

	// Fonction pour effacer la recherche DataTable
	function clearDataTableSearch(accordionItem) {
		var dataTable = accordionItem.querySelector('.accordion-body table');
		if (dataTable) {
			var tableId = dataTable.getAttribute('id');
			
			// Fonction helper pour effacer la recherche DataTable
			var clearDataTableSearchInternal = function() {
				if ((dataTable.classList.contains('searchable_nosort_datatable') || dataTable.classList.contains('balance_searchable_datatable')) && typeof $ !== 'undefined') {
					try {
						// Vérifier si la DataTable est initialisée (compatible ancienne/nouvelle API)
						var isDataTable = false;
						if ($.fn.DataTable && $.fn.DataTable.isDataTable) {
							isDataTable = $.fn.DataTable.isDataTable('#' + tableId);
						} else if ($.fn.dataTable && $.fn.dataTable.fnIsDataTable) {
							isDataTable = $.fn.dataTable.fnIsDataTable(dataTable);
						} else if ($(dataTable).hasClass('dataTable')) {
							isDataTable = true;
						}

						if (isDataTable) {
							// Utiliser l'ancienne API pour être compatible
							var dt = $('#' + tableId).dataTable();
							dt.fnFilter('');
							// Recalculer les totaux immédiatement après le draw
							setTimeout(function() {
								if (typeof recalculateGroupTotals === 'function') {
									recalculateGroupTotals('#' + tableId);
								}
							}, 100);
							return true; // Succès
						}
					} catch (e) {
						console.warn('Erreur lors de l\'effacement DataTable pour ' + tableId + ':', e);
					}
				}
				return false; // Pas de DataTable ou échec
			};
			
			// Essayer d'effacer la recherche DataTable
			var success = clearDataTableSearchInternal();
			
			if (!success) {
				// Fallback: effacer le filtre manuel
				clearManualTableFilter(dataTable);
			}
		}
	}

	// Fonction pour appliquer un filtre manuel sur une table simple
	function applyManualTableFilter(table, searchTerm) {
		var tbody = table.querySelector('tbody');
		if (tbody) {
			var rows = tbody.querySelectorAll('tr');
			var searchTermLower = searchTerm.toLowerCase().trim();
			var visibleCount = 0;

			rows.forEach(function(row) {
				// Utiliser textContent et innerText pour plus de robustesse
				var rowText = (row.textContent || row.innerText || '').toLowerCase();
				// Nettoyer les espaces multiples et caractères spéciaux
				rowText = rowText.replace(/\s+/g, ' ').trim();

				if (searchTermLower === '' || rowText.indexOf(searchTermLower) !== -1) {
					row.style.display = '';
					visibleCount++;
				} else {
					row.style.display = 'none';
				}
			});

			// Log pour debug
			console.log('Filtre manuel appliqué:', {
				table: table.getAttribute('id') || 'table sans ID',
				searchTerm: searchTermLower,
				totalRows: rows.length,
				visibleRows: visibleCount
			});

			// Recalculer les totaux pour les lignes visibles
			recalculateManualTableTotals(table);
		}
	}

	// Fonction pour recalculer les totaux d'une table avec filtre manuel
	function recalculateManualTableTotals(table) {
		console.log('recalculateManualTableTotals appelée pour:', table);

		// Trouver le wrapper qui contient la table et la ligne de total
		var wrapper = table.closest('.balance-datatable-wrapper');
		if (!wrapper) {
			console.warn('Wrapper non trouvé pour la table manuelle');
			return;
		}

		// Trouver la ligne de total (dans la table séparée après la datatable)
		// La table des totaux est la dernière table.table-sm dans le wrapper et n'a pas de thead
		var allTables = wrapper.querySelectorAll('table.table-sm');
		var totalTable = null;

		// Chercher la table sans thead (c'est la table des totaux)
		for (var i = 0; i < allTables.length; i++) {
			if (!allTables[i].querySelector('thead')) {
				totalTable = allTables[i];
				break;
			}
		}

		if (!totalTable) {
			console.log('Pas de table de totaux trouvée (groupe avec une seule ligne)');
			return;
		}

		var totalRow = totalTable.querySelector('tbody tr');
		if (!totalRow) {
			console.log('Pas de ligne de total dans la table de totaux');
			return;
		}

		console.log('Table de totaux trouvée:', totalTable);

		var totalSoldeDebit = 0;
		var totalSoldeCredit = 0;

		// Parcourir les lignes VISIBLES du tbody
		var tbody = table.querySelector('tbody');
		if (tbody) {
			var rows = tbody.querySelectorAll('tr');
			var visibleCount = 0;

			rows.forEach(function(row) {
				// Ignorer les lignes masquées
				if (row.style.display === 'none') {
					return;
				}

				visibleCount++;
				var cells = row.querySelectorAll('td');
				// Les colonnes sont: Actions (0), Codec (1), Nom (2), Section (3), Solde Débit (4), Solde Crédit (5)
				var soldeDebitCell = cells[4];
				var soldeCreditCell = cells[5];

				if (soldeDebitCell && soldeCreditCell) {
					var soldeDebit = parseEuroValue(soldeDebitCell.textContent || soldeDebitCell.innerText || '');
					var soldeCredit = parseEuroValue(soldeCreditCell.textContent || soldeCreditCell.innerText || '');

					console.log('Ligne:', (cells[2] ? (cells[2].textContent || cells[2].innerText) : ''), '- Débit:', soldeDebit, '- Crédit:', soldeCredit);

					totalSoldeDebit += soldeDebit;
					totalSoldeCredit += soldeCredit;
				}
			});

			console.log('Lignes visibles:', visibleCount, '- Total Débit:', totalSoldeDebit, '- Total Crédit:', totalSoldeCredit);
		}

		// Mettre à jour la ligne de total
		// Structure de la ligne de total: Actions (0), Codec (1), Nom (2), Section/Label (3), Solde Débit (4), Solde Crédit (5)
		var totalCells = totalRow.querySelectorAll('td');
		if (totalCells.length >= 6) {
			totalCells[4].innerHTML = '<strong>' + formatEuro(totalSoldeDebit) + '</strong>';
			totalCells[5].innerHTML = '<strong>' + formatEuro(totalSoldeCredit) + '</strong>';
			console.log('Totaux mis à jour (manuel)');
		}
	}

	// Fonction pour effacer le filtre manuel d'une table simple
	function clearManualTableFilter(table) {
		var tbody = table.querySelector('tbody');
		if (tbody) {
			var rows = tbody.querySelectorAll('tr');
			rows.forEach(function(row) {
				row.style.display = '';
			});
			// Recalculer les totaux après avoir effacé le filtre
			recalculateManualTableTotals(table);
		}
	}

	// Essayer plusieurs méthodes pour s'assurer que le code s'exécute
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			initializeBalanceAccordion();
			initializeAccordionSearch();
			// Ajouter des listeners pour capturer les changements d'état manuels
			addAccordionStateChangeListeners();
		});
	} else {
		initializeBalanceAccordion();
		initializeAccordionSearch();
		// Ajouter des listeners pour capturer les changements d'état manuels
		addAccordionStateChangeListeners();
	}

	// Initialiser le recalcul des totaux APRÈS que tout soit chargé (y compris le footer avec les DataTables)
	$(window).on('load', function() {
		console.log('Window load - initialisation du recalcul des totaux');
		initializeDataTableTotalsRecalculation();
	});

	// Fonction pour ajouter des listeners aux changements d'état des accordéons
	function addAccordionStateChangeListeners() {
		// Listener pour les clics sur les boutons d'accordéon
		var accordionButtons = document.querySelectorAll('#balanceAccordion .accordion-button');
		accordionButtons.forEach(function(button) {
			button.addEventListener('click', function() {
				// Attendre que Bootstrap ait fini de traiter le changement d'état
				setTimeout(function() {
					captureOriginalAccordionStates();
				}, 50);
			});
		});

		// Listener pour le bouton "Développer tout"
		var expandBtn = document.getElementById('expand-all');
		if (expandBtn) {
			// Utiliser capture pour intercepter avant le handler existant
			expandBtn.addEventListener('click', function() {
				setTimeout(function() {
					// Marquer tous comme étant ouverts dans l'état original
					updateAllAccordionStates(true);
				}, 400);
			}, true);
		}

		// Listener pour le bouton "Réduire tout"
		var collapseBtn = document.getElementById('collapse-all');
		if (collapseBtn) {
			// Utiliser capture pour intercepter avant le handler existant
			collapseBtn.addEventListener('click', function() {
				setTimeout(function() {
					// Marquer tous comme étant fermés dans l'état original
					updateAllAccordionStates(false);
				}, 400);
			}, true);
		}
	}

	// Fonction pour mettre à jour l'état de tous les accordéons
	function updateAllAccordionStates(isExpanded) {
		for (var accordionId in originalAccordionStates) {
			originalAccordionStates[accordionId].isExpanded = isExpanded;
		}
	}

	// Fonction pour parser une valeur monétaire en nombre
	function parseEuroValue(euroString) {
		if (!euroString || euroString.trim() === '') {
			return 0;
		}
		// Supprimer les espaces, le symbole €, et remplacer la virgule par un point
		var cleanValue = euroString.replace(/\s/g, '').replace('€', '').replace(',', '.');
		var numValue = parseFloat(cleanValue);
		return isNaN(numValue) ? 0 : numValue;
	}

	// Fonction pour formater un nombre en euros
	function formatEuro(value) {
		if (value === 0) {
			return '';
		}
		// Formater avec 2 décimales et remplacer le point par une virgule
		var formatted = value.toFixed(2).replace('.', ',');
		return formatted + '&nbsp;€';
	}

	// Fonction pour recalculer les totaux d'un groupe basé sur les lignes visibles
	function recalculateGroupTotals(dataTable) {
		console.log('recalculateGroupTotals appelée pour:', dataTable);

		// Trouver le wrapper qui contient la table et la ligne de total
		var wrapper = $(dataTable).closest('.balance-datatable-wrapper');
		if (!wrapper.length) {
			console.warn('Wrapper non trouvé pour:', dataTable);
			return;
		}

		// Trouver la ligne de total (dans la table séparée après la datatable)
		// La table des totaux est celle sans thead
		var allTables = wrapper.find('table.table-sm');
		var totalTable = null;

		allTables.each(function() {
			if (!$(this).find('thead').length) {
				totalTable = $(this);
				return false; // break
			}
		});

		if (!totalTable) {
			console.log('Pas de table de totaux trouvée (groupe avec une seule ligne)');
			return;
		}

		var totalRow = totalTable.find('tbody tr');
		if (!totalRow.length) {
			console.log('Pas de ligne de total dans la table de totaux');
			return;
		}

		console.log('Table de totaux trouvée:', totalTable[0]);

		var totalSoldeDebit = 0;
		var totalSoldeCredit = 0;

		// Parcourir les lignes visibles de la DataTable (compatible ancienne API)
		var dt = $(dataTable).dataTable();

		// Avec l'ancienne API, on doit parcourir directement les lignes du tbody
		var tbody = $(dataTable).find('tbody');
		var visibleRows = tbody.find('tr:visible');

		console.log('Nombre de lignes visibles:', visibleRows.length);

		visibleRows.each(function() {
			var cells = $(this).find('td');

			// Ignorer la ligne "Aucun élément à afficher"
			if (cells.length === 1 && cells.eq(0).hasClass('dataTables_empty')) {
				return;
			}

			// Les colonnes sont: Actions (0), Codec (1), Nom (2), Section (3), Solde Débit (4), Solde Crédit (5)
			var soldeDebitCell = cells.eq(4);
			var soldeCreditCell = cells.eq(5);

			if (soldeDebitCell.length && soldeCreditCell.length) {
				var soldeDebit = parseEuroValue(soldeDebitCell.text());
				var soldeCredit = parseEuroValue(soldeCreditCell.text());

				console.log('Ligne:', cells.eq(2).text(), '- Débit:', soldeDebit, '- Crédit:', soldeCredit);

				totalSoldeDebit += soldeDebit;
				totalSoldeCredit += soldeCredit;
			}
		});

		console.log('Total Débit:', totalSoldeDebit, '- Total Crédit:', totalSoldeCredit);

		// Mettre à jour la ligne de total
		// Structure de la ligne de total: Actions (0), Codec (1), Nom (2), Section/Label (3), Solde Débit (4), Solde Crédit (5)
		var totalCells = totalRow.find('td');
		totalCells.eq(4).html('<strong>' + formatEuro(totalSoldeDebit) + '</strong>');
		totalCells.eq(5).html('<strong>' + formatEuro(totalSoldeCredit) + '</strong>');

		console.log('Totaux mis à jour');
	}

	// Fonction pour initialiser le recalcul des totaux sur toutes les DataTables
	function initializeDataTableTotalsRecalculation() {
		console.log('Initialisation du recalcul des totaux...');

		// Fonction pour attacher les listeners
		var attachListeners = function() {
			var attachedCount = 0;
			// Trouver toutes les DataTables dans les accordéons
			var tables = $('#balanceAccordion .balance_searchable_datatable');
			console.log('Nombre de tables balance_searchable_datatable trouvées:', tables.length);

			tables.each(function() {
				var tableId = $(this).attr('id');
				console.log('Vérification de la table:', tableId);

				// Vérifier si la DataTable est initialisée (compatible avec ancienne et nouvelle API)
				var isDataTable = false;
				try {
					// Nouvelle API
					if ($.fn.DataTable && $.fn.DataTable.isDataTable) {
						isDataTable = $.fn.DataTable.isDataTable('#' + tableId);
					}
					// Ancienne API (fallback)
					if (!isDataTable && $.fn.dataTable && $.fn.dataTable.fnIsDataTable) {
						isDataTable = $.fn.dataTable.fnIsDataTable(document.getElementById(tableId));
					}
					// Vérifier si l'élément a déjà les classes DataTable
					if (!isDataTable && $(this).hasClass('dataTable')) {
						isDataTable = true;
					}
				} catch (e) {
					console.warn('Erreur lors de la vérification DataTable pour ' + tableId + ':', e);
				}

				if (isDataTable) {
					console.log('DataTable initialisée pour:', tableId);
					try {
						// Obtenir l'instance DataTable (compatible ancienne/nouvelle API)
						var dt = $('#' + tableId).dataTable();

						// Attacher un listener sur l'événement draw avec jQuery
						$('#' + tableId).on('draw.dt', function() {
							console.log('Événement draw déclenché pour:', tableId);
							recalculateGroupTotals('#' + tableId);
						});
						attachedCount++;
					} catch (e) {
						console.warn('Erreur lors de l\'attachement du listener pour ' + tableId + ':', e);
					}
				} else {
					console.log('DataTable NON initialisée pour:', tableId);
				}
			});
			console.log('Nombre de listeners attachés:', attachedCount);
			return attachedCount;
		};

		// Essayer d'attacher les listeners avec retry
		var tryAttach = function(attempt) {
			console.log('Tentative d\'attachement des listeners, essai:', attempt);
			if (attempt > 5) {
				console.warn('Abandon après 5 tentatives');
				return; // Abandonner après 5 tentatives
			}

			setTimeout(function() {
				var count = attachListeners();
				if (count === 0) {
					// Aucun listener attaché, réessayer
					console.log('Aucun listener attaché, nouvelle tentative...');
					tryAttach(attempt + 1);
				} else {
					console.log('Initialisation terminée avec succès!');
				}
			}, 200 * attempt); // Délai croissant: 200ms, 400ms, 600ms, 800ms, 1000ms
		};

		tryAttach(1);
	}
	</script>

	<script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>
