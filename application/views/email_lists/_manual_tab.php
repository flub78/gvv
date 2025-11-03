<!-- VIEW: application/views/email_lists/_manual_tab.php -->
<?php
/**
 * Partial view for manual member selection tab
 */

// Get current manual members if in edit mode
$current_member_ids = array();
if (isset($current_manual_members) && is_array($current_manual_members)) {
    foreach ($current_manual_members as $member) {
        $current_member_ids[$member['membre_id']] = $member;
    }
}

// Get current external emails if in edit mode
$current_external = array();
if (isset($current_external_emails) && is_array($current_external_emails)) {
    $current_external = $current_external_emails;
}
?>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">
            <i class="bi bi-person-plus"></i>
            <?= $this->lang->line("email_lists_manual_members") ?>
        </h5>
        <p class="text-muted">
            <?= $this->lang->line("email_lists_manual_help") ?>
        </p>

        <!-- Add member selector -->
        <div class="row mb-3">
            <div class="col-md-8">
                <select class="form-select" id="member_selector">
                    <option value=""><?= $this->lang->line("email_lists_select_member") ?></option>
                    <?php if (isset($available_members) && is_array($available_members)): ?>
                        <?php foreach ($available_members as $member_id => $member_name): ?>
                            <option value="<?= htmlspecialchars($member_id) ?>">
                                <?= htmlspecialchars($member_name) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="button"
                        class="btn btn-primary"
                        onclick="addManualMember()">
                    <i class="bi bi-plus-circle"></i>
                    <?= $this->lang->line("email_lists_add_member") ?>
                </button>
            </div>
        </div>

        <!-- Selected members list -->
        <div id="manual_members_list">
            <?php if (!empty($current_member_ids)): ?>
                <?php foreach ($current_member_ids as $member_id => $member): ?>
                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2" data-member-id="<?= htmlspecialchars($member_id) ?>">
                    <div>
                        <input type="hidden" name="manual_members[]" value="<?= htmlspecialchars($member_id) ?>">
                        <i class="bi bi-person"></i>
                        <strong><?= htmlspecialchars($member['name'] ?? $member_id) ?></strong>
                        <?php if (!empty($member['email'])): ?>
                            - <code><?= htmlspecialchars($member['email']) ?></code>
                        <?php endif; ?>
                    </div>
                    <button type="button"
                            class="btn btn-sm btn-outline-danger"
                            onclick="removeManualMember(this)">
                        <i class="bi bi-trash"></i>
                        <?= $this->lang->line("email_lists_remove_member") ?>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- External emails section -->
<div class="card">
    <div class="card-body">
        <h5 class="card-title">
            <i class="bi bi-envelope-at"></i>
            <?= $this->lang->line("email_lists_external_emails") ?>
        </h5>
        <p class="text-muted">
            <?= $this->lang->line("email_lists_external_help") ?>
        </p>

        <!-- Add external email form -->
        <div class="row mb-3">
            <div class="col-md-5">
                <input type="email"
                       class="form-control"
                       id="external_email"
                       placeholder="<?= $this->lang->line("email_lists_external_email") ?>">
            </div>
            <div class="col-md-5">
                <input type="text"
                       class="form-control"
                       id="external_name"
                       placeholder="<?= $this->lang->line("email_lists_external_name") ?> (<?= $this->lang->line("gvv_str_optional") ?>)">
            </div>
            <div class="col-md-2">
                <button type="button"
                        class="btn btn-primary"
                        onclick="addExternalEmail()">
                    <i class="bi bi-plus-circle"></i>
                    <?= $this->lang->line("email_lists_add_external") ?>
                </button>
            </div>
        </div>

        <!-- Paste multiple emails -->
        <div class="mb-3">
            <label for="paste_emails" class="form-label">
                <?= $this->lang->line("email_lists_paste_emails") ?>
            </label>
            <textarea class="form-control"
                      id="paste_emails"
                      rows="4"
                      placeholder="email1@example.com&#10;email2@example.com&#10;email3@example.com"></textarea>
            <button type="button"
                    class="btn btn-secondary btn-sm mt-2"
                    onclick="bulkAddExternalEmails()">
                <i class="bi bi-upload"></i>
                <?= $this->lang->line("email_lists_import_pasted") ?>
            </button>
        </div>

        <!-- External emails list -->
        <div id="external_emails_list">
            <?php if (!empty($current_external)): ?>
                <?php foreach ($current_external as $idx => $ext): ?>
                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                    <div>
                        <input type="hidden" name="external_emails[]" value="<?= htmlspecialchars($ext['external_email']) ?>">
                        <input type="hidden" name="external_names[]" value="<?= htmlspecialchars($ext['external_name'] ?? '') ?>">
                        <i class="bi bi-envelope"></i>
                        <code><?= htmlspecialchars($ext['external_email']) ?></code>
                        <?php if (!empty($ext['external_name'])): ?>
                            - <span class="text-muted"><?= htmlspecialchars($ext['external_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="button"
                            class="btn btn-sm btn-outline-danger"
                            onclick="removeExternalEmail(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Add manual member
function addManualMember() {
    const selector = document.getElementById('member_selector');
    const memberId = selector.value;
    const memberName = selector.options[selector.selectedIndex].text;

    if (!memberId) {
        return; // No popup needed, just don't add anything
    }

    // Check if already added
    if (document.querySelector('#manual_members_list [data-member-id="' + memberId + '"]')) {
        return; // Already in list, silently ignore
    }

    // Add to list
    const listDiv = document.getElementById('manual_members_list');
    const memberDiv = document.createElement('div');
    memberDiv.className = 'd-flex justify-content-between align-items-center mb-2 border-bottom pb-2';
    memberDiv.setAttribute('data-member-id', memberId);
    memberDiv.innerHTML = `
        <div>
            <input type="hidden" name="manual_members[]" value="${memberId}">
            <i class="bi bi-person"></i>
            <strong>${memberName}</strong>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeManualMember(this)">
            <i class="bi bi-trash"></i>
            <?= $this->lang->line("email_lists_remove_member") ?>
        </button>
    `;
    listDiv.appendChild(memberDiv);

    // Reset selector
    selector.value = '';
}

// Remove manual member
function removeManualMember(button) {
    button.closest('div[data-member-id]').remove();
}

// Add external email
function addExternalEmail() {
    const emailInput = document.getElementById('external_email');
    const nameInput = document.getElementById('external_name');
    const email = emailInput.value.trim();
    const name = nameInput.value.trim();

    if (!email) {
        return; // No popup needed, just don't add anything
    }

    // Basic email validation
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        return; // Invalid email, silently ignore
    }

    // Add to list
    addExternalEmailToList(email, name);

    // Reset inputs
    emailInput.value = '';
    nameInput.value = '';
}

// Bulk add external emails
function bulkAddExternalEmails() {
    const textarea = document.getElementById('paste_emails');
    const emails = textarea.value.split(/[\n,;]+/).map(e => e.trim()).filter(e => e);

    let added = 0;
    let invalid = 0;

    emails.forEach(email => {
        if (email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            addExternalEmailToList(email, '');
            added++;
        } else {
            invalid++;
        }
    });

    textarea.value = '';
    // No popup needed, visual feedback is obvious from list update
}

// Helper to add external email to DOM
function addExternalEmailToList(email, name) {
    const listDiv = document.getElementById('external_emails_list');
    const emailDiv = document.createElement('div');
    emailDiv.className = 'd-flex justify-content-between align-items-center mb-2 border-bottom pb-2';

    // Create the main content div
    const contentDiv = document.createElement('div');

    // Add hidden inputs
    const emailInput = document.createElement('input');
    emailInput.type = 'hidden';
    emailInput.name = 'external_emails[]';
    emailInput.value = email;
    contentDiv.appendChild(emailInput);

    const nameInput = document.createElement('input');
    nameInput.type = 'hidden';
    nameInput.name = 'external_names[]';
    nameInput.value = name || '';
    contentDiv.appendChild(nameInput);

    // Add icon
    const icon = document.createElement('i');
    icon.className = 'bi bi-envelope';
    contentDiv.appendChild(icon);
    contentDiv.appendChild(document.createTextNode(' '));

    // Add email in code tag
    const codeTag = document.createElement('code');
    codeTag.textContent = email;
    contentDiv.appendChild(codeTag);

    // Add name if present
    if (name && name.trim()) {
        contentDiv.appendChild(document.createTextNode(' - '));
        const nameSpan = document.createElement('span');
        nameSpan.className = 'text-muted';
        nameSpan.textContent = name;
        contentDiv.appendChild(nameSpan);
    }

    // Create delete button
    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.className = 'btn btn-sm btn-outline-danger';
    deleteBtn.onclick = function() { removeExternalEmail(this); };

    const trashIcon = document.createElement('i');
    trashIcon.className = 'bi bi-trash';
    deleteBtn.appendChild(trashIcon);

    // Assemble the row
    emailDiv.appendChild(contentDiv);
    emailDiv.appendChild(deleteBtn);

    listDiv.appendChild(emailDiv);
}

// Remove external email
function removeExternalEmail(button) {
    button.closest('div').remove();

    // Update preview counts automatically
    if (typeof updatePreviewCounts === 'function') {
        updatePreviewCounts();
    }
}
</script>
