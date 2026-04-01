<!-- VIEW: application/views/paiements_en_ligne/bs_genere_bar_qrcode.php -->
<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<h3><?= $this->lang->line('gvv_bar_qrcode_title') ?></h3>
<p class="text-muted">
    <?= $this->lang->line('gvv_bar_qrcode_intro') ?>
    <strong><?= htmlspecialchars($section['nom']) ?></strong>
</p>

<?php if (!empty($error)): ?>
<div class="alert alert-danger" role="alert">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div style="max-width: 760px;">

<?= form_open('paiements_en_ligne/genere_bar_qrcode') ?>

    <div class="mb-3">
        <label for="title" class="form-label"><?= $this->lang->line('gvv_bar_qrcode_label_title') ?> <span class="text-danger">*</span></label>
        <input type="text" id="title" name="title" class="form-control"
               value="<?= htmlspecialchars($title) ?>" maxlength="120" required>
    </div>

    <div class="mb-3">
        <label for="text_top" class="form-label"><?= $this->lang->line('gvv_bar_qrcode_label_text_top') ?></label>
        <textarea id="text_top" name="text_top" class="form-control" rows="4" maxlength="1200"><?= htmlspecialchars($text_top) ?></textarea>
    </div>

    <div class="mb-3">
        <label for="text_bottom" class="form-label"><?= $this->lang->line('gvv_bar_qrcode_label_text_bottom') ?></label>
        <textarea id="text_bottom" name="text_bottom" class="form-control" rows="4" maxlength="1200"><?= htmlspecialchars($text_bottom) ?></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label"><?= $this->lang->line('gvv_bar_qrcode_label_url') ?></label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($payment_url) ?>" readonly>
    </div>

    <?php if (!empty($can_generate)): ?>
    <button type="submit" name="button" value="generate_pdf" class="btn btn-primary">
        <i class="fas fa-file-pdf"></i> <?= $this->lang->line('gvv_bar_qrcode_button_generate_pdf') ?>
    </button>
    <?php endif; ?>

<?= form_close() ?>

</div>

</div>
