<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Minimum retention of passenger briefings: three months from the flight date,
 * or from the submission date for a briefing that has no flight or no flight date.
 */
class Briefing_retention {

    const FORM_SLUG = 'briefing-passager-ulm';
    const RETENTION = '+3 months';

    /** @var CI_Controller */
    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('vols_decouverte_model');
        $this->CI->load->model('form_submissions_model');
    }

    /**
     * @param array $form Form row.
     * @return bool True when the retention rule covers the responses of this form.
     */
    public function applies_to(array $form) {
        return ($form['public_slug'] ?? '') === self::FORM_SLUG;
    }

    /**
     * Protected submissions among those of a form.
     *
     * @param array $form        Form row.
     * @param array $submissions Submission rows of this form.
     * @return array Y-m-d end of retention keyed by submission id, protected submissions only.
     */
    public function protected_until(array $form, array $submissions) {
        if (!$this->applies_to($form)) {
            return array();
        }

        $flight_ids = array();
        foreach ($submissions as $submission) {
            if (($submission['subject_type'] ?? null) === 'vols_decouverte' && !empty($submission['subject_id'])) {
                $flight_ids[] = (int) $submission['subject_id'];
            }
        }
        $flight_dates = $this->CI->vols_decouverte_model->dates_vol($flight_ids);

        $today = date('Y-m-d');
        $protected = array();
        foreach ($submissions as $submission) {
            $is_flight = ($submission['subject_type'] ?? null) === 'vols_decouverte';
            $reference = $is_flight ? ($flight_dates[(int) $submission['subject_id']] ?? '') : '';
            if ($reference === '') {
                $reference = (string) ($submission['submitted_at'] ?: $submission['created_at']);
            }

            $until = date('Y-m-d', strtotime(substr($reference, 0, 10) . ' ' . self::RETENTION));
            if ($today < $until) {
                $protected[(int) $submission['id']] = $until;
            }
        }
        return $protected;
    }

    /**
     * @return bool True when deleting this form would remove a protected response.
     */
    public function form_has_protected_submissions(array $form) {
        if (!$this->applies_to($form)) {
            return false;
        }
        $rows = $this->CI->db->where('form_id', (int) $form['id'])->get('form_submissions')->result_array();
        return !empty($this->protected_until($form, $rows));
    }
}

/* End of file Briefing_retention.php */
/* Location: ./application/libraries/Briefing_retention.php */
