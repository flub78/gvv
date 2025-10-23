<!-- VIEW: application/views/bs_menu_cpta.php -->
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
 *    Menu specifique par club
 *
 *    @package vues
 */

?>

<!-- Navbar -->
<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?= translation("Aide") ?></a>
  <ul class="dropdown-menu">

    <li><a class="dropdown-item" href="https://github.com/flub78/gvv/blob/main/README.md" target="_blank" rel="noopener noreferrer"><i class="fas fa-book text-primary"></i> <?= translation("GVV") ?></a></li>
    <li><a class="dropdown-item" href="http://planeur-troyes.fr/" target="_blank" rel="noopener noreferrer"><i class="fas fa-home text-success"></i> <?= translation("Site club") ?></a></li>

    <li><a class="dropdown-item" href="https://www.ffvp.fr/" target="_blank" rel="noopener noreferrer"><i class="fas fa-globe text-primary"></i> <?= translation("FFVP") ?></a></li>
    <li><a class="dropdown-item" href="http://www.isimages.com/ffvvsec/" target="_blank" rel="noopener noreferrer"><i class="fas fa-exclamation-triangle text-warning"></i> <?= translation("Retours d'expérience") ?></a></li>
    <li><a class="dropdown-item" href="https://www.netcoupe.net/main.aspx" target="_blank" rel="noopener noreferrer"><i class="fas fa-trophy text-warning"></i> <?= translation("Netcoupe") ?></a></li>
    <li><a class="dropdown-item" href="https://aviation.meteo.fr/login.php" target="_blank" rel="noopener noreferrer"><i class="fas fa-cloud-sun text-info"></i> <?= translation("Prévisions vol à voile") ?></a></li>
    <li><a class="dropdown-item" href="https://heva.ffvp.fr/guard/login" target="_blank" rel="noopener noreferrer"><i class="fas fa-id-card text-success"></i> <?= translation("Licences assurance") ?></a></li>

  </ul>
</li>