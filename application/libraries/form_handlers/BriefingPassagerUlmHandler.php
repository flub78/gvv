<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Handler post-soumission du formulaire `briefing-passager-ulm` (Lot 6, étape 6.4).
 *
 * Périmètre réduit (voir doc/design_notes/remplissage_formulaires_design.md,
 * décisions actées juillet 2026) : met uniquement à jour les champs éditables
 * de `vols_decouverte` depuis les valeurs soumises. Ni génération PDF, ni
 * archivage, ni invalidation de token.
 */
class BriefingPassagerUlmHandler implements GvvFormHandlerInterface {

    public function after_submit(int $submission_id, ?string $subject_type, ?int $subject_id): array {
        $CI = &get_instance();
        $CI->lang->load('briefing_passager');
        $redirect_url = site_url('vols_decouverte/page');

        if ($subject_type !== 'vols_decouverte' || empty($subject_id)) {
            $CI->session->set_flashdata('message', $this->_alert('danger', $CI->lang->line('briefing_passager_upload_error')));
            return array(
                'redirect_url' => $redirect_url,
                'error'        => 'BriefingPassagerUlmHandler: subject_type/subject_id manquant ou invalide pour la soumission ' . $submission_id,
            );
        }

        $CI->load->model('vols_decouverte_model');
        $CI->load->model('form_submissions_model');

        $vld = $CI->vols_decouverte_model->get_by_id('id', $subject_id);
        if (!$vld) {
            $CI->session->set_flashdata('message', $this->_alert('danger', $CI->lang->line('briefing_passager_not_found')));
            return array(
                'redirect_url' => $redirect_url,
                'error'        => 'BriefingPassagerUlmHandler: vols_decouverte #' . $subject_id . ' introuvable (soumission ' . $submission_id . ')',
            );
        }

        $values = array();
        foreach ($CI->form_submissions_model->get_submission_values($submission_id) as $row) {
            if (!empty($row['field_name'])) {
                $values[$row['field_name']] = $row['value_text'];
            }
        }

        $nom     = trim((string) ($values['nom'] ?? ''));
        $prenom  = trim((string) ($values['prenom'] ?? ''));
        $beneficiaire        = trim($nom . ($prenom !== '' ? ' ' . $prenom : ''));
        $participation       = trim((string) ($values['poids_declare'] ?? ''));
        $urgence             = trim((string) ($values['personne_a_prevenir'] ?? ''));
        $beneficiaire_tel    = trim((string) ($values['telephone'] ?? ''));
        $date_vol            = trim((string) ($values['date_vol'] ?? ''));

        $update = array();
        if ($beneficiaire !== '' && $beneficiaire !== $vld['beneficiaire']) {
            $update['beneficiaire'] = $beneficiaire;
        }
        if ($participation !== '' && $participation !== (string) ($vld['participation'] ?? '')) {
            $update['participation'] = $participation;
        }
        if ($urgence !== '' && $urgence !== (string) ($vld['urgence'] ?? '')) {
            $update['urgence'] = $urgence;
        }
        if ($beneficiaire_tel !== '' && $beneficiaire_tel !== (string) ($vld['beneficiaire_tel'] ?? '')) {
            $update['beneficiaire_tel'] = $beneficiaire_tel;
        }
        if ($date_vol !== '' && $date_vol !== (string) ($vld['date_vol'] ?? '')) {
            $update['date_vol'] = $date_vol;
        }

        if (!empty($update)) {
            $CI->vols_decouverte_model->update('id', $update, $subject_id);
        }

        $CI->session->set_flashdata('message', $this->_alert('success', $CI->lang->line('briefing_passager_upload_success')));
        return array('redirect_url' => $redirect_url, 'error' => null);
    }

    /**
     * Alerte Bootstrap 5 fermable, au même format que celles déjà utilisées
     * sur la page vols_decouverte (voir vols_decouverte::send_email_with_pdf()).
     */
    private function _alert(string $type, string $message): string {
        return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
             . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
             . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
             . '</div>';
    }
}
