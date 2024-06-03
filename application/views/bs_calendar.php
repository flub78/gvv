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
 * Simple vue pour afficher un message à l'utilisateur
 * 
 * Saisie d'événements:
 *    - date obligatoire
 *    - intention facultative
 */

$this->load->view('bs_header', array('new_layout' => true));
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('welcome');
$this->lang->load('config');
?>
<section class="container-fluid">
	<article class="">
		<input type="hidden" name="base_url" value="<?= base_url() ?>" />
		<input type="hidden" name="cal_id" value="<?= $cal_id ?>" />

		<?php
		echo p($this->lang->line("welcome_intro1"));

		e_html_script(array('type' => "text/javascript", 'src' => js_url('fullcalendar')));
		e_html_script(array('type' => "text/javascript", 'src' => js_url('gcal')));

		$lang = $this->config->item('language');
		if ($lang == "french") {
			e_html_script(array('type' => "text/javascript", 'src' => js_url('lang/fr')));
		} elseif ($lang == "dutch") {
			e_html_script(array('type' => "text/javascript", 'src' => js_url('lang/nl')));
		}

		# ----------------------------------------------------------------------------------------------
		# Formulaire sur selection d'événement
		e_div($this->lang->line("welcome_confirm_delete"), array('id' => 'calendar_dialog', 'style' => "display:none"));

		if ($mod) {
			echo form_hidden('mod_title', $this->lang->line("gvv_config_mod"));

			e_div(
				$this->config->item("mod")
					. '</br></br>' . $this->lang->line("gvv_no_more_mod") . ": " . nbs() . '<input type="checkbox" name="no_mod" value="0" id="no_mod"   />',
				array('id' => 'mod', 'style' => "display:none")
			);
		}

		e_div_open(array('id' => "body3", 'class' => "ui-widget-content container-fluid"));

		# titre
		if (isset($title)) e_heading($title, 3);

		# Le calendrier lui même
		if ($cal_id) {
			e_div('', array('id' => 'calendar'));
		}
		# ----------------------------------------------------------------------------------------------
		# Formulaire de création
		e_div_open(array('id' => 'calendar_form', 'style' => "display:none"));
		echo form_open(controller_url(""), array('id' => 'event_add_form')) . "\n";

		// e_heading("welcome_attendance", 3);
		echo form_hidden('mlogin', $this->dx_auth->get_username());
		echo form_hidden('event_id', $event_id);

		// if (isset($pilote_selector)) {
		$pilot = dropdown_field('mlogin', $mlogin, $pilote_selector, "id='mlogin' ");
		// } else {
		// 	$pilot = form_hidden('mlogin', $this->dx_auth->get_username());
		// }

		$table = array(
			array('<span class="ui-helper-hidden-accessible"><input type="text"/></span>', ''),
			array(
				label('welcome_date', array('for' => 'date_ajout')),
				input(array(
					'type' => "text",
					'name' => "date_ajout",
					'id' => "date_ajout",
					'value' => "",
					'size' => "12",
					'class' => "datepicker",
					'title' => "JJ/MM/AAAA"
				))
			),
			array(
				label("gvv_membres_field_mlogin", array('for' => 'mlogin')),
				$pilot
			),
			array(
				label('welcome_intent', array('for' => 'role')),
				form_dropdown("role", $this->lang->line("welcome_options"), "", 'id="role"')
			),
			array(
				label('welcome_comment', array('for' => 'commentaire')),
				input(array(
					'type' => "text",
					'name' => "commentaire",
					'id' => "commentaire",
					'value' => "",
					'size' => "32"
				))
			)
		);

		display_form_table($table);

		echo form_close();
		e_div_close();

		# ----------------------------------------------------------------------------------------------

		$club = $this->config->item('club');

		if ($club == 'accabs') {
			echo p('<div class="error">Les paiements par chèque doivent être remis exclusivement à Guilaume Pruvost, ceux en liquide à Mathieu Caudrelier.</div>');
			echo p(" Les chèques sont à rédiger à l'ordre de l'aéroclub d'Abbeville - section vol à voile.");
			echo br();
		}

		echo p($this->lang->line("welcome_intro2"));

		e_div_close();
		?>

	</article>
</section>
<script type="text/javascript" src="<?= js_url('calendar') ?>"></script>

<style>
	#calendar {
		max-width: 900px;
		margin: 0 auto;
		margin: 40px 10px;
		padding: 0;
		font-family: "Lucida Grande", Helvetica, Arial, Verdana, sans-serif;
		font-size: 14px;
		clear: both;
	}

	#calendar_form {
		border: 1px solid #9e9e9e;
	}
</style>