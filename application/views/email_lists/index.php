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
        <a href="<?= controller_url($controller) ?>/addresses" class="btn btn-info ms-2">
            <i class="fas fa-paper-plane" aria-hidden="true"></i> <?= translation("Envoi email") ?>
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
                        <th class="text-center"><?= $this->lang->line("email_lists_visibility") ?></th>
                        <th><?= $this->lang->line("email_lists_updated") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lists as $list): ?>
                        <tr>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="<?= controller_url($controller) ?>/view/<?= $list['id'] ?>" class="btn btn-primary"
                                        title="<?= $this->lang->line("email_lists_view") ?>">
                                        <i class="fas fa-eye text-white" aria-hidden="true"></i>
                                    </a>
                                    <a href="<?= controller_url($controller) ?>/edit/<?= $list['id'] ?>"
                                        class="btn btn-secondary" title="<?= $this->lang->line("email_lists_edit") ?>">
                                        <i class="fas fa-edit text-white" aria-hidden="true"></i>
                                    </a>

                                    <?php $confirm_msg = str_replace('{name}', $list['name'], $this->lang->line("email_lists_delete_confirm")); ?>
                                    <a href="<?= controller_url($controller) ?>/delete/<?= $list['id'] ?>"
                                        class="btn btn-danger" title="<?= $this->lang->line("email_lists_delete") ?>"
                                        onclick="return confirm(<?= htmlspecialchars(json_encode($confirm_msg), ENT_QUOTES) ?>)">
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
                                <?php if ($list['can_edit_visible']): ?>
                                    <!-- Editable checkbox for users who can edit -->
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input visibility-toggle" type="checkbox" role="switch"
                                            data-list-id="<?= $list['id'] ?>" <?= $list['visible'] ? 'checked' : '' ?>
                                            title="<?= $list['visible'] ? 'Public' : 'Privé' ?>">
                                        <label class="form-check-label ms-2">
                                            <?php if ($list['visible']): ?>
                                                <span class="badge bg-success visibility-label">
                                                    <i class="fas fa-globe" aria-hidden="true"></i> Public
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark visibility-label">
                                                    <i class="fas fa-lock" aria-hidden="true"></i> Privé
                                                </span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php else: ?>
                                    <!-- Read-only badge for users who cannot edit -->
                                    <?php if ($list['visible']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-globe" aria-hidden="true"></i> Public
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-lock" aria-hidden="true"></i> Privé
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($is_admin): ?>
                                    <!-- Show owner name or username for admins -->
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            <i class="fas fa-user" aria-hidden="true"></i>
                                            <?php
                                                $owner_display = !empty($list['owner_name']) ? $list['owner_name'] : $list['owner_username'];
                                                echo htmlspecialchars($owner_display);
                                            ?>
                                        </small>
                                    </div>
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

<script>
    $(document).ready(function () {
        // Handle visibility toggle checkbox change
        $('.visibility-toggle').on('change', function () {
            const checkbox = $(this);
            const listId = checkbox.data('list-id');
            const newVisible = checkbox.is(':checked');
            const label = checkbox.closest('td').find('.visibility-label');

            // Disable checkbox during AJAX call
            checkbox.prop('disabled', true);

            $.ajax({
                url: '<?= controller_url($controller) ?>/toggle_visible',
                type: 'POST',
                data: {
                    list_id: listId
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // Update the badge label
                        if (response.visible) {
                            label.removeClass('bg-warning text-dark').addClass('bg-success');
                            label.html('<i class="fas fa-globe" aria-hidden="true"></i> Public');
                            checkbox.attr('title', 'Public');
                        } else {
                            label.removeClass('bg-success').addClass('bg-warning text-dark');
                            label.html('<i class="fas fa-lock" aria-hidden="true"></i> Privé');
                            checkbox.attr('title', 'Privé');
                        }

                        // Show success message
                        showAlert('success', response.message || 'Visibilité mise à jour');
                    } else {
                        // Revert checkbox state
                        checkbox.prop('checked', !newVisible);
                        showAlert('danger', response.message || 'Erreur lors de la mise à jour');
                    }
                },
                error: function (xhr, status, error) {
                    // Revert checkbox state
                    checkbox.prop('checked', !newVisible);
                    showAlert('danger', 'Erreur serveur: ' + error);
                },
                complete: function () {
                    // Re-enable checkbox
                    checkbox.prop('disabled', false);
                }
            });
        });

        // Helper function to show alerts
        function showAlert(type, message) {
            const alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                '<strong><i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-triangle') + '" aria-hidden="true"></i></strong> ' +
                message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                '</div>';

            $('#body h3').after(alertHtml);

            // Auto-dismiss after 3 seconds
            setTimeout(function () {
                $('.alert').fadeOut(500, function () {
                    $(this).remove();
                });
            }, 3000);
        }
    });
</script>