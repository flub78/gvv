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

    $menubar['submenu'][] = array (
        'label' => "Liens",
        'class' => 'menuheader',
        'role' => '',
        'submenu' => array (
            array ('label' => "OpenFLyers", 'url' => "http://abbeville.openflyers.fr", 'role' => ''),
        	array ('label' => "FFPLUM", 'url' => "http://www.ffplum.com/", 'role' => ''),
            array ('label' => "Retour d'expérience", 'url' => "http://rex.isimedias.com/ffplum/", 'role' => ''),
            array ('label' => "Topmétéo", 'url' => "http://fr.topmeteo.eu/go/home", 'role' => ''),
            array ('label' => "Licences Assurances", 'url' => "https://ffplum-goal.multimediabs.com/grandpublic/saisielicencegp", 'role' => 'ca'),
            array ('label' => "CDN", 'url' => "https://www.credit-du-nord.fr/", 'role' => 'bureau'),
            )
    );
}

