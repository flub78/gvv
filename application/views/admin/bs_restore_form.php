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

echo heading("gvv_admin_title_restore", 3);

// Display errors if any
if (!empty($error)) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
}

// Display available backups
if (isset($backups)) {
    echo heading("Sauvegardes de base de données disponibles", 4);
    echo ul($backups);
    echo br();
}

echo '<div class="row">';

// Database restore section
echo '<div class="col-lg-6 col-md-12 mb-4">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h4>' . $this->lang->line("gvv_admin_menu_restore") . '</h4>';
echo '</div>';
echo '<div class="card-body">';

echo '<div class="alert alert-warning">';
echo p($this->lang->line("gvv_admin_db_warning"));
echo '</div>';

echo p($this->lang->line("gvv_admin_db_select"));

echo form_open_multipart('admin/do_restore');
echo '<div class="mb-3">';
echo '<label for="userfile" class="form-label">Fichier de sauvegarde (.zip ou .gz)</label>';
echo '<input type="file" class="form-control" name="userfile" id="userfile" accept=".zip,.gz" />';
echo '</div>';

$checked = "";
if (isset($erase_db) && $erase_db) {
    $checked = ' checked="checked" ';
}

echo '<div class="mb-3 form-check">';
echo '<input type="checkbox" class="form-check-input" name="erase_db" id="erase_db" value="1"' . $checked . ' />';
echo '<label class="form-check-label" for="erase_db">' . $this->lang->line("gvv_admin_db_overwrite") . '</label>';
echo '</div>';

echo '<button type="submit" class="btn btn-primary">' . $this->lang->line("gvv_button_validate") . '</button>';
echo form_close();

echo '</div>';
echo '</div>';
echo '</div>';

// Media restore section
echo '<div class="col-lg-6 col-md-12 mb-4">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h4>Restauration des médias</h4>';
echo '</div>';
echo '<div class="card-body">';

echo '<div class="alert alert-info">';
echo '<p><strong>Mode de restauration :</strong></p>';
echo '<p><strong>Fusion (recommandé) :</strong> Les nouveaux fichiers sont ajoutés, les fichiers existants avec le même nom sont remplacés.</p>';
echo '<p><strong>Remplacement complet :</strong> Tous les fichiers existants sont supprimés avant la restauration.</p>';
echo '</div>';

echo '<p>Sélectionnez un fichier de sauvegarde des médias (formats acceptés: .tar.gz, .tgz, .tar, .gz)</p>';

echo form_open_multipart('admin/do_restore_media');
echo '<div class="mb-3">';
echo '<label for="userfile_media" class="form-label">Fichier de sauvegarde des médias</label>';
echo '<input type="file" class="form-control" name="userfile" id="userfile_media" accept=".tar,.gz,.tgz,application/gzip,application/x-tar,application/x-gzip" />';
echo '<div class="form-text">Taille maximum autorisée par le serveur: ' . ini_get('upload_max_filesize') . '</div>';
echo '</div>';

// Add JavaScript for client-side file size validation
echo '<script>
document.getElementById("userfile_media").addEventListener("change", function() {
    const file = this.files[0];
    if (file) {
        const maxSize = ' . (int)(ini_get('upload_max_filesize')) * 1024 * 1024 . '; // Convert to bytes
        const fileSize = file.size;
        
        if (fileSize > maxSize) {
            alert("Attention: Le fichier sélectionné (" + (fileSize / (1024*1024)).toFixed(1) + " MB) dépasse la taille maximum autorisée par le serveur (' . ini_get('upload_max_filesize') . ').\n\nVeuillez contacter l\'administrateur pour augmenter les limites du serveur ou utilisez un fichier plus petit.");
            this.value = "";
        }
    }
});
</script>';

$checked_merge = isset($merge_media) && $merge_media ? ' checked="checked" ' : ' checked="checked" '; // Default to merge

echo '<div class="mb-3">';
echo '<div class="form-check">';
echo '<input type="radio" class="form-check-input" name="merge_media" id="merge_media_yes" value="1"' . $checked_merge . ' />';
echo '<label class="form-check-label" for="merge_media_yes">Fusion avec les fichiers existants</label>';
echo '</div>';
echo '<div class="form-check">';
echo '<input type="radio" class="form-check-input" name="merge_media" id="merge_media_no" value="0" />';
echo '<label class="form-check-label" for="merge_media_no">Remplacement complet</label>';
echo '</div>';
echo '</div>';

echo '<button type="submit" class="btn btn-success">Restaurer les médias</button>';
echo form_close();

echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // End row
