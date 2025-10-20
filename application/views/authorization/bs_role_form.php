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
 * Role Form View (Create/Edit)
 *
 * @package views
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= site_url('authorization') ?>">Authorization</a></li>
            <li class="breadcrumb-item"><a href="<?= site_url('authorization/roles') ?>">Roles</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $title ?></li>
        </ol>
    </nav>

    <h3><?= $title ?></h3>

    <?php if (!empty($message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-body">
            <?= form_open('', array('class' => 'needs-validation', 'novalidate' => '')) ?>
                
                <div class="mb-3">
                    <label for="nom" class="form-label">
                        <?= $this->lang->line('authorization_role_name') ?> <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="nom" 
                           name="nom" 
                           value="<?= isset($role['nom']) ? htmlspecialchars($role['nom']) : '' ?>" 
                           required
                           maxlength="100">
                    <div class="invalid-feedback">
                        Please enter a role name.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">
                        <?= $this->lang->line('authorization_role_description') ?> <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" 
                              id="description" 
                              name="description" 
                              rows="3" 
                              required><?= isset($role['description']) ? htmlspecialchars($role['description']) : '' ?></textarea>
                    <div class="invalid-feedback">
                        Please enter a description.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="scope" class="form-label">
                        <?= $this->lang->line('authorization_role_scope') ?> <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="scope" name="scope" required>
                        <option value="section" <?= (isset($role['scope']) && $role['scope'] === 'section') ? 'selected' : '' ?>>
                            <?= $this->lang->line('authorization_section') ?>
                        </option>
                        <option value="global" <?= (isset($role['scope']) && $role['scope'] === 'global') ? 'selected' : '' ?>>
                            <?= $this->lang->line('authorization_global') ?>
                        </option>
                    </select>
                    <div class="form-text">
                        Section roles are assigned per section, global roles apply across all sections.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="translation_key" class="form-label">
                        <?= $this->lang->line('authorization_translation_key') ?>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="translation_key" 
                           name="translation_key" 
                           value="<?= isset($role['translation_key']) ? htmlspecialchars($role['translation_key']) : '' ?>"
                           maxlength="100">
                    <div class="form-text">
                        Optional language file key for translatable role name (e.g., 'authorization_role_member')
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $this->lang->line('gvv_button_save') ?>
                    </button>
                    <a href="<?= site_url('authorization/roles') ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> <?= $this->lang->line('gvv_button_cancel') ?>
                    </a>
                </div>

            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
// Bootstrap form validation
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php $this->load->view('bs_footer'); ?>
