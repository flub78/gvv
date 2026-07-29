<!-- VIEW: application/views/motd/bs_my_messages.php -->
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
 * Page dediee "Tous mes messages" (PRD EF4) : liste tous les messages
 * actuellement actifs et applicables a l'utilisateur (cible directe, liste
 * de diffusion, ou tous), y compris ceux masques sur le dashboard.
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('motd');
?>

<div id="body" class="body container-fluid">
    <?= heading("motd_my_messages_title", 3) ?>

    <?php if (empty($motd_messages)): ?>
        <p class="text-muted"><?= $this->lang->line('motd_my_messages_empty') ?></p>
    <?php else: ?>
        <?php $this->load->view('motd/_message_accordion', array('motd_messages' => $motd_messages, 'is_admin' => $is_admin)); ?>
    <?php endif; ?>
</div>

<?php if (!empty($motd_messages)): ?>
<?= html_script(array('type' => "text/javascript", 'src' => js_url('motd'))) ?>
<script>
$(function() {
    motd_init_dashboard_actions({
        hideUrl: '<?= controller_url('motd') ?>/hide_message',
        hideAllUrl: '<?= controller_url('motd') ?>/hide_all',
        ackUrl: '<?= controller_url('motd') ?>/acknowledge_message',
        replyUrl: '<?= controller_url('motd') ?>/reply',
        confirmHideAll: <?= json_encode($this->lang->line('motd_confirm_hide_all')) ?>,
        errorFallback: <?= json_encode($this->lang->line('motd_error_action_failed')) ?>,
        ackBadgeLabel: <?= json_encode($this->lang->line('motd_acknowledged_badge')) ?>,
        repliesTitle: <?= json_encode($this->lang->line('motd_replies_title')) ?>
    });
});
</script>
<?php endif; ?>
