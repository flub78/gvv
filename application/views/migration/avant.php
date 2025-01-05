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
 * Simple vue, affichage avant migration
 * 
 */

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

$this->lang->load('migration');

echo '<div id="body" class="body ui-widget-content">';

echo heading("migration_title", 3);

if (isset($popup)) echo checkalert($this->session, $popup);

echo br(2);

echo p($this->lang->line("migration_explain"));
echo br();

echo p('<div class="error">' . $this->lang->line("migration_advice") . '</div>');
echo br();

$migration_errors = $this->session->flashdata('migration_errors');
if ($migration_errors) {
	$txt = "Erreurs pendant la migration: $migration_errors erreur(s)." . br();
	$migration_msgs = $this->session->flashdata('migration_msgs');
	foreach ($migration_msgs as $line) {
		$txt .= $line . br();
	}
	$txt .= br();
	echo p('<div class="error">' . $txt . '</div>');
}
echo form_open_multipart('migration/to_level');

echo form_hidden('program_level', $program_level, '');
echo form_hidden('base_level', $base_level, '');

echo $this->lang->line("migration_program_level") . ": $program_level" . br();
echo $this->lang->line("migration_base_level") . ": $base_level" . br();
echo "\n" . $this->lang->line("migration_target_level") . ": " . form_dropdown('target_level', $levels, $program_level, "");
echo br(2);

if ($program_level == $base_level) {
	echo $this->lang->line("migration_uptodate") . br();
}

echo validation_button(VALIDATION);

echo form_close('</div>');

echo '</div>';
