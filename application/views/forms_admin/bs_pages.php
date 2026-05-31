<div class="container mt-4">
    <?php $form = isset($form) ? $form : array('id' => 0, 'title' => '', 'code' => ''); ?>
    <?php $pages = isset($pages) ? $pages : array(); ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Pages du formulaire</h1>
            <p class="text-muted mb-0">
                <?= html_escape(isset($form['title']) ? $form['title'] : '') ?>
                (<?= html_escape(isset($form['code']) ? $form['code'] : '') ?>)
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/edit/' . (int) $form['id']) ?>">Retour formulaire</a>
            <a class="btn btn-primary" href="<?= site_url('forms_admin/page_create/' . (int) $form['id']) ?>">Nouvelle page</a>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Titre</th>
                            <th>Apercu</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Aucune page pour ce formulaire.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $page): ?>
                                <tr>
                                    <td><?= (int) $page['page_number'] ?></td>
                                    <td><?= html_escape((string) $page['title']) ?></td>
                                    <td class="text-muted"><?= html_escape(mb_substr(trim(strip_tags((string) $page['content_html'])), 0, 120)) ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('forms_admin/page_edit/' . (int) $form['id'] . '/' . (int) $page['id']) ?>">Modifier</a>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('forms_admin/fields/' . (int) $form['id'] . '/' . (int) $page['id']) ?>">Champs</a>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('forms_admin/page_export/' . (int) $form['id'] . '/' . (int) $page['id'] . '/html') ?>">Export HTML</a>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('forms_admin/page_export/' . (int) $form['id'] . '/' . (int) $page['id'] . '/txt') ?>">Export TXT</a>
                                        <a class="btn btn-sm btn-outline-danger" href="<?= site_url('forms_admin/page_delete/' . (int) $form['id'] . '/' . (int) $page['id']) ?>" onclick="return confirm('Supprimer cette page ?');">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Importer une page</h2>
            <form method="post" action="<?= site_url('forms_admin/page_import/' . (int) $form['id']) ?>">
                <div class="mb-3">
                    <label class="form-label" for="import_title">Titre</label>
                    <input class="form-control" type="text" id="import_title" name="import_title" maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="import_format">Format source</label>
                    <select class="form-select" id="import_format" name="import_format" required>
                        <option value="html">HTML</option>
                        <option value="text">Texte</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="import_content">Contenu</label>
                    <textarea class="form-control" id="import_content" name="import_content" rows="8" required></textarea>
                    <div class="form-text">Le contenu sera ajoute comme nouvelle page en fin de formulaire.</div>
                </div>
                <button class="btn btn-primary" type="submit">Importer</button>
            </form>
        </div>
    </div>
</div>
