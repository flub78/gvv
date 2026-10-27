<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once(APPPATH . 'third_party/tcpdf/tcpdf.php');
require_once(APPPATH . 'libraries/Parsedown.php');
require_once(APPPATH . 'libraries/Newsletter_files.php');

/**
 * Page TCPDF de la newsletter : en-tête discret et pied de page paginé à
 * partir de la deuxième page (la première porte le bandeau de titre).
 */
class Newsletter_tcpdf extends TCPDF {

    public $nl_title = '';
    public $nl_footer = '';

    public function __construct(...$args) {
        parent::__construct(...$args);
        $this->tcpdflink = false;   // pas de mention « Powered by TCPDF »
    }

    public function Header() {
        if ($this->getPage() === 1) return;
        $this->SetFont('dejavusans', '', 7.5);
        $this->SetTextColor(110, 120, 135);
        $this->SetXY(18, 8);
        $this->Cell(0, 5, $this->nl_title, 0, 0, 'L');
        $this->SetDrawColor(31, 58, 95);
        $this->SetLineWidth(0.3);
        $this->Line(18, 14, $this->getPageWidth() - 18, 14);
    }

    public function Footer() {
        $this->SetY(-13);
        $this->SetDrawColor(210, 215, 222);
        $this->SetLineWidth(0.2);
        $this->Line(18, $this->GetY(), $this->getPageWidth() - 18, $this->GetY());
        $this->SetFont('dejavusans', '', 7);
        $this->SetTextColor(110, 120, 135);
        $this->Cell(0, 7, $this->nl_footer, 0, 0, 'L');
        $this->SetX(18);
        $this->Cell(0, 7, $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

/**
 * Newsletter_pdf
 *
 * Mise en forme PDF d'une newsletter rédigée en Markdown (Parsedown → HTML
 * → TCPDF) : bandeau de titre (club, titre, période), rubriques sur fond de
 * couleur, articles en couleur d'accent, citations et commentaires de
 * l'administrateur en encadrés, images de la version intégrées. Utilisée
 * pour le brouillon joint aux relecteurs et pour la version publiée.
 *
 * @see doc/design_notes/veille_documentaire_design.md (Mise en forme PDF)
 */
class Newsletter_pdf {

    const PRIMARY = '#1F3A5F';
    const ACCENT  = '#1F6FB2';
    const NOTE_BG = '#F2F5F9';
    const COMMENT_BG = '#FFF4DC';
    const COMMENT_BORDER = '#E0A100';

    /** Marque de début de section (titre de niveau 2) dans le HTML produit */
    const SECTION_MARK = '<!--nl-section-->';

    /** Page de début de chaque section du dernier rendu (diagnostic, tests) */
    public $section_pages = array();

    /** Formats d'image que TCPDF sait intégrer */
    const PDF_IMAGES = array('png', 'jpg', 'jpeg', 'gif', 'svg');

    /**
     * @param string $markdown
     * @param string $title    Titre (bandeau et métadonnées)
     * @param array  $options  period (texte), club (nom), badge (ex. « BROUILLON v3 »),
     *                         image_dir (répertoire des images img/…),
     *                         comment_labels (libellés des commentaires de l'administrateur)
     * @return string          Contenu PDF
     */
    public function render($markdown, $title, $options = array()) {
        $club = trim($options['club'] ?? '');

        $pdf = new Newsletter_tcpdf('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->nl_title  = $title;
        $pdf->nl_footer = $club !== '' ? $club : $title;
        $pdf->SetCreator('GVV');
        $pdf->SetAuthor($club);
        $pdf->SetTitle($title);
        $pdf->SetMargins(18, 20, 18);
        $pdf->SetHeaderMargin(8);
        $pdf->SetFooterMargin(13);
        $pdf->SetAutoPageBreak(TRUE, 18);
        $pdf->setCellHeightRatio(1.35);
        $pdf->setImageScale(1.53);
        $pdf->AddPage();

        $this->banner($pdf, $title, $club, $options['period'] ?? '', $options['badge'] ?? '');

        $pdf->SetFont('dejavusans', '', 9.5);
        $pdf->SetTextColor(33, 37, 41);
        $this->section_pages = array();
        $parts = explode(self::SECTION_MARK, $this->html($markdown, $options));
        $pdf->writeHTML(array_shift($parts), true, false, true, false, '');
        foreach ($parts as $section) {
            $this->write_section($pdf, $section);
        }
        return $pdf->Output('newsletter.pdf', 'S');
    }

    /**
     * Écrit une section sur la page courante si elle y tient entièrement ;
     * sinon, elle commence sur une nouvelle page.
     */
    private function write_section($pdf, $section) {
        $start_page = $pdf->getPage();
        $at_top = $pdf->GetY() <= $pdf->getMargins()['top'] + 1;
        $pdf->startTransaction();
        $pdf->writeHTML($section, true, false, true, false, '');
        if ($pdf->getPage() === $start_page || $at_top) {
            $pdf->commitTransaction();
            $this->section_pages[] = $start_page;
            return;
        }
        $pdf->rollbackTransaction(true);
        $pdf->AddPage();
        $this->section_pages[] = $pdf->getPage();
        $pdf->writeHTML($section, true, false, true, false, '');
    }

    /**
     * Bandeau de la première page.
     */
    private function banner($pdf, $title, $club, $period, $badge) {
        $w = $pdf->getPageWidth();
        $pdf->SetFillColor(31, 58, 95);
        $pdf->Rect(0, 0, $w, 46, 'F');
        $pdf->SetFillColor(224, 161, 0);
        $pdf->Rect(0, 46, $w, 1.6, 'F');

        $pdf->SetTextColor(190, 205, 225);
        $pdf->SetFont('dejavusans', 'B', 8);
        $pdf->SetXY(18, 9);
        $pdf->Cell($w - 36, 5, mb_strtoupper($club), 0, 0, 'L');
        if ($badge !== '') {
            $pdf->SetTextColor(255, 214, 120);
            $pdf->SetXY(18, 9);
            $pdf->Cell($w - 36, 5, mb_strtoupper($badge), 0, 0, 'R');
        }

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('dejavusans', 'B', 17);
        $pdf->SetXY(18, 16);
        $pdf->MultiCell($w - 36, 8, $title, 0, 'L', false, 1, '', '', true, 0, false, true, 17, 'M', true);

        if ($period !== '') {
            $pdf->SetTextColor(210, 222, 238);
            $pdf->SetFont('dejavusans', '', 9.5);
            $pdf->SetXY(18, 36);
            $pdf->Cell($w - 36, 5, $period, 0, 0, 'L');
        }
        $pdf->SetY(54);
    }

    /**
     * HTML adapté à TCPDF : TCPDF ne gère ni les citations ni les styles de
     * titres, d'où leur transformation en tableaux d'une cellule.
     */
    public function html($markdown, $options = array()) {
        $markdown = Newsletter_files::strip_html_comments($markdown);
        // Emojis hors du plan de base : absents de la police, ils s'afficheraient en carrés
        $markdown = preg_replace('/[\x{10000}-\x{10FFFF}]|\x{FE0F}/u', '', $markdown);
        // Le titre de niveau 1 figure dans le bandeau
        $markdown = preg_replace('/^#[ \t]+.*\R?/m', '', $markdown, 1);

        $parser = new Parsedown();
        $parser->setSafeMode(true);
        $html = $parser->text($markdown);

        $html = $this->images($html, $options['image_dir'] ?? '');
        $html = $this->quotes($html, $options['comment_labels'] ?? array());

        $html = preg_replace('#<h2>(.*?)</h2>#s',
            self::SECTION_MARK . '<table cellpadding="5"><tr><td style="background-color:' . self::PRIMARY . ';color:#FFFFFF;font-size:12.5pt;font-weight:bold;">$1</td></tr></table>', $html);
        $html = preg_replace('#<h3>(.*?)</h3>#s', '<h3 style="color:' . self::ACCENT . ';font-size:11pt;">$1</h3>', $html);
        $html = preg_replace('#<h4>(.*?)</h4>#s', '<h4 style="color:' . self::PRIMARY . ';font-size:10pt;">$1</h4>', $html);
        $html = str_replace('<a href=', '<a style="color:' . self::ACCENT . ';text-decoration:none;" href=', $html);
        $html = str_replace('<hr />', '<hr style="color:#D2D7DE;" />', $html);
        $html = str_replace('<table>', '<table border="1" cellpadding="3" style="border-color:#D2D7DE;">', $html);
        $html = str_replace('<th>', '<th style="background-color:' . self::NOTE_BG . ';font-weight:bold;">', $html);
        return $html;
    }

    /**
     * Images de la version : chemin absolu et taille en millimètres ; les
     * images introuvables ou non intégrables sont retirées.
     */
    private function images($html, $image_dir) {
        return preg_replace_callback('#<img\b[^>]*>#i', function ($m) use ($image_dir) {
            if (!preg_match('#src="([^"]+)"#', $m[0], $src) || $image_dir === ''
                || !preg_match('#^img/([^/]+)$#', html_entity_decode($src[1]), $rel)) {
                return '';
            }
            $path = rtrim($image_dir, '/') . '/' . Newsletter_files::safe_name($rel[1]);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (!is_file($path) || !in_array($ext, self::PDF_IMAGES, TRUE)) return '';

            list($wpx, $hpx) = $this->pixel_size($path, $ext);
            if ($wpx <= 64 && $hpx <= 64) {
                $size = 'height="5mm"';                         // pictogramme dans le texte
            } else {
                $size = 'width="' . round(min(170, $wpx * 0.2646), 1) . 'mm"';
            }
            return '<img src="' . htmlspecialchars($path) . '" ' . $size . ' />';
        }, $html);
    }

    private function pixel_size($path, $ext) {
        if ($ext !== 'svg') {
            $info = @getimagesize($path);
            return $info ? array($info[0], $info[1]) : array(600, 400);
        }
        $svg = (string) @file_get_contents($path, FALSE, NULL, 0, 4096);
        if (preg_match('/<svg[^>]*\bwidth="([\d.]+)(px)?"[^>]*\bheight="([\d.]+)(px)?"/i', $svg, $m)) {
            return array((float) $m[1], (float) $m[3]);
        }
        if (preg_match('/viewBox="[\d.\-]+\s+[\d.\-]+\s+([\d.]+)\s+([\d.]+)"/i', $svg, $m)) {
            return array((float) $m[1], (float) $m[2]);
        }
        return array(600, 400);
    }

    /**
     * Citations en encadrés ; celles qui commencent par le libellé d'un
     * commentaire de l'administrateur ont leur propre couleur.
     */
    private function quotes($html, array $labels) {
        // Citations les plus internes d'abord, pour les citations imbriquées
        $innermost = '#<blockquote>((?:(?!<blockquote>).)*?)</blockquote>#s';
        while (preg_match($innermost, $html)) {
            $html = preg_replace_callback($innermost, function ($m) use ($labels) {
                $inner = trim($m[1]);
                $is_comment = FALSE;
                foreach ($labels as $l) {
                    if (strpos($inner, '<p><strong>' . htmlspecialchars(trim($l)) . ' :') === 0) $is_comment = TRUE;
                }
                // Paragraphes de la citation : sauts de ligne dans une seule cellule,
                // sans balise <p> (une balise mal fermée interrompt le rendu de TCPDF)
                $inner = str_replace('<p>', '', $inner);
                $inner = preg_replace('#</p>\s*#', '<br /><br />', $inner);
                $inner = preg_replace('#(<br />\s*)+$#', '', $inner);
                $inner = preg_replace('#(<br />\s*)+(?=<(ul|ol)>)#', '', $inner);
                $bg = $is_comment ? self::COMMENT_BG : self::NOTE_BG;
                $border = $is_comment ? self::COMMENT_BORDER : self::ACCENT;
                return '<table cellpadding="5"><tr><td style="background-color:' . $bg . ';border-left:1.2mm solid ' . $border . ';">'
                     . $inner . '</td></tr></table><br />';
            }, $html);
        }
        return $html;
    }
}
