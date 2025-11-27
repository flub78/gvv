<!-- VIEW: application/views/email_lists/_sublists_tab.php -->
<?php
/**
 * Sublists tab - Add/remove email lists as sublists
 *
 * This tab allows users to:
 * - Include other lists as sublists
 * - View available lists that can be added
 * - See recipient counts for each sublist
 *
 * Validation rules:
 * - Depth = 1 only (lists with sublists cannot be sublists)
 * - Visibility coherence (public lists can only contain public sublists)
 *
 * @package vues
 */

// Get current sublists and available lists
$current_sublists = isset($sublists) ? $sublists : array();
$available_lists = isset($available_sublists) ? $available_sublists : array();
$list_id = isset($email_list_id) ? $email_list_id : 0;
?>

<div class="sublists-tab-container">
    <!-- Info alert explaining sublists -->
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        <?= $this->lang->line('email_lists_sublists_help') ?>
    </div>

    <?php if ($list_id > 0): ?>
    <!-- Current Sublists Section -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
                <i class="bi bi-folder-check"></i> <?= $this->lang->line('email_lists_current_sublists') ?>
                <span class="badge bg-light text-dark ms-2" id="current-sublists-count"><?= count($current_sublists) ?></span>
            </h6>
        </div>
        <div class="card-body">
            <div id="current-sublists-container">
                <?php if (empty($current_sublists)): ?>
                <div class="text-muted text-center py-3" id="no-sublists-message">
                    <i class="bi bi-inbox"></i> <?= $this->lang->line('email_lists_no_sublists') ?>
                </div>
                <?php else: ?>
                <div class="list-group" id="sublists-list">
                    <?php foreach ($current_sublists as $sublist): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center"
                         data-sublist-id="<?= $sublist['id'] ?>">
                        <div>
                            <strong><?= htmlspecialchars($sublist['name']) ?></strong>
                            <?php if (isset($sublist['recipient_count'])): ?>
                            <span class="badge bg-secondary ms-2">
                                <?= $sublist['recipient_count'] ?> <?= $this->lang->line('email_lists_recipients') ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($sublist['visible'] == 0): ?>
                            <span class="badge bg-warning text-dark ms-1">
                                <i class="bi bi-eye-slash"></i> <?= $this->lang->line('email_lists_private') ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger remove-sublist-btn"
                                data-sublist-id="<?= $sublist['id'] ?>"
                                data-sublist-name="<?= htmlspecialchars($sublist['name']) ?>">
                            <i class="bi bi-trash"></i> <?= $this->lang->line('email_lists_remove') ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Available Sublists Section -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0">
                <i class="bi bi-folder-plus"></i> <?= $this->lang->line('email_lists_available_sublists') ?>
                <span class="badge bg-light text-dark ms-2" id="available-sublists-count"><?= count($available_lists) ?></span>
            </h6>
        </div>
        <div class="card-body">
            <div id="available-sublists-container">
                <?php if (empty($available_lists)): ?>
                <div class="text-muted text-center py-3">
                    <i class="bi bi-inbox"></i> <?= $this->lang->line('email_lists_no_available_sublists') ?>
                </div>
                <?php else: ?>
                <div class="list-group" id="available-lists">
                    <?php foreach ($available_lists as $avail): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center"
                         data-list-id="<?= $avail['id'] ?>">
                        <div>
                            <strong><?= htmlspecialchars($avail['name']) ?></strong>
                            <?php if (isset($avail['recipient_count'])): ?>
                            <span class="badge bg-secondary ms-2">
                                <?= $avail['recipient_count'] ?> <?= $this->lang->line('email_lists_recipients') ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($avail['visible'] == 0): ?>
                            <span class="badge bg-warning text-dark ms-1">
                                <i class="bi bi-eye-slash"></i> <?= $this->lang->line('email_lists_private') ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <button type="button"
                                class="btn btn-sm btn-primary add-sublist-btn"
                                data-list-id="<?= $avail['id'] ?>"
                                data-list-name="<?= htmlspecialchars($avail['name']) ?>">
                            <i class="bi bi-plus-circle"></i> <?= $this->lang->line('email_lists_add') ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hidden field to track list ID for JavaScript -->
    <input type="hidden" id="current_list_id" value="<?= $list_id ?>">

    <?php else: ?>
    <!-- Creation mode - sublists not available yet -->
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        <?= $this->lang->line('email_lists_save_first_to_add_sublists') ?>
    </div>
    <?php endif; ?>
</div>

<style>
.sublists-tab-container .list-group-item {
    transition: background-color 0.2s;
}

.sublists-tab-container .list-group-item:hover {
    background-color: #f8f9fa;
}

.sublists-tab-container .remove-sublist-btn,
.sublists-tab-container .add-sublist-btn {
    transition: all 0.2s;
}

.sublists-tab-container .remove-sublist-btn:hover {
    transform: scale(1.05);
}

.sublists-tab-container .add-sublist-btn:hover {
    transform: scale(1.05);
}
</style>
