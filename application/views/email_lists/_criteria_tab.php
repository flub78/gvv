<!-- VIEW: application/views/email_lists/_criteria_tab.php -->
<?php
/**
 * Partial view for role-based criteria selection tab
 * Uses same grid layout as authorization/bs_user_roles.php
 */

// Get current role assignments if in edit mode
$current_role_ids = array();
if (isset($current_roles) && is_array($current_roles)) {
    foreach ($current_roles as $role) {
        $key = $role['types_roles_id'] . '_' . ($role['section_id'] ?? '0');
        $current_role_ids[$key] = true;
    }
}

// Group roles: show global roles first, then section roles
$global_roles = array();
$section_roles = array();
if (!empty($available_roles)) {
    foreach ($available_roles as $role) {
        if ($role['scope'] === 'global') {
            $global_roles[] = $role;
        } else {
            $section_roles[] = $role;
        }
    }
}
$ordered_roles = array_merge($global_roles, $section_roles);
?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">
            <i class="bi bi-funnel"></i>
            <?= $this->lang->line("email_lists_select_roles") ?>
        </h5>
        <p class="text-muted">
            <?= $this->lang->line("email_lists_criteria_help") ?>
        </p>

        <?php if (empty($available_roles)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <?= $this->lang->line("email_lists_no_roles_available") ?>
            </div>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th><?= $this->lang->line('authorization_role') ?></th>
                            <th>Global</th>
                            <th>Toutes sections</th>
                            <?php if (!empty($available_sections)): ?>
                                <?php foreach ($available_sections as $section): ?>
                                    <th style="background-color: <?= htmlspecialchars($section['couleur'] ?? '#e9ecef') ?>; color: black;">
                                        <?= htmlspecialchars($section['nom']) ?>
                                    </th>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordered_roles as $role): ?>
                        <tr>
                            <td>
                                <?php if ($role['scope'] === 'global'): ?><strong><?php endif; ?>
                                <?php 
                                    // Use translation if available, fallback to nom
                                    if (!empty($role['translation_key']) && $this->lang->line($role['translation_key'])) {
                                        echo htmlspecialchars($this->lang->line($role['translation_key']));
                                    } else {
                                        echo htmlspecialchars($role['nom']);
                                    }
                                ?>
                                <?php if ($role['scope'] === 'global'): ?></strong><?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($role['scope'] === 'global'): ?>
                                    <?php
                                    $key = $role['id'] . '_0';
                                    $is_checked = isset($current_role_ids[$key]);
                                    ?>
                                    <input type="checkbox"
                                           class="form-check-input"
                                           name="roles[]"
                                           value="<?= $role['id'] ?>_0"
                                           <?= $is_checked ? 'checked' : '' ?>>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($role['scope'] === 'section'): ?>
                                    <!-- Checkbox "Toutes sections" - will check all sections for this role -->
                                    <input type="checkbox"
                                           class="form-check-input check-all-sections"
                                           data-role-id="<?= $role['id'] ?>">
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <?php if (!empty($available_sections)): ?>
                                <?php foreach ($available_sections as $section): ?>
                                    <td class="text-center">
                                        <?php if ($role['scope'] === 'section'): ?>
                                            <?php
                                            $key = $role['id'] . '_' . $section['id'];
                                            $is_checked = isset($current_role_ids[$key]);
                                            ?>
                                            <input type="checkbox"
                                                   class="form-check-input section-checkbox"
                                                   name="roles[]"
                                                   value="<?= $role['id'] ?>_<?= $section['id'] ?>"
                                                   data-role-id="<?= $role['id'] ?>"
                                                   <?= $is_checked ? 'checked' : '' ?>>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>
</div>

<script>
// Handle "Toutes sections" checkbox
document.addEventListener('DOMContentLoaded', function() {
    // Listen for "Toutes sections" checkbox changes
    document.querySelectorAll('.check-all-sections').forEach(function(checkAllBox) {
        checkAllBox.addEventListener('change', function() {
            const roleId = this.dataset.roleId;
            const isChecked = this.checked;

            // Find all section checkboxes for this role
            document.querySelectorAll('.section-checkbox[data-role-id="' + roleId + '"]').forEach(function(sectionBox) {
                sectionBox.checked = isChecked;
                
                // Trigger change event to update email preview
                // This ensures handleRoleChange() is called in form.php
                sectionBox.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });

    // Update "Toutes sections" state when individual section checkboxes change
    document.querySelectorAll('.section-checkbox').forEach(function(sectionBox) {
        sectionBox.addEventListener('change', function() {
            const roleId = this.dataset.roleId;
            const allSectionBoxes = document.querySelectorAll('.section-checkbox[data-role-id="' + roleId + '"]');
            const checkAllBox = document.querySelector('.check-all-sections[data-role-id="' + roleId + '"]');

            if (checkAllBox) {
                // Check if all section boxes are checked
                let allChecked = true;
                allSectionBoxes.forEach(function(box) {
                    if (!box.checked) {
                        allChecked = false;
                    }
                });
                checkAllBox.checked = allChecked;
            }
        });
    });
});
</script>
