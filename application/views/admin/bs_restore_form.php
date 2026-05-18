<!-- VIEW: application/views/admin/bs_restore_form.php -->
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

// Display available backups as a table
if (!empty($backups)) {
    echo '<h4>Sauvegardes disponibles</h4>';
    echo '<div class="table-responsive mb-4">';
    echo '<table class="table table-striped table-hover table-sm align-middle">';
    echo '<thead class="table-dark"><tr>';
    echo '<th>Nom</th><th>Date</th><th>Âge</th><th>Taille</th><th>Type</th><th>Actions</th>';
    echo '</tr></thead><tbody>';
    foreach ($backups as $b) {
        $type_label = $b['type'] === 'media' ? '<span class="badge bg-info">Médias</span>' : '<span class="badge bg-primary">Base de données</span>';
        if ($b['encrypted']) {
            $type_label .= ' <span class="badge bg-warning text-dark">Chiffré</span>';
        }
        echo '<tr>';
        echo '<td class="text-break">' . htmlspecialchars($b['name']) . '</td>';
        echo '<td class="text-nowrap">' . $b['date'] . '</td>';
        echo '<td class="text-nowrap">' . $b['age'] . '</td>';
        echo '<td class="text-nowrap">' . $b['size'] . '</td>';
        echo '<td>' . $type_label . '</td>';
        echo '<td class="text-nowrap">';
        echo '<button class="btn btn-sm btn-success me-1 btn-restore" '
            . 'data-filename="' . htmlspecialchars($b['name'], ENT_QUOTES) . '" '
            . 'data-type="' . $b['type'] . '" '
            . 'data-encrypted="' . ($b['encrypted'] ? '1' : '0') . '" '
            . 'data-bs-toggle="modal" data-bs-target="#restoreModal">'
            . '<i class="fas fa-undo"></i> Restaurer</button>';
        echo '<button class="btn btn-sm btn-danger btn-delete" '
            . 'data-filename="' . htmlspecialchars($b['name'], ENT_QUOTES) . '" '
            . 'data-bs-toggle="modal" data-bs-target="#deleteModal">'
            . '<i class="fas fa-trash"></i> Supprimer</button>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
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
echo '<label for="userfile" class="form-label">Fichier de sauvegarde (.zip, .gz ou .enc.zip, .enc.gz)</label>';
echo '<input type="file" class="form-control" name="userfile" id="userfile" accept=".zip,.gz,.enc.zip,.enc.gz" />';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="passphrase_restore" class="form-label">Passphrase (pour sauvegardes chiffrées)</label>';
echo '<input type="password" class="form-control" name="passphrase" id="passphrase_restore" placeholder="Laisser vide pour utiliser la passphrase par défaut" />';
echo '<div class="form-text">Requis uniquement pour les fichiers chiffrés (.enc.zip). Si vide, la passphrase configurée sera utilisée.</div>';
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
echo '<input type="file" class="form-control" name="userfile" id="userfile_media" accept=".tar,.gz,.tgz,.enc.tar.gz,.enc.tgz,.enc.gz,application/gzip,application/x-tar,application/x-gzip" />';
echo '<div class="form-text">Taille maximum autorisée par le serveur: ' . ini_get('upload_max_filesize') . '</div>';
echo '</div>';

echo '<div class="mb-3">';
echo '<label for="passphrase_restore_media" class="form-label">Passphrase (pour sauvegardes chiffrées)</label>';
echo '<input type="password" class="form-control" name="passphrase" id="passphrase_restore_media" placeholder="Laisser vide pour utiliser la passphrase par défaut" />';
echo '<div class="form-text">Requis uniquement pour les fichiers chiffrés (.enc.tar.gz). Si vide, la passphrase configurée sera utilisée.</div>';
echo '</div>';

echo '<script>
document.getElementById("userfile_media").addEventListener("change", function() {
    const file = this.files[0];
    if (file) {
        const maxSize = ' . (int)(ini_get('upload_max_filesize')) * 1024 * 1024 . ';
        if (file.size > maxSize) {
            alert("Attention: Le fichier sélectionné (" + (file.size / (1024*1024)).toFixed(1) + " MB) dépasse la taille maximum autorisée par le serveur (' . ini_get('upload_max_filesize') . ').\n\nVeuillez contacter l\'administrateur pour augmenter les limites du serveur ou utilisez un fichier plus petit.");
            this.value = "";
        }
    }
});
</script>';

$checked_merge = ' checked="checked" ';

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

// ---- Modal: Supprimer une sauvegarde ----
echo '
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteModalLabel"><i class="fas fa-trash"></i> Confirmer la suppression</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger">Cette action est irréversible. Le fichier sera définitivement supprimé.</div>
        <p>Fichier : <strong id="delete-filename-display"></strong></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <a id="delete-confirm-link" href="#" class="btn btn-danger"><i class="fas fa-trash"></i> Supprimer</a>
      </div>
    </div>
  </div>
</div>';

// ---- Modal: Restaurer une sauvegarde ----
echo '
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="restoreModalLabel"><i class="fas fa-undo"></i> Confirmer la restauration</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <form id="restore-form" method="post" action="">
        <div class="modal-body">
          <div class="alert alert-warning">
            <strong>Attention :</strong> cette opération remplacera les données actuelles par celles de la sauvegarde.
          </div>
          <p>Fichier : <strong id="restore-filename-display"></strong></p>
          <div id="passphrase-section" class="mb-3" style="display:none">
            <label for="modal-passphrase" class="form-label">Passphrase (fichier chiffré)</label>
            <input type="password" class="form-control" name="passphrase" id="modal-passphrase" placeholder="Laisser vide pour utiliser la passphrase par défaut" />
          </div>
          <div id="erase-section" class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="erase_db" id="modal-erase-db" value="1" checked />
            <label class="form-check-label" for="modal-erase-db">Écraser la base de données existante</label>
          </div>
          <div id="merge-section" class="mb-3" style="display:none">
            <div class="form-check">
              <input type="radio" class="form-check-input" name="merge_media" id="modal-merge-yes" value="1" checked />
              <label class="form-check-label" for="modal-merge-yes">Fusion avec les fichiers existants (recommandé)</label>
            </div>
            <div class="form-check">
              <input type="radio" class="form-check-input" name="merge_media" id="modal-merge-no" value="0" />
              <label class="form-check-label" for="modal-merge-no">Remplacement complet</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-success"><i class="fas fa-undo"></i> Restaurer</button>
        </div>
      </form>
    </div>
  </div>
</div>';

$restore_db_url    = controller_url('admin/restore_from_backup');
$restore_media_url = controller_url('admin/restore_media_from_backup');
$delete_url        = controller_url('admin/delete_backup');

echo '<script>
(function() {
    var restoreDbBase    = ' . json_encode($restore_db_url) . ';
    var restoreMediaBase = ' . json_encode($restore_media_url) . ';
    var deleteBase       = ' . json_encode($delete_url) . ';

    document.querySelectorAll(".btn-delete").forEach(function(btn) {
        btn.addEventListener("click", function() {
            var filename = this.dataset.filename;
            document.getElementById("delete-filename-display").textContent = filename;
            document.getElementById("delete-confirm-link").href = deleteBase + "/" + encodeURIComponent(filename);
        });
    });

    document.querySelectorAll(".btn-restore").forEach(function(btn) {
        btn.addEventListener("click", function() {
            var filename  = this.dataset.filename;
            var type      = this.dataset.type;
            var encrypted = this.dataset.encrypted === "1";

            document.getElementById("restore-filename-display").textContent = filename;

            var base = type === "media" ? restoreMediaBase : restoreDbBase;
            document.getElementById("restore-form").action = base + "/" + encodeURIComponent(filename);

            document.getElementById("passphrase-section").style.display = encrypted ? "block" : "none";
            document.getElementById("erase-section").style.display      = type === "db" ? "block" : "none";
            document.getElementById("merge-section").style.display      = type === "media" ? "block" : "none";
        });
    });
})();
</script>';

echo '</div>'; // End body
