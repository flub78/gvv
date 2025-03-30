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
 */
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
function date_db2ht($datedb) // retourne une date depuis MySQL au format aaaa-mm-dd vers le format d'affichage dd/mm/aaaa
{
    $str = $datedb;

    // echo "date_db2ht = $str" . br();

    if ($datedb == '')
        return '';

    $pattern = '%((19|20)[0-9]{2})\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])%';

    if (preg_match($pattern, $datedb, $matches)) {
        $day = $matches[4];
        $month = $matches[3];
        $year = $matches[1];
        $str = $day . "/" . $month . "/" . $year;
        return $str;
        echo "match $str" . br();
    }

    if ($str == "0000-00-00") {
        return "";
    }
    return $str;
}

/**
 * Traduit une date au format "JJ/MM/AAAA" au format "AAAA-MM-JJ"
 *
 * @param unknown $datedb
 * @return string
 */
function date_ht2db($datedb) // retourne une date depuis le format d'affichage dd/mm/aaaa vers le format aaaa-mm-jj
{
    if ($datedb == "")
        return "";

    // to match french dates with day and month on one or two digits
    $date_regexp = '%([0-9]|0[1-9]|[12][0-9]|3[01])[\/]([0-9]|0[1-9]|1[012])[\/]((19|20)[0-9]{2})%';

    if (preg_match($date_regexp, $datedb, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        return $year . "-" . $month . "-" . $day;
    } else {
        return $datedb;
    }
}

if (! function_exists('french_date_compare')) {
    /**
     *
     *
     * Enter description here ...
     *
     * @param unknown_type $date1
     * @param unknown_type $date2
     * @param unknown_type $operator
     */
    function french_date_compare($date1, $date2, $operator = '<') {
        $date_regexp = '%(0[1-9]|[12][0-9]|3[01])[\/](0[1-9]|1[012])[\/]((19|20)[0-9]{2})%';

        if (preg_match($date_regexp, $date1, $matches)) {
            $year1 = substr($date1, 6, 4);
            $month1 = substr($date1, 3, 2);
            $day1 = substr($date1, 0, 2);
        } else {
            throw new Exception("Format de date incorrect: $date1, format attendu JJ/MM/AAAA");
        }

        if (preg_match($date_regexp, $date2, $matches)) {
            $year2 = substr($date2, 6, 4);
            $month2 = substr($date2, 3, 2);
            $day2 = substr($date2, 0, 2);
        } else {
            throw new Exception("Format de date incorrect: $date2, format attendu JJ/MM/AAAA");
        }
        $time1 = mktime(0, 0, 0, $month1, $day1, $year1);
        $time2 = mktime(0, 0, 0, $month2, $day2, $year2);

        if ($operator == '<') {
            return ($time1 < $time2);
        } elseif ($operator == '<=') {
            return ($time1 <= $time2);
        } elseif ($operator == '>') {
            return ($time1 > $time2);
        } elseif ($operator == '>=') {
            return ($time1 >= $time2);
        } elseif ($operator == '==') {
            return ($time1 == $time2);
        } else {
            throw new Exception("french_date_compare, opérateur $operator non supporté.");
        }
    }
}

if (! function_exists('mysql_date')) {
    /**
     * Traduit une date au format MySQL.
     * Cette fonction devrait egalement accepter les dates nulles.
     *
     * @param unknown_type $date
     * @return boolean
     */
    function mysql_date($date) {
        $CI = &get_instance();

        $CI->lang->load('gvv');
        $CI->form_validation->set_message('valid_date', $CI->lang->line("valid_activity_date"));

        if ($date == '')
            return '';

        // $date_regexp = '(19|20)[0-9]{2}[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])';
        $date_regexp = '%(0*[1-9]|[12][0-9]|3[01])[- \/\.](0*[1-9]|1[012])[- \/\.]((19|20)[0-9]{2})%';
        if (preg_match($date_regexp, $date, $matches)) {
            // transformation en date MSQL
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
            // vérification que la date existe
            $date = $year . '-' . $month . '-' . $day;
            return $date;
        }
        return FALSE;
    }
}

if (! function_exists('mysql_time')) {
    /**
     * Traduit une heure au format MySQL.
     * Cette fonction devrait egalement accepter les temps nulles.
     *
     * @param unknown_type $date
     * @return boolean
     */
    function mysql_time($time) {
        $CI = &get_instance();
        $CI->lang->load('gvv');
        $CI->form_validation->set_message('valid_time', $CI->lang->line("valid_minute_time"));

        if ($time == '')
            return '00:00';

        $time_regexp = '%([012][0-9]|[0-9])[\:h\.]([0-9][0-9]|[0-9])%';
        if (preg_match($time_regexp, $time, $matches)) {
            // transformation en date MSQL
            $hours = $matches[1];
            $minutes = $matches[2];
            // vérification que la date existe
            $time = $hours . ':' . $minutes;
            return $time;
        }
        return FALSE;
    }
}

if (! function_exists('mysql_minutes')) {
    /**
     * Traduit une heure au format MySQL.
     * Cette fonction devrait egalement accepter les temps nulles.
     *
     * @param unknown_type $date
     * @return boolean
     */
    function mysql_minutes($time) {
        $CI = &get_instance();
        $CI->lang->load('gvv');
        $CI->form_validation->set_message('valid_time', $CI->lang->line("valid_minute_time"));

        if ($time == '')
            return 0;

        $time_regexp = '/(\d+)[\:|h|\.](\d+)/';
        if (preg_match($time_regexp, $time, $matches)) {
            // transformation en minutes
            $hours = $matches[1];
            $minutes = $matches[2];
            $res = 60 * $hours + $minutes;
            return $res;
        }
        return FALSE;
    }
}

if (! function_exists('minute_to_time')) {
    /**
     * Traduit une durée en minute en heure minute
     *
     * @param unknown_type $date
     * @return un chaine au format HHhMM
     */
    function minute_to_time($time) {
        $pattern = '/(\d+)\:(\d+)/';
        if (preg_match($pattern, $time)) {
            // time est déjà au format 
            gvv_debug("minute_to_time($time) = $time ");
            return $time;
        }
        // time est un floatant
        $minutes = intval($time) % 60;
        $hours = (intval($time) - $minutes) / 60;
        $res = sprintf("%02d:%02d", $hours, $minutes);
        gvv_debug("minute_to_time($time) = $res ");
        return $res;
    }
}

if (! function_exists('decimal_to_time')) {
    /**
     * Traduit une durée décimale en heure : minute
     *
     * @param unknown_type $date
     * @return un chaine au format HH:MM
     */
    function decimal_to_time($time) {
        $hours = floor($time);
        $minutes = ($time - floor($time)) * 100 + 0.0001;
        return sprintf("%02d:%02d", $hours, $minutes);
    }
}


if (! function_exists('euro')) {
    /**
     * Formatte un montant
     *
     * @param
     *            $montant
     * @param $separator decimal
     *            separator
     *            @target html or csv or pdf
     * @return boolean
     */
    function euro($montant, $separator = ',', $target = 'html') {
        if ($target == 'html') {
            $thousand_sep = '&nbsp;';
            $symbol = '&nbsp;€';
        } elseif ($target == 'pdf') {
            $thousand_sep = ' ';
            $symbol = '';
        } else {
            $thousand_sep = ' ';
            $symbol = '';
        }
        return number_format(floatval($montant), 2, $separator, $thousand_sep) . $symbol;
    }
}

if (! function_exists('decimal_to_hm')) {
    function decimal_to_hm($dec) {
        $hour = floor($dec);
        $min = round(60 * ($dec - $hour));

        // return the time formatted HH:MM:SS
        return $hour . "h" . lz($min);
    }

    // lz = leading zero
    function lz($num) {
        return (strlen($num) < 2) ? "0{$num}" : $num;
    }
}

/**
 *
 * @param unknown_type $pattern
 * @param unknown_type $nb
 */
if (! function_exists('line_of')) {
    function line_of($pattern, $nb = 1) {
        $txt = "";
        for ($i = 0; $i < $nb; $i++) {
            $txt .= $pattern;
        }
        return $txt;
    }
}
