<div class="container mt-4 mb-5">
    <?php
        $form   = isset($form)   ? $form   : array('id' => 0, 'title' => '', 'code' => '');
        $page   = isset($page)   ? $page   : array('id' => 0, 'page_number' => 1, 'title' => '');
        $fields = isset($fields) ? $fields : array();
        $field_type_labels = array(
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
            <h1 class="h3 mb-1">Champs — page <?= (int) $page['page_number'] ?></h1>
            <p class="text-muted mb-0">
                <?= html_escape($form['title']) ?> (<?= html_escape($form['code']) ?>)
                <?php if (!empty($page['title'])): ?> — <?= html_escape($page['title']) ?><?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/pages/' . (int) $form['id']) ?>">Retour pages</a>
            <a class="btn btn-primary" href="<?= site_url('forms_admin/field_create/' . (int) $form['id'] . '/' . (int) $page['id']) ?>">Ajouter un champ</a>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= html_escape($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= html_escape($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px">Ordre</th>
                            <th>Libellé</th>
                            <th>Nom technique</th>
                            <th>Type</th>
                            <th style="width:80px">Requis</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fields)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Aucun champ défini pour cette page.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fields as $field): ?>
                                <tr>
                                    <td><?= (int) $field['sort_order'] ?></td>
                                    <td><?= html_escape((string) $field['label']) ?></td>
                                    <td><code><?= html_escape((string) $field['name']) ?></code></td>
                                    <td><?= html_escape($field_type_labels[$field['field_type']] ?? $field['field_type']) ?></td>
                                    <td><?= $field['is_required'] ? '<span class="badge bg-danger">Oui</span>' : '<span class="text-muted">Non</span>' ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('forms_admin/field_edit/' . (int) $form['id'] . '/' . (int) $page['id'] . '/' . (int) $field['id']) ?>">Modifier</a>
                                        <a class="btn btn-sm btn-outline-danger" href="<?= site_url('forms_admin/field_delete/' . (int) $form['id'] . '/' . (int) $page['id'] . '/' . (int) $field['id']) ?>" onclick="return confirm('Supprimer ce champ ?');">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
