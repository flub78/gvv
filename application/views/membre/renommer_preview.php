<!-- VIEW: application/views/membre/renommer_preview.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$old_mlogin = $preview['old_mlogin'];
$new_mlogin = $preview['new_mlogin'];
$member = $preview['member_info'];
$affected_tables = $preview['affected_tables'];
$total_records = $preview['total_records'];
$dx_auth_exists = $preview['dx_auth_exists'];
$sample_records = $preview['sample_records'];
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12">
            <h4><i class="fas fa-search text-warning"></i>
                Prévisualisation du renommage
            </h4>
            <p class="text-muted">Vérifiez attentivement les informations ci-dessous avant de confirmer l'opération.</p>
        </div>
    </div>

    <!-- Résumé du changement -->
    <div class="card border-primary mb-3">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-info-circle"></i>
            Changement d'identifiant
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-5">
                    <div class="alert alert-secondary mb-0">
                        <h6 class="mb-2"><i class="fas fa-user"></i> Identifiant actuel</h6>
                        <div class="fs-4 fw-bold"><?= htmlspecialchars($old_mlogin) ?></div>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-center justify-content-center">
                    <i class="fas fa-arrow-right fa-3x text-primary"></i>
                </div>
                <div class="col-md-5">
                    <div class="alert alert-success mb-0">
                        <h6 class="mb-2"><i class="fas fa-user-check"></i> Nouvel identifiant</h6>
                        <div class="fs-4 fw-bold text-success"><?= htmlspecialchars($new_mlogin) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informations du membre -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-id-card"></i>
            Informations du membre concerné
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong>Nom :</strong></div>
                <div class="col-md-9"><?= htmlspecialchars($member['mnom']) ?></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3"><strong>Prénom :</strong></div>
                <div class="col-md-9"><?= htmlspecialchars($member['mprenom']) ?></div>
            </div>
            <?php if (!empty($member['memail'])): ?>
            <div class="row mt-2">
                <div class="col-md-3"><strong>Email :</strong></div>
                <div class="col-md-9"><?= htmlspecialchars($member['memail']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($member['mdaten'])): ?>
            <div class="row mt-2">
                <div class="col-md-3"><strong>Date de naissance :</strong></div>
                <div class="col-md-9"><?= htmlspecialchars($member['mdaten']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Impact sur la base de données -->
    <div class="card mb-3 border-warning">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-database"></i>
            Impact sur la base de données
            <span class="badge bg-dark ms-2"><?= $total_records ?> enregistrements</span>
        </div>
        <div class="card-body">
            <?php if (empty($affected_tables)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Aucune référence trouvée dans les autres tables. Seule la table <code>membres</code> sera modifiée.
                </div>
            <?php else: ?>
                <p>Les tables suivantes seront mises à jour :</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Table</th>
                                <th>Colonne</th>
                                <th class="text-end">Nombre d'enregistrements</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($affected_tables as $table_info): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($table_info['table']) ?></code></td>
                                <td><code><?= htmlspecialchars($table_info['column']) ?></code></td>
                                <td class="text-end"><span class="badge bg-secondary"><?= $table_info['count'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Total :</th>
                                <th class="text-end"><span class="badge bg-dark"><?= $total_records ?></span></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Compte d'authentification -->
    <?php if ($dx_auth_exists): ?>
    <div class="alert alert-info">
        <i class="fas fa-key"></i>
        <strong>Compte d'authentification :</strong> Un compte utilisateur (dx_auth) existe pour cet identifiant.
        Le <code>username</code> sera également mis à jour de <code><?= htmlspecialchars($old_mlogin) ?></code>
        vers <code><?= htmlspecialchars($new_mlogin) ?></code>.
    </div>
    <?php endif; ?>

    <!-- Exemples d'enregistrements -->
    <?php if (!empty($sample_records)): ?>
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-list"></i>
            Exemples d'enregistrements qui seront modifiés
        </div>
        <div class="card-body">
            <?php if (isset($sample_records['vols_planeur'])): ?>
                <h6><i class="fas fa-plane"></i> Vols planeur récents</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Décollage</th>
                                <th>Atterrissage</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sample_records['vols_planeur'] as $vol): ?>
                            <tr>
                                <td><?= htmlspecialchars($vol['vpdate']) ?></td>
                                <td><?= htmlspecialchars($vol['vpdecol']) ?></td>
                                <td><?= htmlspecialchars($vol['vpatterr']) ?></td>
                                <td><?= htmlspecialchars($vol['vptype']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (isset($sample_records['vols_avion'])): ?>
                <h6><i class="fas fa-plane"></i> Vols avion récents</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Décollage</th>
                                <th>Atterrissage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sample_records['vols_avion'] as $vol): ?>
                            <tr>
                                <td><?= htmlspecialchars($vol['vadate']) ?></td>
                                <td><?= htmlspecialchars($vol['vadecol']) ?></td>
                                <td><?= htmlspecialchars($vol['vaatterr']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (isset($sample_records['tickets'])): ?>
                <h6><i class="fas fa-ticket-alt"></i> Tickets récents</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Prix</th>
                                <th>Commentaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sample_records['tickets'] as $ticket): ?>
                            <tr>
                                <td><?= htmlspecialchars($ticket['tdate']) ?></td>
                                <td><?= htmlspecialchars($ticket['tprix']) ?> €</td>
                                <td><?= htmlspecialchars($ticket['tcommentaire']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Avertissement final et confirmation -->
    <div class="card border-danger mb-3">
        <div class="card-header bg-danger text-white">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>ATTENTION : Opération irréversible</strong>
        </div>
        <div class="card-body">
            <p class="mb-2"><strong>Cette opération va :</strong></p>
            <ul>
                <li>Modifier l'identifiant de <code><?= htmlspecialchars($old_mlogin) ?></code> vers <code><?= htmlspecialchars($new_mlogin) ?></code></li>
                <li>Mettre à jour <strong><?= $total_records ?></strong> enregistrements dans <strong><?= count($affected_tables) ?></strong> table(s)</li>
                <?php if ($dx_auth_exists): ?>
                <li>Modifier le username du compte d'authentification</li>
                <?php endif; ?>
            </ul>
            <p class="mb-3 text-danger">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Cette modification est définitive et ne peut pas être annulée automatiquement.</strong>
            </p>

            <form method="post" action="<?= controller_url('membre/renommer') ?>" class="d-inline">
                <input type="hidden" name="step" value="execute">
                <input type="hidden" name="old_mlogin" value="<?= htmlspecialchars($old_mlogin) ?>">
                <input type="hidden" name="new_mlogin" value="<?= htmlspecialchars($new_mlogin) ?>">
                <button type="submit" class="btn btn-danger btn-lg" onclick="return confirm('Êtes-vous ABSOLUMENT certain de vouloir effectuer ce renommage ?\n\nAncien identifiant : <?= htmlspecialchars($old_mlogin) ?>\nNouvel identifiant : <?= htmlspecialchars($new_mlogin) ?>\n\nCette action est IRRÉVERSIBLE.');">
                    <i class="fas fa-check-circle"></i>
                    Confirmer le renommage
                </button>
            </form>

            <a href="<?= controller_url('membre/renommer') ?>" class="btn btn-secondary btn-lg ms-2">
                <i class="fas fa-times"></i>
                Annuler
            </a>
        </div>
    </div>

</div>
