<!-- VIEW: application/views/authorization/role_form.php -->
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
 * Role Create/Edit Form View
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
            <li class="breadcrumb-item"><a href="<?= site_url('authorization/roles') ?>"><?= $this->lang->line('authorization_roles') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $title ?></li>
        </ol>
    </nav>

    <h3><?= $title ?></h3>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-body">
            <form action="<?= site_url('authorization/save_role') ?>" method="post">
                <input type="hidden" name="id" value="<?= isset($role['id']) ? $role['id'] : '' ?>">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

                <div class="mb-3">
                    <label for="nom" class="form-label"><?= $this->lang->line('authorization_role_name') ?> *</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($role['nom']) ?>" required>
                    <small class="form-text text-muted"><?= $this->lang->line('authorization_role_name_help') ?></small>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label"><?= $this->lang->line('authorization_role_description') ?> *</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required><?= htmlspecialchars($role['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="scope" class="form-label"><?= $this->lang->line('authorization_role_scope') ?> *</label>
                    <select class="form-control" id="scope" name="scope" required>
                        <option value="global" <?= $role['scope'] === 'global' ? 'selected' : '' ?>><?= $this->lang->line('authorization_global') ?></option>
                        <option value="section" <?= $role['scope'] === 'section' ? 'selected' : '' ?>><?= $this->lang->line('authorization_section') ?></option>
                    </select>
                    <small class="form-text text-muted"><?= $this->lang->line('authorization_scope_help') ?></small>
                </div>

                <div class="mb-3">
                    <label for="translation_key" class="form-label"><?= $this->lang->line('authorization_translation_key') ?></label>
                    <input type="text" class="form-control" id="translation_key" name="translation_key" value="<?= htmlspecialchars($role['translation_key']) ?>">
                    <small class="form-text text-muted"><?= $this->lang->line('authorization_translation_key_help') ?></small>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_system_role" name="is_system_role" value="1" <?= $role['is_system_role'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_system_role">
                        <?= $this->lang->line('authorization_is_system_role') ?>
                    </label>
                    <small class="form-text text-muted d-block"><?= $this->lang->line('authorization_system_role_help') ?></small>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $this->lang->line('authorization_save') ?>
                    </button>
                    <a href="<?= site_url('authorization/roles') ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> <?= $this->lang->line('authorization_cancel') ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
