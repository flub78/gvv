<!-- VIEW: application/views/briefing_passager/bs_viewView.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('briefing_passager');
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-clipboard-check"></i> <?= $this->lang->line('briefing_passager_title') ?></h3>

<?= $message ?>

<?php if ($vld): ?>
<div class="card mb-3">
    <div class="card-header"><i class="fas fa-plane"></i> <?= $this->lang->line('briefing_passager_field_vld') ?> #<?= htmlspecialchars($vld['id']) ?></div>
    <div class="card-body row">
        <div class="col-md-3">
            <strong><?= $this->lang->line('briefing_passager_field_date_vol') ?> :</strong>
            <?= $vld['date_vol'] ? date_db2ht($vld['date_vol']) : '—' ?>
        </div>
        <div class="col-md-3">
            <strong><?= $this->lang->line('briefing_passager_field_aerodrome') ?> :</strong>
            <?= htmlspecialchars($vld['aerodrome'] ?? '—') ?>
        </div>
        <div class="col-md-3">
            <strong><?= $this->lang->line('briefing_passager_field_appareil') ?> :</strong>
            <?= htmlspecialchars($vld['airplane_immat'] ?? '—') ?>
        </div>
        <div class="col-md-3">
            <strong><?= $this->lang->line('briefing_passager_field_nom') ?> :</strong>
            <?= htmlspecialchars($vld['beneficiaire'] ?? '—') ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header"><i class="fas fa-file"></i> <?= $this->lang->line('archived_documents_view') ?></div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3"><?= $this->lang->line('briefing_passager_field_date_sign') ?></dt>
            <dd class="col-sm-9"><?= $doc['uploaded_at'] ? date('d/m/Y H:i', strtotime($doc['uploaded_at'])) : '—' ?></dd>
            <dt class="col-sm-3"><?= $this->lang->line('archived_documents_file') ?></dt>
            <dd class="col-sm-9"><?= htmlspecialchars($doc['original_filename'] ?? '—') ?></dd>
            <dt class="col-sm-3"><?= $this->lang->line('archived_documents_uploaded_by') ?></dt>
            <dd class="col-sm-9"><?= htmlspecialchars($doc['uploaded_by'] ?? '—') ?></dd>
        </dl>

        <?php
        $file     = $doc['file_path'] ?? '';
        $abs_file = $file ? (FCPATH . ltrim($file, './')) : '';
        $mime     = $abs_file ? (mime_content_type($abs_file) ?: '') : '';
        if ($file && file_exists($abs_file)):
            if ($mime === 'application/pdf'):
        ?>
        <div class="ratio" style="height:600px;">
            <iframe src="<?= site_url('archived_documents/download/' . $doc['id']) ?>" style="height:600px;" class="w-100"></iframe>
        </div>
        <?php elseif (strpos($mime, 'image/') === 0): ?>
        <img src="<?= site_url('archived_documents/download/' . $doc['id']) ?>" class="img-fluid" alt="briefing">
        <?php endif; endif; ?>

        <div class="mt-3 d-flex gap-2">
            <a href="<?= site_url('archived_documents/download/' . $doc['id']) ?>" class="btn btn-outline-primary">
                <i class="fas fa-download"></i> <?= $this->lang->line('archived_documents_download') ?>
            </a>
            <?php if (!empty($vld)): ?>
            <a href="<?= site_url('briefing_passager/upload/' . $vld['id']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-exchange-alt"></i> <?= $this->lang->line('briefing_passager_replace') ?>
            </a>
            <?php endif; ?>
            <a href="<?= site_url('vols_decouverte') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= $this->lang->line('gvv_button_back') ?>
            </a>
            <?php if ($is_dev_user): ?>
            <form method="post" action="<?= site_url('briefing_passager/delete/' . $doc['id']) ?>"
                  class="ms-auto"
                  onsubmit="return confirm('<?= $this->lang->line('briefing_passager_confirm_delete') ?>')">
                <button type="submit" class="btn btn-outline-danger">
                    <i class="fas fa-trash"></i> <?= $this->lang->line('briefing_passager_delete') ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</div><!-- /body -->
