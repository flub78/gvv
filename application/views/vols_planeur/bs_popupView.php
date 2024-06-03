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
 * Affichage après saisie vol via la planche automatique
 *
 * @package vues
 */


 
echo '<HTML><HEAD><meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<script type="text/javascript">
function oload() {
	window.opener.masqueligne('.$numligne.');
	
}

</script>
</HEAD><BODY onload="oload()"><div>';


if (isset($message)) {
    echo p($message) . br();
}

echo validation_errors();
echo $vol_ok;


echo '</div></BODY></HTML>';
