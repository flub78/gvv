<?php
//    GVV Gestion vol à voile
//    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// Accès interdit
// ----------------------------------------------------------------------------------------

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

$this->lang->load('welcome');

echo '<div id="body" class="body ui-widget-content">';

echo heading("welcome_forbiden_title", 3);

echo "<p>" . $this->lang->line("welcome_forbiden_text") . "</p>";
$this->load->library('session');
echo "<p>Your current role is: " . $this->session->userdata('DX_role_name') . "</p>";
?>
</div>