<!-- VIEW: application/views/login_as.php -->
<?php
/**
 * Vue pour la fonctionnalité "Login As"
 * Permet de sélectionner un utilisateur pour se connecter en tant que lui
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="fas fa-user-secret text-warning"></i>
                Connexion en tant qu'autre utilisateur
            </h2>
            <p class="text-muted">
                <i class="fas fa-info-circle"></i>
                Cette fonctionnalité permet de tester l'application avec les privilèges d'un autre utilisateur.
                Actuellement connecté: <strong><?= htmlspecialchars($current_user) ?></strong>
            </p>
        </div>
    </div>

    <!-- Messages flash -->
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        <?= $this->session->flashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        <?= $this->session->flashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-exchange-alt"></i>
                    Sélectionner un utilisateur
                </div>
                <div class="card-body">
                    <form action="<?= controller_url('login_as/switch_to') ?>" method="post">

                        <div class="mb-3">
                            <label for="username" class="form-label">Utilisateur cible</label>
                            <select name="username" id="username" class="form-select big_select" style="width: 100%;" required>
                                <option value="">-- Sélectionner un utilisateur --</option>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $disabled = ($user['username'] === $current_user || $user['banned']) ? 'disabled' : '';
                                    $banned_indicator = $user['banned'] ? ' [BANNI]' : '';
                                    ?>
                                    <option value="<?= htmlspecialchars($user['username']) ?>" <?= $disabled ?>>
                                        <?= htmlspecialchars($user['display_name']) ?> - <?= htmlspecialchars($user['role_name']) ?><?= $banned_indicator ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                L'utilisateur actuellement connecté et les utilisateurs bannis sont désactivés.
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Attention:</strong> Cette action sera enregistrée dans l'historique.
                            Vous serez déconnecté de votre session actuelle.
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-sign-in-alt"></i>
                                Se connecter en tant que cet utilisateur
                            </button>
                            <a href="<?= controller_url('welcome') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Retour au tableau de bord
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-users"></i>
                    Liste des utilisateurs (<?= count($users) ?>)
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-sm table-striped table-hover">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Utilisateur</th>
                                <th>Rôle</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $is_current = ($user['username'] === $current_user);
                                $badge_class = 'bg-secondary';
                                if ($user['role_name'] === 'admin') $badge_class = 'bg-danger';
                                elseif ($user['role_name'] === 'tresorier' || $user['role_name'] === 'super-tresorier') $badge_class = 'bg-warning text-dark';
                                elseif ($user['role_name'] === 'ca' || $user['role_name'] === 'bureau') $badge_class = 'bg-primary';
                                elseif ($user['role_name'] === 'planchiste') $badge_class = 'bg-success';
                                ?>
                                <tr class="<?= $is_current ? 'table-active' : '' ?> <?= $user['banned'] ? 'table-danger' : '' ?>">
                                    <td>
                                        <?= htmlspecialchars($user['display_name']) ?>
                                        <?php if ($is_current): ?>
                                            <span class="badge bg-info">Actuel</span>
                                        <?php endif; ?>
                                        <?php if ($user['banned']): ?>
                                            <span class="badge bg-danger">Banni</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($user['role_name']) ?></span>
                                    </td>
                                    <td>
                                        <?php if (!$is_current && !$user['banned']): ?>
                                            <a href="<?= controller_url('login_as/switch_to/' . urlencode($user['username'])) ?>"
                                               class="btn btn-sm btn-outline-warning"
                                               onclick="return confirm('Se connecter en tant que <?= htmlspecialchars($user['display_name']) ?> ?');">
                                                <i class="fas fa-sign-in-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<?php $this->load->view('bs_footer'); ?>
