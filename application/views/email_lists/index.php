<!-- VIEW: application/views/email_lists/index.php -->
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
 * Vue liste des listes de diffusion email
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('email_lists');

?>
<div id="body" class="body container-fluid">
    <h3><?= $this->lang->line("email_lists_title") ?></h3>

<?php
// Show success message
if ($this->session->flashdata('success')) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<strong><i class="fas fa-check-circle" aria-hidden="true"></i></strong> ';
    echo nl2br(htmlspecialchars($this->session->flashdata('success')));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// Show error message
if ($this->session->flashdata('error')) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<strong><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></strong> ';
    echo nl2br(htmlspecialchars($this->session->flashdata('error')));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}
?>

    <!-- Action buttons -->
    <div class="mb-3">
        <a href="<?= controller_url($controller) ?>/create" class="btn btn-primary">
            <i class="fas fa-plus" aria-hidden="true"></i> <?= $this->lang->line("email_lists_create") ?>
        </a>
    </div>

    <!-- Lists table -->
    <?php if (empty($lists)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle" aria-hidden="true"></i> <?= $this->lang->line("email_lists_no_lists") ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover datatable" id="email-lists-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 120px;"><?= $this->lang->line("gvv_str_actions") ?></th>
                        <th><?= $this->lang->line("email_lists_name") ?></th>
                        <th><?= $this->lang->line("email_lists_description") ?></th>
                        <th class="text-center"><?= $this->lang->line("email_lists_recipient_count") ?></th>
                        <th class="text-center"><?= $this->lang->line("email_lists_visible") ?></th>
                        <th><?= $this->lang->line("email_lists_updated") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lists as $list): ?>
                    <tr>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?= controller_url($controller) ?>/view/<?= $list['id'] ?>"
                                   class="btn btn-primary"
                                   title="<?= $this->lang->line("email_lists_view") ?>">
                                    <i class="fas fa-eye text-white" aria-hidden="true"></i>
                                </a>
                                <a href="<?= controller_url($controller) ?>/edit/<?= $list['id'] ?>"
                                   class="btn btn-secondary"
                                   title="<?= $this->lang->line("email_lists_edit") ?>">
                                    <i class="fas fa-edit text-white" aria-hidden="true"></i>
                                </a>
                                <a href="<?= controller_url($controller) ?>/delete/<?= $list['id'] ?>"
                                   class="btn btn-danger"
                                   title="<?= $this->lang->line("email_lists_delete") ?>"
                                   onclick="return confirm('<?= $this->lang->line("email_lists_delete_confirm") ?>')">
                                    <i class="fas fa-trash text-white" aria-hidden="true"></i>
                                </a>
                            </div>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($list['name']) ?></strong>
                        </td>
                        <td>
                            <?= htmlspecialchars(substr($list['description'], 0, 100)) ?>
                            <?= strlen($list['description']) > 100 ? '...' : '' ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary"><?= $list['recipient_count'] ?></span>
                        </td>
                        <td class="text-center">
                            <?php if ($list['visible']): ?>
                                <i class="fas fa-eye text-success" aria-hidden="true"></i>
                            <?php else: ?>
                                <i class="fas fa-eye-slash text-muted" aria-hidden="true"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= date('d/m/Y H:i', strtotime($list['updated_at'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
