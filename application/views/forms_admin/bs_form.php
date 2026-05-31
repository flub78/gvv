<div class="container mt-4">
    <div class="mb-3">
        <h1 class="h3 mb-1"><?= (isset($form_mode) && $form_mode === 'edit') ? 'Modifier le formulaire' : 'Nouveau formulaire' ?></h1>
        <p class="text-muted mb-0">Creation du conteneur formulaire avant ajout des pages et champs.</p>
        <?php if (isset($form_mode) && $form_mode === 'edit' && !empty($form['id'])): ?>
            <div class="mt-2">
                <a class="btn btn-sm btn-outline-dark" href="<?= site_url('forms_admin/pages/' . (int) $form['id']) ?>">Gerer les pages</a>
                <a class="btn btn-sm btn-outline-info" href="<?= site_url('forms_admin/submissions/' . (int) $form['id']) ?>">Voir les reponses</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('forms_admin/css_preview/' . (int) $form['id']) ?>" target="_blank">Preview CSS</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= isset($form_action) ? $form_action : site_url('forms_admin/store') ?>">
                <?php if (!empty($section_id) && (int) $section_id > 0): ?>
                    <div class="alert alert-info">
                        Section active : <strong><?= (int) $section_id ?></strong>
                        <br>
                        Cochez "Formulaire global" pour creer un formulaire visible dans toutes les sections.
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary">
                        Aucune section active : le formulaire sera cree comme <strong>global</strong>.
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label" for="code">Code</label>
                    <input class="form-control" id="code" name="code" type="text" maxlength="50" <?= (isset($form_mode) && $form_mode === 'edit') ? '' : 'required' ?> value="<?= html_escape(isset($form['code']) ? $form['code'] : '') ?>" <?= (isset($form_mode) && $form_mode === 'edit') ? 'readonly' : '' ?>>
                    <div class="form-text">Identifiant stable en snake_case ou kebab-case.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="title">Titre</label>
                    <input class="form-control" id="title" name="title" type="text" maxlength="255" required value="<?= html_escape(isset($form['title']) ? $form['title'] : '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?= html_escape(isset($form['description']) ? $form['description'] : '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="public_slug">Lien public</label>
                        <input class="form-control" id="public_slug" name="public_slug" type="text" maxlength="100" value="<?= html_escape(isset($form['public_slug']) ? $form['public_slug'] : '') ?>">
                        <?php if (!empty($form['public_slug'])): ?>
                            <?php $public_url = site_url('forms/' . $form['public_slug']); ?>
                            <div class="mt-1 d-flex align-items-center gap-2">
                                <a href="<?= $public_url ?>" target="_blank" class="form-text text-truncate" style="max-width:260px;"><?= $public_url ?></a>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Copier le lien" onclick="var url='<?= $public_url ?>',btn=this;if(navigator.clipboard){navigator.clipboard.writeText(url).then(function(){btn.innerHTML='&#10003; Copie';setTimeout(function(){btn.innerHTML='&#128203; Copier';},1500);});}else{var t=document.createElement('textarea');t.value=url;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);btn.innerHTML='&#10003; Copie';setTimeout(function(){btn.innerHTML='&#128203; Copier';},1500);}">&#128203; Copier</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="css_scope">CSS scope</label>
                        <input class="form-control" id="css_scope" name="css_scope" type="text" maxlength="100" value="<?= html_escape(isset($form['css_scope']) ? $form['css_scope'] : '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="global_css">CSS global du formulaire</label>
                    <textarea class="form-control" id="global_css" name="global_css" rows="8" placeholder=".forms-public-root h1 { color: #0d6efd; }"><?= html_escape(isset($form['global_css']) ? $form['global_css'] : '') ?></textarea>
                    <div class="form-text">Ce CSS est injecte sur le rendu public et dans la preview admin.</div>
                </div>

                <div class="form-check mb-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="is_global"
                        name="is_global"
                        value="1"
                        <?= !empty($form['is_global']) ? 'checked' : '' ?>
                        <?= (!empty($section_id) && (int) $section_id <= 0) ? 'checked disabled' : '' ?>
                    >
                    <label class="form-check-label" for="is_global">Formulaire global (non rattache a une section)</label>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= isset($submit_label) ? $submit_label : 'Creer' ?></button>
                    <a class="btn btn-outline-secondary" href="<?= site_url('forms_admin') ?>">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>