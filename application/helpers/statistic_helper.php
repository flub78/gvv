<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	  Statistique génération de tableaux
 */
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

    /* pChart library inclusions */
include (PCHART . "class/pData.class.php");
include (PCHART . "class/pDraw.class.php");
include (PCHART . "class/pImage.class.php");

if (! function_exists('month_chart')) {

    /**
     * Generate a JPEG with stats
     *
     * @param
     *            filename
     * @param unknown_type $data
     *            source
     * @param $selection array
     *            of data line number to display
     */
    function month_chart($filename, $data, $selection, $title) {
        /* CAT:Stacked chart */

        // echo "filename=$filename";
        // var_dump($data);
        // exit;

        /* Create and populate the pData object */
        $MyData = new pData();
        $MyData->loadPalette(PCHART . "palettes/blind2.color", TRUE);
        // $MyData->loadPalette(PCHART . "palettes/evening.color",TRUE);

        // Sauvegarde de la première ligne
        $header = array_shift($data);

        // lignes de données
        $nb = 1;
        foreach ( $data as $row ) {
            $label = array_shift($row);
            array_shift($row);
            if (in_array($nb, $selection))
                $MyData->addPoints($row, $label);
            $nb ++;
        }

        $MyData->setAxisName(0, $title);
        array_shift($header); // supprime ''
        array_shift($header); // supprime total

        $MyData->addPoints($header, "Labels");
        $MyData->setSerieDescription("Labels", "Mois");
        $MyData->setAbscissa("Labels");

        /* Normalize all the data series to 100% */
        // $MyData->normalize(100,"%");

        /* Create the pChart object */
        $width = 900;
        $hight = 600;
        $myPicture = new pImage($width, $hight, $MyData);
        $myPicture->drawGradientArea(0, 0, $width, $hight, DIRECTION_VERTICAL, array (
                "StartR" => 240,
                "StartG" => 240,
                "StartB" => 240,
                "EndR" => 180,
                "EndG" => 180,
                "EndB" => 180,
                "Alpha" => 40
        ));
        $myPicture->drawGradientArea(0, 0, $width, $hight, DIRECTION_HORIZONTAL, array (
                "StartR" => 240,
                "StartG" => 240,
                "StartB" => 240,
                "EndR" => 180,
                "EndG" => 180,
                "EndB" => 180,
                "Alpha" => 10
        ));

        /* Set the default font properties */
        $myPicture->setFontProperties(array (
                "FontName" => PCHART . "/fonts/verdana.ttf",
                "FontSize" => 12
        ));

        /* Draw the scale and the chart */
        $myPicture->setGraphArea(60, 20, $width - 20, $hight - 50);
        $myPicture->drawScale(array (
                "DrawSubTicks" => TRUE,
                "Mode" => SCALE_MODE_ADDALL
        ));
        $myPicture->setShadow(TRUE, array (
                "X" => 1,
                "Y" => 1,
                "R" => 0,
                "G" => 0,
                "B" => 0,
                "Alpha" => 10
        ));
        $settings = array (
                "DisplayValues" => TRUE,
                "DisplayColor" => DISPLAY_AUTO,
                "Gradient" => TRUE,
                "GradientMode" => GRADIENT_EFFECT_CAN,
                "Surrounding" => 30
        );
        /* Write the chart legend */
        $myPicture->drawLegend(40, $hight - 25, array (
                "Style" => LEGEND_NOBORDER,
                "Mode" => LEGEND_HORIZONTAL
        ));

        $myPicture->setShadow(TRUE);
        $myPicture->drawBarChart($settings);

        $myPicture->render($filename);
    }
}

if (! function_exists('pdf_per_month_page')) {

    /**
     * Enter description here .
     * ..
     *
     * @param unknown_type $pdf
     * @param unknown_type $data
     */
    function pdf_per_month_page(& $pdf, $year, $data, $title, $type = "planeur") {
        $pdf->AddPage();
        $pdf->title($title);

        $w = array (
                20,
                15,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12,
                12
        );
        $align = array (
                'L',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R',
                'R'
        );

        $pdf->table($w, 8, $align, $data);

        $pdf->title($title);

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Image(image_dir() . $type . "_mois_$year.png", $x + 15, $y, 150);
    }
}

if (! function_exists('no_file_or_file_too_old')) {

    /**
     * Retourne vrai quand le fichier n'éxiste pas ou est plus vieux que la référence
     *
     * @param unknown_type $filename
     * @param unknown_type $epoch
     */
    function no_file_or_file_too_old($filename, $epoch) {
        if (! file_exists($filename)) {
            return TRUE;
        }
        $stat = stat($filename);
        $mtime = $stat ['mtime'];
        // echo "flight epoch=$epoch, date=" .date(DATE_RFC822, $epoch) . br();;
        // echo "file mtime=$mtime, date=" .date(DATE_RFC822, $mtime) . br();;
        if ($epoch > $mtime) {
            // Le fichier est plus vieux que les vols il faut le re-générer
            return TRUE;
        }
        return FALSE;
    }
}

if (! function_exists('machine_barchart')) {
    /**
     * Barchart par machines
     */
    function machine_barchart($filename, $data, $title) {
        // var_dump($filename);
        // var_dump($data);

        /* Create and populate the pData object */
        $MyData = new pData();
        $MyData->loadPalette(PCHART . "palettes/blind2.color", TRUE);

        // Sauvegarde de la première ligne
        $header = array_shift($data);

        // lignes de données
        foreach ( $data as $row ) {
            $label = $row [1];
            array_shift($row);
            array_shift($row);
            array_shift($row);
            $MyData->addPoints($row, $label);
        }

        $MyData->setAxisName(0, $title);

        array_shift($header); // supprime model
        array_shift($header); // supprime immat
        array_shift($header); // supprime total

        $MyData->addPoints($header, "Labels");
        $MyData->setSerieDescription("Labels", "Mois");
        $MyData->setAbscissa("Labels");

        // $MyData->setSerieDrawable("Total", FALSE);

        /* Create the pChart object */
        $width = 900;
        $hight = 600;
        $myPicture = new pImage($width, $hight, $MyData);
        $myPicture->drawGradientArea(0, 0, $width, $hight, DIRECTION_VERTICAL, array (
                "StartR" => 240,
                "StartG" => 240,
                "StartB" => 240,
                "EndR" => 180,
                "EndG" => 180,
                "EndB" => 180,
                "Alpha" => 40
        ));
        $myPicture->drawGradientArea(0, 0, $width, $hight, DIRECTION_HORIZONTAL, array (
                "StartR" => 240,
                "StartG" => 240,
                "StartB" => 240,
                "EndR" => 180,
                "EndG" => 180,
                "EndB" => 180,
                "Alpha" => 10
        ));

        /* Set the default font properties */
        $myPicture->setFontProperties(array (
                "FontName" => PCHART . "/fonts/verdana.ttf",
                "FontSize" => 12
        ));

        /* Draw the scale and the chart */
        $myPicture->setGraphArea(60, 20, $width - 20, $hight - 50);
        $myPicture->drawScale(array (
                "DrawSubTicks" => TRUE,
                "Mode" => SCALE_MODE_ADDALL
        ));
        $myPicture->setShadow(TRUE, array (
                "X" => 1,
                "Y" => 1,
                "R" => 0,
                "G" => 0,
                "B" => 0,
                "Alpha" => 10
        ));
        $settings = array (
                "DisplayValues" => TRUE,
                "DisplayColor" => DISPLAY_AUTO,
                "Gradient" => TRUE,
                "GradientMode" => GRADIENT_EFFECT_CAN,
                "Surrounding" => 30
        );
        /* Write the chart legend */
        $myPicture->drawLegend(40, $hight - 25, array (
                "Style" => LEGEND_NOBORDER,
                "Mode" => LEGEND_HORIZONTAL
        ));

        $myPicture->drawStackedBarChart($settings);
        $myPicture->render($filename);
    }
}

if (! function_exists('date_m25ans')) {
    /**
     * date_m25ans
     *
     * retourne la date de naissance de basculement +/- 25 ans en fonctin de l'année
     * Format MySQL
     *
     * Ex Vincent né le 7/7/1988 => anniversaire 25 ans = 7/7/2013
     * donc en 2013 tous les pilotes né après le 1/1/1988 ont moins de 25 ans
     */
    function date_m25ans($year) {
        return ($year - 25) . '-01-01';
    }
}