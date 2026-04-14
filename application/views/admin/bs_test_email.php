<!-- VIEW: application/views/admin/bs_test_email.php -->
<?php
/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Vue de test d'envoi d'email
 * @package vues
 * @filesource bs_test_email.php
 */
?>

<div id="body" class="body container-fluid py-3">
    <div class="row">
        <div class="col-12 col-md-8 col-lg-6">

            <h2 class="mb-3">
                <i class="fas fa-paper-plane text-primary"></i>
                Test email
            </h2>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5><i class="fas fa-exclamation-triangle"></i> Erreur d'envoi</h5>
                    <p class="mb-1"><?= $error ?></p>
                    <?php if (!empty($debug)): ?>
                        <hr>
                        <p class="mb-1"><strong>Détails du débogage SMTP :</strong></p>
                        <pre class="bg-dark text-light p-2 rounded small" style="white-space: pre-wrap; word-break: break-all;"><?= $debug ?></pre>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-envelope"></i> Envoyer un email de test</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= controller_url('admin/test_email') ?>">

                        <div class="mb-3">
                            <label for="to" class="form-label fw-bold">Destinataire <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="to" name="to"
                                   value="<?= htmlspecialchars($to) ?>"
                                   placeholder="destinataire@example.com"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label fw-bold">Sujet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject" name="subject"
                                   value="<?= htmlspecialchars($subject) ?>"
                                   placeholder="Sujet du message"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="body" class="form-label fw-bold">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="body" name="body" rows="6"
                                      placeholder="Contenu du message..." required><?= htmlspecialchars($body) ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="send" value="1" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Envoyer
                            </button>
                            <a href="<?= controller_url('admin') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Retour
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
