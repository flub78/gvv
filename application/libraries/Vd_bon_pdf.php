<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!defined('PDF_CREATOR')) define('PDF_CREATOR', 'GVV');

require_once(APPPATH . 'third_party/tcpdf/tcpdf.php');
require_once(APPPATH . 'third_party/phpqrcode/qrlib.php');

/**
 * Moteur de composition PDF pour les bons de vol de découverte.
 *
 * Format A5 paysage, deux pages (recto / verso). Le layout injecté (array
 * PHP décodé depuis le JSON de `vols_decouverte_looks_model`) contrôle la
 * position, la police, la taille et la couleur de chaque champ. Aucune
 * position n'est codée en dur dans ce moteur : la résolution des fonds
 * (y compris le repli sur l'image par défaut historique) et du contenu de
 * `$data` reste à la charge de l'appelant, comme pour `Cartes_membre_pdf`.
 *
 * Reproduit le comportement de `vols_decouverte::generate_pdf()` : même
 * gabarit de fond (0,0,210,150), même génération de QR code
 * (QRcode::png, QR_ECLEVEL_L, taille 10, marge 1), même position par
 * défaut de QR code (cf. `Vols_decouverte_looks_model::default_layout()`).
 */
class Vd_bon_pdf extends TCPDF {

    // Dimensions du fond recto historique (mm) — voir vols_decouverte::generate_pdf()
    const BG_W = 210;
    const BG_H = 150;

    function __construct() {
        parent::__construct('L', 'mm', 'A5', true, 'UTF-8', false);

        $this->SetCreator(PDF_CREATOR);
        $this->SetTitle('Bon de vol de découverte');
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false);
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
    }

    /**
     * Résout la valeur d'un champ variable depuis les données du vol de
     * découverte.
     *
     * @param string $id    Identifiant du champ (ex. 'beneficiaire')
     * @param array  $data  Données enrichies du vol de découverte
     * @return string
     */
    private function resolve_variable($id, array $data) {
        switch ($id) {
            case 'numero':
                return isset($data['numero']) ? (string) $data['numero'] : '';
            case 'date_vente':
                return $data['date_vente'] ?? '';
            case 'date_validite':
                return $data['date_validite'] ?? '';
            case 'beneficiaire':
                return $data['beneficiaire'] ?? '';
            case 'occasion':
                return $data['occasion'] ?? '';
            case 'de_la_part':
                return $data['de_la_part'] ?? '';
            case 'beneficiaire_email':
                return $data['beneficiaire_email'] ?? '';
            case 'type_vol':
                return $data['type_vol'] ?? '';
            default:
                return $data[$id] ?? '';
        }
    }

    /**
     * Dessine le fond d'une page, si fourni et existant. Aucun repli : la
     * résolution d'une image par défaut (ex. Bon-Bapteme.png) est à la
     * charge de l'appelant.
     *
     * @param string|null $fond  Chemin absolu de l'image de fond, ou null
     */
    private function render_background($fond) {
        if ($fond && file_exists($fond)) {
            $this->Image($fond, 0, 0, self::BG_W, self::BG_H, '', '', '', false, 300, '', false, false, 0);
        }
    }

    /**
     * Dessine un champ texte (variable ou statique) à sa position absolue.
     *
     * @param array  $field  Définition du champ (x, y, font, bold, size, color, align, width)
     * @param string $value  Valeur à afficher
     */
    private function render_field(array $field, $value) {
        $style = !empty($field['bold']) ? 'B' : '';
        $this->SetFont($field['font'], $style, (int) $field['size']);

        $color = $field['color'];
        $this->SetTextColor($color[0], $color[1], $color[2]);

        $width = isset($field['width']) ? (float) $field['width'] : 60.0;
        $align = $field['align'] ?? 'L';

        $this->SetXY((float) $field['x'], (float) $field['y']);
        $this->Cell($width, 4, $value, 0, 0, $align);
    }

    /**
     * Place le QR code à la position configurée, si activé et si l'image a
     * bien été générée.
     *
     * @param array|null  $qr_field       Entrée 'qr_field' du layout (x, y, size, enabled), ou null
     * @param string|null $qr_image_path  Chemin absolu vers le PNG du QR code généré
     */
    private function render_qr($qr_field, $qr_image_path) {
        if (empty($qr_field) || empty($qr_field['enabled'])) {
            return;
        }
        if (empty($qr_image_path) || !file_exists($qr_image_path)) {
            return;
        }
        $size = (float) $qr_field['size'];
        $this->Image($qr_image_path, (float) $qr_field['x'], (float) $qr_field['y'], $size, $size, 'PNG', '', 'T', false, 300, '', false, false, 0, 'CM');
    }

    /**
     * Génère l'image PNG du QR code vers un fichier temporaire.
     * Mêmes paramètres que l'ancien mécanisme : QR_ECLEVEL_L, taille 10, marge 1.
     *
     * @param string $qr_url   URL encodée dans le QR code
     * @param string $qr_path  Chemin absolu du fichier PNG à générer
     */
    private function generate_qr_image($qr_url, $qr_path) {
        QRcode::png($qr_url, $qr_path, QR_ECLEVEL_L, 10, 1);
    }

    /**
     * Dessine une face (recto ou verso) : fond, champs variables, champs
     * statiques, puis QR code si configuré sur cette face.
     *
     * @param array       $face_layout    Entrée 'recto' ou 'verso' du layout décodé
     * @param array       $data           Données enrichies du vol de découverte
     * @param string|null $fond           Chemin absolu du fond de cette face, ou null
     * @param string|null $qr_image_path  Chemin absolu du PNG de QR code déjà généré, ou null
     */
    private function render_face(array $face_layout, array $data, $fond, $qr_image_path) {
        $this->render_background($fond);

        foreach ($face_layout['variable_fields'] as $field) {
            if (empty($field['enabled'])) continue;
            $value = $this->resolve_variable($field['id'], $data);
            $this->render_field($field, $value);
        }

        foreach ($face_layout['static_fields'] as $field) {
            $this->render_field($field, $field['text']);
        }

        $this->render_qr($face_layout['qr_field'] ?? null, $qr_image_path);
    }

    /**
     * Génère le bon complet (recto + verso).
     *
     * Si `$data['qr_url']` est fourni, le QR code est généré une seule fois
     * puis placé sur la face dont le layout l'active (recto par défaut,
     * comme l'ancien mécanisme).
     *
     * @param array       $data        Données enrichies du vol de découverte
     *                                 (numero, date_vente, date_validite,
     *                                 beneficiaire, occasion, de_la_part,
     *                                 type_vol, qr_url)
     * @param array       $layout      Layout décodé (clés 'recto' et 'verso')
     * @param string|null $fond_recto  Chemin absolu du fond recto, ou null
     * @param string|null $fond_verso  Chemin absolu du fond verso, ou null
     */
    public function generate(array $data, array $layout, $fond_recto = null, $fond_verso = null) {
        $qr_image_path = null;
        if (!empty($data['qr_url'])) {
            $qr_image_path = sys_get_temp_dir() . '/vd_qrcode_' . (isset($data['numero']) ? $data['numero'] : uniqid()) . '.png';
            $this->generate_qr_image($data['qr_url'], $qr_image_path);
        }

        $this->AddPage();
        $this->render_face($layout['recto'], $data, $fond_recto, $qr_image_path);

        $this->AddPage();
        $this->render_face($layout['verso'], $data, $fond_verso, $qr_image_path);
    }
}
