<div class="container mt-4">
    <?php $form = isset($form) ? $form : array('id' => 0, 'title' => '', 'code' => ''); ?>
    <?php $page = isset($page) ? $page : array(); ?>
    <div class="mb-3">
        <h1 class="h3 mb-1"><?= (isset($page_mode) && $page_mode === 'edit') ? 'Modifier la page' : 'Nouvelle page' ?></h1>
        <p class="text-muted mb-0">
            Formulaire : <?= html_escape(isset($form['title']) ? $form['title'] : '') ?>
            (<?= html_escape(isset($form['code']) ? $form['code'] : '') ?>)
        </p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= isset($form_action) ? $form_action : '#' ?>">
                <div class="mb-3">
                    <label class="form-label" for="page_number">Numero de page</label>
                    <input class="form-control" id="page_number" name="page_number" type="number" min="1" required value="<?= html_escape(isset($page['page_number']) ? $page['page_number'] : 1) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="title">Titre</label>
                    <input class="form-control" id="title" name="title" type="text" maxlength="255" value="<?= html_escape(isset($page['title']) ? $page['title'] : '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="content_html">Contenu HTML</label>
                    <textarea class="form-control" id="content_html" name="content_html" rows="12"><?= html_escape(html_entity_decode(isset($page['content_html']) ? $page['content_html'] : '', ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= isset($submit_label) ? $submit_label : 'Enregistrer' ?></button>
                    <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin/pages/' . (int) $form['id']) ?>">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
