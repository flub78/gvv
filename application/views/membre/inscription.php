<!-- VIEW: application/views/membre/inscription.php -->
<?php
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');
?>
<div id="body" class="body ui-widget-content">
<FORM action="index.php?action=i" method="post">
<TABLE>
	<TR>
		<TD colspan="3">Membre: <select name="pilid" size="1"></select></TD>
	</TR>
	<TR>
		<TD colspan="2">Date mouvement</TD>
		<TD><INPUT size="15" maxlength="10" type="text" name="datemouv"
			value="2010-12-31"></TD>
	</TR>
	<TR>
		<TD colspan="2">Inscription ann&eacute;e:</TD>
		<TD><INPUT size="5" MAXLENGTH="4" type="text" name="annee"
			value="2011"></TD>
	</TR>
	<TR>
		<TD></TD>
		<TD colspan="2" align="center"><INPUT TYPE="radio" NAME="m25a"
			VALUE="1" CHECKED> -25ans | <INPUT TYPE="radio" NAME="m25a" VALUE="2">
		+25ans</TD>
	</TR>
	<TR>
		<TD bgcolor="#EEEEFF"><INPUT TYPE="radio" NAME="method" VALUE="1"
			CHECKED></TD>
		<TD colspan="2" bgcolor="#EEEEFF">Au d&eacute;tail...</TD>
	</TR>
	<TR>
		<TD></TD>
		<TD colspan="2" align="right"><INPUT type="checkbox" name="rem50"
			value=O>50% cot+F.fixes</TD>
	</TR>
	<TR>
		<TD></TD>
		<TD><INPUT type="checkbox" name="cotis" value=O>Cotisation:</TD>
		<TD><INPUT size="5" MAXLENGTH="7" type="text" name="cotisval"
			value="50">&euro;</TD>
	</TR>
	<TR>
		<TD></TD>
		<TD colspan="2"><INPUT type="checkbox" name="ffixe" value=O>Frais
		fixes: (-25ans 55 &euro; | +25ans 120 &euro;)</TD>
	</TR>
	<TR>
		<TD></TD>
		<TD><INPUT type="checkbox" name="assffvv" value=O>Assurance FFVV:</TD>
		<TD><INPUT size="5" MAXLENGTH="7" type="text" name="assffvvval"
			value="79.5">&euro;</TD>
	</TR>
	<TR>
		<TD></TD>
		<TD><INPUT type="checkbox" name="assffa" value=O>Assurance FFA:</TD>
		<TD><INPUT size="5" MAXLENGTH="7" type="text" name="assffaval"
			value="96">&euro;</TD>
	</TR>
	<TR>
		<TD></TD>
		<TD><INPUT type="checkbox" name="forf30h" value=O>Forfait 30H planeur:</TD>
		<TD>350 &euro;</TD>
	</TR>
	<TR>
		<TD></TD>
		<TD><INPUT type="checkbox" name="forfilli" value=O>Forfait H illim.
		planeur:</TD>
		<TD>600 &euro;</TD>
	</TR>
	<TR>
		<TD bgcolor="#EEEEFF"><INPUT TYPE="radio" NAME="method" VALUE="2"></TD>
		<TD colspan="2" bgcolor="#EEEEFF">Forfait Planeur 10 vols découvertes</TD>
	</TR>
	<TR>
		<TD></TD>
		<TD colspan="2">( -25ans 430 &euro;&nbsp;&nbsp;|&nbsp;&nbsp;+25ans 550
		&euro; )</TD>
	</TR>
	<TR>
		<TD bgcolor="#EEEEFF"><INPUT TYPE="radio" NAME="method" VALUE="3"></TD>
		<TD colspan="2" bgcolor="#EEEEFF">Forfait Planeur 2 ans jusqu'au
		l&acirc;ch&eacute;</TD>
	</TR>
	<TR>
		<TD></TD>
		<TD colspan="2">( -25ans 1500 &euro;&nbsp;&nbsp;|&nbsp;&nbsp;+25ans
		1600 &euro; )</TD>
	</TR>
	<TR>
		<TD colspan="3" bgcolor="#FFEEEE">R&eacute;glement:</TD>
	</TR>
	<TR>
		<TD></TD>
		<TD><INPUT type="checkbox" name="chq" value=O>Ch&egrave;que:</TD>
		<TD><INPUT size="5" MAXLENGTH="7" type="text" name="chqval">&euro;</TD>
	</TR>
	<TR>
		<TD></TD>
		<TD colspan="2" align="right">Num:<INPUT size="15" MAXLENGTH="15"
			type="text" name="chqnum"></TD>
	</TR>
	<TR>
		<TD></TD>
		<TD><INPUT type="checkbox" name="esp" value=O>Esp&egrave;ces:</TD>
		<TD><INPUT size="5" MAXLENGTH="7" type="text" name="espval">&euro;</TD>
	</TR>
</TABLE>
<INPUT type="submit" value="Validez"></FORM>
</div>
