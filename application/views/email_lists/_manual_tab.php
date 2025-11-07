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
<?php
// DEBUG: Check what variables are available
if (ENVIRONMENT === 'development') {
    echo '<!-- DEBUG _manual_tab: list_id=' . (isset($list_id) ? $list_id : 'NOT SET') . ', ';
    echo 'email_list_id=' . (isset($email_list_id) ? $email_list_id : 'NOT SET') . ', ';
    echo 'controller=' . (isset($controller) ? $controller : 'NOT SET') . ' -->' . "\n";
}
?>
<div class="card" data-list-id="<?= isset($email_list_id) ? $email_list_id : 0 ?>" data-controller="<?= isset($controller) ? $controller : 'email_lists' ?>">
    <div class="card-body">
        <h5 class="card-title">
            <i class="bi bi-envelope-at"></i>
            <?= $this->lang->line("email_lists_external_emails") ?>
        </h5>
        <p class="text-muted">
            <?= $this->lang->line("email_lists_external_help") ?>
        </p>

        <!-- Add external email form (v1.4: multiple emails via textarea) -->
        <div class="mb-3">
            <label for="external_emails_textarea" class="form-label">
                <?= $this->lang->line("email_lists_external_emails_label") ?>
            </label>
            <textarea class="form-control"
                      id="external_emails_textarea"
                      rows="5"
                      placeholder="exemple@domaine.com&#10;jean@exemple.fr, paul@exemple.fr Jean et Paul Dupont&#10;contact@societe.com; info@societe.com Société XYZ"></textarea>
            <div class="form-text">
                <?= $this->lang->line("email_lists_external_emails_help") ?>
            </div>
        </div>
        <div class="mb-3">
            <button type="button"
                    class="btn btn-primary"
                    onclick="addExternalEmails()">
                <i class="bi bi-plus-circle"></i>
                <?= $this->lang->line("email_lists_add_external") ?>
            </button>
        </div>

        <!-- External emails list -->
        <div id="external_emails_list">
            <?php if (!empty($current_external)): ?>
                <?php foreach ($current_external as $idx => $ext): ?>
                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                    <div>
                        <i class="bi bi-envelope"></i>
                        <code><?= htmlspecialchars($ext['email']) ?></code>
                        <?php if (!empty($ext['name'])): ?>
                            - <span class="text-muted"><?= htmlspecialchars($ext['name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <button type="button"
                            class="btn btn-sm btn-outline-danger"
                            onclick="removeExternalEmail(this)">
                        <i class="bi bi-trash"></i>
                        <?= $this->lang->line("email_lists_remove_member") ?>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Add manual member
window.addManualMember = function() {
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

    // Get list_id
    const card = document.querySelector('.card[data-list-id]');
    const listId = card.dataset.listId;

    if (!listId || listId == '0') {
        alert('Please save the list first before adding manual members');
        return;
    }

    // Save to database via AJAX
    const url = '<?= controller_url($controller) ?>/add_manual_member_ajax';
    console.log('Adding manual member:', memberId, 'to list:', listId);

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'list_id': listId,
            'membre_id': memberId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add to DOM display
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

            // Update preview counts automatically
            if (typeof updatePreviewCounts === 'function') {
                updatePreviewCounts();
            }
        } else {
            alert('Error: ' + (data.message || 'Failed to add member'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred');
    });
}

// Remove manual member
window.removeManualMember = function(button) {
    const memberDiv = button.closest('div[data-member-id]');
    const memberId = memberDiv.getAttribute('data-member-id');

    if (!memberId) {
        memberDiv.remove();
        return;
    }

    // Remove from database via AJAX
    const card = document.querySelector('.card[data-list-id]');
    const listId = card.dataset.listId;

    fetch('<?= controller_url($controller) ?>/remove_manual_member_ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'list_id': listId,
            'membre_id': memberId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove from DOM
            memberDiv.remove();

            // Update preview counts automatically
            if (typeof updatePreviewCounts === 'function') {
                updatePreviewCounts();
            }
        } else {
            alert('Error: ' + (data.message || 'Failed to remove member'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred');
    });
}

// Add external emails (v1.4: multiple emails per line, CSV support)
window.addExternalEmails = function() {
    const textarea = document.getElementById('external_emails_textarea');
    const content = textarea.value.trim();

    if (!content) {
        return; // Nothing to add
    }

    // Get list_id
    const card = document.querySelector('.card[data-list-id]');
    const listId = card.dataset.listId;

    if (!listId || listId == '0') {
        alert('Please save the list first before adding external emails');
        return;
    }

    // Parse lines
    const lines = content.split('\n');
    const emailsToAdd = [];

    for (let line of lines) {
        line = line.trim();
        if (!line) continue;

        // Extract ALL emails from the line
        const emailRegex = /([^\s@,;]+@[^\s@,;]+\.[^\s@,;]+)/g;
        const emails = [];
        let match;

        while ((match = emailRegex.exec(line)) !== null) {
            emails.push(match[1]);
        }

        if (emails.length > 0) {
            // Get name: everything that's NOT an email address
            let name = line;
            emails.forEach(email => {
                name = name.replace(email, '');
            });
            // Clean up separators and whitespace
            name = name.replace(/[,;]+/g, ' ').replace(/\s+/g, ' ').trim();

            // Add all emails from this line with the same name
            emails.forEach(email => {
                emailsToAdd.push({ email: email, name: name });
            });
        }
    }

    if (emailsToAdd.length === 0) {
        alert('<?= $this->lang->line("email_lists_no_valid_emails") ?>');
        return;
    }

    // Check for duplicates
    const listDiv = document.getElementById('external_emails_list');
    const existingEmails = Array.from(listDiv.querySelectorAll('code')).map(code => code.textContent.toLowerCase());

    const newEmails = emailsToAdd.filter(item => !existingEmails.includes(item.email.toLowerCase()));

    if (newEmails.length === 0) {
        alert('<?= $this->lang->line("email_lists_all_already_added") ?>');
        return;
    }

    // Save all emails to database via AJAX
    const url = '<?= controller_url($controller) ?>/add_external_ajax';
    const promises = [];

    for (let item of newEmails) {
        const promise = fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'list_id': listId,
                'email': item.email,
                'name': item.name
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add to DOM display
                addExternalEmailToList(item.email, item.name);
                return { success: true, email: item.email };
            } else {
                return { success: false, email: item.email, error: data.message };
            }
        })
        .catch(error => {
            console.error('Error:', error);
            return { success: false, email: item.email, error: 'Network error' };
        });

        promises.push(promise);
    }

    // Wait for all to complete
    Promise.all(promises).then(results => {
        const successCount = results.filter(r => r.success).length;
        const failCount = results.filter(r => !r.success).length;

        if (successCount > 0) {
            // Clear textarea
            textarea.value = '';

            // Show success message
            let message = successCount + ' <?= $this->lang->line("email_lists_emails_added") ?>';
            if (failCount > 0) {
                message += ', ' + failCount + ' <?= $this->lang->line("email_lists_emails_failed") ?>';
            }
            alert(message);
        } else if (failCount > 0) {
            alert('<?= $this->lang->line("email_lists_all_failed") ?>');
        }
    });
}

// Helper to add external email to DOM
window.addExternalEmailToList = function(email, name) {
    const listDiv = document.getElementById('external_emails_list');
    const emailDiv = document.createElement('div');
    emailDiv.className = 'd-flex justify-content-between align-items-center mb-2 border-bottom pb-2';

    // Create the main content div
    const contentDiv = document.createElement('div');

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
    deleteBtn.appendChild(document.createTextNode(' <?= $this->lang->line("email_lists_remove_member") ?>'));

    // Assemble the row
    emailDiv.appendChild(contentDiv);
    emailDiv.appendChild(deleteBtn);

    listDiv.appendChild(emailDiv);

    // Update preview counts automatically
    if (typeof updatePreviewCounts === 'function') {
        updatePreviewCounts();
    }
}

// Remove external email
window.removeExternalEmail = function(button) {
    const emailDiv = button.closest('div');
    const emailElement = emailDiv.querySelector('code');
    const email = emailElement ? emailElement.textContent : '';

    if (!email) {
        emailDiv.remove();
        return;
    }

    // Remove from database via AJAX
    const card = document.querySelector('.card[data-list-id]');
    const listId = card.dataset.listId;
    const controller = card.dataset.controller;

    fetch('<?= controller_url($controller) ?>/remove_external_ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'list_id': listId,
            'email': email
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove from DOM
            emailDiv.remove();

            // Update preview counts automatically
            if (typeof updatePreviewCounts === 'function') {
                updatePreviewCounts();
            }
        } else {
            alert('Error: ' + (data.message || 'Failed to remove email'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred');
    });
}
</script>
