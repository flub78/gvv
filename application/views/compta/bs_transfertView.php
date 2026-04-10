<!-- VIEW: application/views/compta/bs_transfertView.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid">

<h3><?= $this->lang->line('gvv_transfert_title') ?></h3>

<?= checkalert($this->session) ?>

<!-- Sélecteur d'année -->
<div class="mb-3">
    <?= $this->lang->line('gvv_year') ?> :
    <select id="year-selector" class="form-select d-inline-block w-auto">
        <?php foreach ($year_selector as $y => $label): ?>
            <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Sélecteur d'écriture -->
<div class="row mb-3 align-items-end">
    <div class="col-md-10">
        <label for="ecriture-select" class="form-label">
            <?= $this->lang->line('gvv_transfert_select_ecriture') ?>
        </label>
        <select class="form-select big_select_large" id="ecriture-select" style="width:100%">
            <option value=""></option>
            <?php foreach ($ecriture_selector as $id => $label): ?>
                <option value="<?= (int)$id ?>"
                        data-label="<?= htmlspecialchars($label, ENT_QUOTES) ?>">
                    <?= htmlspecialchars($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="button" id="btn-add" class="btn btn-success w-100">
            <i class="fas fa-plus me-1"></i><?= $this->lang->line('gvv_transfert_add') ?>
        </button>
    </div>
</div>

<!-- Liste des écritures sélectionnées -->
<h5 class="mt-4"><?= $this->lang->line('gvv_transfert_selection') ?></h5>

<div id="selection-empty" class="text-muted fst-italic mb-3">
    <?= $this->lang->line('gvv_transfert_empty') ?>
</div>

<table id="selection-table" class="table table-sm table-bordered" style="display:none">
    <thead class="table-light">
        <tr>
            <th style="width:40px"></th>
            <th><?= $this->lang->line('gvv_transfert_col_description') ?></th>
        </tr>
    </thead>
    <tbody id="selection-body"></tbody>
</table>

<!-- Formulaire d'export (soumis par JS) -->
<form id="export-form" method="post" action="<?= controller_url('compta/export_ecritures') ?>">
    <div id="export-inputs"></div>
    <button type="submit" id="btn-export" class="btn btn-primary mt-3" style="display:none">
        <i class="fas fa-file-export me-1"></i><?= $this->lang->line('gvv_transfert_export') ?>
    </button>
</form>

</div>

<script>
(function () {
    var ajaxUrl = '<?= site_url('compta/ajax_ecritures_for_transfer') ?>/';
    var selected = []; // [{id, label}]

    // ---- Year selector : recharge le big_select via AJAX ----
    document.getElementById('year-selector').addEventListener('change', function () {
        var year = this.value;
        var $sel = jQuery('#ecriture-select');

        // Vider et désactiver pendant le chargement
        $sel.empty().append('<option value=""></option>');
        if ($sel.data('select2')) {
            $sel.trigger('change');
        }

        jQuery.getJSON(ajaxUrl + year, function (data) {
            data.forEach(function (item) {
                var opt = new Option(item.text, item.id, false, false);
                jQuery(opt).attr('data-label', item.text);
                $sel.append(opt);
            });
            if ($sel.data('select2')) {
                $sel.trigger('change');
            }
        });
    });

    // ---- Ajout à la liste ----
    function render() {
        var empty  = document.getElementById('selection-empty');
        var table  = document.getElementById('selection-table');
        var body   = document.getElementById('selection-body');
        var inputs = document.getElementById('export-inputs');
        var btn    = document.getElementById('btn-export');

        if (selected.length === 0) {
            empty.style.display = '';
            table.style.display = 'none';
            btn.style.display   = 'none';
            inputs.innerHTML    = '';
            body.innerHTML      = '';
            return;
        }

        empty.style.display = 'none';
        table.style.display = '';
        btn.style.display   = '';

        body.innerHTML   = '';
        inputs.innerHTML = '';

        selected.forEach(function (entry, idx) {
            var tr = document.createElement('tr');

            var tdBtn = document.createElement('td');
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-danger btn-sm';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.addEventListener('click', (function (i) {
                return function () {
                    var removed = selected.splice(i, 1)[0];
                    // Remettre l'option dans le big_select, à sa position d'origine (ordre valeur)
                    var $sel = jQuery('#ecriture-select');
                    var opt  = new Option(removed.label, removed.id, false, false);
                    jQuery(opt).attr('data-label', removed.label);
                    // Insertion triée par valeur numérique (id)
                    var inserted = false;
                    $sel.find('option').each(function () {
                        if (parseInt(this.value) > parseInt(removed.id)) {
                            jQuery(opt).insertBefore(jQuery(this));
                            inserted = true;
                            return false;
                        }
                    });
                    if (!inserted) { $sel.append(opt); }
                    if ($sel.data('select2')) { $sel.trigger('change'); }
                    render();
                };
            })(idx));
            tdBtn.appendChild(removeBtn);

            var tdLabel = document.createElement('td');
            tdLabel.textContent = entry.label;

            tr.appendChild(tdBtn);
            tr.appendChild(tdLabel);
            body.appendChild(tr);

            var input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'ids[]';
            input.value = entry.id;
            inputs.appendChild(input);
        });
    }

    document.getElementById('btn-add').addEventListener('click', function () {
        var $sel = jQuery('#ecriture-select');
        var id   = $sel.val();
        if (!id) return;

        var label = $sel.find('option:selected').attr('data-label')
                 || $sel.find('option:selected').text();

        for (var i = 0; i < selected.length; i++) {
            if (selected[i].id === id) {
                alert('<?= $this->lang->line('gvv_transfert_already_added') ?>');
                return;
            }
        }

        selected.push({id: id, label: label});

        // Supprimer l'option du big_select
        $sel.find('option[value="' + id + '"]').remove();
        $sel.val(null).trigger('change');

        render();
    });

    render();
})();
</script>
