<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

// TCPDF constants
if (!defined('PDF_PAGE_FORMAT')) define('PDF_PAGE_FORMAT', 'A4');
if (!defined('PDF_CREATOR'))     define('PDF_CREATOR', 'GVV');

require_once(APPPATH . 'third_party/tcpdf/tcpdf.php');

/**
 * Moteur de composition PDF pour les cartes de membre.
 *
 * Gabarit Avery C32016-10 : 10 cartes par A4, 2 colonnes × 5 lignes.
 * Carte ISO ID-1 : 85,6 × 54 mm.
 *
 * Ordre verso : miroir horizontal — pour N cartes par page, le verso
 * imprime les cartes en ordre inverse afin d'assurer l'alignement lors
 * de l'impression recto-verso par rapport au bord long.
 *
 * Le layout injecté (array PHP décodé depuis JSON) contrôle la position,
 * la police, la taille et la couleur de chaque champ. Aucune position
 * n'est codée en dur dans ce moteur.
 */
class Cartes_membre_pdf extends TCPDF {

    // Gabarit Avery C32016-10 (mm)
    const CARD_W  = 85.6;
    const CARD_H  = 54.0;
    const MARGIN_LEFT = 15.0;
    const MARGIN_TOP  = 13.0;
    const GAP_H   = 10.0;  // gouttière horizontale
    const GAP_V   = 0.0;   // gouttière verticale
    const COLS    = 2;
    const ROWS    = 5;
    const CARDS_PER_PAGE = 10;

    // Épaisseur de la bordure de substitution (fond absent), en mm
    const BORDER_WIDTH = 0.35;  // ≈ 1 px à 72 dpi

    function __construct() {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);

        $this->SetCreator(PDF_CREATOR);
        $this->SetTitle('Cartes de membre');
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false);
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
    }

    /**
     * Calcule la position (x, y) en mm du coin supérieur gauche de la carte
     * à l'index $i (0..9) sur la page.
     */
    private function card_position($i) {
        $col = $i % self::COLS;
        $row = (int)($i / self::COLS);
        $x = self::MARGIN_LEFT + $col * (self::CARD_W + self::GAP_H);
        $y = self::MARGIN_TOP  + $row * (self::CARD_H + self::GAP_V);
        return array($x, $y);
    }

    /**
     * Résout la valeur d'un champ variable depuis les données membre.
     *
     * @param string $id    Identifiant du champ (ex. 'nom_prenom')
     * @param array  $data  Données membre enrichies (annee, nom_club, nom_president, …)
     * @return string
     */
    private function resolve_variable($id, array $data) {
        switch ($id) {
            case 'nom_prenom':
                return strtoupper($data['mnom'] ?? '') . ' ' . ($data['mprenom'] ?? '');
            case 'saison':
                return isset($data['annee']) ? (string)$data['annee'] : '';
            case 'numero_membre':
                return !empty($data['mnumero']) ? 'N° ' . $data['mnumero'] : '';
            case 'numero_carte':
                return isset($data['numero_carte']) ? (string)$data['numero_carte'] : '';
            case 'activites':
                return $data['activites'] ?? '';
            case 'nom_club':
                return $data['nom_club'] ?? '';
            default:
                return $data[$id] ?? '';
        }
    }

    /**
     * Dessine le fond (image ou blanc + bordure) pour une carte.
     *
     * @param string|null $fond  Chemin absolu vers l'image de fond, ou null
     * @param float       $ox    Coin supérieur gauche X (mm)
     * @param float       $oy    Coin supérieur gauche Y (mm)
     */
    private function render_background($fond, $ox, $oy) {
        $w = self::CARD_W;
        $h = self::CARD_H;

        if ($fond && file_exists($fond)) {
            $this->Image($fond, $ox, $oy, $w, $h, '', '', '', false, 300, '', false, false, 0);
        } else {
            $this->SetFillColor(255, 255, 255);
            $this->Rect($ox, $oy, $w, $h, 'F');
            $this->SetDrawColor(0, 0, 0);
            $this->SetLineWidth(self::BORDER_WIDTH);
            $this->Rect($ox, $oy, $w, $h, 'D');
        }
    }

    /**
     * Dessine une face (recto ou verso) d'une carte à la position absolue donnée.
     *
     * @param array       $face_layout  Entrée 'recto' ou 'verso' du layout JSON décodé
     * @param array       $data         Données membre enrichies
     * @param string|null $fond         Chemin absolu fond, ou null
     * @param float       $ox
     * @param float       $oy
     */
    private function render_face(array $face_layout, array $data, $fond, $ox, $oy) {
        $this->render_background($fond, $ox, $oy);

        // Champs variables
        foreach ($face_layout['variable_fields'] as $field) {
            if (empty($field['enabled'])) continue;
            $value = $this->resolve_variable($field['id'], $data);
            $this->render_field($field, $value, $ox, $oy);
        }

        // Champs statiques
        foreach ($face_layout['static_fields'] as $field) {
            $this->render_field($field, $field['text'], $ox, $oy);
        }

        // Photo
        if (!empty($face_layout['photo']['enabled']) && !empty($data['photo_path']) && file_exists($data['photo_path'])) {
            $p = $face_layout['photo'];
            $this->Image($data['photo_path'], $ox + $p['x'], $oy + $p['y'], $p['w'], $p['h'], '', '', '', false, 150, '', false, false, 0);
        }
    }

    /**
     * Dessine un champ texte (variable ou statique) à la position relative à la carte.
     *
     * @param array  $field  Définition du champ (x, y, font, bold, size, color, align, width)
     * @param string $value  Valeur à afficher
     * @param float  $ox     Coin supérieur gauche de la carte (mm)
     * @param float  $oy
     */
    private function render_field(array $field, $value, $ox, $oy) {
        $style = $field['bold'] ? 'B' : '';
        $this->SetFont($field['font'], $style, (int)$field['size']);

        $color = $field['color'];
        $this->SetTextColor($color[0], $color[1], $color[2]);

        $width = isset($field['width']) ? (float)$field['width'] : 60.0;
        $align = isset($field['align']) ? $field['align'] : 'L';

        $this->SetXY($ox + (float)$field['x'], $oy + (float)$field['y']);
        $this->Cell($width, 4, $value, 0, 0, $align);
    }

    /**
     * Dessine une carte recto à la position absolue ($ox, $oy) sur la page.
     *
     * @param array       $data    Données membre enrichies (mnom, mprenom, mnumero, photo_path, annee, nom_club, nom_president)
     * @param array       $layout  Layout JSON décodé (complet, avec 'recto' et 'verso')
     * @param string|null $fond    Chemin absolu du fond recto, ou null
     * @param float       $ox
     * @param float       $oy
     */
    public function render_recto(array $data, array $layout, $fond, $ox, $oy) {
        $this->render_face($layout['recto'], $data, $fond, $ox, $oy);
    }

    /**
     * Dessine une carte verso à la position absolue ($ox, $oy) sur la page.
     *
     * @param array       $data    Données membre enrichies
     * @param array       $layout  Layout JSON décodé
     * @param string|null $fond    Chemin absolu du fond verso, ou null
     * @param float       $ox
     * @param float       $oy
     */
    public function render_verso(array $data, array $layout, $fond, $ox, $oy) {
        $this->render_face($layout['verso'], $data, $fond, $ox, $oy);
    }

    /**
     * Génère une page de rectos pour un tableau de cartes (max 10).
     *
     * @param array       $cards   Tableau de données membre
     * @param array       $layout  Layout JSON décodé
     * @param string|null $fond    Chemin du fond recto, ou null
     */
    public function render_recto_page(array $cards, array $layout, $fond) {
        $this->AddPage();
        foreach ($cards as $i => $card) {
            if ($i >= self::CARDS_PER_PAGE) break;
            list($x, $y) = $this->card_position($i);
            $this->render_recto($card, $layout, $fond, $x, $y);
        }
    }

    /**
     * Génère une page de versos pour un tableau de cartes (max 10).
     * Ordre miroir horizontal : les cartes sont inversées pour l'impression recto-verso.
     *
     * @param array       $cards     Même tableau que render_recto_page (même ordre)
     * @param array       $layout    Layout JSON décodé
     * @param string|null $fond      Chemin du fond verso, ou null
     */
    public function render_verso_page(array $cards, array $layout, $fond) {
        $this->AddPage();
        $mirrored = array_reverse($cards);
        foreach ($mirrored as $i => $card) {
            if ($i >= self::CARDS_PER_PAGE) break;
            list($x, $y) = $this->card_position($i);
            $this->render_verso($card, $layout, $fond, $x, $y);
        }
    }

    /**
     * Génère un PDF complet pour un lot de membres.
     * Alterne pages recto / pages verso par tranches de 10.
     *
     * @param array       $membres     Tableau de données membres enrichies
     * @param array       $layout      Layout JSON décodé
     * @param string|null $fond_recto  Chemin absolu fond recto, ou null
     * @param string|null $fond_verso  Chemin absolu fond verso, ou null
     */
    public function generate_lot(array $membres, array $layout, $fond_recto, $fond_verso) {
        $chunks = array_chunk($membres, self::CARDS_PER_PAGE);
        foreach ($chunks as $chunk) {
            $this->render_recto_page($chunk, $layout, $fond_recto);
            $this->render_verso_page($chunk, $layout, $fond_verso);
        }
    }

    /**
     * Génère une page A4 avec une seule carte (individuelle), centrée.
     *
     * @param array       $data        Données membre enrichies
     * @param array       $layout      Layout JSON décodé
     * @param string|null $fond_recto
     * @param string|null $fond_verso
     */
    public function generate_individuelle(array $data, array $layout, $fond_recto, $fond_verso) {
        // Centré sur A4 (210 × 297 mm), recto et verso côte à côte verticalement
        $cx = (210 - self::CARD_W) / 2;
        $cy = (297 - self::CARD_H * 2 - 10) / 2;

        $this->AddPage();
        $this->render_recto($data, $layout, $fond_recto, $cx, $cy);
        $this->render_verso($data, $layout, $fond_verso, $cx, $cy + self::CARD_H + 10);
    }
}
