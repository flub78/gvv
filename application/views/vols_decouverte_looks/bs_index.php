<!-- VIEW: application/views/vols_decouverte_looks/bs_index.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-cog text-secondary"></i> <?= $this->lang->line('gvv_vd_looks_index_title') ?></h4>
            <a href="<?= controller_url('vols_decouverte_looks/sections') ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-random"></i> <?= $this->lang->line('gvv_vd_looks_sections_link') ?>
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <h5 class="mb-3"><?= $this->lang->line('gvv_vd_looks_list_title') ?></h5>

    <?php if (empty($looks)): ?>
        <p class="text-muted"><i class="fas fa-info-circle"></i> <?= $this->lang->line('gvv_vd_looks_no_looks') ?></p>
    <?php else: ?>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th><?= $this->lang->line('gvv_vd_looks_name') ?></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($looks as $look): ?>
                        <tr>
                            <td>
                                <a href="<?= controller_url('vols_decouverte_looks/edit/' . $look['id']) ?>">
                                    <?= htmlspecialchars($look['nom']) ?>
                                </a>
                                <?php if (!empty($look['is_default'])): ?>
                                    <span class="badge bg-primary ms-2"><?= $this->lang->line('gvv_vd_looks_default_badge') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <?php if (empty($look['is_default'])): ?>
                                <form method="post" action="<?= controller_url('vols_decouverte_looks/set_default/' . $look['id']) ?>" class="d-inline">
                                    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                                        <?= $this->lang->line('gvv_vd_looks_set_default') ?>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <form method="post" action="<?= controller_url('vols_decouverte_looks/delete/' . $look['id']) ?>" class="d-inline"
                                      onsubmit="return confirm('<?= htmlspecialchars($this->lang->line('gvv_vd_looks_delete_confirm'), ENT_QUOTES) ?>')">
                                    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash"></i> <?= $this->lang->line('gvv_vd_looks_delete') ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 480px;">
        <div class="card-header"><strong><?= $this->lang->line('gvv_vd_looks_new') ?></strong></div>
        <div class="card-body">
            <form method="post" action="<?= controller_url('vols_decouverte_looks/create') ?>" class="d-flex gap-2">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <input type="text" name="nom" class="form-control" placeholder="<?= $this->lang->line('gvv_vd_looks_name') ?>" required>
                <button type="submit" class="btn btn-primary text-nowrap">
                    <i class="fas fa-plus"></i> <?= $this->lang->line('gvv_vd_looks_create') ?>
                </button>
            </form>
        </div>
    </div>

</div><!-- /#body -->
