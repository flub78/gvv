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
 * Vue planche (table) pour les planeurs
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('planeur');

$CI = &get_instance();
$gvv_user = $CI->dx_auth->get_username();
$gvv_role = $CI->dx_auth->get_role_name();

?>

<div id="body" class="body container-fluid">
	<h3><?= $this->lang->line("gvv_planeur_title_list") ?></h3>

	<input type="hidden" name="gvv_role" value="<?= $gvv_role ?>" />
	<input type="hidden" name="controller_url" value="<?= controller_url($controller) ?>" />

	<input type="hidden" name="filter_active" value="<?= $filter_active ?>" />

	<!-- Filtre -->
	<div class="accordion accordion-flush collapsed container-fluid mt-3 mb-3" id="accordionFilter">

		<div class="accordion-item">
			<h2 class="accordion-header" id="headingOne">
				<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
					<?= $this->lang->line("gvv_str_filter") ?>
				</button>
			</h2>
			<div id="collapseOne" class="accordion-collapse collapse <?= ($filter_active) ? "show" : "" ?>" aria-labelledby="headingOne"="#accordionFilter">
				<div class="accordion-body">
					<div>
						<form action="<?= controller_url($controller) . "/filterValidation/" . $action ?>" method="post" accept-charset="utf-8" name="saisie">
							<div>
								<?php
								echo $this->lang->line("planeur_filter_active") . ": " . enumerate_radio_fields($this->lang->line("planeur_filter_active_select"), 'filter_machine_actif', $filter_machine_actif);
								echo "</div><div>";
								echo $this->lang->line("planeur_filter_owner") . ": " .  enumerate_radio_fields($this->lang->line("planeur_filter_owner_select"), 'filter_proprio', $filter_proprio);
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


	<?php

	// --------------------------------------------------------------------------------------------------
	// Data
	$attrs = array(
		'controller' => $controller,
		'actions' => array('edit', 'delete'),
		'fields' => array(
			'mpimmat',
			'mpmodele',
			'mpconstruc',
			'mpnumc',
			'mpbiplace',
			'mpautonome',
			'mptreuil',
			'mpprive',
			'actif',
			'vols',
			'fabrication'
		),
		'mode' => ($has_modification_rights) ? "rw" : "ro",
		'class' => "datatable table table-striped"
	);

	echo $this->gvvmetadata->table("vue_planeurs", $attrs, "");

	echo '</div>';
