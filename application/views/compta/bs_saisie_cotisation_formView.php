<!-- VIEW: application/views/compta/bs_saisie_cotisation_formView.php -->
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
 * Formulaire de saisie simplifiée de cotisation
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('compta');
$this->lang->load('attachments');

echo '<div id="body" class="body container-fluid">';

echo checkalert($this->session, isset($popup) ? $popup : "");

?>
<h3><?= $title ?></h3>

<?php
// Show error message (from direct call or from redirect)
$error_msg = isset($error_message) ? $error_message : $this->session->flashdata('error');
if ($error_msg) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-exclamation-triangle"></i></strong> ';
    echo nl2br(htmlspecialchars($error_msg));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// Show success message (only from redirect)
if ($this->session->flashdata('success')) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-check-circle"></i></strong> ';
    echo nl2br(htmlspecialchars($this->session->flashdata('success')));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

echo form_open_multipart('compta/formValidation_saisie_cotisation', array('name' => 'saisie_cotisation', 'id' => 'form_saisie_cotisation'));

echo validation_errors();
?>

<div class="row g-3">
    <!-- Section Membre et Cotisation -->
    <div class="col-12 col-lg-6">
        <fieldset class="border p-3 h-100">
            <legend class="w-auto px-2"><?= $this->lang->line('gvv_compta_label_pilote') ?> & <?= $this->lang->line('gvv_compta_label_annee_cotisation') ?></legend>

            <div class="mb-3">
                <label for="pilote" class="form-label"><?= $this->lang->line('gvv_compta_label_pilote') ?> <span class="text-danger">*</span></label>
                <?= form_dropdown('pilote', $pilote_selector, $pilote, 'class="form-select big_select" id="pilote"') ?>
            </div>

            <div class="mb-3">
                <label for="annee_cotisation" class="form-label"><?= $this->lang->line('gvv_compta_label_annee_cotisation') ?> <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="annee_cotisation" name="annee_cotisation" value="<?= $annee_cotisation ?>" min="2000" max="2100">
            </div>
        </fieldset>
    </div>

    <!-- Section Comptes -->
    <div class="col-12 col-lg-6">
        <fieldset class="border p-3 h-100">
            <legend class="w-auto px-2"><?= $this->lang->line('gvv_compta_comptes') ?></legend>

            <div class="mb-3">
                <label for="compte_banque" class="form-label"><?= $this->lang->line('gvv_compta_label_compte_banque') ?> <span class="text-danger">*</span></label>
                <?php if ($single_compte_banque): ?>
                    <!-- Un seul compte 512 disponible : affichage en lecture seule -->
                    <input type="text" class="form-control" value="<?= $compte_banque_label ?>" readonly>
                    <input type="hidden" name="compte_banque" value="<?= $compte_banque ?>">
                <?php else: ?>
                    <!-- Plusieurs comptes 512 : affichage du sélecteur -->
                    <?= form_dropdown('compte_banque', $compte_banque_selector, $compte_banque, 'class="form-select big_select" id="compte_banque"') ?>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="compte_pilote" class="form-label"><?= $this->lang->line('gvv_compta_label_compte_pilote') ?> <span class="text-danger">*</span></label>
                <div id="compte_pilote_container">
                    <?= form_dropdown('compte_pilote', $compte_pilote_selector, $compte_pilote, 'class="form-select big_select" id="compte_pilote"') ?>
                </div>
                <small class="form-text text-muted" id="compte_pilote_info"></small>
            </div>

            <div class="mb-3">
                <label for="compte_recette" class="form-label"><?= $this->lang->line('gvv_compta_label_compte_recette') ?> <span class="text-danger">*</span></label>
                <?php if ($single_compte_recette): ?>
                    <!-- Compte de recette configuré : affichage en lecture seule -->
                    <input type="text" class="form-control" value="<?= $compte_recette_label ?>" readonly>
                    <input type="hidden" name="compte_recette" value="<?= $compte_recette ?>">
                <?php else: ?>
                    <!-- Plusieurs comptes 700 : affichage du sélecteur -->
                    <?= form_dropdown('compte_recette', $compte_recette_selector, $compte_recette, 'class="form-select big_select" id="compte_recette"') ?>
                <?php endif; ?>
            </div>
        </fieldset>
    </div>

    <!-- Section Paiement -->
    <div class="col-12 col-lg-6">
        <fieldset class="border p-3 h-100">
            <legend class="w-auto px-2">Paiement</legend>

            <div class="mb-3">
                <label for="date_op" class="form-label"><?= $this->lang->line('gvv_ecritures_field_date_op') ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control activity_date" id="date_op" name="date_op" value="<?= $date_op ?>" placeholder="dd/mm/yyyy">
            </div>

            <div class="mb-3">
                <label for="montant" class="form-label"><?= $this->lang->line('gvv_compta_label_montant') ?> <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="montant" name="montant" value="<?= $montant ?>" step="0.01" min="0.01">
            </div>

            <div class="mb-3">
                <label for="description" class="form-label"><?= $this->lang->line('gvv_ecritures_field_description') ?></label>
                <input type="text" class="form-control description" id="description" name="description" value="<?= $description ?>">
            </div>

            <div class="mb-3">
                <label for="num_cheque" class="form-label"><?= $this->lang->line('gvv_ecritures_field_num_cheque') ?></label>
                <input type="text" class="form-control num_cheque" id="num_cheque" name="num_cheque" value="<?= $num_cheque ?>">
            </div>
        </fieldset>
    </div>

    <!-- Section Justificatifs (optionnelle) -->
    <div class="col-12 col-lg-6">
        <fieldset class="border p-3 h-100">
            <legend class="w-auto px-2"><?= $this->lang->line("gvv_attachments_title") ?> <small class="text-muted">(<?= $this->lang->line("gvv_optional") ?>)</small></legend>

            <div class="form-group mt-2">
                <label for="attachment_files" class="form-label">
                    <i class="bi bi-paperclip"></i> <?= $this->lang->line("gvv_choose_files") ?>
                </label>
                <input type="file" name="attachment_files[]" id="attachment_files" class="form-control" multiple
                       accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.csv,.txt">
                <small class="form-text text-muted d-block mt-1">
                    <?= $this->lang->line("gvv_supported_formats") ?>: PDF, Images, Office, CSV (Max 20MB par fichier)
                </small>
            </div>
        </fieldset>
    </div>
</div>

<!-- Boutons de validation -->
<div class="row mt-3">
    <div class="col-12">
        <button type="submit" id="btnValidate" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> <?= $this->lang->line("gvv_button_validate") ?>
        </button>
        <button type="button" class="btn btn-secondary" onclick="history.back()">
            <i class="bi bi-x-circle"></i> Annuler
        </button>
    </div>
</div>

<?php
echo form_close();
?>

<style>
/* Ensure big_select (select2) fields maintain consistent width */
.mb-3 .select2-container {
    width: 100% !important;
    display: block;
}
.mb-3 .select2-container .select2-selection--single {
    height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}
.mb-3 .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: calc(1.5em + 0.75rem);
    padding-left: 0;
}
.mb-3 .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(1.5em + 0.75rem);
}
</style>

<script>
$(document).ready(function() {
    var formChanged = false;
    var submitSuccess = <?= $this->session->flashdata('success') ? 'true' : 'false' ?>;

    // Désactiver bouton si succès précédent
    if (submitSuccess) {
        $('#btnValidate').prop('disabled', true);
        formChanged = false;
    }

    // Réactiver bouton si changement de formulaire
    $('#form_saisie_cotisation input, #form_saisie_cotisation select').on('change input', function() {
        if (!formChanged) {
            $('#btnValidate').prop('disabled', false);
            formChanged = true;
        }
    });

    // Gestion automatique du libellé "Cotisation YYYY"
    function updateDescriptionIfDefault() {
        var year = $('#annee_cotisation').val();
        var description = $('#description').val().trim();

        // Si vide ou déjà "Cotisation YYYY", mettre à jour avec la nouvelle année
        if (description === '' || /^Cotisation \d{4}$/.test(description)) {
            $('#description').val('Cotisation ' + year);
        }
    }

    // Initialiser le libellé au chargement si vide
    if ($('#description').val().trim() === '') {
        var initialYear = $('#annee_cotisation').val();
        $('#description').val('Cotisation ' + initialYear);
    }

    // Mettre à jour le libellé quand l'année change
    $('#annee_cotisation').on('change', function() {
        updateDescriptionIfDefault();
    });

    // Récupération automatique du compte 411 quand le pilote change
    // Fonction pour gérer le changement de pilote
    function handlePiloteChange(piloteId) {
        console.log('handlePiloteChange appelé avec:', piloteId);
        
        if (!piloteId) {
            // Réafficher le sélecteur si aucun pilote sélectionné
            var selectorHtml = <?= json_encode(form_dropdown('compte_pilote', $compte_pilote_selector, '', 'class="form-select big_select" id="compte_pilote"')) ?>;
            $('#compte_pilote_container').html(selectorHtml);
            $('#compte_pilote_info').text('');
            // Réinitialiser select2 si utilisé
            if (typeof $.fn.select2 !== 'undefined') {
                setTimeout(function() {
                    $('#compte_pilote').select2({
                        placeholder: 'Filtre...',
                        width: '300px',
                        allowClear: true
                    });
                }, 100);
            }
            return;
        }

        // Requête AJAX pour obtenir le compte 411 du pilote
        console.log('Envoi requête AJAX pour pilote:', piloteId);
        $.ajax({
            url: '<?= site_url('compta/ajax_get_compte_pilote') ?>',
            type: 'POST',
            dataType: 'json',
            data: { pilote_id: piloteId },
            success: function(response) {
                console.log('Réponse AJAX:', response);
                if (response.success) {
                    // Remplacer le sélecteur par un champ en lecture seule
                    $('#compte_pilote_container').html(
                        '<input type="text" class="form-control" value="' + response.compte_label + '" readonly>' +
                        '<input type="hidden" name="compte_pilote" value="' + response.compte_id + '">'
                    );
                    $('#compte_pilote_info').text('Compte automatiquement sélectionné').removeClass('text-danger').addClass('text-success');
                } else {
                    // Pas de compte trouvé, garder le sélecteur
                    $('#compte_pilote_info').text(response.message || 'Veuillez sélectionner un compte manuellement').removeClass('text-success').addClass('text-danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                $('#compte_pilote_info').text('Erreur lors de la récupération du compte').removeClass('text-success').addClass('text-danger');
            }
        });
    }
    
    // IMPORTANT: Attacher l'événement APRÈS que select2 soit initialisé par bs_footer.php
    // Utiliser un événement window load pour s'assurer que tout est prêt
    $(window).on('load', function() {
        console.log('Attachement des événements pilote...');
        
        // Détacher d'abord tous les événements existants pour éviter les doublons
        $('#pilote').off('change.piloteHandler select2:select.piloteHandler select2:clear.piloteHandler');
        
        // Attacher avec des namespaces pour pouvoir les détacher facilement
        $('#pilote').on('select2:select.piloteHandler', function(e) {
            console.log('select2:select déclenché');
            var piloteId = $(this).val();
            handlePiloteChange(piloteId);
        });
        
        $('#pilote').on('change.piloteHandler', function(e) {
            console.log('change déclenché, valeur:', $(this).val());
            var piloteId = $(this).val();
            handlePiloteChange(piloteId);
        });
        
        $('#pilote').on('select2:clear.piloteHandler', function(e) {
            console.log('select2:clear déclenché');
            handlePiloteChange(null);
        });
        
        console.log('Événements attachés à #pilote');
    });
});
</script>

<?= '</div>' ?>
