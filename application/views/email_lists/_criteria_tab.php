<!-- VIEW: application/views/email_lists/_criteria_tab.php -->
<?php
/**
 * Partial view for role-based criteria selection tab
 */

// Get current role assignments if in edit mode
$current_role_ids = array();
if (isset($current_roles) && is_array($current_roles)) {
    foreach ($current_roles as $role) {
        $key = $role['types_roles_id'] . '_' . ($role['section_id'] ?? '0');
        $current_role_ids[$key] = true;
    }
}
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

            <!-- Group roles by section -->
            <div class="accordion" id="rolesAccordion">
                <?php
                // Group global roles separately
                $global_roles = array();
                $section_roles = array();

                foreach ($available_roles as $role) {
                    if ($role['scope'] === 'global') {
                        $global_roles[] = $role;
                    }
                }

                // Get sections for role display
                $sections_map = array();
                if (!empty($available_sections)) {
                    foreach ($available_sections as $section) {
                        $sections_map[$section['id']] = $section;
                    }
                }
                ?>

                <!-- Global roles section -->
                <?php if (!empty($global_roles)): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingGlobal">
                        <button class="accordion-button"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapseGlobal"
                                aria-expanded="true"
                                aria-controls="collapseGlobal">
                            <i class="bi bi-globe me-2"></i>
                            <?= $this->lang->line("email_lists_global_roles") ?>
                        </button>
                    </h2>
                    <div id="collapseGlobal"
                         class="accordion-collapse collapse show"
                         aria-labelledby="headingGlobal"
                         data-bs-parent="#rolesAccordion">
                        <div class="accordion-body">
                            <?php foreach ($global_roles as $role): ?>
                                <?php
                                $key = $role['id'] . '_0';
                                $is_checked = isset($current_role_ids[$key]);
                                ?>
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="roles[]"
                                           value="<?= $role['id'] ?>_0"
                                           id="role_<?= $key ?>"
                                           <?= $is_checked ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="role_<?= $key ?>">
                                        <strong><?= htmlspecialchars($role['nom']) ?></strong>
                                        <?php if (!empty($role['description'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($role['description']) ?></small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Section-specific roles -->
                <?php if (!empty($available_sections)): ?>
                    <?php foreach ($available_sections as $idx => $section): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingSection<?= $section['id'] ?>">
                            <button class="accordion-button <?= $idx > 0 || !empty($global_roles) ? 'collapsed' : '' ?>"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapseSection<?= $section['id'] ?>"
                                    aria-expanded="<?= $idx === 0 && empty($global_roles) ? 'true' : 'false' ?>"
                                    aria-controls="collapseSection<?= $section['id'] ?>">
                                <i class="bi bi-building me-2"></i>
                                <?= htmlspecialchars($section['nom']) ?>
                            </button>
                        </h2>
                        <div id="collapseSection<?= $section['id'] ?>"
                             class="accordion-collapse collapse <?= $idx === 0 && empty($global_roles) ? 'show' : '' ?>"
                             aria-labelledby="headingSection<?= $section['id'] ?>"
                             data-bs-parent="#rolesAccordion">
                            <div class="accordion-body">
                                <?php
                                // Find all roles that can be assigned to this section
                                $section_role_found = false;
                                foreach ($available_roles as $role) {
                                    // Show section-scoped roles for this section
                                    if ($role['scope'] === 'section') {
                                        $section_role_found = true;
                                        $key = $role['id'] . '_' . $section['id'];
                                        $is_checked = isset($current_role_ids[$key]);
                                        ?>
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="roles[]"
                                                   value="<?= $role['id'] ?>_<?= $section['id'] ?>"
                                                   id="role_<?= $key ?>"
                                                   <?= $is_checked ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="role_<?= $key ?>">
                                                <strong><?= htmlspecialchars($role['nom']) ?></strong>
                                                <?php if (!empty($role['description'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($role['description']) ?></small>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                        <?php
                                    }
                                }

                                if (!$section_role_found) {
                                    echo '<p class="text-muted">' . $this->lang->line("email_lists_no_roles_for_section") . '</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Preview recipients count -->
            <div class="mt-3">
                <button type="button"
                        class="btn btn-outline-primary btn-sm"
                        id="previewCriteriaBtn"
                        onclick="previewRecipientCount()">
                    <i class="bi bi-eye"></i>
                    <?= $this->lang->line("email_lists_preview_count") ?>
                </button>
                <span id="previewCountResult" class="ms-2"></span>
            </div>

        <?php endif; ?>
    </div>
</div>

<script>
function previewRecipientCount() {
    // Get selected roles
    const selectedRoles = [];
    document.querySelectorAll('input[name="roles[]"]:checked').forEach(function(checkbox) {
        selectedRoles.push(checkbox.value);
    });

    if (selectedRoles.length === 0) {
        document.getElementById('previewCountResult').innerHTML = '<span class="text-muted"><?= $this->lang->line("email_lists_select_at_least_one_role") ?></span>';
        return;
    }

    // Get active_member filter
    const activeMember = document.getElementById('active_member').value;

    // Show loading
    document.getElementById('previewCountResult').innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

    // AJAX request to preview endpoint
    fetch('<?= controller_url($controller) ?>/preview_count', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'roles=' + encodeURIComponent(JSON.stringify(selectedRoles)) + '&active_member=' + encodeURIComponent(activeMember)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('previewCountResult').innerHTML =
                '<span class="badge bg-success">' + data.count + ' <?= $this->lang->line("email_lists_recipients") ?></span>';
        } else {
            document.getElementById('previewCountResult').innerHTML =
                '<span class="text-danger"><?= $this->lang->line("email_lists_preview_error") ?></span>';
        }
    })
    .catch(error => {
        document.getElementById('previewCountResult').innerHTML =
            '<span class="text-danger"><?= $this->lang->line("email_lists_preview_error") ?></span>';
    });
}
</script>
