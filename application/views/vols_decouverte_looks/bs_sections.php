<!-- VIEW: application/views/vols_decouverte_looks/bs_sections.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-random text-secondary"></i> <?= $this->lang->line('gvv_vd_looks_sections_title') ?></h4>
            <a href="<?= controller_url('vols_decouverte_looks') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> <?= $this->lang->line('gvv_vd_looks_back_to_list') ?>
            </a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= controller_url('vols_decouverte_looks/sections') ?>">
        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th><?= $this->lang->line('gvv_vd_looks_sections_section') ?></th>
                        <th><?= $this->lang->line('gvv_vd_looks_sections_look') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $section): ?>
                        <tr>
                            <td><?= htmlspecialchars($section['nom']) ?></td>
                            <td>
                                <select class="form-select form-select-sm" name="look_<?= $section['id'] ?>" style="max-width:320px">
                                    <option value=""><?= $this->lang->line('gvv_vd_looks_sections_default_option') ?></option>
                                    <?php foreach ($looks as $look): ?>
                                        <option value="<?= $look['id'] ?>" <?= ((int) ($current[$section['id']] ?? 0) === (int) $look['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($look['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <button type="submit" name="save_sections" value="1" class="btn btn-primary">
            <i class="fas fa-save"></i> <?= $this->lang->line('gvv_vd_looks_sections_save') ?>
        </button>
    </form>

</div><!-- /#body -->
