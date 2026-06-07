<?php $this->lang->load('forms'); ?>
<div class="container mt-4">
    <div class="mb-3">
        <h1 class="h3 mb-1"><?= $this->lang->line('forms_generate_title') ?></h1>
        <p class="text-muted mb-0"><?= html_escape($form['title']) ?></p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= site_url('forms_admin/generate_submit/' . rawurlencode($form['public_slug'])) ?>">

                <?php if (in_array($required_params, array('instructor', 'pilot+instructor'))): ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="instructor_login">
                        <?= $this->lang->line('forms_generate_instructor') ?> <span class="text-danger">*</span>
                    </label>
                    <?php
                    $inst_opts = '';
                    foreach ($instructor_selector as $login => $label) {
                        $inst_opts .= '<option value="' . html_escape($login) . '">' . html_escape($label) . '</option>';
                    }
                    echo '<select class="form-select" id="instructor_login" name="instructor_login" style="max-width:380px;">';
                    echo '<option value="">' . $this->lang->line('forms_generate_select_placeholder') . '</option>';
                    echo $inst_opts;
                    echo '</select>';
                    ?>
                </div>
                <?php endif; ?>

                <?php if (in_array($required_params, array('pilot', 'pilot+instructor'))): ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="pilot_login">
                        <?= $this->lang->line('forms_generate_pilot') ?> <span class="text-danger">*</span>
                    </label>
                    <?php
                    $pilot_opts = '';
                    foreach ($pilot_selector as $login => $label) {
                        $pilot_opts .= '<option value="' . html_escape($login) . '">' . html_escape($label) . '</option>';
                    }
                    echo '<select class="form-select" id="pilot_login" name="pilot_login" style="max-width:380px;">';
                    echo '<option value="">' . $this->lang->line('forms_generate_select_placeholder') . '</option>';
                    echo $pilot_opts;
                    echo '</select>';
                    ?>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit"><?= $this->lang->line('forms_generate_button') ?></button>
                    <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin') ?>"><?= $this->lang->line('forms_button_cancel') ?></a>
                </div>
            </form>
        </div>
    </div>
</div>
