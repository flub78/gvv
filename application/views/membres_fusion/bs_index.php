<!-- VIEW: application/views/membres_fusion/bs_index.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12">
            <h4><i class="fas fa-code-branch text-danger"></i>
                <?= $this->lang->line('gvv_fusion_title') ?>
            </h4>
            <p class="text-muted"><?= $this->lang->line('gvv_fusion_intro') ?></p>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php $success = $this->session->flashdata('fusion_success'); if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card border-danger">
        <div class="card-header bg-danger text-white">
            <i class="fas fa-exclamation-triangle"></i>
            <?= $this->lang->line('gvv_fusion_warning_title') ?>
        </div>
        <div class="card-body">
            <p class="mb-3"><?= $this->lang->line('gvv_fusion_warning_body') ?></p>

            <form method="post" action="<?= controller_url('membres_fusion/preview') ?>">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-bold text-danger">
                            <i class="fas fa-arrow-right"></i>
                            <?= $this->lang->line('gvv_fusion_source_label') ?>
                            <small class="text-muted fw-normal"><?= $this->lang->line('gvv_fusion_source_hint') ?></small>
                        </label>
                        <select name="source" class="form-select big_select" required>
                            <option value=""><?= $this->lang->line('gvv_fusion_select_member') ?></option>
                            <?php foreach ($membres as $m): ?>
                            <option value="<?= htmlspecialchars($m['mlogin']) ?>">
                                <?= htmlspecialchars($m['mnom'] . ' ' . $m['mprenom']) ?>
                                (<?= htmlspecialchars($m['mlogin']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end justify-content-center pb-1">
                        <i class="fas fa-long-arrow-alt-right fa-2x text-muted"></i>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-bold text-success">
                            <i class="fas fa-user-check"></i>
                            <?= $this->lang->line('gvv_fusion_dest_label') ?>
                            <small class="text-muted fw-normal"><?= $this->lang->line('gvv_fusion_dest_hint') ?></small>
                        </label>
                        <select name="destination" class="form-select big_select" required>
                            <option value=""><?= $this->lang->line('gvv_fusion_select_member') ?></option>
                            <?php foreach ($membres as $m): ?>
                            <option value="<?= htmlspecialchars($m['mlogin']) ?>">
                                <?= htmlspecialchars($m['mnom'] . ' ' . $m['mprenom']) ?>
                                (<?= htmlspecialchars($m['mlogin']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-search"></i>
                            <?= $this->lang->line('gvv_fusion_btn_analyse') ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

