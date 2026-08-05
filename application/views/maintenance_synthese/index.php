<?php
/**
 * Vue : synthese flotte, pire etat par aeronef (PRD EF7.3)
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<div id="body" class="body container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>
            <i class="fas fa-shield-alt" aria-hidden="true"></i>
            <?= $this->lang->line('maintenance_synthese_titre') ?>
        </h3>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="section_select" class="form-label"><?= $this->lang->line('maintenance_programme_section') ?></label>
                    <?= form_dropdown('section_select', $section_selector, $section_id, 'class="form-select" id="section_select"') ?>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary" id="btn-filtrer-section">
                        <i class="fas fa-filter" aria-hidden="true"></i> <?= $this->lang->line('maintenance_synthese_filtrer') ?>
                    </button>
                </div>
            </form>
            <script>
                document.getElementById('btn-filtrer-section').addEventListener('click', function () {
                    var section = document.getElementById('section_select').value;
                    window.location.href = '<?= controller_url($controller) ?>/index/' + encodeURIComponent(section);
                });
            </script>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th><?= $this->lang->line('maintenance_synthese_aeronef') ?></th>
                        <th><?= $this->lang->line('maintenance_synthese_etat_global') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($aeronefs)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            <?= $this->lang->line('maintenance_synthese_aucun_aeronef') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($aeronefs as $aeronef): ?>
                    <tr>
                        <td>
                            <a href="<?= controller_url($controller) ?>/aeronef/<?= htmlspecialchars($aeronef['macimmat']) ?>">
                                <?= htmlspecialchars($aeronef['macmodele']) ?> - <?= htmlspecialchars($aeronef['macimmat']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge <?= $etat_badges[$aeronef['etat']] ?>"><?= htmlspecialchars($etat_labels[$aeronef['etat']]) ?></span>
                        </td>
                        <td class="text-end">
                            <a href="<?= controller_url($controller) ?>/aeronef/<?= htmlspecialchars($aeronef['macimmat']) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-eye" aria-hidden="true"></i> <?= $this->lang->line('maintenance_programmes_view') ?>
                            </a>
                            <a href="<?= controller_url($controller) ?>/export_pdf/<?= htmlspecialchars($aeronef['macimmat']) ?>" class="btn btn-sm btn-outline-danger" target="_blank">
                                <i class="fas fa-file-pdf" aria-hidden="true"></i> PDF
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<?php $this->load->view('bs_footer'); ?>
