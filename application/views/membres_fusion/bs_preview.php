<!-- VIEW: application/views/membres_fusion/bs_preview.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$src     = $rapport['source'];
$dst     = $rapport['destination'];
$fields  = $rapport['fields_comparison'];
$refs    = $rapport['references'];
$soldes  = $rapport['soldes'];
$conflicts = $rapport['conflicts'];
$auth_src  = $rapport['auth_source'];

// Labels lisibles pour les champs membres
$field_labels = array(
    'mnom' => 'Nom', 'mprenom' => 'Prénom', 'memail' => 'Email',
    'memailparent' => 'Email parents', 'madresse' => 'Adresse', 'cp' => 'Code postal',
    'ville' => 'Ville', 'pays' => 'Pays', 'mtelf' => 'Tél. fixe', 'mtelm' => 'Mobile',
    'mdaten' => 'Date naissance', 'm25ans' => '< 25 ans', 'mlieun' => 'Lieu naissance',
    'msexe' => 'Sexe',
    'club' => 'Club', 'ext' => 'Ext.', 'actif' => 'Actif', 'username' => 'Username',
    'photo' => 'Photo', 'compte' => 'Compte', 'comment' => 'Commentaire',
    'trigramme' => 'Trigramme', 'categorie' => 'Catégorie', 'profession' => 'Profession',
    'inst_glider' => 'Instr. planeur', 'inst_airplane' => 'Instr. avion',
    'licfed' => 'Licence fédérale', 'place_of_birth' => 'Lieu naissance (alt.)',
    'inscription_date' => 'Date inscription', 'validation_date' => 'Date validation',
    'membre_payeur' => 'Membre payeur',
);

$total_records = array_sum(array_column($refs, 'count'));
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12">
            <h4><i class="fas fa-code-branch text-danger"></i>
                <?= $this->lang->line('gvv_fusion_preview_title') ?>
            </h4>
        </div>
    </div>

    <!-- Résumé des membres -->
    <div class="row mb-3 g-3">
        <div class="col-md-5">
            <div class="card border-danger h-100">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-user-times"></i>
                    <?= $this->lang->line('gvv_fusion_source_label') ?> :
                    <strong><?= htmlspecialchars($src['mnom'] . ' ' . $src['mprenom']) ?></strong>
                    <small>(<?= htmlspecialchars($source) ?>)</small>
                </div>
                <div class="card-body py-2">
                    <small class="text-muted"><?= $this->lang->line('gvv_fusion_source_will_be_deleted') ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-1 d-flex align-items-center justify-content-center">
            <i class="fas fa-long-arrow-alt-right fa-2x text-muted"></i>
        </div>
        <div class="col-md-5">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-user-check"></i>
                    <?= $this->lang->line('gvv_fusion_dest_label') ?> :
                    <strong><?= htmlspecialchars($dst['mnom'] . ' ' . $dst['mprenom']) ?></strong>
                    <small>(<?= htmlspecialchars($destination) ?>)</small>
                </div>
                <div class="card-body py-2">
                    <small class="text-muted"><?= $this->lang->line('gvv_fusion_dest_will_be_kept') ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Avertissement dx_auth -->
    <?php if ($auth_src): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <?= sprintf($this->lang->line('gvv_fusion_auth_warning'), htmlspecialchars($source)) ?>
    </div>
    <?php endif; ?>

    <!-- Section 1 : Comparaison des fiches membres -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-id-card"></i>
            <?= $this->lang->line('gvv_fusion_section_fields') ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:20%"><?= $this->lang->line('gvv_fusion_col_field') ?></th>
                            <th style="width:35%" class="text-danger"><?= $this->lang->line('gvv_fusion_col_source') ?> (<?= htmlspecialchars($source) ?>)</th>
                            <th style="width:35%" class="text-success"><?= $this->lang->line('gvv_fusion_col_dest') ?> (<?= htmlspecialchars($destination) ?>)</th>
                            <th style="width:10%"><?= $this->lang->line('gvv_fusion_col_action') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields as $f): ?>
                        <?php
                        $src_disp = ($f['src_val'] !== null && $f['src_val'] !== '') ? htmlspecialchars($f['src_val']) : '<em class="text-muted">—</em>';
                        $dst_disp = ($f['dst_val'] !== null && $f['dst_val'] !== '') ? htmlspecialchars($f['dst_val']) : '<em class="text-muted">—</em>';
                        $label    = isset($field_labels[$f['field']]) ? $field_labels[$f['field']] : $f['field'];
                        ?>
                        <tr <?= $f['will_copy'] ? 'class="table-warning"' : '' ?>>
                            <td class="fw-semibold small"><?= $label ?></td>
                            <td class="small"><?= $src_disp ?></td>
                            <td class="small"><?= $dst_disp ?></td>
                            <td class="text-center small">
                                <?php if ($f['will_copy']): ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-arrow-right"></i> <?= $this->lang->line('gvv_fusion_will_copy') ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 2 : Données liées -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-database"></i>
            <?= $this->lang->line('gvv_fusion_section_refs') ?>
            <span class="badge bg-secondary ms-2"><?= $total_records ?> enregistrement(s)</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($refs)): ?>
            <p class="p-3 text-muted mb-0"><?= $this->lang->line('gvv_fusion_no_refs') ?></p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= $this->lang->line('gvv_fusion_col_table') ?></th>
                            <th><?= $this->lang->line('gvv_fusion_col_column') ?></th>
                            <th class="text-end"><?= $this->lang->line('gvv_fusion_col_count') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($refs as $r): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($r['table']) ?></code></td>
                            <td><code><?= htmlspecialchars($r['column']) ?></code></td>
                            <td class="text-end"><?= $r['count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section 3 : Conflits détectés -->
    <?php if (!empty($conflicts)): ?>
    <div class="card mb-3 border-warning">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-exclamation-circle"></i>
            <?= $this->lang->line('gvv_fusion_section_conflicts') ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= $this->lang->line('gvv_fusion_col_table') ?></th>
                            <th><?= $this->lang->line('gvv_fusion_col_column') ?></th>
                            <th class="text-end"><?= $this->lang->line('gvv_fusion_col_deleted') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conflicts as $c): ?>
                        <tr class="table-warning">
                            <td><code><?= htmlspecialchars($c['table']) ?></code></td>
                            <td><code><?= htmlspecialchars($c['column']) ?></code></td>
                            <td class="text-end text-danger fw-bold"><?= $c['count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Section 4 : Récapitulatif financier -->
    <?php if (!empty($soldes)): ?>
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-euro-sign"></i>
            <?= $this->lang->line('gvv_fusion_section_soldes') ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= $this->lang->line('gvv_fusion_col_section') ?></th>
                            <th class="text-end text-danger"><?= $this->lang->line('gvv_fusion_col_solde_src') ?></th>
                            <th class="text-end text-success"><?= $this->lang->line('gvv_fusion_col_solde_dst') ?></th>
                            <th class="text-end fw-bold"><?= $this->lang->line('gvv_fusion_col_solde_apres') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($soldes as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['section']) ?></td>
                            <td class="text-end"><?= number_format($s['solde_src'], 2, ',', ' ') ?> €</td>
                            <td class="text-end"><?= number_format($s['solde_dst'], 2, ',', ' ') ?> €</td>
                            <td class="text-end fw-bold"><?= number_format($s['solde_apres'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Boutons de confirmation -->
    <div class="card border-danger">
        <div class="card-body">
            <div class="alert alert-danger mb-3">
                <i class="fas fa-skull-crossbones"></i>
                <strong><?= $this->lang->line('gvv_fusion_confirm_warning') ?></strong>
                <?= sprintf($this->lang->line('gvv_fusion_confirm_detail'), htmlspecialchars($source), htmlspecialchars($destination)) ?>
            </div>
            <div class="d-flex gap-2">
                <form method="post" action="<?= controller_url('membres_fusion/executer') ?>">
                    <input type="hidden" name="source"      value="<?= htmlspecialchars($source) ?>">
                    <input type="hidden" name="destination" value="<?= htmlspecialchars($destination) ?>">
                    <button type="submit" class="btn btn-danger"
                            onclick="return confirm('<?= $this->lang->line('gvv_fusion_confirm_js') ?>')">
                        <i class="fas fa-code-branch"></i>
                        <?= $this->lang->line('gvv_fusion_btn_confirm') ?>
                    </button>
                </form>
                <a href="<?= controller_url('membres_fusion') ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    <?= $this->lang->line('gvv_fusion_btn_cancel') ?>
                </a>
            </div>
        </div>
    </div>

</div>

