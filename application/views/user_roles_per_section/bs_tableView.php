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
 * Vue table pour les terrains
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('sections');

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array('username', 'email', 'section_name', 'role_type'),
    'mode' => "rw",
    'class' => "datatable table table-striped"
);
?>

<div id="body" class="body container-fluid">
    <h3><?= $this->lang->line("gvv_user_roles_per_section_title") ?></h3>
    <input type="hidden" name="controller_url" value="<?= controller_url($controller) ?>" />

    <div>
        <div style="width: fit-content; float: left; margin-bottom: 10px;">
            <?= $this->lang->line("gvv_sections_element") . " : " ?>
            <?= dropdown_field('section', $this->session->userdata('section'), $section_selector, 'class="form-control big_select" onchange="updateSection(this.value)"') ?>
        </div>
        <div style="clear: both;"><?= $this->gvvmetadata->table("vue_user_roles_per_section", $attrs, "") ?></div>
        <script>
            function updateSection(value) {
                $.post('<?= site_url('user_roles_per_section/set_section') ?>', {
                    section: value
                }, function() {
                    window.location.reload();
                });
            }
        </script>
    </div>



</div>