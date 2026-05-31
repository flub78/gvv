<div class="container mt-4 mb-5">
    <?php
        $form         = isset($form)         ? $form         : array('id' => 0, 'title' => '', 'code' => '');
        $page         = isset($page)         ? $page         : array('id' => 0, 'page_number' => 1, 'title' => '');
        $field        = isset($field)        ? $field        : array();
        $field_mode   = isset($field_mode)   ? $field_mode   : 'create';
        $form_action  = isset($form_action)  ? $form_action  : '';
        $submit_label = isset($submit_label) ? $submit_label : 'Enregistrer';
        $error        = isset($error)        ? $error        : '';

        $v_name         = isset($field['name'])         ? $field['name']         : '';
        $v_label        = isset($field['label'])        ? $field['label']        : '';
        $v_field_type   = isset($field['field_type'])   ? $field['field_type']   : 'text';
        $v_is_required  = !empty($field['is_required']);
        $v_sort_order   = isset($field['sort_order'])   ? $field['sort_order']   : '';
        $v_options_text = isset($field['options_text']) ? $field['options_text'] : '';

        $types_with_options = array('select', 'radio', 'checkbox');
        $field_types = array(
            'text'     => 'Texte',
            'email'    => 'Email',
            'date'     => 'Date',
            'number'   => 'Nombre',
            'textarea' => 'Zone de texte',
            'select'   => 'Liste déroulante',
            'radio'    => 'Boutons radio',
            'checkbox' => 'Cases à cocher',
            'file'     => 'Fichier',
        );
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1"><?= $field_mode === 'create' ? 'Ajouter un champ' : 'Modifier le champ' ?></h1>
            <p class="text-muted mb-0">
                <?= html_escape($form['title']) ?> (<?= html_escape($form['code']) ?>)
                — page <?= (int) $page['page_number'] ?>
                <?php if (!empty($page['title'])): ?>(<?= html_escape($page['title']) ?>)<?php endif; ?>
            </p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/fields/' . (int) $form['id'] . '/' . (int) $page['id']) ?>">Retour champs</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= $form_action ?>">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="label">Libellé <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" id="label" name="label" maxlength="255" required
                               value="<?= html_escape($v_label) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="name">Nom technique <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" id="name" name="name" maxlength="100" required
                               pattern="[a-zA-Z0-9_\-]+" value="<?= html_escape($v_name) ?>">
                        <div class="form-text">Lettres, chiffres, underscore, tiret. Utilisé comme identifiant du champ.</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="field_type">Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="field_type" name="field_type" required onchange="toggleOptions(this.value)">
                            <?php foreach ($field_types as $type_key => $type_label): ?>
                                <option value="<?= $type_key ?>" <?= $v_field_type === $type_key ? 'selected' : '' ?>><?= $type_label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="sort_order">Ordre d'affichage</label>
                        <input class="form-control" type="number" id="sort_order" name="sort_order" min="1"
                               value="<?= html_escape((string) $v_sort_order) ?>">
                        <div class="form-text">Laissez vide pour ajouter en fin de liste.</div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center pt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_required" name="is_required" value="1" <?= $v_is_required ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_required">Champ obligatoire</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3" id="options_block" style="<?= in_array($v_field_type, $types_with_options) ? '' : 'display:none' ?>">
                    <label class="form-label" for="options_text">Options <span class="text-muted">(une par ligne)</span></label>
                    <textarea class="form-control" id="options_text" name="options_text" rows="6"><?= html_escape($v_options_text) ?></textarea>
                    <div class="form-text">Chaque ligne correspond à une option proposée à l'utilisateur.</div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= html_escape($submit_label) ?></button>
                    <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/fields/' . (int) $form['id'] . '/' . (int) $page['id']) ?>">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var typesWithOptions = <?= json_encode($types_with_options) ?>;

function toggleOptions(type) {
    var block = document.getElementById('options_block');
    block.style.display = typesWithOptions.indexOf(type) !== -1 ? '' : 'none';
}

document.getElementById('label').addEventListener('input', function() {
    var nameField = document.getElementById('name');
    if (nameField.dataset.manual) return;
    nameField.value = this.value
        .toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
});

document.getElementById('name').addEventListener('input', function() {
    this.dataset.manual = '1';
});
</script>
