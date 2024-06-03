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
 * Formulaire de saisie d'une planche de vol planeur
 *
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('vols_planeur');

echo '<div id="body" class="body container-fluid">';

/*
 * if (isset($message)) {
 * echo p($message) . br();
 * }
 * echo checkalert($this->session, isset($popup) ? $popup : "");
 * echo validation_errors();
 */

/*
 * *****************************************************************************************
 * ************************ Affichage de la planche ************************************
 * *****************************************************************************************
 */

echo heading($this->lang->line("gvv_vols_planeur_logs_input") . " " . $vpdate . nbs() . $this->lang->line("gvv_vols_planeur_logs_at") . " " . $vplieudeco, 3);

$tabs = nbs(3);
$table = array ();
$row = 0;
$altitude = ($remorque_100eme) ? $this->lang->line("gvv_vols_planeur_label_centieme") : $this->lang->line("gvv_vols_planeur_label_alt");

$vols = $planche ['flights'];
$launch = $this->lang->line("gvv_launch_type");
$towing = $launch [3];
$winch = $launch [1];
$auto = $launch [2];
$ext = $launch [4];
$flight_type_selector = $this->config->item('categories_vol_planeur_short');

if (count($vols) > 0) {
    // --------- ligne d' entête --------------------
    echo "<TABLE>";
    echo "<TR><TD>";
    echo $this->lang->line("gvv_volsp_field_vpmacid");
    echo "</TD><TD>";
    echo $this->lang->line("gvv_volsp_field_vppilid");
    echo "</TD><TD>";
    echo $this->lang->line("gvv_volsp_field_vpdc");
    echo "</TD><TD>";
    echo $this->lang->line("gvv_volsp_field_instructeur");
    echo "</TD><TD>";
    echo $this->lang->line("gvv_vue_vols_planeur_short_field_vplieudeco");
    echo "</TD><TD>";
    echo $this->lang->line("gvv_vue_vols_planeur_short_field_vplieuatt");
    echo "</TD><TD>";
    echo $this->lang->line("gvv_vue_vols_planeur_short_field_vpduree");
    echo "</TD><TD>";
    echo $this->lang->line("gvv_vue_vols_planeur_short_field_launch");
    echo "</TD><TD>";
    echo "$altitude";
    echo "</TD><TD>";
    echo $this->lang->line("gvv_ticket");
    echo "</TD><TD>";

    echo $this->lang->line("gvv_vue_vols_planeur_short_field_remorqueur");
    echo "</TD><TD>";
    echo $this->lang->line("gvv_vue_vols_planeur_short_field_pilote_remorqueur");
    echo "</TD><TD>";
    echo $this->lang->line("gvv_vue_vols_planeur_short_field_type");
    echo "</TD><TD>";
    echo $this->lang->line("gvv_vue_vols_planeur_short_field_vpnumvi");
    echo "</TD><TD>";
    echo $this->lang->line("gvv_vue_vols_planeur_short_field_vpobs");
    echo "</TD><TD>";
    echo " "; // champs cachés + bouton de validation
    echo "</TD></TR>";

    // pour chaque ligne ouvrir un formulaire:

    $row = 0;
    foreach ( $vols as $vol ) {
        ++ $row;
        $divdur = "dur$row";
        echo "<TR id='tr$row'><TD>"; // machine
        echo form_open(controller_url($controller) . "/formValidation/1", array (
                'name' => 'saisie',
                'id' => "saisie$row",
                'onsubmit' => 'targetpop(this)'
        ));
        echo $this->gvvmetadata->input_field("volsp", 'vpmacid', $vol ['glider']);

        echo "</TD><TD>"; // pilote planeur

        echo $this->gvvmetadata->input_field("volsp", 'vppilid', $vppilid);

        echo "</TD><TD>"; // DC

        // $attrs = array('onChange' => "instonoff('$row')");
                          // echo $this->gvvmetadata->input_field("volsp", 'vpdc', false, "rw", $attrs);
        echo "<input type=\"checkbox\" name=\"vpdc\" value=\"1\" id=\"dc$row\" onChange=\"instonoff('$row')\" />";

        echo "</TD><TD>"; // instructeur

        echo "<DIV id=\"inst$row\" style=\"display:none\">";
        echo $this->gvvmetadata->input_field("volsp", 'vpinst', '');
        echo "</DIV>";

        echo "</TD><TD>"; // heure déco

        // $attrs = array('size' => "3",'onChange' => "calculp2('$row')");
                          // echo $this->gvvmetadata->input_field("volsp", 'vpcdeb', $vol['takeoff'], "rw", $attrs);
        echo "<input type=\"text\" name=\"vpcdeb\" value=\"${vol['takeoff']}\" onChange=\"calculp2('$row')\" size=\"2\" id=\"vpcdeb$row\" />";

        echo "</TD><TD>"; // heure attero

        // echo $this->gvvmetadata->input_field("volsp", 'vpcfin', $vol['glider_landing'], "rw", $attrs);
        echo "<input type=\"text\" name=\"vpcfin\" value=\"${vol['glider_landing']}\" onChange=\"calculp2('$row')\" size=\"2\" id=\"vpcfin$row\" />";

        echo "</TD><TD>"; // durée

        // $attrsd = array( 'size' => "53");
                          // echo $this->gvvmetadata->input_field("volsp", 'vpduree', $vol['glider_time'], 'r', $attrsd);
                          // echo "<input type=\"text\" id=\"$divdur\" name=\"vpduree\" value=\"${vol['glider_time']}\" size=\"2\" readonly=\"readonly\" />";
        $dec = $vol ['takeoff'];
        $att = $vol ['glider_landing'];
        $result = "";
        if ($dec != "" && $att != "") {
            $debe = intval($dec);
            $debd = ($dec - $debe) * 100;
            $fine = intval($att);
            $find = ($att - $fine) * 100;
            $diff = (($fine * 60) + $find) - (($debe * 60) + $debd);
            if ($diff > 0) {
                $rese = floor($diff / 60);
                $resd = round($diff - ($rese * 60));
                if ($resd < 10) {
                    $resdaff = "0" . $resd;
                } else {
                    $resdaff = $resd;
                }
                $result = "" . $rese . "h" . $resdaff;
            }
        }
        echo "<input type=\"text\" id=\"$divdur\" name=\"vpduree\" value=\"$result\" size=\"2\" readonly=\"readonly\"  />";

        echo "</TD><TD>"; // remorquage

        if ($vol ['plane'] != "")
            $remval = "3";
        else
            $remval = "";
        echo dropdown_field('vpautonome', "$remval", $launch, "");

        echo "</TD><TD>"; // altitude

        // echo $this->gvvmetadata->input_field("volsp", 'vpaltrem', '500');
        echo "<input type=\"text\" name=\"vpaltrem\" value=\"${vol['towplane_max_alt']}\" size=\"4\"  />";

        echo "</TD><TD>";

        echo $this->gvvmetadata->input_field("volsp", 'vpticcolle', false);
        echo "</TD><TD>";

        // avion remorqueur

        echo $this->gvvmetadata->input_field("volsp", 'remorqueur', $vol ['plane']);

        echo "</TD><TD>"; // pilote remorqueur

        echo $this->gvvmetadata->input_field("volsp", 'pilote_remorqueur', $pilote_remorqueur);

        echo "</TD><TD>"; // vol normal
        echo dropdown_field('vpcategorie', $vpcategorie, $flight_type_selector, "");

        echo "</TD><TD>"; // N° VI

        // echo $this->gvvmetadata->input_field("volsp", 'vpnumvi', '');
        echo "<input type=\"text\" name=\"vpnumvi\" value=\"\" size=\"3\"  />";

        echo "</TD><TD>"; // observations

        // echo $this->gvvmetadata->input_field("volsp", 'vpobs', '');
        echo "<input type=\"text\" name=\"vpobs\" value=\"\" size=\"15\" />";

        echo "</TD><TD>"; // champs cachés + bouton valisation
        echo form_hidden('vpid', 0);
        echo form_hidden('vpdate', $vpdate);
        echo form_hidden('saisie_par', $saisie_par);
        echo form_hidden('vplieudeco', $vplieudeco);
        echo form_hidden('vplieuatt', $vplieudeco);
        echo form_hidden('essence', 0);
        echo form_hidden('numlign', $row);
        // <input type="text" name="essence" value="0" id="essence" size="11" />
        echo "<div id=\"but$row\">";
        echo form_submit('button', $this->lang->line("gvv_button_logs_submitbutton"));
        echo "</div>";
        echo form_close();

        echo "</TD></TR>";
    }
    echo '</TABLE>';
} else {
    echo $this->lang->line("gvv_vols_planeur_logs_no_flights") . " $vpdate " . $this->lang->line("gvv_vols_planeur_logs_at") . nbs() . $vplieudeco;
}

echo '</div>';
echo "\n";
?>
<script type="text/javascript"
	src="<?php echo js_url('form_vols_planeur'); ?>"></script>


