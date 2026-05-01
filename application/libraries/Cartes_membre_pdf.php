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
 */
class Cartes_membre_pdf extends TCPDF {

    // Gabarit Avery C32016-10 (mm)
    const CARD_W  = 85.6;
    const CARD_H  = 54.0;
    const MARGIN_LEFT = 7.2;
    const MARGIN_TOP  = 13.0;
    const GAP_H   = 2.5;   // gouttière horizontale
    const GAP_V   = 0.0;   // gouttière verticale
    const COLS    = 2;
    const ROWS    = 5;
    const CARDS_PER_PAGE = 10;

    // Épaisseur de la bordure de substitution (fond absent), en mm
    const BORDER_WIDTH = 0.35;  // ≈ 1 px à 72 dpi

    /** @var string Nom du club */
    private $nom_club = '';

    function __construct($nom_club = '') {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->nom_club = $nom_club;

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
     * Dessine une carte recto à la position absolue ($ox, $oy) sur la page.
     *
     * @param array  $data     Données membre : mnom, mprenom, mnumero, photo_path, annee
     * @param string $fond     Chemin absolu du fond recto, ou null
     * @param float  $ox       Coordonnée X du coin supérieur gauche (mm)
     * @param float  $oy       Coordonnée Y du coin supérieur gauche (mm)
     */
    public function render_recto($data, $fond, $ox, $oy) {
        $w = self::CARD_W;
        $h = self::CARD_H;

        if ($fond && file_exists($fond)) {
            $this->Image($fond, $ox, $oy, $w, $h, '', '', '', false, 300, '', false, false, 0);
        } else {
            // Fond blanc + bordure
            $this->SetFillColor(255, 255, 255);
            $this->Rect($ox, $oy, $w, $h, 'F');
            $this->SetDrawColor(0, 0, 0);
            $this->SetLineWidth(self::BORDER_WIDTH);
            $this->Rect($ox, $oy, $w, $h, 'D');
        }

        // Nom du club (haut gauche)
        $this->SetFont('helvetica', 'B', 7);
        $this->SetTextColor(0, 0, 0);
        $this->SetXY($ox + 3, $oy + 3);
        $this->Cell($w - 26, 4, $this->nom_club, 0, 0, 'L');

        // Année (haut droite)
        $this->SetFont('helvetica', '', 7);
        $this->SetXY($ox + $w - 23, $oy + 3);
        $this->Cell(20, 4, isset($data['annee']) ? (string)$data['annee'] : '', 0, 0, 'R');

        // Photo (droite, milieu)
        if (!empty($data['photo_path']) && file_exists($data['photo_path'])) {
            $this->Image($data['photo_path'], $ox + 62, $oy + 14, 20, 25, '', '', '', false, 150, '', false, false, 0);
        }

        // Nom + Prénom
        $nom_complet = strtoupper($data['mnom']) . ' ' . $data['mprenom'];
        $this->SetFont('helvetica', 'B', 9);
        $this->SetXY($ox + 3, $oy + 28);
        $this->Cell(58, 5, $nom_complet, 0, 0, 'L');

        // Numéro de membre
        $num = !empty($data['mnumero']) ? 'N° ' . $data['mnumero'] : '';
        $this->SetFont('helvetica', '', 7);
        $this->SetXY($ox + 3, $oy + 36);
        $this->Cell(58, 4, $num, 0, 0, 'L');
    }

    /**
     * Dessine une carte verso à la position absolue ($ox, $oy) sur la page.
     *
     * @param array  $president  ['mnom' => ..., 'mprenom' => ...] ou null
     * @param string $fond       Chemin absolu du fond verso, ou null
     * @param float  $ox
     * @param float  $oy
     */
    public function render_verso($president, $fond, $ox, $oy) {
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

        if ($president) {
            $this->SetFont('helvetica', '', 6);
            $this->SetTextColor(0, 0, 0);
            $this->SetXY($ox + 3, $oy + 32);
            $this->Cell(40, 3, 'Le Président', 0, 0, 'L');

            $this->SetFont('helvetica', 'B', 7);
            $this->SetXY($ox + 3, $oy + 36);
            $nom_president = $president['mprenom'] . ' ' . strtoupper($president['mnom']);
            $this->Cell(50, 4, $nom_president, 0, 0, 'L');
        }
    }

    /**
     * Génère une page de rectos pour un tableau de cartes (max 10).
     *
     * @param array  $cards    Tableau de données membre (chaque entrée : données recto)
     * @param string $fond     Chemin du fond recto, ou null
     */
    public function render_recto_page(array $cards, $fond) {
        $this->AddPage();
        foreach ($cards as $i => $card) {
            if ($i >= self::CARDS_PER_PAGE) break;
            list($x, $y) = $this->card_position($i);
            $this->render_recto($card, $fond, $x, $y);
        }
    }

    /**
     * Génère une page de versos pour un tableau de cartes (max 10).
     * Ordre miroir horizontal : les cartes sont inversées pour l'impression recto-verso.
     *
     * Le miroir s'applique par colonne : dans chaque rangée, la colonne gauche et
     * droite sont échangées, puis l'ordre des rangées est inversé.
     *
     * @param array  $cards    Même tableau que render_recto_page (même ordre)
     * @param array  $president
     * @param string $fond     Chemin du fond verso, ou null
     */
    public function render_verso_page(array $cards, $president, $fond) {
        $this->AddPage();

        // Miroir : inverser l'ordre global des cartes sur la page
        $mirrored = array_reverse($cards);

        foreach ($mirrored as $i => $card) {
            if ($i >= self::CARDS_PER_PAGE) break;
            list($x, $y) = $this->card_position($i);
            $this->render_verso($president, $fond, $x, $y);
        }
    }

    /**
     * Génère un PDF complet pour un lot de membres.
     * Alterne pages recto / pages verso par tranches de 10.
     *
     * @param array  $membres    Tableau de données membres (mnom, mprenom, mnumero, photo_path, annee)
     * @param array  $president  Données du président
     * @param string $fond_recto Chemin absolu fond recto, ou null
     * @param string $fond_verso Chemin absolu fond verso, ou null
     */
    public function generate_lot(array $membres, $president, $fond_recto, $fond_verso) {
        $chunks = array_chunk($membres, self::CARDS_PER_PAGE);
        foreach ($chunks as $chunk) {
            $this->render_recto_page($chunk, $fond_recto);
            $this->render_verso_page($chunk, $president, $fond_verso);
        }
    }

    /**
     * Génère une page A4 avec une seule carte (individuelle), centrée.
     *
     * @param array  $data       Données membre
     * @param array  $president  Données du président
     * @param string $fond_recto
     * @param string $fond_verso
     */
    public function generate_individuelle($data, $president, $fond_recto, $fond_verso) {
        // Recto — centré sur A4 (210 × 297 mm)
        $cx = (210 - self::CARD_W) / 2;
        $cy = (297 - self::CARD_H * 2 - 10) / 2;

        $this->AddPage();
        $this->render_recto($data, $fond_recto, $cx, $cy);

        // Verso — dessous avec une marge de 10 mm
        $this->render_verso($president, $fond_verso, $cx, $cy + self::CARD_H + 10);
    }
}
