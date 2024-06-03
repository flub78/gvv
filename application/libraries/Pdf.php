<?php
// GVV Gestion vol à voile
// Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
//
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

$CI = & get_instance();
$CI->load->library('Fpdf');
class PDF extends FPDF {
    protected $table_header = FALSE;
    protected $table_header_line;
    protected $width;
    protected $height;
    protected $align;
    protected $border;
    protected $fill;
    protected $link;

    /**
     * Constructor
     *
     * Affiche header et menu
     */
    function __construct($orientation = "P", $unit = "mm", $format = "A4") {
        parent::__construct($orientation, $unit, $format);
        $CI = & get_instance();
        $nom_club = $CI->config->item('nom_club');
        $this->SetTitle($nom_club);
        $this->set_title($nom_club);
        $this->AliasNbPages();
    }

    /**
     * Set the document's title
     */
    public function set_title($str) {
        $this->title = $str;
    }

    /**
     * Display a row
     */
    public function row($w, $height, $align, $row, $border = 'LRTB', $fill = FALSE, $link = '') {

        // Check the number of real lines
        $col = 0;
        $ln = 1;
        foreach ( $row as $field ) {
            $width = $w [$col ++]; // colomn width
            $sw = $this->GetStringWidth($field); // string width
            $ratio = 1 + ( int ) ($sw / $width); // Compute number of line required
            if ($ratio > $ln) {
                $ln = $ratio;
            }
        }

        $col = 0;
        foreach ( $row as $field ) {

            /*
             * Problem:
             *
             * When some lines require at least three lines, single cells have the right height,
             * but multicell that do not require three lines are too small.
             */
            $algn = ($ln == 1) ? $align [$col] : 'L';
            $algn = $align [$col];
            if ($this->GetStringWidth($field) <= $w [$col]) {
                parent::Cell($w [$col], $height * $ln, $field, $border, 0, $algn);
            } else {

                // fill with spaces to force the correc size
                while ( $this->GetStringWidth($field) <= $w [$col] * ($ln - 1) ) {
                    $field .= ' ';
                }

                $x = $this->GetX();
                $y = $this->GetY();
                $this->MultiCell($w [$col], $height, $field, $border, $algn);
                $this->SetXY($x + $w [$col], $y);
            }
            $col ++;
        }
        $this->Ln();
        if ($ln > 1) {
            $this->SetXY($this->GetX(), $this->GetY() + ($ln - 2) * $height);
        }
    }

    /**
     *
     * Display a table header.
     *
     * @param unknown_type $width
     * @param unknown_type $align
     * @param unknown_type $data
     */
    public function set_table_header($w, $height, $align, $data = array(), $border = 'LRTB', $fill = FALSE, $link = '') {
        $this->width = $w;
        $this->height = $height;
        $this->align = $align;
        $this->border = $border;
        $this->fill = $fill;
        $this->link = $link;
        $this->table_header_line = $data [0];
        $this->table_header = TRUE;
        $this->table_header();
    }
    public function table_header() {
        if (! $this->table_header)
            return;
        $size = $this->GetFontSize();
        $style = $this->GetFontStyle();
        $this->SetFont('Arial', 'B', 9);
        $this->row($this->width, $this->height, $this->align, $this->table_header_line, $this->border, $this->fill, $this->link);
        $this->SetFont('Arial', $style, $size);
    }

    /**
     *
     * Display an array.
     *
     * @param unknown_type $width
     * @param unknown_type $align
     * @param unknown_type $data
     *            MultiCell(float w, float h, string txt [, mixed border [, string align [, boolean fill]]])
     *            Cell (float w [, float h [, string txt [, mixed border [, int ln [, string align [, boolean fill [, mixed link]]]]]]])
     */
    public function table($w, $height, $align, $data, $border = 'LRTB', $fill = FALSE, $link = '') {
        $line = 0;
        foreach ( $data as $row ) {
            if ($line == 0) {
                $this->set_table_header($w, $height, $align, $data, $border, $fill, $link);
            } else {
                $this->row($w, $height, $align, $row, $border, $fill, $link);
            }
            $line ++;
        }
        $this->table_header = FALSE;
    }

    /**
     *
     * Display a cell
     *
     * @param unknown_type $w
     *            width
     * @param unknown_type $h
     *            height
     * @param unknown_type $txt
     * @param boolean $border
     * @param boolean $ln
     * @param unknown_type $align
     * @param unknown_type $fill
     * @param sting $link
     */
    function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '') {
        // $txt = @ iconv('UTF-8', 'windows-1252//IGNORE', $txt);
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    /*
     * Imprime une liste de cellules
     */
    function cell_block($list, $data) {
        $label_max = 0;
        // cherche le label le plus large
        foreach ( $list as $row ) {
            $len = strlen($row ['label']);
            if ($len > $label_max) {
                $label_max = $len;
            }
        }

        $width = 3;
        foreach ( $list as $row ) {
            $this->cell($label_max * 1.5, 1, $row ['label']);

            $field = $row ['field'];
            $value = isset($data [$field]) ? $data [$field] : "";
            if ($value != "") {
                $w = strlen($value) * $width;
            } else {
                if (isset($row ['size'])) {
                    $w = $row ['size'];
                } else {
                    $w = 0;
                }
            }
            $this->cell($w, 5, $value, true, true);
            $this->Ln(4);
        }
    }

    /**
     * Affiche une ligne standard
     *
     * @param unknown_type $str
     */
    public function printl($str) {
        $this->Cell(0, 5, $str, 0, 1);
    }

    // En-tête
    function Header() {
        $CI = & get_instance();

        // Logo
        $logofile = $CI->config->item('logo_club');
        if (file_exists($logofile)) {
            $this->Image($logofile, 10, 6, 30);
        }
        // Police Arial gras 15
        $this->SetFont('Arial', 'B', 12);

        // Calcul de la largeur du titre et positionnement
        $w = $this->GetStringWidth($this->title) + 6;

        // Décalage à droite
        $this->Cell(100 - $w / 2);
        // Titre
        $this->Cell($w, 10, $this->title, 1, 0, 'C');

        // Date
        $this->SetFont('Arial', '', 10);
        $this->Cell(70 - $w / 2);
        $this->cell(0, 0, date("d/m/Y", time()));

        // Saut de ligne
        $this->Ln(20);
        $this->table_header();
    }

    // Pied de page
    function Footer() {
        // Positionnement à 1,5 cm du bas
        $this->SetY(- 15);
        // Police Arial italique 8
        $this->SetFont('Arial', 'I', 8);
        // Numéro de page
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Return the current font size
    function GetFontSize() {
        return $this->FontSizePt;
    }

    // Return the current font size
    function GetFontStyle() {
        return $this->FontStyle;
    }

    // Crée un titre hiérarchique
    function title($title, $level = 1) {
        $this->SetFont('Arial', 'B', 13 - $level);
        $this->printl($title);
        $this->SetFont('Arial', '', 8);
        $this->Ln();
    }
}
