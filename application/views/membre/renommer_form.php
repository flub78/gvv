<!-- VIEW: application/views/membre/renommer_form.php -->
<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12">
            <h4><i class="fas fa-edit text-warning"></i>
                Renommer un utilisateur
            </h4>
            <p class="text-muted">Modifier l'identifiant (login) d'un utilisateur. Le changement sera propagé dans toute la base de données.</p>
        </div>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <?= $this->session->flashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?= $this->session->flashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card border-warning">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-info-circle"></i>
            Sélection de l'utilisateur à renommer
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-lightbulb"></i>
                <strong>Information :</strong> Cette fonctionnalité permet de corriger des identifiants mal saisis (ex: identifiants purement numériques, fautes d'orthographe).
                Le changement est propagé de manière atomique dans toutes les tables de la base de données.
            </div>

            <form method="post" action="<?= controller_url('membre/renommer') ?>" id="rename-form">
                <input type="hidden" name="step" value="preview">

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user"></i>
                            Utilisateur à renommer
                        </label>
                        <select name="old_mlogin" class="form-select big_select" required>
                            <option value="">-- Sélectionner un membre --</option>
                            <?php foreach ($membres_selector as $mlogin => $nom): ?>
                                <?php if ($mlogin !== ''): ?>
                                <option value="<?= htmlspecialchars($mlogin) ?>">
                                    <?= htmlspecialchars($nom) ?> (<?= htmlspecialchars($mlogin) ?>)
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-1 d-flex align-items-end justify-content-center pb-1">
                        <i class="fas fa-arrow-right fa-2x text-muted"></i>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-bold">
                            <i class="fas fa-keyboard"></i>
                            Nouvel identifiant
                        </label>
                        <input type="text"
                               name="new_mlogin"
                               class="form-control"
                               placeholder="Ex: jdupont"
                               pattern="[a-zA-Z0-9_-]+"
                               required
                               maxlength="50">
                        <small class="form-text text-muted">
                            Lettres, chiffres, tirets et underscores uniquement. Pas uniquement des chiffres.
                        </small>
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-search"></i>
                            Prévisualiser
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-3">
        <a href="<?= controller_url('welcome') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour au tableau de bord
        </a>
    </div>

</div>

<script>
// Validate form before submission
document.getElementById('rename-form').addEventListener('submit', function(e) {
    const newLogin = document.querySelector('input[name="new_mlogin"]').value.trim();

    // Check if purely numeric
    if (/^\d+$/.test(newLogin)) {
        e.preventDefault();
        alert('L\'identifiant ne peut pas être uniquement numérique.');
        return false;
    }

    // Check format
    if (!/^[a-zA-Z0-9_-]+$/.test(newLogin)) {
        e.preventDefault();
        alert('L\'identifiant ne peut contenir que des lettres, chiffres, tirets et underscores.');
        return false;
    }

    return true;
});
</script>
