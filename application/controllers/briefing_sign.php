<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

include_once(APPPATH . '/third_party/phpqrcode/qrlib.php');
include_once(APPPATH . '/third_party/tcpdf/tcpdf.php');

/**
 * Public signature controller for passenger briefing (UC2).
 *
 * Accessible without authentication — uses a one-time token for security.
 * Routes:
 *   GET  /briefing_sign/{token}        — signature form
 *   POST /briefing_sign/submit/{token} — form submission
 */
class Briefing_sign extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->helper(array('url', 'form', 'date', 'views', 'form_elements'));
        $this->load->model('archived_documents_model');
        $this->load->model('document_types_model');
        $this->load->model('vols_decouverte_model');
        $this->load->model('terrains_model');
        $this->lang->load('briefing_passager');
        $this->lang->load('gvv');
    }

    // -----------------------------------------------------------------------
    // Public signature form
    // -----------------------------------------------------------------------

    /**
     * Display the signature form for the given token.
     * Generates a QR code of the current URL so the passenger can scan it.
     * @param string $token One-time token
     */
    function index($token = '') {
        $token_row = $this->_validate_token($token);
        if (!$token_row) {
            $this->_show_error($this->lang->line('briefing_passager_sign_invalid_token'));
            return;
        }
        if ($token_row['used_at']) {
            $this->_show_error($this->lang->line('briefing_passager_sign_already_done'));
            return;
        }

        $vld_id = (int)$token_row['vld_id'];
        $vld = $this->vols_decouverte_model->get_by_id('id', $vld_id);
        if (!$vld) {
            $this->_show_error($this->lang->line('briefing_passager_sign_invalid_token'));
            return;
        }

        $vld['aerodrome_nom'] = $this->_terrain_nom($vld['aerodrome']);
        $consignes = $this->archived_documents_model->get_consignes_by_section($vld['club']);

        $sign_url = $this->_build_public_sign_url($token);

        // Generate QR code to temp file, embed as base64, then clean up
        $qr_file = sys_get_temp_dir() . '/bp_sign_qr_' . md5($token) . '.png';
        QRcode::png($sign_url, $qr_file, QR_ECLEVEL_L, 6, 2);
        $qr_base64 = base64_encode(file_get_contents($qr_file));
        @unlink($qr_file);

        $data = array(
            'token'      => $token,
            'vld'        => $vld,
            'consignes'  => $consignes,
            'qr_base64'  => $qr_base64,
            'sign_url'   => $sign_url,
            'message'    => '',
        );

        $this->load->view('briefing_passager/bs_signView', $data);
        
    }

    /**
     * Process the submitted signature form.
     * @param string $token One-time token
     */
    function submit($token = '') {
        $token_row = $this->_validate_token($token);
        if (!$token_row) {
            $this->_show_error($this->lang->line('briefing_passager_sign_invalid_token'));
            return;
        }
        if ($token_row['used_at']) {
            $this->_show_error($this->lang->line('briefing_passager_sign_already_done'));
            return;
        }

        $vld_id = (int)$token_row['vld_id'];
        $vld = $this->vols_decouverte_model->get_by_id('id', $vld_id);
        if (!$vld) {
            $this->_show_error($this->lang->line('briefing_passager_sign_invalid_token'));
            return;
        }

        $vld['aerodrome_nom'] = $this->_terrain_nom($vld['aerodrome']);

        // Collect passenger form data
        $nom     = trim($this->input->post('nom',     true));
        $prenom  = trim($this->input->post('prenom',  true));
        $ddn_raw = trim($this->input->post('ddn',     true));
        $ddn     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $ddn_raw) ? $ddn_raw : '';
        $poids   = (int)$this->input->post('poids',   true);
        $urgence = trim($this->input->post('urgence', true));
        $accept  = $this->input->post('accept',       true);
        // Raw base64 only — prefix stripped client-side to bypass CI2 global_xss_filtering
        $signature_data = $this->input->post('signature_data', false);

        if (!$accept) {
            $sign_url = $this->_build_public_sign_url($token);
            $consignes = $this->archived_documents_model->get_consignes_by_section($vld['club']);
            $qr_file = sys_get_temp_dir() . '/bp_sign_qr_' . md5($token) . '.png';
            QRcode::png($sign_url, $qr_file, QR_ECLEVEL_L, 6, 2);
            $qr_base64 = base64_encode(file_get_contents($qr_file));
            @unlink($qr_file);
            $data = array(
                'token'     => $token,
                'vld'       => $vld,
                'consignes' => $consignes,
                'qr_base64' => $qr_base64,
                'sign_url'  => $sign_url,
                'message'   => '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' . $this->lang->line('briefing_passager_sign_accept_required') . '</div>',
            );
            $this->load->view('briefing_passager/bs_signView', $data);
            
            return;
        }

        // Update VLD if passenger data differs
        $update = array();
        if ($nom     && $nom     != $vld['beneficiaire']) $update['beneficiaire'] = $nom . ($prenom ? ' ' . $prenom : '');
        if ($urgence && $urgence != $vld['urgence'])      $update['urgence']      = $urgence;
        if ($poids   && $poids   != ($vld['participation'] ?? 0)) $update['participation'] = $poids;
        if (!empty($update)) {
            $this->db->where('id', $vld_id)->update('vols_decouverte', $update);
        }

        // Generate PDF summary (with consignes prepended if available)
        $consignes = $this->archived_documents_model->get_consignes_by_section($vld['club']);
        $consignes_abs = null;
        if (!empty($consignes['file_path'])) {
            $raw = $consignes['file_path'];
            $abs = (strpos($raw, './') === 0) ? FCPATH . substr($raw, 2) : FCPATH . $raw;
            if (file_exists($abs)) $consignes_abs = $abs;
        }
        $pdf_path = $this->_generate_pdf($vld, $nom, $prenom, $ddn, $poids, $urgence, $signature_data, $token, $consignes_abs);

        // Archive the PDF, then mark token as used
        $pdf_base64 = null;
        if ($pdf_path) {
            $doc_type = $this->document_types_model->get_by_code('briefing_passager');
            if ($doc_type) {
                $existing = $this->archived_documents_model->get_briefing_by_vld($vld_id);
                if ($existing) {
                    $this->archived_documents_model->update_document($existing['id'], array('is_current_version' => 0));
                }
                $this->archived_documents_model->create_document(array(
                    'document_type_id'  => $doc_type['id'],
                    'vld_id'            => $vld_id,
                    'section_id'        => $vld['club'],
                    'file_path'         => $pdf_path,
                    'original_filename' => 'briefing_passager_vld' . $vld_id . '.pdf',
                    'description'       => 'Briefing passager numérique VLD #' . $vld_id . ' — ' . $nom,
                    'uploaded_by'       => 'system',
                    'validation_status' => 'approved',
                ));
            }

            // Mark token as used only after successful archiving
            $this->db->where('token', $token)->update('briefing_tokens', array(
                'used_at'    => date('Y-m-d H:i:s'),
                'ip_address' => $this->input->ip_address(),
            ));

            // Embed PDF for display on confirmation page
            $abs_pdf = (strpos($pdf_path, '/') === 0) ? $pdf_path : FCPATH . $pdf_path;
            if (file_exists($abs_pdf)) {
                $pdf_base64 = base64_encode(file_get_contents($abs_pdf));
            }

            // Send email if passenger email is on file
            if (!empty($vld['beneficiaire_email'])) {
                $this->_send_email($vld['beneficiaire_email'], $nom, $pdf_path);
            }
        }

        // Confirmation page
        $data = array('vld' => $vld, 'nom' => $nom, 'pdf_base64' => $pdf_base64);
        $this->load->view('briefing_passager/bs_signConfirmView', $data);
        
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function _validate_token($token) {
        if (empty($token) || strlen($token) !== 64) {
            return null;
        }
        $token = preg_replace('/[^a-f0-9]/', '', $token);
        if (strlen($token) !== 64) {
            return null;
        }
        $row = $this->db->get_where('briefing_tokens', array('token' => $token))->row_array();
        if (!$row) {
            return null;
        }
        // Check expiry
        if ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
            return null;
        }
        return $row;
    }

    private function _terrain_nom($oaci) {
        if (empty($oaci)) return '';
        $row = $this->db->get_where('terrains', array('oaci' => $oaci))->row_array();
        return $row ? $oaci . ' — ' . $row['nom'] : $oaci;
    }

    private function _show_error($message) {
        $data = array('error_message' => $message);
        $this->load->view('briefing_passager/bs_signErrorView', $data);
        
    }

    /**
     * Build the public signature URL used by QR code.
     * When qrcode_raw_ip is enabled, force server IP instead of configured hostname.
     */
    private function _build_public_sign_url($token) {
        $url = site_url('briefing_sign/' . $token);
        if (!$this->config->item('qrcode_raw_ip')) {
            return $url;
        }

        $server_addr = $this->_resolve_qrcode_ip();
        if ($server_addr === '') {
            return $url;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return $url;
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'] : 'http';
        $path   = isset($parts['path']) ? $parts['path'] : '';
        $query  = isset($parts['query']) ? '?' . $parts['query'] : '';
        $port   = isset($parts['port']) ? ':' . $parts['port'] : '';

        return $scheme . '://' . $server_addr . $port . $path . $query;
    }

    /**
     * Resolve the best local network IP for QR code links.
     */
    private function _resolve_qrcode_ip() {
        $forced = trim((string)$this->config->item('qrcode_forced_ip'));
        if ($this->_is_usable_qrcode_ip($forced)) {
            return $forced;
        }

        $server_addr = isset($_SERVER['SERVER_ADDR']) ? trim($_SERVER['SERVER_ADDR']) : '';
        if ($this->_is_usable_qrcode_ip($server_addr)) {
            return $server_addr;
        }

        $http_host = isset($_SERVER['HTTP_HOST']) ? trim($_SERVER['HTTP_HOST']) : '';
        if ($http_host !== '') {
            $host = preg_replace('/:\\d+$/', '', $http_host);
            if ($this->_is_usable_qrcode_ip($host)) {
                return $host;
            }
        }

        $hostname = gethostname();
        if ($hostname) {
            $candidates = @gethostbynamel($hostname);
            if (is_array($candidates)) {
                foreach ($candidates as $candidate) {
                    if ($this->_is_usable_qrcode_ip($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return '';
    }

    /**
     * Keep only IPv4 addresses that are reachable from a local network.
     */
    private function _is_usable_qrcode_ip($ip) {
        if (empty($ip)) {
            return false;
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        if (strpos($ip, '127.') === 0) {
            return false;
        }
        if (strpos($ip, '169.254.') === 0) {
            return false;
        }
        if ($ip === '0.0.0.0') {
            return false;
        }
        return true;
    }

    private function _generate_pdf($vld, $nom, $prenom, $ddn, $poids, $urgence, $signature_data, $token, $consignes_path = null) {
        $section_id  = $vld['club'];
        $dirname = FCPATH . 'uploads/documents/sections/' . $section_id . '/briefing_passager/';
        if (!file_exists($dirname)) {
            @mkdir($dirname, 0777, true);
        }
        $filename = 'briefing_sign_' . $vld['id'] . '_' . time() . '.pdf';
        $filepath = $dirname . $filename;

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('GVV');
        $pdf->SetTitle($this->lang->line('briefing_passager_pdf_title'));
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, $this->lang->line('briefing_passager_pdf_title'), 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $this->lang->line('briefing_passager_sign_flight_info'), 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(60, 6, $this->lang->line('briefing_passager_field_date_vol') . ' :', 0);
        $pdf->Cell(0, 6, $vld['date_vol'] ? date('d/m/Y', strtotime($vld['date_vol'])) : '—', 0, 1);
        $pdf->Cell(60, 6, $this->lang->line('briefing_passager_field_aerodrome') . ' :', 0);
        $pdf->Cell(0, 6, !empty($vld['aerodrome_nom']) ? $vld['aerodrome_nom'] : ($vld['aerodrome'] ?? '—'), 0, 1);
        $pdf->Cell(60, 6, $this->lang->line('briefing_passager_field_appareil') . ' :', 0);
        $pdf->Cell(0, 6, $vld['airplane_immat'] ?? '—', 0, 1);
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $this->lang->line('briefing_passager_sign_passenger'), 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(60, 6, $this->lang->line('briefing_passager_field_nom') . ' :', 0);
        $pdf->Cell(0, 6, $nom . ' ' . $prenom, 0, 1);
        $pdf->Cell(60, 6, $this->lang->line('briefing_passager_field_ddn') . ' :', 0);
        $pdf->Cell(0, 6, $ddn ?: '—', 0, 1);
        $pdf->Cell(60, 6, $this->lang->line('briefing_passager_field_poids') . ' :', 0);
        $pdf->Cell(0, 6, $poids ? $poids . ' kg' : '—', 0, 1);
        $pdf->Cell(60, 6, $this->lang->line('briefing_passager_field_urgence') . ' :', 0);
        $pdf->MultiCell(0, 6, $urgence ?: '—', 0, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $this->lang->line('briefing_passager_sign_acceptance'), 0, 1);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 6, $this->lang->line('briefing_passager_sign_checkbox'), 0, 'L');
        $pdf->Ln(3);

        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 5, 'Date : ' . date('d/m/Y H:i:s') . ' — IP : ' . $this->input->ip_address(), 0, 1);
        $pdf->Ln(3);

        // Embed signature image if provided
        // $signature_data is raw base64 (no data URI prefix — stripped client-side)
        if (!empty($signature_data)) {
            $img_data = base64_decode($signature_data, true);
            if ($img_data !== false && strlen($img_data) > 0) {
                try {
                    $pdf->Image('@' . $img_data, 15, '', 53, 0, 'PNG');
                    $pdf->Ln(5);
                } catch (Exception $e) {
                    log_message('error', 'briefing_sign: Image() failed: ' . $e->getMessage());
                }
            }
        }

        // Write signature page to temp file, then merge with consignes if available
        if ($consignes_path) {
            $sig_tmp = tempnam(sys_get_temp_dir(), 'bpsig_') . '.pdf';
            $pdf->Output($sig_tmp, 'F');
            $merged = $this->_merge_pdfs($consignes_path, $sig_tmp, $filepath);
            @unlink($sig_tmp);
            if (!$merged) {
                // Ghostscript unavailable: fallback to signature page only
                $pdf->Output($filepath, 'F');
            }
        } else {
            $pdf->Output($filepath, 'F');
        }

        // Return relative path (relative to FCPATH)
        $base = realpath(FCPATH) . '/';
        $abs  = realpath($filepath);
        return ($abs && strpos($abs, $base) === 0) ? substr($abs, strlen($base)) : $filepath;
    }

    private function _merge_pdfs($pdf1, $pdf2, $output) {
        $gs = $this->_find_ghostscript();
        if (!$gs) return false;

        $cmd = escapeshellarg($gs)
             . ' -dBATCH -dNOPAUSE -dQUIET -sDEVICE=pdfwrite'
             . ' -sOutputFile=' . escapeshellarg($output)
             . ' ' . escapeshellarg($pdf1)
             . ' ' . escapeshellarg($pdf2);
        exec($cmd, $out, $ret);
        return ($ret === 0 && file_exists($output) && filesize($output) > 0);
    }

    private function _find_ghostscript() {
        foreach (array('/usr/bin/gs', '/usr/local/bin/gs') as $path) {
            if (file_exists($path) && is_executable($path)) return $path;
        }
        $which = trim(shell_exec('which gs 2>/dev/null'));
        return $which ?: null;
    }

    private function _send_email($to, $nom, $pdf_path) {
        $this->load->library('email');
        $this->email->from(
            $this->config->item('smtp_user') ?: 'noreply@gvv.net',
            'GVV'
        );
        $this->email->to($to);
        $this->email->subject($this->lang->line('briefing_passager_email_subject'));
        $this->email->message($this->lang->line('briefing_passager_email_body'));
        if (file_exists(FCPATH . $pdf_path)) {
            $this->email->attach(FCPATH . $pdf_path);
        }
        @$this->email->send(); // Silent fail — don't crash if email is not configured
    }
}

/* End of file briefing_sign.php */
/* Location: ./application/controllers/briefing_sign.php */
