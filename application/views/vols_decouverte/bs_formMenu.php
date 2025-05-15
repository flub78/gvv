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
 * Formulaire de saisie des terrains
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

$this->lang->load('vols_decouverte');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
	echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo heading("gvv_vols_decouverte_element", 3);

?>

<?php

// echo validation_errors();
$form = $this->gvvmetadata->form_flexbox('vols_decouverte', array(
	'date_vente' => $date_vente,
	'product' => $product,
	'beneficiaire' => $beneficiaire,
	'de_la_part' => $de_la_part,
	'beneficiaire_email' => $beneficiaire_email,
	'urgence' => $urgence,
	'cancelled' => $cancelled
));

// echo $form;

$index_page = $this->config->item('index_page');

$index = ($index_page) ? "$index_page/" : "";
$pdf_url = base_url() . $index . "vols_decouverte/pdf/" . $obfuscated_id;
$email_url = base_url() . $index . "vols_decouverte/email/" . $obfuscated_id;
$pre_flight_url = base_url() . $index . "vols_decouverte/pre_flight/" . $obfuscated_id;
$done_url = base_url() . $index . "vols_decouverte/done/" . $obfuscated_id;

?>
</div>


<?php if ($cancelled): ?>
<div class="alert alert-danger" role="alert">
    Ce vol a été annulé
</div>
<?php endif; ?>
<?php if ($date_vol): ?>
<div class="alert alert-success" role="alert">
    Ce vol a été effectué le <?= $date_vol ?>
</div>
<?php endif; ?>
<?php if ($expired): ?>
<div class="alert alert-warning" role="alert">
    La date de validité est expirée
</div>
<?php endif; ?>

<div class="d-flex flex-wrap">
	<div  class="m-2 ">
		<div class="mb-2 ">Numéro: <?= $id ?> </div>
		<div class="mb-2 ">Date de vente: <?= $date_vente ?> </div>
		<div class="mb-2 ">Description: <?= $description ?></div>
		<div class="mb-2 ">Bénéficiaire: <?= $beneficiaire ?></div>
	</div>
	<div class="m-2 ">
		<div class="mb-2 ">De la part de: <?= $de_la_part ?></div>
		<div class="mb-2 ">Email bénéficiaire: <?= $beneficiaire_email ?></div>
		<div class="mb-2 ">Contact en cas d'urgence: <?= $urgence ?></div>
	</div>

</div>

<div class="container mt-4">

	<div class="d-flex flex-column flex-lg-row gap-3">
		<a href="<?= $pdf_url ?>" class="btn btn-primary px-4 text-decoration-none">Impression</a>
		<!--a href="<?= $pdf_url ?>" class="btn btn-primary px-4 text-decoration-none">Envoi par mail</!--a -->
		<a href="<?= $pdf_url ?>" class="btn btn-primary px-4 text-decoration-none">Ajout contact d'urgence</a>
		<a href="<?= $pdf_url ?>" class="btn btn-warning px-4 text-decoration-none">Vol effectué</a>
	</div>
</div>