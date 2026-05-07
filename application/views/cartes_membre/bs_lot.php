<!-- VIEW: application/views/cartes_membre/bs_lot.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

// Données membres pour le JS (big_select individuel)
$all_membres_js = array();
foreach ($all_membres as $m) {
    $all_membres_js[$m['mlogin']] = array(
        'mnom'    => $m['mnom'],
        'mprenom' => $m['mprenom'],
        'mnumero' => $m['mnumero'] ?? '',
    );
}
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-id-card text-primary"></i> <?= $this->lang->line('gvv_cartes_membre_lot_title') ?></h4>
            <a href="<?= controller_url('cartes_membre/config') ?>?year=<?= $year ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-cog"></i> <?= $this->lang->line('gvv_cartes_membre_config') ?>
            </a>
        </div>
    </div>

    <form method="post" action="<?= controller_url('cartes_membre/lot') ?>">
        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>

        <!-- Liste complète des membres affichés : permet de restaurer les coches après rechargement -->
        <?php foreach ($membres as $m): ?>
        <input type="hidden" name="membres_prev[]" value="<?= htmlspecialchars($m['mlogin']) ?>">
        <?php endforeach; ?>

        <!-- Filtres : année, type de sélection, années couvertes -->
        <div class="row mb-3 g-3 align-items-end">

            <!-- Sélecteur d'année -->
            <div class="col-md-2">
                <label class="form-label"><?= $this->lang->line('gvv_cartes_membre_year') ?></label>
                <select name="year" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($year_selector as $y => $label): ?>
                        <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtre membres -->
            <div class="col-md-4">
                <label class="form-label d-block">&nbsp;</label>
                <div class="d-flex gap-3 align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="filtre" id="filtre_cotisation"
                               value="cotisation" <?= ($filtre !== 'tous') ? 'checked' : '' ?>
                               onchange="updateFiltre(); this.form.submit()">
                        <label class="form-check-label" for="filtre_cotisation">
                            <?= $this->lang->line('gvv_cartes_membre_filtre_cotisation') ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="filtre" id="filtre_tous"
                               value="tous" <?= ($filtre === 'tous') ? 'checked' : '' ?>
                               onchange="updateFiltre(); this.form.submit()">
                        <label class="form-check-label" for="filtre_tous">
                            <?= $this->lang->line('gvv_cartes_membre_filtre_tous') ?>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Checkbox année précédente (visible uniquement si filtre cotisation) -->
            <div class="col-md-4" id="div_annee_precedente" <?= ($filtre === 'tous') ? 'style="display:none"' : '' ?>>
                <label class="form-label d-block">&nbsp;</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="annee_precedente" id="annee_precedente"
                           value="1" <?= $annee_precedente ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    <label class="form-check-label" for="annee_precedente">
                        <?= $this->lang->line('gvv_cartes_membre_annee_precedente') ?>
                    </label>
                </div>
            </div>

            <!-- Compteur -->
            <div class="col-md-2 text-muted small d-flex align-items-end pb-1" id="membres-count">
                <?= count($membres) ?> <?= $this->lang->line($filtre === 'tous' ? 'gvv_cartes_membre_membres_count_tous' : 'gvv_cartes_membre_membres_count') ?>
            </div>

        </div>

        <!-- Ajout individuel via big_select -->
        <div class="row mb-3 g-2 align-items-center">
            <div class="col-md-6">
                <div class="d-flex gap-2 align-items-center">
                    <div class="flex-grow-1">
                        <select id="add-membre-select" class="form-select big_select_large">
                            <option value=""></option>
                            <?php foreach ($all_membres as $m): ?>
                            <option value="<?= htmlspecialchars($m['mlogin']) ?>">
                                <?= htmlspecialchars(strtoupper($m['mnom']) . ' ' . $m['mprenom'] . ($m['mnumero'] ? ' — N° ' . $m['mnumero'] : '')) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-success" id="btn-add-membre" title="<?= $this->lang->line('gvv_cartes_membre_ajouter_individuel') ?>">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($membres)): ?>
            <div class="alert alert-warning" id="no-membres-alert">
                <?= $this->lang->line($filtre === 'tous' ? 'gvv_cartes_membre_no_membres_tous' : 'gvv_cartes_membre_no_membres') ?>
            </div>
        <?php endif; ?>

        <!-- Liste membres -->
        <div class="card mb-3" id="membres-card" <?= empty($membres) ? 'style="display:none"' : '' ?>>
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= $this->lang->line('gvv_cartes_membre_select_membres') ?></span>
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm me-1" onclick="selectAll(true)">
                        <?= $this->lang->line('gvv_cartes_membre_select_all') ?>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAll(false)">
                        <?= $this->lang->line('gvv_cartes_membre_deselect_all') ?>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <?php
                                $all_checked = true;
                                foreach ($membres as $m) {
                                    $l = $m['mlogin'];
                                    if (!(empty($membres_prev) || !isset($membres_prev[$l]) || isset($membres_selected[$l]))) {
                                        $all_checked = false;
                                        break;
                                    }
                                }
                            ?>
                            <th style="width:40px"><input type="checkbox" id="checkAll" <?= $all_checked ? 'checked' : '' ?> onchange="selectAll(this.checked)"></th>
                            <th><?= $this->lang->line('gvv_cartes_membre_nom') ?></th>
                            <th><?= $this->lang->line('gvv_cartes_membre_prenom') ?></th>
                            <th><?= $this->lang->line('gvv_cartes_membre_numero') ?></th>
                        </tr>
                    </thead>
                    <tbody id="membres-tbody">
                        <?php foreach ($membres as $m): ?>
                        <?php
                            $login = $m['mlogin'];
                            $is_checked = empty($membres_prev)
                                || !isset($membres_prev[$login])
                                || isset($membres_selected[$login]);
                        ?>
                        <tr>
                            <td><input type="checkbox" name="membres[]" value="<?= htmlspecialchars($login) ?>" class="membre-cb" <?= $is_checked ? 'checked' : '' ?>></td>
                            <td><?= htmlspecialchars($m['mnom']) ?></td>
                            <td><?= htmlspecialchars($m['mprenom']) ?></td>
                            <td><?= htmlspecialchars($m['mnumero'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex gap-2" id="generate-btn-row" <?= empty($membres) ? 'style="display:none"' : '' ?>>
            <button type="submit" name="generate" value="1" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> <?= $this->lang->line('gvv_cartes_membre_generate') ?>
            </button>
        </div>

    </form>

</div>

<script>
var allMembres = <?= json_encode($all_membres_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

function esc(str) {
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function selectAll(checked) {
    document.querySelectorAll('.membre-cb').forEach(cb => cb.checked = checked);
    document.getElementById('checkAll').checked = checked;
}

function updateFiltre() {
    var isCotisation = document.getElementById('filtre_cotisation').checked;
    var div = document.getElementById('div_annee_precedente');
    if (div) div.style.display = isCotisation ? '' : 'none';
}

document.getElementById('btn-add-membre').addEventListener('click', function() {
    var select = document.getElementById('add-membre-select');
    var login  = select.value;
    if (!login) return;

    var m = allMembres[login];
    if (!m) return;

    // Déjà dans la table → flash la ligne et décocher/cocher
    var existing = document.querySelector('input[name="membres[]"][value="' + CSS.escape(login) + '"]');
    if (existing) {
        var row = existing.closest('tr');
        row.classList.add('table-warning');
        row.scrollIntoView({behavior: 'smooth', block: 'center'});
        setTimeout(function() { row.classList.remove('table-warning'); }, 2000);
        $('#add-membre-select').val(null).trigger('change');
        return;
    }

    // Ajouter une ligne dans le tbody
    var tbody = document.getElementById('membres-tbody');
    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td><input type="checkbox" name="membres[]" value="' + esc(login) + '" class="membre-cb" checked></td>' +
        '<td>' + esc(m.mnom) + '</td>' +
        '<td>' + esc(m.mprenom) + '</td>' +
        '<td>' + esc(m.mnumero) + '</td>';
    tbody.appendChild(tr);

    // Ajouter le champ membres_prev[] pour la persistance
    var hiddenPrev = document.createElement('input');
    hiddenPrev.type  = 'hidden';
    hiddenPrev.name  = 'membres_prev[]';
    hiddenPrev.value = login;
    document.querySelector('form').appendChild(hiddenPrev);

    // Afficher la carte et le bouton générer si cachés
    document.getElementById('membres-card').style.display = '';
    document.getElementById('generate-btn-row').style.display = '';
    var alert = document.getElementById('no-membres-alert');
    if (alert) alert.style.display = 'none';

    // Réinitialiser le select2
    $('#add-membre-select').val(null).trigger('change');
});
</script>
