<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once(APPPATH . 'third_party/tcpdf/tcpdf.php');
require_once(APPPATH . 'libraries/Parsedown.php');

/**
 * Newsletter_pdf
 *
 * Mise en forme PDF d'une newsletter rédigée en Markdown (Parsedown → HTML
 * → TCPDF). Utilisée pour le brouillon joint aux relecteurs et pour la
 * version publiée.
 */
class Newsletter_pdf {

    /**
     * @param string $markdown
     * @param string $title     Titre du document (métadonnées)
     * @return string           Contenu PDF
     */
    public function render($markdown, $title) {
        $parser = new Parsedown();
        $parser->setSafeMode(true);
        $html = $parser->text($markdown);

        // Images locales relatives (ex. img/planeur.svg) : non embarquées
        $html = preg_replace('#<img\b(?![^>]*\bsrc="https?://)[^>]*>#i', '', $html);
        // TCPDF ne gère pas les citations : rendu en paragraphe encadré
        $html = str_replace(array('<blockquote>', '</blockquote>'),
                            array('<div style="border-left:2px solid #999;padding-left:6px;color:#333;">', '</div>'), $html);

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('GVV');
        $pdf->SetTitle($title);
        $pdf->SetMargins(18, 18, 18);
        $pdf->SetAutoPageBreak(TRUE, 18);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true); // numéros de page
        $pdf->setFooterFont(array('dejavusans', '', 7));
        $pdf->SetFooterMargin(10);
        $pdf->SetFont('dejavusans', '', 9.5);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        return $pdf->Output('newsletter.pdf', 'S');
    }
}
