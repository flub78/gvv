<!-- VIEW: application/views/admin/bs_backup_form.php -->
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
//    unified backup view

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('admin');

echo '<div id="body" class="body container-fluid">';

echo heading("Sauvegardes", 3);

echo '<div class="row">';

// Database backup section
echo '<div class="col-lg-6 col-md-12 mb-4">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h4>' . $this->lang->line("gvv_admin_menu_backup") . '</h4>';
echo '</div>';
echo '<div class="card-body">';
echo '<p>' . $this->lang->line("gvv_admin_menu_backup") . ' - Télécharger une sauvegarde complète de la base de données au format ZIP.</p>';

echo form_open('admin/backup');

echo '<div class="mb-3 form-check">';
echo '<input type="checkbox" class="form-check-input" name="encrypt_backup" id="encrypt_backup_db" value="1" />';
echo '<label class="form-check-label" for="encrypt_backup_db">Sauvegarde chiffrée</label>';
echo '</div>';

echo '<div class="mb-3" id="passphrase_db_container" style="display:none;">';
echo '<label for="passphrase_db" class="form-label">Passphrase (optionnel)</label>';
echo '<input type="password" class="form-control" name="passphrase" id="passphrase_db" placeholder="Laisser vide pour utiliser la passphrase par défaut" />';
echo '<div class="form-text">Si vide, la passphrase configurée dans le système sera utilisée.</div>';
echo '</div>';

echo '<div class="d-grid gap-2">';
echo '<button type="submit" name="type" value="" class="btn btn-primary btn-lg mb-2">Sauvegarde complète</button>';

if (ENVIRONMENT == 'development') {
    echo '<p>Options avancées (uniquement en mode développement) :</p>';
    echo '<button type="submit" name="type" value="structure" class="btn btn-secondary mb-2">Structure seulement</button>';
    echo '<button type="submit" name="type" value="defaut" class="btn btn-secondary mb-2">Tables par défaut</button>';
}

echo '</div>';
echo form_close();

echo '<script>
document.getElementById("encrypt_backup_db").addEventListener("change", function() {
    document.getElementById("passphrase_db_container").style.display = this.checked ? "block" : "none";
});
</script>';
echo '</div>';
echo '</div>';
echo '</div>';

// Media backup section
echo '<div class="col-lg-6 col-md-12 mb-4">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h4>' . $this->lang->line("gvv_admin_media_backup_title") . '</h4>';
echo '</div>';
echo '<div class="card-body">';
echo '<p>' . $this->lang->line("gvv_admin_media_backup_desc") . ' au format TAR.GZ.</p>';

echo form_open('admin/backup_media');

echo '<div class="mb-3 form-check">';
echo '<input type="checkbox" class="form-check-input" name="encrypt_backup" id="encrypt_backup_media" value="1" />';
echo '<label class="form-check-label" for="encrypt_backup_media">Sauvegarde chiffrée</label>';
echo '</div>';

echo '<div class="mb-3" id="passphrase_media_container" style="display:none;">';
echo '<label for="passphrase_media" class="form-label">Passphrase (optionnel)</label>';
echo '<input type="password" class="form-control" name="passphrase" id="passphrase_media" placeholder="Laisser vide pour utiliser la passphrase par défaut" />';
echo '<div class="form-text">Si vide, la passphrase configurée dans le système sera utilisée.</div>';
echo '</div>';

echo '<div class="d-grid gap-2">';
echo '<button type="submit" class="btn btn-success btn-lg mb-2">Sauvegarde médias</button>';
echo '</div>';
echo form_close();

echo '<script>
document.getElementById("encrypt_backup_media").addEventListener("change", function() {
    document.getElementById("passphrase_media_container").style.display = this.checked ? "block" : "none";
});
</script>';

echo '<div class="alert alert-info mt-3">';
echo '<small><strong>Note:</strong> La sauvegarde des médias inclut tous les fichiers du répertoire uploads/ sauf le dossier de restauration temporaire.</small>';
echo '</div>';

echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // End row

echo '</div>'; // End container
