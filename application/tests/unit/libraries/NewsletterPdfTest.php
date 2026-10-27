<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests de la mise en forme PDF de la newsletter (Newsletter_pdf).
 *
 * Non-régression : une citation contenant une liste produisait un </p>
 * orphelin dans la cellule de l'encadré ; TCPDF abandonnait alors le rendu
 * et le PDF ne contenait que le bandeau et le début du texte.
 *
 * Mise en page : chaque section (titre de niveau 2) commence sur une
 * nouvelle page, sauf si elle tient entièrement sur la page en cours.
 */
class NewsletterPdfTest extends TestCase
{
    private const DRAFT = "# Veille – essai\n\nPériode : du 1er mars au 31 mars 1990.\n\n"
        . "> **BROUILLON v3 – NON VALIDÉ.** À relire.\n>\n> **Changements :**\n> - premier point\n> - second point\n>\n> **Rappels :**\n> - un rappel\n\n"
        . "> Citation externe\n>\n> > Citation interne\n\n"
        . "> **Commentaire du DTO :** le plafond à 400 ft suffit\n\n"
        . "## 1. Rubrique\n\n### Article\n\nTexte de l'article ✈ 🛩\n\n| a | b |\n|---|---|\n| 1 | 2 |\n";

    private $pdf;

    protected function setUp(): void
    {
        if (!function_exists('log_message')) {
            function log_message($level, $message) {}
        }
        require_once APPPATH . 'libraries/Newsletter_pdf.php';
        $this->pdf = new Newsletter_pdf();
    }

    public function test_quotes_become_balanced_cells(): void
    {
        $html = $this->pdf->html(self::DRAFT, array('comment_labels' => array('Commentaire du DTO')));
        $this->assertStringNotContainsString('<blockquote', $html);
        $this->assertStringNotContainsString('Veille – essai', $html, 'le titre figure dans le bandeau');
        $this->assertStringNotContainsString('🛩', $html);

        preg_match_all('#<td[^>]*>(.*?)</td>#s', $html, $cells);
        foreach ($cells[1] as $cell) {
            $this->assertSame(substr_count($cell, '<p>'), substr_count($cell, '</p>'), $cell);
        }
        $this->assertStringContainsString('Citation interne', $html);
        $this->assertStringContainsString(Newsletter_pdf::COMMENT_BG, $html);
    }

    public function test_render_goes_through_the_whole_document(): void
    {
        // Un avertissement de TCPDF (balise mal formée) fait échouer ce test
        $out = $this->pdf->render(self::DRAFT, 'Veille – essai', array('club' => 'Club', 'period' => 'mars 1990', 'badge' => 'Brouillon v3'));
        $this->assertStringStartsWith('%PDF-', $out);
        $this->assertGreaterThan(5000, strlen($out));
    }

    private static function section(string $title, int $paragraphs): string
    {
        return "## $title\n\n" . str_repeat("Paragraphe de texte assez long pour occuper deux lignes dans la page du PDF de la newsletter de veille, sans plus.\n\n", $paragraphs);
    }

    public function test_short_sections_share_a_page(): void
    {
        $this->pdf->render("# T\n\n" . self::section('1. Un', 2) . self::section('2. Deux', 2) . self::section('3. Trois', 2), 'T');
        $this->assertSame(array(1, 1, 1), $this->pdf->section_pages);
    }

    public function test_section_that_does_not_fit_starts_on_a_new_page(): void
    {
        // La section 2 tient sur une page entière, mais pas dans la place laissée par la section 1
        $this->pdf->render("# T\n\n" . self::section('1. Un', 12) . self::section('2. Deux', 14) . self::section('3. Trois', 1), 'T');
        $this->assertSame(array(1, 2, 2), $this->pdf->section_pages);
    }

    public function test_section_longer_than_a_page_starts_at_top_and_continues(): void
    {
        $this->pdf->render("# T\n\n" . self::section('1. Un', 2) . self::section('2. Long', 70) . self::section('3. Trois', 1), 'T');
        $this->assertSame(2, $this->pdf->section_pages[1]);
        $this->assertGreaterThan(2, $this->pdf->section_pages[2]);
    }
}
