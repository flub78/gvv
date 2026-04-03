<!-- VIEW: application/views/admin/bs_logs.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('admin');
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-file-alt text-secondary"></i>
                <?= $this->lang->line('gvv_logs_title') ?>
            </h2>
            <a href="<?= controller_url('welcome') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <?php if (empty($log_files)) : ?>
        <div class="alert alert-info"><?= $this->lang->line('gvv_logs_no_files') ?></div>
    <?php else : ?>
    <div class="card">
        <div class="card-body p-0">
            <table class="datatable table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= $this->lang->line('gvv_logs_col_file') ?></th>
                        <th><?= $this->lang->line('gvv_logs_col_date') ?></th>
                        <th><?= $this->lang->line('gvv_logs_col_size') ?></th>
                        <th class="text-center"><?= $this->lang->line('gvv_logs_col_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($log_files as $file) : ?>
                    <tr>
                        <td><?= htmlspecialchars(str_replace('.php', '', $file['name'])) ?></td>
                        <td data-sort="<?= $file['modified'] ?>"><?= date('d/m/Y H:i:s', $file['modified']) ?></td>
                        <td data-sort="<?= $file['size'] ?>"><?= number_format($file['size'] / 1024, 1) ?> Ko</td>
                        <td class="text-center">
                            <a href="<?= controller_url('admin/download_log/' . urlencode($file['name'])) ?>"
                               class="btn btn-sm btn-outline-primary"
                               title="<?= $this->lang->line('gvv_logs_download') ?>">
                                <i class="fas fa-download"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>
