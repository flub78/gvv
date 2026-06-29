<!-- VIEW: application/views/admin/bs_test_sms.php -->
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
 * Vue de test d'envoi de SMS via Brevo
 * @package vues
 * @filesource bs_test_sms.php
 */
?>

<div id="body" class="body container-fluid py-3">
    <div class="row">
        <div class="col-12 col-md-8 col-lg-6">

            <h2 class="mb-3">
                <i class="fas fa-mobile-alt text-primary"></i>
                Test SMS
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
                    <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-mobile-alt"></i> Envoyer un SMS de test</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= controller_url('admin/test_sms') ?>">

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-bold">Numéro de téléphone <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?= htmlspecialchars($phone) ?>"
                                   placeholder="+33612345678 ou 0612345678"
                                   required>
                            <div class="form-text">Format accepté : +33XXXXXXXXX, 06XXXXXXXX, 07XXXXXXXX</div>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label fw-bold">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="4"
                                      maxlength="160"
                                      placeholder="Texte du SMS (160 caractères max)..."
                                      oninput="document.getElementById('char_count').textContent = this.value.length"
                                      required><?= htmlspecialchars($message) ?></textarea>
                            <div class="form-text">
                                <span id="char_count"><?= mb_strlen($message) ?></span> / 160 caractères
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="send" value="1" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Envoyer
                            </button>
                            <a href="<?= controller_url('welcome/section/dev') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Retour
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
