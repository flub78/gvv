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

$CI = &get_instance();
// Define font path for tFPDF before loading the library
if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', APPPATH . 'libraries/font_fpdf/');
}
require_once(APPPATH . 'libraries/tfpdf.php');
class PDF extends tFPDF {
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
     */
    function __construct($orientation = "P", $unit = "mm", $format = "A4") {
        parent::__construct($orientation, $unit, $format);

        // Add DejaVu fonts for UTF-8 support
        $this->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
        $this->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
        $this->AddFont('DejaVu', 'I', 'DejaVuSans-Oblique.ttf', true);
        $this->AddFont('DejaVu', 'BI', 'DejaVuSans-BoldOblique.ttf', true);

        $CI = &get_instance();
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

    public function row($w, $height, $align, $row, $border = 'LRTB', $fill = FALSE, $link = '') {

        $col = 0;
        foreach ($row as $field) {

            /*
             * Truncate text if it's too long to fit in the cell
             * Add ellipsis (...) to indicate truncation
             * Account for the width of the ellipsis when truncating
             */
            $field_str = (string)$field;
            
            // Only truncate if the text doesn't fit in the column
            if ($this->GetStringWidth($field_str) > $w[$col]) {
                $ellipsis_width = $this->GetStringWidth('...');
                
                // Truncate until the text + ellipsis fits within the column width
                while ($this->GetStringWidth($field_str) > $w[$col] - $ellipsis_width && strlen($field_str) > 0) {
                    $field_str = substr($field_str, 0, -1);
                }
                
                // Cut two more characters to add spacing before the border
                if (strlen($field_str) > 2) {
                    $field_str = substr($field_str, 0, -2);
                }
                
                // Add ellipsis since we truncated
                $field_str .= '...';
            }

            $algn = $align[$col];

            // Support per-column border specification
            $cell_border = is_array($border) ? $border[$col] : $border;

            // Always use Cell for single-line content to avoid overflow
            parent::Cell($w[$col], $height, $field_str, $cell_border, 0, $algn);

            $col++;
        }
        $this->Ln();
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
        $this->table_header_line = $data[0];
        $this->table_header = TRUE;
        $this->table_header();
    }
    public function table_header() {
        if (! $this->table_header)
            return;
        $size = $this->GetFontSize();
        $style = $this->GetFontStyle();
        $this->SetFont('DejaVu', 'B', 6);  // Reduced to 6pt for more compact layout
        $this->row($this->width, $this->height, $this->align, $this->table_header_line, $this->border, $this->fill, $this->link);
        $this->SetFont('DejaVu', $style, $size);
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
        // Set smaller font for table content to fit narrower columns
        $this->SetFont('DejaVu', '', 6);  // Reduced to 6pt for more compact layout

        $line = 0;
        foreach ($data as $row) {
            if ($line == 0) {
                $this->set_table_header($w, $height, $align, $data, $border, $fill, $link);
            } else {
                $this->row($w, $height, $align, $row, $border, $fill, $link);
            }
            $line++;
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
        foreach ($list as $row) {
            $len = strlen($row['label']);
            if ($len > $label_max) {
                $label_max = $len;
            }
        }

        $width = 3;
        foreach ($list as $row) {
            $this->cell($label_max * 1.5, 1, $row['label']);

            $field = $row['field'];
            $value = isset($data[$field]) ? $data[$field] : "";
            if ($value != "") {
                $w = strlen($value) * $width;
            } else {
                if (isset($row['size'])) {
                    $w = $row['size'];
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
        $CI = &get_instance();

        // Logo
        $logofile = $CI->config->item('logo_club');
        if (file_exists($logofile)) {
            $this->Image($logofile, 10, 6, 30);
        }
        // Police DejaVu gras 12 (UTF-8 support)
        $this->SetFont('DejaVu', 'B', 12);

        // Calcul de la largeur du titre et positionnement
        $w = $this->GetStringWidth($this->title) + 6;

        // Décalage à droite
        $this->Cell(100 - $w / 2);
        // Titre
        $this->Cell($w, 10, $this->title, 1, 0, 'C');

        // Date
        $this->SetFont('DejaVu', '', 10);
        $this->Cell(70 - $w / 2);
        $this->cell(0, 0, date("d/m/Y", time()));

        // Saut de ligne
        $this->Ln(20);
        $this->table_header();
    }

    // Pied de page
    function Footer() {
        // Positionnement à 1,5 cm du bas
        $this->SetY(-15);
        // Police DejaVu italique 8
        $this->SetFont('DejaVu', 'I', 8);
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
        $this->SetFont('DejaVu', 'B', 13 - $level);
        $this->printl($title);
        $this->SetFont('DejaVu', '', 8);
        $this->Ln();
    }
}
