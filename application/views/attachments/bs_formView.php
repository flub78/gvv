<!-- VIEW: application/views/attachments/bs_formView.php -->
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

	<div class="card uper">
		<div class="card-header">
			<h3><?= $this->lang->line("gvv_attachments_title") ?> </h3>
		</div>
		<div class="card-body">

			<p><?= $image ?></p>
			<form action="<?= controller_url($controller) . '/formValidation/' . $action ?>" method="post" accept-charset="utf-8" name="saisie" enctype="multipart/form-data">
				<input type="hidden" name="referenced_table" value="<?= $referenced_table ?>" />
				<input type="hidden" name="referenced_id" value="<?= $referenced_id ?>" />
				<input type="hidden" name="user_id" value="<?= $user_id ?>" />
				<input type="hidden" name="club" value="<?= isset($club) ? $club : 0 ?>" />

				<?php
				// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
				if (isset($kid) && isset($$kid)) {
					echo '<input type="hidden" name="original_' . $kid . '" value="' . $$kid . '" />';
				}
				?>

				<?= ($this->gvvmetadata->form('attachments', array(
					'description' => $description,
					'file' => $file
				))); ?>
				<?= validation_button($action, FALSE,FALSE); ?>
			</form>
		</div>
	</div>
</div>

<style>
.drop-zone {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background-color 0.2s;
    background: #fafafa;
}
.drop-zone.drag-over {
    border-color: #0d6efd;
    background-color: #e8f0fe;
}
.drop-zone.has-file {
    border-color: #198754;
    background-color: #f0fff4;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[type="file"]').forEach(function (input) {
        var inputId = input.id || input.name;

        // Create drop zone wrapper
        var zone = document.createElement('div');
        zone.className = 'drop-zone';

        var icon = document.createElement('i');
        icon.className = 'fas fa-cloud-upload-alt fa-2x text-muted mb-2';

        var text = document.createElement('p');
        text.className = 'mb-1';
        text.textContent = <?= json_encode($this->lang->line('gvv_drop_file_here')) ?>;

        var orText = document.createElement('p');
        orText.className = 'text-muted small';
        orText.textContent = <?= json_encode($this->lang->line('gvv_or')) ?>;

        var btnLabel = document.createElement('label');
        btnLabel.className = 'btn btn-outline-secondary btn-sm';
        if (input.id) btnLabel.setAttribute('for', input.id);
        btnLabel.innerHTML = '<i class="fas fa-folder-open"></i> ' + <?= json_encode($this->lang->line('gvv_choose_file')) ?>;

        var filenameLabel = document.createElement('p');
        filenameLabel.className = 'mt-2 small text-muted';
        filenameLabel.textContent = <?= json_encode($this->lang->line('gvv_no_file_selected')) ?>;

        input.classList.add('d-none');

        zone.appendChild(icon);
        zone.appendChild(document.createElement('br'));
        zone.appendChild(text);
        zone.appendChild(orText);
        zone.appendChild(btnLabel);
        zone.appendChild(input.cloneNode(true));
        zone.appendChild(filenameLabel);

        // Replace original input with zone
        input.parentNode.replaceChild(zone, input);

        var newInput = zone.querySelector('input[type="file"]');

        function updateFilename(files) {
            if (files && files.length > 0) {
                filenameLabel.textContent = files[0].name;
                zone.classList.add('has-file');
            }
        }

        zone.addEventListener('click', function (e) {
            if (e.target.tagName !== 'LABEL' && e.target.tagName !== 'INPUT') {
                newInput.click();
            }
        });

        newInput.addEventListener('change', function () {
            updateFilename(this.files);
        });

        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            zone.classList.add('drag-over');
        });

        zone.addEventListener('dragleave', function () {
            zone.classList.remove('drag-over');
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.classList.remove('drag-over');
            var dt = e.dataTransfer;
            if (dt.files.length > 0) {
                newInput.files = dt.files;
                updateFilename(dt.files);
            }
        });
    });
});
</script>