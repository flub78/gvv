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
 * Formulaire de saisie des terrains
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

$this->lang->load('attachments');
?>

<div id="body" class="body container-fluid">
	<?php
	if (isset($message)) {
		echo p($message) . br();
	}
	echo checkalert($this->session, isset($popup) ? $popup : "");
	?>

	<h3><?= $this->lang->line("gvv_attachments_title") ?> </h3>

	<form action="<?= controller_url($controller) . '/formValidation/' . $action ?>" method="post" accept-charset="utf-8" name="saisie">

		<?php

		// hidden controller url for java script access
		echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

		// echo validation_errors();
		echo ($this->gvvmetadata->form('terrains', array(
			'oaci' => $oaci,
			'nom' => $nom,
			'freq1' => $freq1,
			'freq2' => $freq2,
			'comment' => $comment
		)));

		?>
		<?= validation_button($action) ?>
	</form>
</div>