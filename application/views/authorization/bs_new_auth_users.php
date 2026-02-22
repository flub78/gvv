<!-- VIEW: application/views/authorization/bs_new_auth_users.php -->
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
 * Vue: gestion de la table use_new_authorization (migration par utilisateur)
 *
 * @package views
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid">

    <h3><i class="fas fa-user-cog text-primary"></i> <?= htmlspecialchars($title) ?></h3>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Cochez un utilisateur pour l'activer sur le <strong>nouveau système d'autorisations</strong>.
        Décochez pour le repasser en mode <strong>legacy</strong>. Les modifications sont immédiates.
    </div>

    <div id="toggle-status" class="mb-2" style="min-height:28px;"></div>

    <div class="card">
        <div class="card-body p-2">
            <table id="new-auth-table" class="datatable table table-striped table-bordered table-sm">
                <thead>
                    <tr>
                        <th style="width:60px;" class="text-center">Nouveau<br>système</th>
                        <th>Utilisateur</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle legacy</th>
                        <th>Activé le</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox"
                                   class="new-auth-toggle form-check-input"
                                   data-username="<?= htmlspecialchars($user['username']) ?>"
                                   <?= $user['is_migrated'] ? 'checked' : '' ?>>
                        </td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= ($user['mnom'] || $user['mprenom']) ? htmlspecialchars(trim($user['mprenom'] . ' ' . $user['mnom'])) : '<span class="text-muted">-</span>' ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?php if ($user['role_name']): ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($user['role_name']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="created-at"><?= $user['created_at'] ? htmlspecialchars($user['created_at']) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="<?= site_url('authorization') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<script>
$(document).ready(function () {
    var ajaxUrl = '<?= site_url('authorization/ajax_toggle_new_auth') ?>';

    $(document).on('change', '.new-auth-toggle', function () {
        var checkbox = $(this);
        var username = checkbox.data('username');
        var enabled  = checkbox.is(':checked');

        checkbox.prop('disabled', true);

        $.ajax({
            url:      ajaxUrl,
            type:     'POST',
            data:     { username: username, enabled: enabled ? 1 : 0 },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    var icon = enabled
                        ? '<i class="fas fa-check-circle text-success"></i>'
                        : '<i class="fas fa-minus-circle text-warning"></i>';
                    var action = enabled ? 'activé sur' : 'retiré du';
                    $('#toggle-status').html(
                        '<div class="alert alert-' + (enabled ? 'success' : 'warning') + ' py-1 mb-1">'
                        + icon + ' <strong>' + username + '</strong> ' + action + ' nouveau système.'
                        + '</div>'
                    );
                    // Update the "Activé le" cell
                    var createdAt = (enabled && response.created_at) ? response.created_at : '-';
                    checkbox.closest('tr').find('.created-at').text(createdAt);
                } else {
                    $('#toggle-status').html(
                        '<div class="alert alert-danger py-1 mb-1">'
                        + '<i class="fas fa-times-circle"></i> Erreur : '
                        + (response.message || 'Erreur inconnue') + '</div>'
                    );
                    checkbox.prop('checked', !enabled);
                }
            },
            error: function () {
                $('#toggle-status').html(
                    '<div class="alert alert-danger py-1 mb-1">'
                    + '<i class="fas fa-times-circle"></i> Erreur de communication avec le serveur.</div>'
                );
                checkbox.prop('checked', !enabled);
            },
            complete: function () {
                checkbox.prop('disabled', false);
            }
        });
    });
});
</script>
