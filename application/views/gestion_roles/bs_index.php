<!-- VIEW: application/views/gestion_roles/bs_index.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->load->helper('form_elements');

// Build role-id → section_id set from current user roles
$checked = array(); // $checked[$role_id][$section_id] = true
foreach ($user_roles as $r) {
    $checked[$r['types_roles_id']][$r['section_id']] = true;
}

// Filter sections (exclude meta-sections 0 and 89)
$real_sections = array_filter($sections, function($s) { return $s['id'] != 0 && $s['id'] != 89; });
?>

<div id="body" class="body container-fluid">
    <h3><?= $this->lang->line('gvv_gestion_roles_title') ?></h3>

    <!-- Sélecteur d'utilisateur -->
    <div class="card mb-4">
        <div class="card-body">
            <label class="form-label fw-bold" for="user-selector">
                <i class="fas fa-user me-1"></i><?= $this->lang->line('authorization_username') ?>
            </label>
            <?= form_open(site_url('gestion_roles/index'), array('id' => 'user-select-form', 'method' => 'get')) ?>
            <?= dropdown_field('user_id', $selected_user_id, $user_selector,
                "id='user-selector' class='form-select big_select' onchange='select_user(this.value)'") ?>
            <?= form_close() ?>
        </div>
    </div>

    <?php if ($selected_user): ?>

    <!-- Informations utilisateur sélectionné -->
    <div class="alert alert-light border mb-3">
        <strong><?php
            $display = trim($selected_user['mnom'] . ' ' . $selected_user['mprenom']);
            echo htmlspecialchars($display ?: $selected_user['username']);
        ?></strong>
        <span class="text-muted ms-2">(<?= htmlspecialchars($selected_user['username']) ?>)</span>
        <?php if (!empty($selected_user['section_name'])): ?>
            — <?= htmlspecialchars($selected_user['section_name']) ?>
        <?php endif; ?>
    </div>

    <!-- Tableau des rôles -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-user-tag me-2"></i><?= $this->lang->line('authorization_current_roles') ?>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="roles-table">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:180px"><?= $this->lang->line('authorization_role') ?></th>
                            <th class="text-center text-nowrap">Toutes</th>
                            <?php foreach ($real_sections as $section): ?>
                            <th class="text-center text-nowrap"
                                style="background-color: <?= htmlspecialchars($section['couleur'] ?? '#e9ecef') ?>; color: black;">
                                <?= htmlspecialchars($section['nom']) ?>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_roles as $role):
                            $label = (!empty($role['translation_key']) && $this->lang->line($role['translation_key']))
                                ? $this->lang->line($role['translation_key'])
                                : $role['nom'];
                            $desc_key = !empty($role['translation_key']) ? $role['translation_key'] . '_desc' : '';
                            $desc = ($desc_key && $this->lang->line($desc_key))
                                ? $this->lang->line($desc_key)
                                : ($role['description'] ?? '');
                            // "Toutes" is checked when all real sections are checked
                            $all_checked = count($real_sections) > 0 && !array_filter($real_sections, function($s) use ($checked, $role) {
                                return empty($checked[$role['id']][$s['id']]);
                            });
                        ?>
                        <tr data-role-id="<?= $role['id'] ?>">
                            <td>
                                <?php if ($desc): ?>
                                <span data-bs-toggle="tooltip" data-bs-placement="right"
                                      title="<?= htmlspecialchars($desc) ?>">
                                    <?= htmlspecialchars($label) ?>
                                    <i class="fas fa-info-circle text-muted small ms-1"></i>
                                </span>
                                <?php else: ?>
                                <?= htmlspecialchars($label) ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input role-checkbox role-checkbox-all"
                                       data-role-id="<?= $role['id'] ?>"
                                       <?= $all_checked ? 'checked' : '' ?>>
                            </td>
                            <?php foreach ($real_sections as $section): ?>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input role-checkbox role-checkbox-section"
                                       data-role-id="<?= $role['id'] ?>"
                                       data-section-id="<?= $section['id'] ?>"
                                       <?= !empty($checked[$role['id']][$section['id']]) ? 'checked' : '' ?>>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Feedback opération -->
    <div id="feedback" class="mt-2" style="min-height:1.5em"></div>

    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i><?= $this->lang->line('gvv_gestion_roles_select_hint') ?>
    </div>
    <?php endif; ?>

</div>

<script>
var ajaxUrl    = '<?= site_url('gestion_roles/edit_user_roles') ?>';
var csrfName   = '<?= $this->security->get_csrf_token_name() ?>';
var csrfHash   = '<?= $this->security->get_csrf_hash() ?>';
var selectedUserId = <?= $selected_user_id ? (int)$selected_user_id : 'null' ?>;

function select_user(uid) {
    if (uid) {
        window.location.href = '<?= site_url('gestion_roles/index') ?>/' + uid;
    }
}

function showFeedback(msg, success) {
    var $fb = $('#feedback');
    $fb.html('<div class="alert alert-' + (success ? 'success' : 'danger') + ' py-1 px-3 d-inline-block">' + msg + '</div>');
    setTimeout(function() { $fb.html(''); }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
});

$(document).ready(function() {

    <?php if ($selected_user): ?>

    $(document).on('change', '.role-checkbox', function() {
        var $cb       = $(this);
        var roleId    = $cb.data('role-id');
        var isChecked = $cb.is(':checked');
        var action    = isChecked ? 'grant' : 'revoke';
        var sectionId = $cb.hasClass('role-checkbox-all') ? -1 : $cb.data('section-id');

        $cb.prop('disabled', true);

        var payload = {
            user_id:       selectedUserId,
            types_roles_id: roleId,
            section_id:    sectionId,
            action:        action
        };
        payload[csrfName] = csrfHash;

        $.ajax({
            url:      ajaxUrl,
            type:     'POST',
            data:     payload,
            dataType: 'json',
            success: function(resp) {
                csrfHash = resp.csrf_hash || csrfHash;
                if (resp.success) {
                    // Reconstruire l'état des cases à partir de la réponse
                    var rolesMap = {};
                    (resp.roles || []).forEach(function(r) {
                        if (!rolesMap[r.types_roles_id]) rolesMap[r.types_roles_id] = {};
                        rolesMap[r.types_roles_id][r.section_id] = true;
                    });

                    // Mettre à jour toutes les cases de ce rôle
                    $('[data-role-id="' + roleId + '"].role-checkbox-section').each(function() {
                        var sid = $(this).data('section-id');
                        $(this).prop('checked', !!(rolesMap[roleId] && rolesMap[roleId][sid]));
                    });

                    // Mettre à jour "Toutes"
                    var $allSections = $('[data-role-id="' + roleId + '"].role-checkbox-section');
                    var checkedCount = $allSections.filter(':checked').length;
                    $('[data-role-id="' + roleId + '"].role-checkbox-all').prop(
                        'checked', $allSections.length > 0 && checkedCount === $allSections.length
                    );

                    showFeedback('<?= $this->lang->line('gvv_role_saved') ?>', true);
                } else {
                    $cb.prop('checked', !isChecked);
                    showFeedback(resp.message || 'Erreur', false);
                }
            },
            error: function() {
                $cb.prop('checked', !isChecked);
                showFeedback('Erreur de communication', false);
            },
            complete: function() {
                $cb.prop('disabled', false);
            }
        });
    });

    <?php endif; ?>
});
</script>
