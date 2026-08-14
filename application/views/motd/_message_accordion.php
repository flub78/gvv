<!-- VIEW: application/views/motd/_message_accordion.php -->
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
 * Partial: accordeon Bootstrap des messages MOTD, utilise par la section
 * repliable du dashboard (bs_dashboard.php).
 *
 * Attend en entree $motd_messages (avec 'replies'/'hidden'/'acknowledged'
 * deja rattaches par l'appelant) et $is_admin.
 *
 * @package vues
 */
?>
<style>
/* Espace visuel entre chaque message pour ne pas les faire percevoir comme
   un bloc continu (les items d'un accordion Bootstrap sont accolés par défaut). */
#motdAccordion .accordion-item {
    border: 1px solid var(--bs-accordion-border-color, rgba(0, 0, 0, .175));
    border-radius: var(--bs-accordion-border-radius, 0.375rem);
    overflow: hidden;
    margin-bottom: 0.75rem;
}
#motdAccordion .accordion-item:last-child {
    margin-bottom: 0;
}
/* Hide/acknowledge stay next to the header (outside .accordion-collapse) so
   they remain visible and clickable even when a message is collapsed. The
   wrapper row holds the <h2> toggle heading and the actions as flex
   siblings (kept out of the <h2> itself: a <div> is not valid heading
   content). */
#motdAccordion .motd-header-row {
    display: flex;
    align-items: stretch;
}
#motdAccordion .accordion-header {
    flex: 1 1 auto;
    min-width: 0;
}
#motdAccordion .accordion-button {
    height: 100%;
}
#motdAccordion .motd-header-actions {
    display: flex;
    align-items: center;
    flex: 0 0 auto;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-left: 1px solid var(--bs-accordion-border-color, rgba(0, 0, 0, .175));
}
</style>
<div class="accordion" id="motdAccordion">
    <?php
    $motd_level_badges = array('urgent' => 'danger', 'important' => 'warning', 'info' => 'info', 'alerte' => 'secondary');
    ?>
    <?php foreach ($motd_messages as $motd_index => $motd_message): ?>
        <?php
        $motd_item_id = 'motd' . $motd_message['id'];
        $motd_priority_unread = in_array($motd_message['level'], array('urgent', 'important'))
            && empty($motd_message['acknowledged']);
        $motd_expand_item = ($motd_index === 0) || $motd_priority_unread;
        $motd_badge_class = isset($motd_level_badges[$motd_message['level']]) ? $motd_level_badges[$motd_message['level']] : 'secondary';
        ?>
        <div class="accordion-item" data-unread="<?= empty($motd_message['acknowledged']) ? '1' : '0' ?>">
            <div class="motd-header-row">
                <h2 class="accordion-header" id="heading<?= $motd_item_id ?>">
                    <button class="accordion-button <?= $motd_expand_item ? '' : 'collapsed' ?>" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapse<?= $motd_item_id ?>">
                        <span class="d-flex align-items-center flex-wrap gap-2 w-100">
                            <?php if (!empty($motd_message['level'])): ?>
                                <span class="badge bg-<?= $motd_badge_class ?>"><?= $this->lang->line('motd_level_' . $motd_message['level']) ?></span>
                            <?php endif; ?>
                            <span><?= !empty($motd_message['title']) ? htmlspecialchars($motd_message['title']) : $this->lang->line('motd_no_title') ?></span>
                            <small class="text-muted ms-auto me-2">
                                <?= date('d/m/Y H:i', strtotime($motd_message['start_date'])) ?> - <?= date('d/m/Y H:i', strtotime($motd_message['end_date'])) ?>
                            </small>
                        </span>
                    </button>
                </h2>
                <!-- Sibling of the <h2>, not nested inside its toggle button, so
                     clicking these never collapses/expands the message; outside
                     .accordion-collapse so they stay visible while collapsed. -->
                <div class="motd-header-actions motd-message-actions">
                    <?php if (!isset($motd_message['dismissible']) || $motd_message['dismissible']): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary motd-hide-btn" data-message-id="<?= $motd_message['id'] ?>">
                            <i class="fas fa-eye-slash" aria-hidden="true"></i> <?= $this->lang->line('motd_action_hide') ?>
                        </button>
                    <?php else: ?>
                        <span class="badge bg-secondary" title="<?= $this->lang->line('motd_error_not_dismissible') ?>">
                            <i class="fas fa-lock" aria-hidden="true"></i> <?= $this->lang->line('motd_not_dismissible_badge') ?>
                        </span>
                    <?php endif; ?>
                    <?php if (empty($motd_message['acknowledged'])): ?>
                        <button type="button" class="btn btn-sm btn-outline-success motd-ack-btn" data-message-id="<?= $motd_message['id'] ?>">
                            <i class="fas fa-check" aria-hidden="true"></i> <?= $this->lang->line('motd_action_acknowledge') ?>
                        </button>
                    <?php else: ?>
                        <span class="badge bg-success"><i class="fas fa-check" aria-hidden="true"></i> <?= $this->lang->line('motd_acknowledged_badge') ?></span>
                    <?php endif; ?>
                    <span class="text-danger small motd-action-error"></span>
                </div>
            </div>
            <div id="collapse<?= $motd_item_id ?>"
                 class="accordion-collapse collapse <?= $motd_expand_item ? 'show' : '' ?>">
                <!-- No data-bs-parent: several unread urgent/important messages
                     must be able to stay expanded simultaneously, which a true
                     Bootstrap accordion group (single panel open) would silently
                     collapse back down on page load. -->
                <div class="accordion-body">
                    <div class="markdown-content"><?= markdown($motd_message['content']) ?></div>

                    <div class="motd-replies-list" id="motdReplies<?= $motd_message['id'] ?>">
                        <?php if (!empty($motd_message['replies'])): ?>
                            <hr>
                            <h6><?= $this->lang->line('motd_replies_title') ?></h6>
                            <?php foreach ($motd_message['replies'] as $motd_reply): ?>
                                <div class="border rounded p-2 mb-2 bg-light" id="motdReply<?= $motd_reply['id'] ?>">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= htmlspecialchars($motd_reply['author_login']) ?></strong>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($motd_reply['created_at'])) ?></small>
                                    </div>
                                    <div class="markdown-content"><?= markdown($motd_reply['content']) ?></div>
                                    <?php if ($is_admin): ?>
                                        <button type="button" class="btn btn-link btn-sm p-0 motd-reply-to-btn"
                                                data-message-id="<?= $motd_message['id'] ?>"
                                                data-reply-id="<?= $motd_reply['id'] ?>"
                                                data-author="<?= htmlspecialchars($motd_reply['author_login']) ?>">
                                            <?= $this->lang->line('motd_reply_to_reply') ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="mt-2 motd-reply-form" data-message-id="<?= $motd_message['id'] ?>">
                        <div class="small text-muted motd-reply-replying-to" style="display:none;">
                            <?= $this->lang->line('motd_reply_replying_to') ?> <span class="motd-reply-replying-to-author"></span>
                            (<a href="#" class="motd-reply-cancel"><?= $this->lang->line('motd_reply_cancel') ?></a>)
                        </div>
                        <textarea class="form-control form-control-sm motd-reply-textarea" rows="2"
                                  placeholder="<?= $this->lang->line('motd_reply_placeholder') ?>"></textarea>
                        <button type="button" class="btn btn-sm btn-primary mt-1 motd-reply-submit-btn" data-message-id="<?= $motd_message['id'] ?>">
                            <?= $this->lang->line('motd_reply_submit') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
