<!-- VIEW: application/views/planeur/bs_tableView.php -->
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

<?php
// Show success message
if ($this->session->flashdata('success')) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-check-circle"></i></strong> ';
    echo nl2br(htmlspecialchars($this->session->flashdata('success')));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// Show error message
if ($this->session->flashdata('error')) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-exclamation-triangle-fill"></i></strong> ';
    echo nl2br(htmlspecialchars($this->session->flashdata('error')));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}
?>

	<input type="hidden" name="gvv_role" value="<?= $gvv_role ?>" />
	<input type="hidden" name="controller_url" value="<?= controller_url($controller) ?>" />

	<input type="hidden" name="filter_active" value="<?= $filter_active ?>" />

	<!-- Filtre -->
	<div class="accordion accordion-flush collapsed mb-3" id="accordionFilter">

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
									<?= filter_buttons() ?>
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

    // Create button above the table (only for users with modification rights)
    if ($has_modification_rights) {
        echo '<div class="mb-3">'
            . '<a href="' . site_url('planeur/create') . '" class="btn btn-sm btn-success">'
            . '<i class="fas fa-plus" aria-hidden="true"></i> '
            . $this->lang->line('gvv_button_create')
            . '</a>'
            . '</div>';
    }

	echo $this->gvvmetadata->table("vue_planeurs", $attrs, "");

	// Export buttons
	$bar = array(
		array('label' => "Excel", 'url' => "$controller/export/csv", 'role' => 'ca'),
		array('label' => "Pdf", 'url' => "$controller/export/pdf", 'role' => 'ca'),
	);
	echo button_bar4($bar);

	echo '</div>';
