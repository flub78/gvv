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
 * Vue table pour les avions
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('avion');

?>
<div id="body" class="body container-fluid">
	<h3><?= $this->lang->line("gvv_avion_title_list") ?></h3>

	<input type="hidden" name="controller_url" value="<?= controller_url($controller) ?>" />

	<input type="hidden" name="filter_active" value="<?= $filter_active ?>" />

	<!-- Filtre -->
	<div class="accordion accordion-flush collapsed mb-3" id="accordionPanelsStayOpenExample">
		<div class="accordion-item">
			<h2 class="accordion-header" id="panelsStayOpen-headingOne">
				<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
					<?= $this->lang->line("gvv_str_filter") ?>
				</button>
			</h2>
			<div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingOne">
				<div class="accordion-body">
					<div>
						<form action="<?= controller_url($controller) . "/filterValidation/" . $action ?>" method="post" accept-charset="utf-8" name="saisie">
							<div>

								<?= $this->lang->line("avion_filter_active") . ": "
									. enumerate_radio_fields($this->lang->line("avion_filter_active_select"), 'filter_machine_actif', $filter_machine_actif); ?>

							</div>
							<div>
								<?=
								$this->lang->line("avion_filter_owner") . ": " .  enumerate_radio_fields($this->lang->line("avion_filter_owner_select"), 'filter_proprio', $filter_proprio)
								?>

								<div class="mb-2 mt-2">
									<input type="submit" name="button" value="<?= $this->lang->line("gvv_str_select") ?>" class="btn bg-primary text-white" />
									<input type="submit" name="button" value="<?= $this->lang->line("gvv_str_display") ?>" class="btn bg-primary text-white" />
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

	</div>

</div>
<?php
// --------------------------------------------------------------------------------------------------
// Data
$attrs = array(
	'controller' => $controller,
	'actions' => array('edit', 'delete'),
	'fields' => array(
		'macmodele',
		'macconstruc',
		'macimmat',
		'macplaces',
		'macrem',
		'maprive',
		'actif',
		'vols',
		'fabrication',
		'section_name'
	),
	'mode' => ($has_modification_rights) ? "rw" : "ro",
	'class' => "datatable table table-striped"
);

echo $this->gvvmetadata->table("vue_avions", $attrs, "");

/*
$bar = array(
	array('label' => "Excel", 'url' =>"$controller/ventes_csv/$year", 'role' => 'ca'),
	array('label' => "Pdf", 'url' => controller_url("rapports/ventes"), 'role' => 'ca'),
	);
echo button_bar4($bar);
*/

echo '</div>';
