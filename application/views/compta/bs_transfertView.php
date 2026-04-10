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
    <div class="mt-3" id="export-actions" style="display:none">
        <button type="button" id="btn-preview-json" class="btn btn-outline-secondary me-2">
            <i class="fas fa-code me-1"></i><?= $this->lang->line('gvv_transfert_preview_json') ?>
        </button>
        <button type="submit" id="btn-export" class="btn btn-primary">
            <i class="fas fa-file-export me-1"></i><?= $this->lang->line('gvv_transfert_export') ?>
        </button>
    </div>
</form>

<div class="modal fade" id="jsonPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $this->lang->line('gvv_transfert_preview_modal_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="small text-muted mb-2" id="json-copy-feedback"></div>
                <textarea id="json-preview-content" class="form-control" rows="18" style="font-family: monospace; overflow: auto; white-space: pre;" readonly></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" id="btn-copy-json">
                    <i class="fas fa-copy me-1"></i><?= $this->lang->line('gvv_transfert_copy_json') ?>
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $this->lang->line('gvv_str_close') ?></button>
            </div>
        </div>
    </div>
</div>

</div>

<script>
(function () {
    var ajaxUrl = '<?= site_url('compta/ajax_ecritures_for_transfer') ?>/';
    var previewUrl = '<?= site_url('compta/preview_export_ecritures') ?>';
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
        var actions = document.getElementById('export-actions');

        if (selected.length === 0) {
            empty.style.display = '';
            table.style.display = 'none';
            actions.style.display = 'none';
            inputs.innerHTML    = '';
            body.innerHTML      = '';
            return;
        }

        empty.style.display = 'none';
        table.style.display = '';
        actions.style.display = '';

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

    function buildRequestBody() {
        var params = new URLSearchParams();
        selected.forEach(function (entry) {
            params.append('ids[]', entry.id);
        });
        return params.toString();
    }

    document.getElementById('btn-preview-json').addEventListener('click', function () {
        if (selected.length === 0) {
            return;
        }
        var previewField = document.getElementById('json-preview-content');
        var feedback = document.getElementById('json-copy-feedback');
        feedback.textContent = '';
        previewField.value = '';

        fetch(previewUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: buildRequestBody()
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.text();
        }).then(function (jsonText) {
            previewField.value = jsonText;
            var modal = new bootstrap.Modal(document.getElementById('jsonPreviewModal'));
            modal.show();
        }).catch(function () {
            previewField.value = '{"error":"preview_failed"}';
            var modal = new bootstrap.Modal(document.getElementById('jsonPreviewModal'));
            modal.show();
        });
    });

    document.getElementById('btn-copy-json').addEventListener('click', function () {
        var previewField = document.getElementById('json-preview-content');
        var feedback = document.getElementById('json-copy-feedback');
        var text = previewField.value || '';
        if (!text) {
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                feedback.textContent = '<?= $this->lang->line('gvv_transfert_copy_ok') ?>';
            }).catch(function () {
                feedback.textContent = '<?= $this->lang->line('gvv_transfert_copy_ko') ?>';
            });
        } else {
            previewField.focus();
            previewField.select();
            try {
                var ok = document.execCommand('copy');
                feedback.textContent = ok
                    ? '<?= $this->lang->line('gvv_transfert_copy_ok') ?>'
                    : '<?= $this->lang->line('gvv_transfert_copy_ko') ?>';
            } catch (e) {
                feedback.textContent = '<?= $this->lang->line('gvv_transfert_copy_ko') ?>';
            }
            window.getSelection().removeAllRanges();
        }
    });

    render();
})();
</script>
