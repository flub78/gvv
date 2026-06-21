<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?></title>
<style>
  body { font-family: Arial, sans-serif; font-size: 14px; color: #212529; background: #f8f9fa; margin: 0; padding: 20px; }
  .card { background: #fff; border-radius: 6px; max-width: 560px; margin: 0 auto; padding: 28px; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
  h2 { color: #0d6efd; margin-top: 0; font-size: 18px; }
  table { width: 100%; border-collapse: collapse; margin: 16px 0; }
  th, td { text-align: left; padding: 8px 12px; border: 1px solid #dee2e6; }
  th { background: #f8f9fa; font-weight: bold; width: 40%; }
  .footer { font-size: 12px; color: #6c757d; margin-top: 20px; }
</style>
</head>
<body>
<div class="card">
  <h2><?= htmlspecialchars($type_label, ENT_QUOTES, 'UTF-8') ?></h2>
  <p><?= htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') ?></p>
  <table>
    <tr><th>Date / Heure</th><td><?= htmlspecialchars($date_heure, ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>Aéronef</th><td><?= htmlspecialchars($aeronef, ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>Pilote</th><td><?= htmlspecialchars($pilote, ENT_QUOTES, 'UTF-8') ?></td></tr>
    <?php if ($instructeur): ?>
    <tr><th>Instructeur</th><td><?= htmlspecialchars($instructeur, ENT_QUOTES, 'UTF-8') ?></td></tr>
    <?php endif; ?>
    <tr><th>Statut</th><td><?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>Votre rôle</th><td><?= htmlspecialchars($role_label, ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>Type de message</th><td><?= htmlspecialchars($type_label, ENT_QUOTES, 'UTF-8') ?></td></tr>
    <?php if ($source): ?>
    <tr><th>Déclenchement</th><td><?= htmlspecialchars($source, ENT_QUOTES, 'UTF-8') ?></td></tr>
    <?php endif; ?>
  </table>
  <div class="footer">
    Message automatique envoyé par <?= htmlspecialchars($nom_club, ENT_QUOTES, 'UTF-8') ?> – GVV.
    Ne pas répondre à cet email.
  </div>
</div>
</body>
</html>
