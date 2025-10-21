<?php
/**
 * Migration Dashboard - Pilot Users Tab
 *
 * Displays and manages pilot user migrations with wizard workflow.
 * Part of Phase 6: Progressive Migration
 *
 * @see /doc/plans_and_progress/phase6_migration_dashboard_mockups.md
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs Pilotes - Migration GVV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .migration-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .user-card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-in_progress { background-color: #0dcaf0; color: #000; }
        .status-completed { background-color: #198754; color: #fff; }
        .status-failed { background-color: #dc3545; color: #fff; }
        .role-badge {
            background-color: #e7f3ff;
            color: #0066cc;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-right: 0.25rem;
        }
        .wizard-step {
            padding: 2rem;
            min-height: 300px;
        }
        .wizard-nav {
            border-top: 1px solid #dee2e6;
            padding-top: 1rem;
            margin-top: 1rem;
        }
        .permission-mapping {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<div class="migration-header">
    <div class="container">
        <h1><i class="bi bi-people-fill"></i> Gestion des Utilisateurs Pilotes</h1>
        <p class="mb-0">Migration progressive - Test et validation</p>
    </div>
</div>

<div class="container">
    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo site_url('authorization/migration'); ?>">
                <i class="bi bi-speedometer2"></i> Vue d'ensemble
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="<?php echo site_url('authorization/migration_pilot_users'); ?>">
                <i class="bi bi-people"></i> Utilisateurs pilotes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo site_url('authorization/migration_comparison_log'); ?>">
                <i class="bi bi-journal-text"></i> Journal de comparaison
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo site_url('authorization/migration_statistics'); ?>">
                <i class="bi bi-graph-up"></i> Statistiques
            </a>
        </li>
    </ul>

    <!-- Filter Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <select class="form-select" id="statusFilter">
                        <option value="">Tous les statuts</option>
                        <option value="pending">En attente</option>
                        <option value="in_progress">En cours</option>
                        <option value="completed">Terminé</option>
                        <option value="failed">Échoué</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <input type="text" class="form-control" id="searchInput" placeholder="Rechercher un utilisateur...">
                </div>
            </div>
        </div>
    </div>

    <!-- Pilot Users List -->
    <div id="pilotUsersList">
        <?php if (empty($pilot_users)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Aucun utilisateur pilote trouvé.
                Assurez-vous que les utilisateurs de test ont été créés via <code>bin/create_test_users.sh</code>.
            </div>
        <?php else: ?>
            <?php foreach ($pilot_users as $user): ?>
            <div class="card user-card" data-status="<?php echo $user['migration_status'] ?? 'pending'; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h5 class="mb-1">
                                <i class="bi bi-person-circle text-primary"></i>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </h5>
                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                        </div>
                        <div class="col-md-3">
                            <strong>Rôles legacy:</strong><br>
                            <?php if (!empty($user['legacy_roles'])): ?>
                                <?php foreach ($user['legacy_roles'] as $role): ?>
                                    <span class="role-badge"><?php echo htmlspecialchars($role['name']); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">Aucun</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 text-center">
                            <?php
                            $status = $user['migration_status'] ?? 'pending';
                            $status_labels = array(
                                'pending' => '<span class="status-badge status-pending"><i class="bi bi-pause-circle"></i> En attente</span>',
                                'in_progress' => '<span class="status-badge status-in_progress"><i class="bi bi-play-circle"></i> En cours</span>',
                                'completed' => '<span class="status-badge status-completed"><i class="bi bi-check-circle"></i> Terminé</span>',
                                'failed' => '<span class="status-badge status-failed"><i class="bi bi-x-circle"></i> Échoué</span>'
                            );
                            echo $status_labels[$status] ?? $status_labels['pending'];
                            ?>
                            <br>
                            <?php if ($user['migrated_at']): ?>
                                <small class="text-muted">Depuis: <?php echo date('d/m/Y H:i', strtotime($user['migrated_at'])); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 text-end">
                            <?php if (!$user['migration_status'] || $user['migration_status'] === 'pending'): ?>
                                <button class="btn btn-primary btn-sm" onclick="openMigrationWizard(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                    <i class="bi bi-play-fill"></i> Migrer
                                </button>
                            <?php elseif ($user['migration_status'] === 'in_progress'): ?>
                                <button class="btn btn-success btn-sm" onclick="completeMigration(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                    <i class="bi bi-check2"></i> Terminer
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="openRollbackModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                    <i class="bi bi-arrow-counterclockwise"></i> Rollback
                                </button>
                            <?php elseif ($user['migration_status'] === 'completed'): ?>
                                <span class="text-success"><i class="bi bi-check-circle-fill"></i> Migration complète</span>
                            <?php elseif ($user['migration_status'] === 'failed'): ?>
                                <button class="btn btn-warning btn-sm" onclick="openMigrationWizard(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                    <i class="bi bi-arrow-repeat"></i> Réessayer
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="text-muted mt-3 mb-5">
        Affichage de <span id="displayCount"><?php echo count($pilot_users); ?></span> utilisateur(s) pilote(s)
    </div>
</div>

<!-- Migration Wizard Modal -->
<div class="modal fade" id="migrationWizardModal" tabindex="-1" aria-labelledby="migrationWizardLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="migrationWizardLabel">
                    <i class="bi bi-magic"></i> Assistant de Migration
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Validation -->
                <div id="wizardStep1" class="wizard-step">
                    <h4>Étape 1/4: Validation</h4>
                    <hr>
                    <p><strong>Utilisateur:</strong> <span id="wizardUsername"></span></p>

                    <div class="alert alert-info">
                        <h6><i class="bi bi-check-circle"></i> Vérifications automatiques:</h6>
                        <ul class="mb-0">
                            <li>✓ Utilisateur existe dans la base</li>
                            <li>✓ Aucune migration en cours</li>
                            <li>✓ Permissions legacy disponibles pour backup</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <h6><i class="bi bi-exclamation-triangle"></i> Important:</h6>
                        <ul class="mb-0">
                            <li>La migration activera le nouveau système d'autorisation</li>
                            <li>La journalisation des comparaisons sera activée</li>
                            <li>Surveillance requise pendant 7 jours</li>
                            <li>Rollback possible à tout moment</li>
                        </ul>
                    </div>
                </div>

                <!-- Step 2: Permission Mapping -->
                <div id="wizardStep2" class="wizard-step" style="display: none;">
                    <h4>Étape 2/4: Mappage des Permissions</h4>
                    <hr>
                    <p>Les permissions suivantes seront utilisées avec le nouveau système:</p>

                    <div class="permission-mapping">
                        <h6>Rôles dans le nouveau système:</h6>
                        <ul>
                            <li>Le rôle legacy sera mappé vers les rôles équivalents</li>
                            <li>Les permissions seront héritées selon le scope (global/section)</li>
                            <li>Les règles d'accès aux données seront appliquées</li>
                        </ul>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Le système comparera automatiquement les autorisations entre l'ancien et le nouveau système
                        à chaque accès pendant la période de test.
                    </div>
                </div>

                <!-- Step 3: Confirmation -->
                <div id="wizardStep3" class="wizard-step" style="display: none;">
                    <h4>Étape 3/4: Confirmation</h4>
                    <hr>

                    <div class="alert alert-warning">
                        <h6><i class="bi bi-exclamation-triangle"></i> ATTENTION: Cette action va:</h6>
                        <ol>
                            <li>Basculer <strong><span id="confirmUsername"></span></strong> vers le nouveau système</li>
                            <li>Activer la journalisation des comparaisons</li>
                            <li>Exiger une surveillance pendant 7 jours</li>
                        </ol>
                    </div>

                    <div class="alert alert-success">
                        <h6><i class="bi bi-shield-check"></i> Sécurité:</h6>
                        <ul class="mb-0">
                            <li>✓ Sauvegarde des permissions actuelles effectuée</li>
                            <li>✓ Rollback possible à tout moment</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="migrationNotes" class="form-label">Notes (optionnel):</label>
                        <textarea class="form-control" id="migrationNotes" rows="3"
                                  placeholder="Ex: Migration vague 1 - utilisateur basique"></textarea>
                    </div>
                </div>

                <!-- Step 4: Complete -->
                <div id="wizardStep4" class="wizard-step" style="display: none;">
                    <h4>Étape 4/4: Migration Terminée</h4>
                    <hr>

                    <div class="text-center py-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <h5 class="mt-3">Migration réussie pour <span id="successUsername"></span>!</h5>
                    </div>

                    <div class="alert alert-info">
                        <h6>Actions suivantes:</h6>
                        <ol>
                            <li>⏰ Surveiller pendant 7 jours (jusqu'au <span id="monitoringEndDate"></span>)</li>
                            <li>🔍 Vérifier le journal de comparaison quotidiennement</li>
                            <li>✅ Marquer comme terminé si aucun problème</li>
                        </ol>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i>
                        L'utilisateur peut maintenant se connecter et utiliser le nouveau système d'autorisation.
                    </div>
                </div>
            </div>
            <div class="modal-footer wizard-nav">
                <button type="button" class="btn btn-secondary" id="wizardPrevBtn" onclick="previousStep()">
                    <i class="bi bi-arrow-left"></i> Retour
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="wizardNextBtn" onclick="nextStep()">
                    Continuer <i class="bi bi-arrow-right"></i>
                </button>
                <button type="button" class="btn btn-success" id="wizardMigrateBtn" style="display: none;" onclick="executeMigration()">
                    <i class="bi bi-play-fill"></i> MIGRER
                </button>
                <button type="button" class="btn btn-primary" id="wizardCloseBtn" style="display: none;" data-bs-dismiss="modal">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Rollback Modal -->
<div class="modal fade" id="rollbackModal" tabindex="-1" aria-labelledby="rollbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rollbackModalLabel">
                    <i class="bi bi-arrow-counterclockwise"></i> Rollback de la Migration
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>ATTENTION:</strong> Vous êtes sur le point de revenir l'utilisateur
                    <strong><span id="rollbackUsername"></span></strong> au système d'autorisation legacy (DX_Auth).
                </div>

                <div class="mb-3">
                    <label for="rollbackReason" class="form-label">Raison du rollback (obligatoire):</label>
                    <textarea class="form-control" id="rollbackReason" rows="3" required
                              placeholder="Ex: Divergences importantes détectées dans l'accès aux pages..."></textarea>
                </div>

                <p><strong>Cette action va:</strong></p>
                <ul>
                    <li>Désactiver use_new_system (0)</li>
                    <li>Restaurer les permissions depuis old_permissions</li>
                    <li>Marquer le statut comme "failed"</li>
                    <li>Conserver l'historique dans l'audit log</li>
                </ul>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Les données du nouveau système ne seront PAS supprimées (pour permettre une future tentative).
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" onclick="executeRollback()">
                    <i class="bi bi-exclamation-triangle"></i> CONFIRMER ROLLBACK
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Global variables
let currentStep = 1;
let currentUserId = null;
let currentUsername = null;
let migrationWizardModal = null;
let rollbackModal = null;

// Initialize modals
document.addEventListener('DOMContentLoaded', function() {
    migrationWizardModal = new bootstrap.Modal(document.getElementById('migrationWizardModal'));
    rollbackModal = new bootstrap.Modal(document.getElementById('rollbackModal'));

    // Filter functionality
    document.getElementById('statusFilter').addEventListener('change', filterUsers);
    document.getElementById('searchInput').addEventListener('input', filterUsers);
});

// Migration Wizard Functions
function openMigrationWizard(userId, username) {
    currentUserId = userId;
    currentUsername = username;
    currentStep = 1;

    document.getElementById('wizardUsername').textContent = username;
    document.getElementById('confirmUsername').textContent = username;

    showStep(1);
    migrationWizardModal.show();
}

function showStep(step) {
    // Hide all steps
    for (let i = 1; i <= 4; i++) {
        document.getElementById('wizardStep' + i).style.display = 'none';
    }

    // Show current step
    document.getElementById('wizardStep' + step).style.display = 'block';
    currentStep = step;

    // Update buttons
    document.getElementById('wizardPrevBtn').style.display = (step > 1 && step < 4) ? 'inline-block' : 'none';
    document.getElementById('wizardNextBtn').style.display = (step < 3) ? 'inline-block' : 'none';
    document.getElementById('wizardMigrateBtn').style.display = (step === 3) ? 'inline-block' : 'none';
    document.getElementById('wizardCloseBtn').style.display = (step === 4) ? 'inline-block' : 'none';
}

function nextStep() {
    if (currentStep < 4) {
        showStep(currentStep + 1);
    }
}

function previousStep() {
    if (currentStep > 1) {
        showStep(currentStep - 1);
    }
}

function executeMigration() {
    const notes = document.getElementById('migrationNotes').value;

    fetch('<?php echo site_url("authorization/ajax_migrate_user"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + currentUserId + '&notes=' + encodeURIComponent(notes)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('successUsername').textContent = currentUsername;

            // Calculate monitoring end date (7 days from now)
            const endDate = new Date();
            endDate.setDate(endDate.getDate() + 7);
            document.getElementById('monitoringEndDate').textContent = endDate.toLocaleDateString('fr-FR');

            showStep(4);

            // Reload page after closing modal
            setTimeout(() => {
                location.reload();
            }, 3000);
        } else {
            alert('Erreur lors de la migration: ' + data.message);
        }
    })
    .catch(error => {
        alert('Erreur réseau: ' + error);
    });
}

// Rollback Functions
function openRollbackModal(userId, username) {
    currentUserId = userId;
    currentUsername = username;

    document.getElementById('rollbackUsername').textContent = username;
    document.getElementById('rollbackReason').value = '';

    rollbackModal.show();
}

function executeRollback() {
    const reason = document.getElementById('rollbackReason').value.trim();

    if (!reason) {
        alert('Veuillez fournir une raison pour le rollback');
        return;
    }

    fetch('<?php echo site_url("authorization/ajax_rollback_user"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + currentUserId + '&reason=' + encodeURIComponent(reason)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Rollback effectué avec succès');
            rollbackModal.hide();
            location.reload();
        } else {
            alert('Erreur lors du rollback: ' + data.message);
        }
    })
    .catch(error => {
        alert('Erreur réseau: ' + error);
    });
}

// Complete Migration Function
function completeMigration(userId, username) {
    if (!confirm('Confirmer la migration comme terminée pour ' + username + ' ?')) {
        return;
    }

    fetch('<?php echo site_url("authorization/ajax_complete_migration"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Migration marquée comme terminée');
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        alert('Erreur réseau: ' + error);
    });
}

// Filter Functions
function filterUsers() {
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const searchText = document.getElementById('searchInput').value.toLowerCase();
    const userCards = document.querySelectorAll('.user-card');

    let visibleCount = 0;

    userCards.forEach(card => {
        const status = card.getAttribute('data-status');
        const username = card.getAttribute('data-username').toLowerCase();

        const statusMatch = !statusFilter || status === statusFilter;
        const searchMatch = !searchText || username.includes(searchText);

        if (statusMatch && searchMatch) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    document.getElementById('displayCount').textContent = visibleCount;
}
</script>

</body>
</html>
