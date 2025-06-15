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

if (! function_exists('checkbox_array')) {
    /**
     * génère un élément d'un tableau de checkbox
     *
     * @param $array_name nom
     *            du tableau
     *            @value idex ?
     *            $array_val valeurs initiales
     */
    function checkbox_array($array_name, $value, $array_val) {
        $default = ($array_val) ? array_key_exists($value, $array_val) : FALSE;
        return form_checkbox($array_name . '[]', $value, set_checkbox($array_name, $value, $default));
    }
}

if (! function_exists('input_field')) {
    /**
     *
     * Concaténation du champ et du message d'erreur du formulaire
     *
     * @param unknown_type $name
     * @param unknown_type $value
     * @param unknown_type $attrs
     * @deprecated
     *
     */
    function input_field($name, $value, $attrs) {
        $attrs['name'] = $name;
        $attrs['value'] = $value;
        return form_input($attrs) . form_error($name);
    }
}

/**
 * Generate a dropdown list
 * 
 * $name: name of the select
 * $options: array of values in keyy=>value
 * $value: current value of the select
 * $attrs: string attributes
 * 
 * $pilot = dropdown_field('mlogin', $mlogin, $pilote_selector, "id='mlogin' ");
 */
if (! function_exists('dropdown_field')) {
    function dropdown_field($name, $value, $options, $attrs) {
        if (!$attrs) {
            $attrs = 'class="form-control big_select" ';
        }
        // $res = ($value) ? form_dropdown($name, $options, $value, $attrs) : "";
        $res = form_dropdown($name, $options, $value, $attrs);
        return $res;
    }
}

if (! function_exists('checkbox_field')) {
    function checkbox_field($name, $value, $default) {
        return form_checkbox($name, 1, $value);
    }
}

if (! function_exists('radio_field')) {
    /*
     * Generate a set of radio button
     * $name: variable to sat in the form
     * $default initial value
     * $values = set of values
     *
     * ex: radio_field ('general' , 1, array('Détaillée' => 0, 'Générale' => 1))
     */
    function radio_field($name, $default, $values, $attrs = "") {
        $res = "";
        // $attrs = 'readonly="readonly"';
        foreach ($values as $key => $value) {
            if ($default == $value) {
                $res .= $key . ":" . form_radio($name, $value, TRUE, $attrs);
            } else {
                $res .= $key . ":" . form_radio($name, $value, FALSE, $attrs);
            }
            $res .= nbs();
        }
        return $res;
    }
}

if (! function_exists('enumerate_radio_fields')) {
    /*
     * Generate a set of radio field from an enumerate
     *
     * Example:
     * enumerate_radio_fields(array(0 => 'Tous', 1 => 'Club', 2 => 'Privé', 3 => 'Extérieur'), 'filter_proprio', $filter_proprio);
     * generates:
     * Tous <input type="radio" name="filter_proprio" value="0" checked="checked" id="Tous" />&nbsp;&nbsp;
     * Club <input type="radio" name="filter_proprio" value="1" id="Club" />&nbsp;&nbsp;
     * Privé <input type="radio" name="filter_proprio" value="2" id="Privé" />&nbsp;&nbsp;
     * Extérieur <input type="radio" name="filter_proprio" value="3" id="Extérieur" />&nbsp;
     */
    function enumerate_radio_fields($values, $name, $value, $mode = "ro", $attrs = array()) {
        $res = "";
        foreach ($values as $key => $val) {
            $attrs['name'] = $name;
            $attrs['id'] = $val;
            $attrs['value'] = $key;
            $attrs['checked'] = ($key == $value);
            /*
            array (
                    'name' => $name,
                    'id' => $val,
                    'value' => $key,
                    'checked' => ($key == $value)
            )
            */
            $res .= nbs() . $val . nbs() . form_radio($attrs);
            $res .= nbs();
        }
        return $res;
    }
}

if (! function_exists('display_form_table')) {
    /**
     *
     * Affiche les champs d'un formulaire dans un tableau
     *
     * @param unknown_type $table
     * @deprecated
     *
     */
    function display_form_table($table) {
        echo "<table>\n";
        foreach ($table as $row) {
            echo "\t<tr>\n";
            $first_cell = TRUE;
            foreach ($row as $cell) {
                if ($first_cell) {
                    echo "\t\t<td align=\"right\">\n";
                    $first_cell = FALSE;
                } else {
                    echo "\t\t<td align=\"left\">\n";
                }
                echo $cell;
                echo "\t\t</td>\n";
            }
            echo "\t</tr>\n";
        }
        echo "</table>\n";
    }
}

if (! function_exists('account_header')) {
    /**
     * Return the header line of an account into an array
     */
    function account_header() {
        $CI = &get_instance();
        $CI->lang->load('comptes');
        return $CI->lang->line('comptes_table_header');
    }
}

if (! function_exists('account_rows')) {
    /**
     * Return the rows of an account into an array
     */
    function account_rows($rows) {
        $CI = &get_instance();
        $CI->load->library('ButtonView');

        $table = array();
        $index = 0;
        foreach ($rows as $row) {
            $table[$index][] = $row['codec'];
            $table[$index][] = $row['nom'];
            if (isset($row['count'])) {
                $table[$index][] = $row['count'];
            } else {
                $table[$index][] = '';
            }
            $table[$index][] = euro($row['debit']);
            $table[$index][] = euro($row['credit']);

            if ($row['solde'] < 0) {
                $table[$index][] = euro(abs($row['solde']));
                $table[$index][] = nbs(11);
            } else {
                $table[$index][] = nbs(11);
                $table[$index][] = euro($row['solde']);
            }
            $button = new ButtonView(array(
                'label' => 'Voir',
                'action' => 'journal_compte',
                'controller' => 'compta',
                'param' => $row['id']
            ));

            if (isset($row['count'])) {
                if ($row['count'] > 1) {
                    $button = new Button(array(
                        'label' => 'Voir',
                        'image' => theme() . "/images/eye.png",
                        'action' => 'balance',
                        'controller' => 'comptes',
                        'param' => $row['codec']
                    ));
                }
            }
            $table[$index][] = $button->image();
            $index++;
        }
        return $table;
    }
}

if (! function_exists('account_sums')) {
    /**
     * Return the sum rows of an account into an array
     */
    function account_sums($row, $title) {
        $table = array();
        $index = 0;
        $solde = $row['total_credit'] - $row['total_debit'];
        $table[$index][] = nbs();
        $table[$index][] = $title;
        $table[$index][] = nbs();
        $table[$index][] = euro($row['total_debit']);
        $table[$index][] = euro($row['total_credit']);
        if ($solde < 0) {
            $table[$index][] = euro(abs($solde));
            $table[$index][] = nbs(11);
        } else {
            $table[$index][] = nbs(11);
            $table[$index][] = euro($solde);
        }
        return $table;
    }
}

/*
 * Transforme une table de hash indexé par une clé en liste des valeurs
 */
if (! function_exists('to_list')) {
    function to_list($hash) {
        $list = array();
        foreach ($hash as $row) {
            $list[] = $row;
        }
        return $list;
    }
}

if (! function_exists('result_rows')) {

    /**
     * Return the rows of an account into an array
     * params:
     * $actif = hash des comptes actifs, clé = compte id
     * $passif = hash des comptes passif, clé = compte id
     * 
     * $rows = result_rows($bilan ['dispo'], array (), $bilan_prec ['dispo'], array (), $html, $sep);
     */
    function result_rows($actif, $passif, $actif_prec = array(), $passif_prec = array(), $with_links = TRUE, $sep = ',') {
        $CI = &get_instance();
        $CI->load->library('ButtonView');

        if (!$actif && !$passif) {
            return array(array('', '', '', '', '', ''));
        }

        $output_format = ($with_links) ? "html" : "csv";
        /*
         * echo ("counts actif=" . count($actif) .
         * ", passif=" . count($passif) .
         * ", actifs prec=" . count($actif_prec) .
         * ", passifs prec=" . count($passif_prec)); exit;
         */
        $actif = to_list($actif);
        $passif = to_list($passif);

        $tab = ($with_links) ? nbs(6) : "";
        $nb_charges = count($actif);
        $nb_produits = count($passif);
        $max = max($nb_charges, $nb_produits);
        $table = array();
        for ($i = 0; $i < $max; $i++) {
            $param = (isset($actif[$i]['code'])) ? $actif[$i]['code'] : '';
            if ($i < $nb_charges) {
                $compte = $actif[$i]['compte'];
                // on affiche plus les codes
                // $table[$i][] = $actif[$i]['code'];
                if ($with_links) {
                    if ($actif[$i]['nom'] == "Immobilisations corporelles") {
                        $table[$i][] = anchor(controller_url("comptes/page/2/28"), "Immobilisations corporelles");
                    } else if ($actif[$i]['nom'] == "Comptes financiers") {
                        $table[$i][] = anchor(controller_url("comptes/page/5/6"), "Comptes de banque et financiers");
                    } else {
                        $table[$i][] = anchor(controller_url("compta/journal_compte/$compte"), $actif[$i]['nom']);
                    }
                } else {
                    $table[$i][] = $actif[$i]['nom'];
                }

                // Amortissements
                $table[$i][] = $tab;

                // Valeur nette année N
                $table[$i][] = $tab;

                // Valeur nette année N
                $table[$i][] = euro($actif[$i]['debit'] - $actif[$i]['credit'], $sep, $output_format);

                // Valeur nette année N - 1
                if (isset($actif_prec[$compte])) {
                    $prec = $actif_prec[$compte];
                    $table[$i][] = euro($prec['debit'] - $prec['credit'], $sep, $output_format);
                } else {
                    $table[$i][] = $tab;
                }
            } else {
                $table[$i][] = $tab;
                $table[$i][] = $tab;
                $table[$i][] = $tab;
                $table[$i][] = $tab;
                $table[$i][] = $tab; // N - 1
            }

            $table[$i][] = $tab; // séparateur milieux

            if ($i < $nb_produits) {
                $compte = $passif[$i]['compte'];
                // On affiche plus les codes
                // $table[$i][] = $passif[$i]['code'];
                if ($with_links) {
                    $table[$i][] = anchor(controller_url("compta/journal_compte/$compte"), $passif[$i]['nom']);
                } else {
                    $table[$i][] = $passif[$i]['nom'];
                }
                $table[$i][] = euro($passif[$i]['credit'] - $passif[$i]['debit'], $sep, $output_format);

                // passif N - 1
                if (isset($passif_prec[$compte])) {
                    $prec = $passif_prec[$compte];
                    $table[$i][] = euro($prec['credit'] - $prec['debit'], $sep, $output_format);
                } else {
                    $table[$i][] = $tab;
                }
            } else {
                $table[$i][] = $tab;
                $table[$i][] = $tab;
                $table[$i][] = $tab; // passif N - 1
            }
        }
        return $table;
    }
}

if (! function_exists('highlight')) {
    function highlight($str, $html) {
        if (! $html)
            return $str;
        return '<h4>' . $str . '</h4>';
    }
}

if (! function_exists('bilan_table')) {
    /**
     * Transforme les données du bilan en table qui puisse être affiché ou exporté
     *
     * @param unknown $bilan
     * @param $bilan_prec
     * @param boolean $html
     */
    function bilan_table($bilan, $bilan_prec, $html) {
        $CI = &get_instance();
        $CI->lang->load('comptes');
        // $CI->lang->line('')

        $tab = ($html) ? nbs(6) : "";
        $sep = ($html) ? '.' : ',';
        $output_format = ($html) ? "html" : "csv";

        $year = $bilan['year'];
        $table = array();          // resultat

        // ligne de titres
        $table[] = array(
            $CI->lang->line('comptes_bilan_actif'),
            $CI->lang->line('comptes_bilan_valeur_brute'),
            $CI->lang->line('comptes_bilan_ammortissement'),
            $CI->lang->line('comptes_bilan_valeur_nette'),
            $CI->lang->line('comptes_bilan_valeur_nette'),
            $tab,
            $CI->lang->line('comptes_bilan_passif'),
            "",
            $tab
        );

        // ligne d'années
        $table[] = array(
            $tab,
            $tab,
            $tab,
            highlight($year, $html),
            highlight($year - 1, $html),
            $tab,
            $tab,
            highlight($year, $html),
            highlight($year - 1, $html)
        );

        // immo ligne 1
        $imo_label = highlight($CI->lang->line('comptes_bilan_immobilise'), $html);
        $fa_label = highlight($CI->lang->line('comptes_bilan_fonds_propres'), $html);
        $table[] = array(
            $imo_label,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $fa_label,
            $tab,
            $tab
        );

        // immos ligne 2
        $imo_label = highlight($CI->lang->line('comptes_bilan_immobilisations_corp'), $html);
        if ($html) {
            $imo_label = anchor(controller_url("comptes/page/2/28"), $imo_label);
        }
        $fa_label = highlight($CI->lang->line('comptes_bilan_fonds_associatifs'), $html);
        if ($html) {
            $fa_label = anchor(controller_url("comptes/page/102"), $fa_label);
        }
        $report_deb_label = $CI->lang->line('comptes_bilan_report_debt');
        if ($html) {
            $report_deb_label = anchor(controller_url("comptes/page/119"), $report_deb_label);
        }
        $report_cred_label = $CI->lang->line('comptes_bilan_report_cred');
        if ($html) {
            $report_cred_label = anchor(controller_url("comptes/page/110"), $report_cred_label);
        }

        $amo = euro($bilan['ammortissements_corp'], $sep, $output_format);
        if ($html) {
            $amo = anchor(controller_url("comptes/page/281"), $amo);
        }
        $valeur_nette_immo = euro($bilan['valeur_nette_immo_corp'], $sep, $output_format);
        $table[] = array(
            $imo_label,
            euro($bilan['valeur_brute_immo_corp'], $sep, $output_format),
            $amo,
            $valeur_nette_immo,
            euro($bilan_prec['valeur_nette_immo_corp'], $sep, $output_format),
            $tab,
            $fa_label,
            euro($bilan['fonds_associatifs'], $sep, $output_format),
            euro($bilan_prec['fonds_associatifs'], $sep, $output_format)
        );

        // reports à nouveau créditeur
        $table[] = array(
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $report_cred_label,
            euro($bilan['reports_cred'], $sep, $output_format),
            euro($bilan_prec['reports_cred'], $sep, $output_format)
        );

        // report à nouveau débiteur
        $table[] = array(
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $report_deb_label,
            euro($bilan['reports_deb'], $sep, $output_format),
            euro($bilan_prec['reports_deb'], $sep, $output_format)
        );

        // résultat
        $table[] = array(
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $CI->lang->line('comptes_bilan_resultat'),
            euro($bilan['resultat'], $sep, $output_format),
            euro($bilan_prec['resultat'], $sep, $output_format)
        );

        // titres Disponible et dette court terme
        $imo_label = highlight($CI->lang->line('comptes_bilan_dispo'), $html);
        $fa_label = highlight($CI->lang->line('comptes_bilan_dettes_court_terme'), $html);
        $table[] = array(
            $imo_label,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $fa_label,
            $tab,
            $tab
        );

        // ligne Créances et dettes de tiers
        $table[] = array(
            ($html) ? anchor(controller_url("comptes/page/4/5/1"), $CI->lang->line('comptes_bilan_creances_tiers')) : $CI->lang->line('comptes_bilan_creances_tiers'),
            $tab,
            $tab,
            euro($bilan['creances_pilotes'], $sep, $output_format),
            euro($bilan_prec['creances_pilotes'], $sep, $output_format),
            $tab,
            ($html) ? anchor(controller_url("comptes/page/4/5/1"), $CI->lang->line('comptes_bilan_dettes_tiers')) : $CI->lang->line('comptes_bilan_dettes_tiers'),
            euro($bilan['dettes_pilotes'], $sep, $output_format),
            euro($bilan_prec['dettes_pilotes'], $sep, $output_format)
        );

        // comptes de banque et emprunts
        foreach ($bilan['dispo'] as $key => $row) {
            $bilan['dispo'][$key]['nom'] = $CI->lang->line('comptes_bilan_comptes_financiers');
        }

        $rows = result_rows($bilan['dispo'], array(), $bilan_prec['dispo'], array(), $html, $sep);

        $str_loan = ($html) ? anchor(controller_url("comptes/page/16/17/1"), $CI->lang->line('comptes_bilan_dettes_banques')) : $CI->lang->line('comptes_bilan_dettes_banques');

        $rows[0][6] = $str_loan;
        $rows[0][7] = euro($bilan['emprunts'], $sep, $output_format); // "emprunts n";
        $rows[0][8] = euro($bilan_prec['emprunts'], $sep, $output_format); // "emprunts n - 1";
        $table = array_merge($table, $rows);

        $imo_label = highlight($CI->lang->line('comptes_bilan_total'), $html);
        $table[] = array(
            $imo_label,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab,
            $tab
        );

        $table[] = array(
            $CI->lang->line('comptes_bilan_total_actif'),
            $tab,
            $tab,
            euro($bilan['total_actif'], $sep, $output_format),
            euro($bilan_prec['total_actif'], $sep, $output_format),
            $tab,
            $CI->lang->line('comptes_bilan_total_passif'),
            euro($bilan['total_passif'], $sep, $output_format),
            euro($bilan_prec['total_passif'], $sep, $output_format)
        );
        return $table;
    }
}

if (! function_exists('button_bar2')) {
    /**
     * Generate a button bar
     *
     * C'est la plus utilisée, mais elle doit être remplacée par la version 4
     */
    function button_bar2($controller, $list = array()) {
        $res = "" . br();
        $res .= form_open(controller_url($controller), array(
            'id' => 'button'
        ));

        foreach ($list as $value => $name) {
            // echo "name=$name, value=$value" . br();
            $res .= form_input(array(
                'type' => 'submit',
                'name' => $name,
                'value' => $value,
                'class' => 'jbutton btn btn-secondary'
            ));
        }
        $res .= form_close();
        return $res;
    }
}

if (! function_exists('button_bar4')) {
    /**
     * Return a button bar.
     *
     * This one only generates an array of anchor. It is the simplest one and the one that
     * should be used everywhere
     *
     * $list = hash with the following entries: 'url', 'label', 'role', 'class'
     */
    function button_bar4($list = array()) {
        $CI = &get_instance();
        $res = '<div class="ui-widget ui-buttonbar">';
        $res .= '<div class="ui-widget-header ui-corner-top">';
        $res .= "<table><tr>\n";
        foreach ($list as $button) {
            $url = isset($button['url']) ? $button['url'] : "";
            $label = isset($button['label']) ? $button['label'] : $url;
            $attrs = isset($button['class']) ? $button['class'] : 'class="jbutton btn"';
            $role = isset($button['role']) ? $button['role'] : '';
            $type = isset($button['type']) ? $button['type'] : '';
            $id = isset($button['id']) ? $button['id'] : '';
            $name = isset($button['name']) ? $button['name'] : '';

            if (! $CI->dx_auth->is_admin()) {
                if ($role != '' && ! $CI->dx_auth->is_role($role, true, true)) {
                    // skip the button
                    continue;
                }
            }
            $res .= "\t<td>";
            if ($type == 'submit') {
                $res .= form_input(array(
                    'type' => 'submit',
                    'id' => $id,
                    'name' => $name,
                    'value' => $label,
                    'class' => 'jbutton btn'
                ));
            } else {
                $res .= anchor($url, $label, $attrs);
            }
            $res .= "</td>\n";
        }
        $res .= "</tr></table>";
        $res .= "</div>";
        $res .= "</div>";
        return $res;
    }
}

if (! function_exists('validation_button')) {
    /**
     * Affiche un boutton de validation
     */
    function validation_button($action, $with_delete = FALSE) {
        $CI = &get_instance();
        $res = "";
        if ($action != VISUALISATION) {
            if ($action == CREATION) {
                $res .= "<table><tr><td>\n";
                $res .= form_input(array(
                    'type' => 'submit',
                    'name' => 'button',
                    'id' => 'validate',
                    'value' => $CI->lang->line("gvv_button_create"),
                    'class' => 'btn btn-primary mt-3'
                ));

                $res .= "</td><td>";
                $res .= form_input(array(
                    'type' => 'submit',
                    'name' => 'button',
                    'id' => 'validate_continue',
                    'value' => $CI->lang->line("gvv_button_create_and_continue"),
                    'class' => 'btn btn-primary mt-3'
                ));
                /*
                 * Abandon n'est pas vraiment utile pour une application WEB
                 * $res .= "</td><td>";
                 * $res .= form_input(array('type' => 'submit', 'name' => 'button', 'value' => 'Abandonner'));
                 */
                $res .= "</td></tr></table>\n";
            } else {
                $txt = $CI->lang->line("gvv_button_confirm");
                $attrs = "onclick=\"return confirm('$txt')\" ";
                $res .= "<table><tr><td>\n";
                $res .= form_input(array(
                    'type' => 'submit',
                    'name' => 'button',
                    'id' => 'validate',
                    'value' => $CI->lang->line("gvv_button_validate"),
                    'class' => 'btn btn-primary mt-3'
                ));

                if ($with_delete) {
                    $res .= "</td><td>";
                    $res .= form_input(array(
                        'type' => 'submit',
                        'name' => 'button',
                        'id' => 'delete',
                        'value' => $CI->lang->line("gvv_button_delete"),
                        'onclick' => "return confirm('$txt')",
                        'class' => 'btn btn-primary mt-3'
                    ));
                }
                /*
                 * Abandon n'est pas vraiment utile pour une application WEB
                 * $res .= "</td><td>";
                 * $res .= form_input(array('type' => 'submit', 'name' => 'button', 'value' => 'Abandonner'));
                 */
                $res .= "</td><td>";
                $res .= "</td></tr></table>\n";
            }
        }
        return $res;
    }
}

if (! function_exists('filter_buttons')) {
    function filter_buttons() {
        $CI = &get_instance();

        $filter_active = $CI->session->userdata('filter_active');

        $res = "";
        $res .= '<!-- Bouttons filtrer, afficher tout -->';

        $lab1 = $CI->lang->line("gvv_str_select");
        $lab2 = $CI->lang->line("gvv_str_display");
        if ($filter_active) {
            $res .= '<div class="d-flex align-items-center">';
            $res .= '<input type="submit" name="button" value="' . $lab1 . '" class="btn btn-warning rounded me-2" />';
            $res .= '<input type="submit" name="button"  value="' . $lab2 . '" class="btn btn-secondary rounded" />';
            $res .= '<p class="mb-0 ms-3">Filtre actif</p>';
            $res .= '</div>';       
         } else {
            $res .= '<input type="submit" name="button"  value="' . $lab1 . '" class="btn btn-secondary rounded me-2" />';
            $res .= '<input type="submit" name="button"  value="' . $lab2 . '" class="btn btn-secondary rounded" />';
        }
        return $res;
    }
}

if (! function_exists('year_selector')) {
    /**
     * Affiche un sélécteur d'année
     *
     * @param unknown_type $controller
     * @param unknown_type $year
     * @param unknown_type $year_selector
     * @return string
     */
    function year_selector($controller, $year, $year_selector, $with_all = false) {
        if (! array_key_exists($year, $year_selector)) {
            $year_selector[$year] = (string) $year;
        }
        $CI = &get_instance();

        if ($with_all) {
            $year_selector['all'] =  $CI->lang->line("gvv_toutes") . "...";
        }
        $url = controller_url($controller);
        $res = '<input type="hidden" name="controller_url" value="' . $url . '" />';
        $res .= nbs() . $CI->lang->line("gvv_year") . " "
            . dropdown_field('year', $year, $year_selector, "id='year_selector' onchange=new_year();");

        return $res;
    }
}

if (! function_exists('licence_selector')) {
    /**
     * Affiche un sélécteur d'année
     *
     * @param unknown_type $controller
     * @param unknown_type $year
     * @return string
     */
    function licence_selector($controller, $type) {
        $licence_selector = array(
            0 => "Cotisation",
            1 => "Licence/Assurance planeur",
            2 => "Licence/Assurance avion",
            3 => "Licence/Assurance ULM"
        );
        $res = form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
        $res .= nbs() . "Type de licence " . dropdown_field('licence', $type, $licence_selector, "id='licence_selector' onchange=new_licence();");
        return $res;
    }
}

if (! function_exists('checkalert')) {
    /**
     * Affiche une alerte Javascript
     */
    function checkalert($session, $popup = '') {
        $flash = $session->flashdata('popup');

        // echo "popup=$popup, flash=$flash" . br();
        if ($popup == '') {
            $popup = $flash;
        }
        $res = "";
        if ($popup) {
            $res .= '<script language="JavaScript">';
            $res .= '<!--' . "\n";
            $res .= "alert(\"$popup\");" . "\n";
            $res .= '//-->' . "\n";
            $res .= '</script>';
        }
        return $res;
    }
}

if (! function_exists('add_first_row')) {
    /**
     * Ajoute une ligne initiale à un tableau de tableau
     */
    function add_first_row(&$table, $first_row) {
        if (count($table) == 0) {
            $table = array(
                $first_row
            );
            return 1;
        }
        return array_unshift($table, $first_row);
    }
}

if (! function_exists('add_first_col')) {
    /**
     * Ajoute une colonne initiale à un tableau de tableau
     */
    function add_first_col(&$table, $first_col) {
        $i = 0;
        for ($j = 0; $j < count($table); $j++) {
            if (isset($first_col[$i])) {
                array_unshift($table[$j], $first_col[$i]);
            }
            $i++;
        }
        return $i;
    }
}

if (! function_exists('label')) {
    /**
     * Ajoute une ligne initiale à un tableau de tableau
     */
    function label($label, $attrs = array()) {
        $res = '<label';
        foreach ($attrs as $key => $value) {
            $res .= " $key=\"$value\"";
        }
        $res .= ">";

        $CI = &get_instance();
        $translation = $CI->lang->line($label);
        if ($translation) {
            $label = $translation;
        }
        $res .= $label;
        $res .= "</label>" . ":" . nbs();
        return $res;
    }
    function e_label($label, $attrs = array()) {
        echo label($label, $attrs);
    }
}

if (! function_exists('e_form_dropdown')) {
    function e_form_dropdown($name, $options, $selected, $extra) {
        echo form_dropdown($name, $options, $selected, $extra);
    }
}

if (! function_exists('translation')) {

    /**
     *
     * @param unknown_type $title_id
     */
    function translation($title_id = '') {
        $CI = &get_instance();
        $translated = $CI->lang->line($title_id);
        return ($translated) ? $translated : $title_id;
    }
}
