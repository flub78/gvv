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
.balance-general-row {
    background-color: #e7f1ff;
    font-weight: 600;
    cursor: pointer;
    border-left: 4px solid #0d6efd;
}
.balance-general-row:hover {
    background-color: #cfe2ff;
}
.balance-general-row td {
    padding: 0.75rem !important;
}
.balance-detail-row {
    background-color: #ffffff;
    display: none;
}
.balance-detail-row.show {
    display: table-row;
}
.balance-detail-row td {
    padding-left: 2rem !important;
}
.toggle-icon {
    display: inline-block;
    margin-right: 0.5rem;
    transition: transform 0.2s;
}
.toggle-icon.expanded {
    transform: rotate(90deg);
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

	// Génération manuelle du tableau pour gérer les lignes développables
	?>
	<table class="table table-striped searchable_nosort_datatable" id="balance-table">
		<thead>
			<tr>
				<th><?= $this->gvvmetadata->label('vue_comptes', 'codec') ?></th>
				<th><?= $this->gvvmetadata->label('vue_comptes', 'nom') ?></th>
				<th><?= $this->gvvmetadata->label('vue_comptes', 'section_name') ?></th>
				<th><?= $this->gvvmetadata->label('vue_comptes', 'solde_debit') ?></th>
				<th><?= $this->gvvmetadata->label('vue_comptes', 'solde_credit') ?></th>
				<th><?= $this->lang->line('gvv_str_actions') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($select_result as $row): ?>
				<?php if (isset($row['is_general']) && $row['is_general']): ?>
					<!-- Ligne générale (développable) -->
					<tr class="balance-general-row" data-codec="<?= $row['codec'] ?>" onclick="toggleCodec('<?= $row['codec'] ?>')">
						<td>
							<span class="toggle-icon">▶</span>
							<?= $row['codec'] ?>
						</td>
						<td><?= $row['nom'] ?></td>
						<td></td>
						<td><?= isset($row['solde_debit']) && $row['solde_debit'] ? euro($row['solde_debit']) : '' ?></td>
						<td><?= isset($row['solde_credit']) && $row['solde_credit'] ? euro($row['solde_credit']) : '' ?></td>
						<td></td>
					</tr>
				<?php elseif (isset($row['is_detail']) && $row['is_detail']): ?>
					<!-- Ligne détaillée (cachée par défaut) -->
					<tr class="balance-detail-row" data-parent-codec="<?= $row['parent_codec'] ?>">
						<td><?= $row['codec'] ?></td>
						<td><?= $row['nom'] ?></td>
						<td><?= isset($row['section_name']) ? $row['section_name'] : '' ?></td>
						<td><?= isset($row['solde_debit']) && $row['solde_debit'] ? euro($row['solde_debit']) : '' ?></td>
						<td><?= isset($row['solde_credit']) && $row['solde_credit'] ? euro($row['solde_credit']) : '' ?></td>
						<td>
							<?php if ($has_modification_rights && $section): ?>
							<a href="<?= site_url($controller . '/edit/' . $row['id']) ?>" title="<?= $this->lang->line('gvv_button_edit') ?>">
								<i class="fas fa-edit"></i>
							</a>
							<a href="<?= site_url($controller . '/delete/' . $row['id']) ?>" title="<?= $this->lang->line('gvv_button_delete') ?>" onclick="return confirm('<?= $this->lang->line('gvv_str_confirm_delete') ?>')">
								<i class="fas fa-trash"></i>
							</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<td></td>
				<td><strong><?= $footer[0][1] ?></strong></td>
				<td></td>
				<td><strong><?= $footer[0][2] ?></strong></td>
				<td><strong><?= $footer[0][3] ?></strong></td>
				<td></td>
			</tr>
		</tfoot>
	</table>

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
	function toggleCodec(codec) {
		var detailRows = document.querySelectorAll('tr[data-parent-codec="' + codec + '"]');
		var generalRow = document.querySelector('tr[data-codec="' + codec + '"]');
		var icon = generalRow.querySelector('.toggle-icon');
		var isExpanded = detailRows[0] && detailRows[0].classList.contains('show');
		
		detailRows.forEach(function(row) {
			if (isExpanded) {
				row.classList.remove('show');
			} else {
				row.classList.add('show');
			}
		});
		
		if (icon) {
			if (isExpanded) {
				icon.classList.remove('expanded');
			} else {
				icon.classList.add('expanded');
			}
		}
	}

	document.getElementById('expand-all').addEventListener('click', function() {
		var detailRows = document.querySelectorAll('.balance-detail-row');
		var icons = document.querySelectorAll('.balance-general-row .toggle-icon');
		
		detailRows.forEach(function(row) {
			row.classList.add('show');
		});
		
		icons.forEach(function(icon) {
			icon.classList.add('expanded');
		});
	});

	document.getElementById('collapse-all').addEventListener('click', function() {
		var detailRows = document.querySelectorAll('.balance-detail-row');
		var icons = document.querySelectorAll('.balance-general-row .toggle-icon');
		
		detailRows.forEach(function(row) {
			row.classList.remove('show');
		});
		
		icons.forEach(function(icon) {
			icon.classList.remove('expanded');
		});
	});

	</script>

	<script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>
