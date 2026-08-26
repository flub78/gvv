<!-- VIEW: application/views/membre/bs_signatureView.php -->
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
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Signature de référence d'un instructeur : self-service (ma_signature) et
 * import admin (signature/$mlogin) partagent cette même vue.
 *
 * @package views
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
?>
<script src="<?= base_url('assets/js/signature_pad.umd.min.js') ?>"></script>

<div id="body" class="body container-fluid py-3">

    <div class="row mb-3">
        <div class="col-12">
            <h2>
                <i class="fas fa-signature text-secondary"></i>
                <?php if ($is_admin): ?>
                    <?= $this->lang->line('membre_signature_title_admin') ?>
                    <?= htmlspecialchars(trim($membre['mprenom'] . ' ' . $membre['mnom'])) ?>
                <?php else: ?>
                    <?= $this->lang->line('membre_signature_title') ?>
                <?php endif; ?>
            </h2>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <?= $message ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <p class="text-muted"><?= $this->lang->line('membre_signature_help') ?></p>

            <?php echo form_open_multipart(
                controller_url('membre') . '/' . ($is_admin ? "signature/$mlogin" : 'ma_signature'),
                array('class' => 'needs-validation')
            ); ?>
                <input type="hidden" name="gvv_signature_submit" value="1">
                <?= $signature_widget_html ?>

                <button type="submit" class="btn btn-primary mt-2">
                    <i class="fas fa-save"></i> <?= $this->lang->line('membre_signature_submit') ?>
                </button>
            <?php echo form_close(); ?>
        </div>
    </div>

    <div class="mt-3">
        <a href="<?= $is_admin ? controller_url('membre') . '/edit/' . $mlogin : controller_url('welcome') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            <?= $this->lang->line('gvv_button_back') ?>
        </a>
    </div>

</div>
