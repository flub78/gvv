<div class="container mt-4">
    <?php $section_id = isset($section_id) ? (int) $section_id : 0; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Formulaires</h1>
            <p class="text-muted mb-0">Socle d'administration minimal du module formulaires.</p>
        </div>
        <a class="btn btn-primary" href="<?= site_url('forms_admin/create') ?>">Nouveau formulaire</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Titre</th>
                            <th>Section</th>
                            <th>Statut</th>
                            <th>Lien public</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($forms)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <?= ($section_id > 0) ? 'Aucun formulaire pour la section active (et aucun formulaire global).' : 'Aucun formulaire.' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($forms as $form): ?>
                                <tr>
                                    <td><code><?= html_escape($form['code']) ?></code></td>
                                    <td><?= html_escape($form['title']) ?></td>
                                    <td>
                                        <?= !empty($form['section_name']) ? html_escape($form['section_name']) : '<span class="text-muted">Global</span>' ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $form['status'] === 'published' ? 'success' : ($form['status'] === 'archived' ? 'secondary' : 'warning text-dark') ?>">
                                            <?= html_escape($form['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($form['public_slug'])): ?>
                                            <?php $public_url = site_url('forms/' . $form['public_slug']); ?>
                                            <a href="<?= $public_url ?>" target="_blank" class="me-1"><?= html_escape($form['public_slug']) ?></a>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Copier le lien" onclick="navigator.clipboard.writeText('<?= $public_url ?>').then(function(){this.innerHTML='&#10003;';var b=this;setTimeout(function(){b.innerHTML='&#128203;';},1500);}.bind(this));" style="font-size:0.8rem;">&#128203;</button>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('forms_admin/edit/' . $form['id']) ?>">Modifier</a>
                                        <a class="btn btn-sm btn-outline-dark" href="<?= site_url('forms_admin/pages/' . $form['id']) ?>">Pages</a>
                                        <a class="btn btn-sm btn-outline-info" href="<?= site_url('forms_admin/submissions/' . $form['id']) ?>">Reponses</a>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('forms_admin/duplicate/' . $form['id']) ?>">Dupliquer</a>
                                        <?php if ($form['status'] !== 'published'): ?>
                                            <a class="btn btn-sm btn-outline-success" href="<?= site_url('forms_admin/publish/' . $form['id']) ?>">Publier</a>
                                        <?php endif; ?>
                                        <a class="btn btn-sm btn-outline-danger" href="<?= site_url('forms_admin/delete/' . $form['id']) ?>" onclick="return confirm('Supprimer ce formulaire ?');">Supprimer</a>
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