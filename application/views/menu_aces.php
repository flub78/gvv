<?php
//    GVV Gestion vol à voile
//    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.


if ($this->dx_auth->is_logged_in())	{
	
		  $menu_liens = array (
        'label' => "Liens",
        'class' => 'menuheader',
        'role' => '',
        'submenu' => array (
						array ('label' => "Click n' Glide", 'url' => "https://clicknglide.com/fr/votreclub", 'role' => ''),
            array ('label' => "FFVV", 'url' => "http://ffvv.org/", 'role' => ''),
            array ('label' => "Retour d'expérience", 'url' => "http://www.isimages.com/ffvvsec/", 'role' => ''),
            array ('label' => "Netcoupe", 'url' => "http://www.netcoupe.net/main.aspx", 'role' => ''),
            array ('label' => "Prévision vol à voile", 'url' => "https://aviation.meteo.fr/login.php", 'role' => ''),
            array ('label' => "Topmétéo", 'url' => "http://fr.topmeteo.eu/go/home", 'role' => ''),
            array ('label' => "Licences Assurances", 'url' => "http://www.licences.ffvv.org/", 'role' => 'ca'),
            array ('label' => "SG", 'url' => "https://professionnels.societegenerale.fr/", 'role' => 'bureau'),
            )
    );

    $menubar['submenu'][] = array (
        'label' => "ACES",
        'class' => 'menuheader',
        'submenu' => array (
						$menu_liens,
            array ('label' => "Documents", 'url' => controller_url("partage/upload/membres/manuels")),
            array ('label' => "100LL - Visu", 'url' => controller_url("pompes/page/0"), 'role' => 'ca'),
            array ('label' => "100LL - Saisie", 'url' => controller_url("pompes/create/0"), 'role' => 'ca'),
            array ('label' => "98SP  - Visu", 'url' => controller_url("pompes/page/1"), 'role' => 'ca'),
            array ('label' => "98SP  - Saisie", 'url' => controller_url("pompes/create/1"), 'role' => 'ca')
        )
    );
}

