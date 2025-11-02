<!-- VIEW: application/views/email_lists/form.php -->
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
 * Vue formulaire de création/édition de liste de diffusion
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('email_lists');

$is_edit = isset($list['id']);
$list_id = $is_edit ? $list['id'] : 0;
?>
<div id="body" class="body container-fluid">
    <h3><?= $title ?></h3>

<?php
// Show validation errors
if (validation_errors()) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-exclamation-triangle-fill"></i></strong> ';
    echo validation_errors();
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// Show error message
if ($this->session->flashdata('error')) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-exclamation-triangle-fill"></i></strong> ';
    echo nl2br(htmlspecialchars($this->session->flashdata('error')));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}
?>

    <form action="<?= controller_url($controller) ?>/<?= $action ?><?= $is_edit ? '/' . $list_id : '' ?>"
          method="post"
          accept-charset="utf-8"
          name="email_list_form"
          id="email_list_form">

        <!-- Basic information -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row mb-3">
                    <label for="name" class="col-sm-2 col-form-label">
                        <?= $this->lang->line("email_lists_name") ?> <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-10">
                        <input type="text"
                               class="form-control"
                               id="name"
                               name="name"
                               value="<?= htmlspecialchars(set_value('name', $list['name'])) ?>"
                               required>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="description" class="col-sm-2 col-form-label">
                        <?= $this->lang->line("email_lists_description") ?>
                    </label>
                    <div class="col-sm-10">
                        <textarea class="form-control"
                                  id="description"
                                  name="description"
                                  rows="3"><?= htmlspecialchars(set_value('description', $list['description'])) ?></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="active_member" class="col-sm-2 col-form-label">
                        <?= $this->lang->line("email_lists_active_member") ?>
                    </label>
                    <div class="col-sm-10">
                        <select class="form-select" id="active_member" name="active_member">
                            <option value="active" <?= set_select('active_member', 'active', $list['active_member'] == 'active') ?>>
                                <?= $this->lang->line("email_lists_active_members_only") ?>
                            </option>
                            <option value="inactive" <?= set_select('active_member', 'inactive', $list['active_member'] == 'inactive') ?>>
                                <?= $this->lang->line("email_lists_inactive_members_only") ?>
                            </option>
                            <option value="all" <?= set_select('active_member', 'all', $list['active_member'] == 'all') ?>>
                                <?= $this->lang->line("email_lists_all_members") ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-10 offset-sm-2">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="visible"
                                   name="visible"
                                   value="1"
                                   <?= set_checkbox('visible', '1', $list['visible']) ?>>
                            <label class="form-check-label" for="visible">
                                <?= $this->lang->line("email_lists_visible") ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for list sources -->
        <ul class="nav nav-tabs mb-3" id="listTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active"
                        id="criteria-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#criteria"
                        type="button"
                        role="tab"
                        aria-controls="criteria"
                        aria-selected="true">
                    <i class="bi bi-funnel"></i> <?= $this->lang->line("email_lists_tab_criteria") ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link"
                        id="manual-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#manual"
                        type="button"
                        role="tab"
                        aria-controls="manual"
                        aria-selected="false">
                    <i class="bi bi-person-plus"></i> <?= $this->lang->line("email_lists_tab_manual") ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link"
                        id="import-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#import"
                        type="button"
                        role="tab"
                        aria-controls="import"
                        aria-selected="false">
                    <i class="bi bi-file-earmark-arrow-up"></i> <?= $this->lang->line("email_lists_tab_import") ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="listTabsContent">
            <!-- Criteria tab -->
            <div class="tab-pane fade show active"
                 id="criteria"
                 role="tabpanel"
                 aria-labelledby="criteria-tab">
                <?php $this->load->view('email_lists/_criteria_tab'); ?>
            </div>

            <!-- Manual selection tab -->
            <div class="tab-pane fade"
                 id="manual"
                 role="tabpanel"
                 aria-labelledby="manual-tab">
                <?php $this->load->view('email_lists/_manual_tab'); ?>
            </div>

            <!-- Import tab -->
            <div class="tab-pane fade"
                 id="import"
                 role="tabpanel"
                 aria-labelledby="import-tab">
                <?php $this->load->view('email_lists/_import_tab'); ?>
            </div>
        </div>

        <!-- Form actions -->
        <div class="mt-4 mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> <?= $this->lang->line("gvv_str_save") ?>
            </button>
            <a href="<?= controller_url($controller) ?>" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> <?= $this->lang->line("gvv_str_cancel") ?>
            </a>
        </div>

    </form>

</div>

<?php
$this->load->view('bs_footer');
?>
