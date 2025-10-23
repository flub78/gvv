<!-- VIEW: application/views/liens/message.php -->
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
 * Simple vue pour afficher un message à l'utilisateur
 * 
 */

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

echo '<div id="body" class="body ui-widget-content">';

if (isset($title))
	echo heading($title, 3);

if (isset($popup)) echo checkalert($this->session, $popup);

if (isset($text)) {
	// echo "<center>\n";
	echo(p($text));
	//echo "</center>";
}

echo '</div>';
?>

<script type="text/javascript">
function link(url, username, password)
{
    
    jQuery('#errcode').attr('innerHTML', "");
    
    // var encode = String(md5(jQuery('#password').val()));
    var encode = String(md5(password));
    //var url = './ajax/login_valid.php';
    var parametres = 'login=' + escape(username) + '&password=' + encode;

    alert(url + ': ' + username + ' -> ' + password + ', param=' + parametres);

    jQuery.ajax(
    {
            url:url,
            type: 'POST',
            data: parametres,
            success: function(transport)
            {
                var response = transport ;
                if (response == 'ok') 
                {
                    // document.location.href = "./accueil.php";
                    document.location.href = "https://aviation.meteo.fr/accueil.php";
                }
                else
                {
                    jQuery('#errcode').attr('innerHTML', response) ;
                }
            },
            error: function() { alert("ajax error"); }
        });
            
}

$(function () {
    // link('./ajax/login_valid.php', 'flubber', 'planeur');
    link('https://aviation.meteo.fr/ajax/login_valid.php', 'flubber', 'planeur');
});
</script>
