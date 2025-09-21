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
//    base restauration view

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('admin');

echo '<div id="body" class="body container-fluid">';

if (isset($restore_type) && $restore_type === 'media') {
    echo heading("Restauration des médias", 3);
    echo '<div class="alert alert-success">';
    echo '<i class="fas fa-check-circle"></i> ';
    echo $this->lang->line("gvv_admin_media_success") . " " . $file_name;
    echo '</div>';
    echo '<p>Les fichiers médias ont été restaurés avec succès dans le répertoire uploads/.</p>';
} else {
    echo heading("gvv_admin_title_restore", 3);
    echo '<div class="alert alert-success">';
    echo '<i class="fas fa-check-circle"></i> ';
    echo $this->lang->line("gvv_admin_db_success") . " " . $file_name;
    echo '</div>';
}

echo '<div class="mt-3">';
echo '<a href="' . controller_url('admin/backup_form') . '" class="btn btn-primary me-2">Retour aux sauvegardes</a>';
echo '<a href="' . controller_url('admin/restore') . '" class="btn btn-secondary">Nouvelle restauration</a>';
echo '</div>';

echo "</div>";
