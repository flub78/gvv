<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div class="container mt-4" style="max-width:700px;">

    <h4 class="mb-3">
        <i class="fas fa-bell text-warning"></i>
        <?= $this->lang->line('test_rappel_titre') ?>
    </h4>

    <?php
    $test_email = $this->config->item('test_email');
    $test_phone = $this->config->item('test_phone');
    ?>

    <?php if ($test_email || $test_phone): ?>
    <div class="alert alert-info py-2 mb-3">
        <i class="fas fa-info-circle"></i>
        <?= $this->lang->line('test_rappel_redirection_info') ?>
        <?php if ($test_email): ?>
            <strong><?= htmlspecialchars($test_email) ?></strong>
        <?php endif; ?>
        <?php if ($test_email && $test_phone): ?> / <?php endif; ?>
        <?php if ($test_phone): ?>
            <strong><?= htmlspecialchars($test_phone) ?></strong>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-warning py-2 mb-3">
        <i class="fas fa-exclamation-triangle"></i>
        <?= $this->lang->line('test_rappel_no_redirection') ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($result)): ?>
        <?php if ($result['ok']): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($result['message']) ?>
            <?php if (!empty($result['details'])): ?>
            <ul class="mb-0 mt-1">
                <?php foreach ($result['details'] as $d): ?>
                <li><?= htmlspecialchars($d) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-danger">
            <i class="fas fa-times-circle"></i> <?= htmlspecialchars($result['message']) ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-vial"></i> <?= $this->lang->line('test_rappel_form_title') ?>
        </div>
        <div class="card-body">
            <form method="post" action="<?= controller_url('reservation_reminder_test/send') ?>">

                <!-- Sélection réservation -->
                <div class="mb-3">
                    <label for="reservation_id" class="form-label fw-bold">
                        <?= $this->lang->line('test_rappel_label_reservation') ?>
                    </label>
                    <?php
                    $sel_id = isset($form['reservation_id']) ? (int) $form['reservation_id'] : 0;
                    ?>
                    <select class="form-select" id="reservation_id" name="reservation_id" required>
                        <option value="">-- <?= $this->lang->line('test_rappel_select_resa') ?> --</option>
                        <?php foreach ($reservations as $r): ?>
                        <?php
                            $date     = date('d/m/Y H:i', strtotime($r['start_datetime']));
                            $fin      = date('H:i',        strtotime($r['end_datetime']));
                            $immat    = htmlspecialchars($r['macimmat'] ?? '');
                            $pilote   = htmlspecialchars(trim($r['pilot_name'] ?? ''));
                            $instr    = htmlspecialchars(trim($r['instructor_name'] ?? ''));
                            $crew     = $pilote . ($instr ? ' + ' . $instr : '');
                            $label    = "$date-$fin | $immat | $crew";
                        ?>
                        <option value="<?= $r['id'] ?>"<?= $sel_id == $r['id'] ? ' selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($reservations)): ?>
                    <div class="text-muted small mt-1"><?= $this->lang->line('test_rappel_no_resa') ?></div>
                    <?php endif; ?>
                </div>

                <div class="row g-3">

                    <!-- Canal -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><?= $this->lang->line('test_rappel_label_canal') ?></label>
                        <?php
                        $sel_channel = isset($form['channel']) ? $form['channel'] : 'email';
                        $channels = [
                            'email'     => $this->lang->line('mes_reservations_channel_email'),
                            'sms'       => $this->lang->line('mes_reservations_channel_sms'),
                            'email+sms' => $this->lang->line('mes_reservations_channel_both'),
                        ];
                        ?>
                        <?php foreach ($channels as $val => $lbl): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="channel"
                                   id="channel_<?= $val ?>" value="<?= $val ?>"
                                   <?= $sel_channel === $val ? 'checked' : '' ?>>
                            <label class="form-check-label" for="channel_<?= $val ?>">
                                <?= htmlspecialchars($lbl) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Type de notification -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><?= $this->lang->line('test_rappel_label_type') ?></label>
                        <?php
                        $sel_notif = isset($form['notification_type']) ? $form['notification_type'] : 'scheduled_reminder';
                        $notif_types = [
                            'scheduled_reminder' => $this->lang->line('reminder_type_scheduled'),
                            'create'             => $this->lang->line('reminder_event_create'),
                            'update'             => $this->lang->line('reminder_event_update'),
                            'cancel'             => $this->lang->line('reminder_event_cancel'),
                        ];
                        ?>
                        <?php foreach ($notif_types as $val => $lbl): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="notification_type"
                                   id="notif_<?= $val ?>" value="<?= $val ?>"
                                   <?= $sel_notif === $val ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notif_<?= $val ?>">
                                <?= htmlspecialchars($lbl) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Destinataire -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><?= $this->lang->line('test_rappel_label_destinataire') ?></label>
                        <?php
                        $sel_role = isset($form['recipient_role']) ? $form['recipient_role'] : 'pilot';
                        $roles = [
                            'pilot'      => $this->lang->line('reminder_role_pilot'),
                            'instructor' => $this->lang->line('reminder_role_instructor'),
                        ];
                        ?>
                        <?php foreach ($roles as $val => $lbl): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="recipient_role"
                                   id="role_<?= $val ?>" value="<?= $val ?>"
                                   <?= $sel_role === $val ? 'checked' : '' ?>>
                            <label class="form-check-label" for="role_<?= $val ?>">
                                <?= htmlspecialchars($lbl) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div><!-- /row -->

                <div class="d-flex gap-2 mt-4">
                    <a href="<?= controller_url('welcome/section/dev') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <?= $this->lang->line('db_btn_retour') ?>
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-paper-plane"></i> <?= $this->lang->line('test_rappel_btn_envoyer') ?>
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
