<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Refusé - GVV</title>
    <link href="<?php echo base_url(); ?>themes/bootstrap5/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .login-denied-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .icon-denied {
            font-size: 64px;
            color: #dc3545;
            text-align: center;
            margin-bottom: 20px;
        }
        h1 {
            color: #dc3545;
            font-size: 24px;
            text-align: center;
            margin-bottom: 20px;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-denied-container">
            <div class="icon-denied">
                <i class="bi bi-shield-x"></i>
                🚫
            </div>

            <h1>Accès Refusé</h1>

            <div class="alert alert-danger">
                <strong>Utilisateur :</strong> <?php echo htmlspecialchars($username); ?>
            </div>

            <?php if ($reason === 'no_user_role'): ?>
            <div class="alert alert-warning">
                <h5>Aucun Rôle Attribué</h5>
                <p>
                    Vous n'avez pas le rôle <strong>'utilisateur'</strong> nécessaire pour accéder
                    à cette section du système.
                </p>
                <p>
                    <strong>Système d'autorisation non-hiérarchique :</strong><br>
                    Le droit de se connecter et le droit d'effectuer des actions sont séparés.
                    Vous devez avoir au minimum le rôle 'utilisateur' pour la section sélectionnée.
                </p>
            </div>

            <div class="alert alert-info">
                <h6>Que faire ?</h6>
                <ul>
                    <li>Contactez votre administrateur système</li>
                    <li>Demandez l'attribution du rôle 'utilisateur' pour cette section</li>
                    <li>Vérifiez que vous êtes dans la bonne section</li>
                </ul>
            </div>
            <?php endif; ?>

            <div class="d-grid gap-2 mt-4">
                <a href="<?php echo base_url(); ?>auth/login" class="btn btn-primary">
                    Retour à la Page de Connexion
                </a>
            </div>

            <div class="text-center mt-3">
                <small class="text-muted">
                    Section ID: <?php echo htmlspecialchars($section_id); ?>
                </small>
            </div>
        </div>
    </div>
</body>
</html>
