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

    <li><a class="dropdown-item" href="https://github.com/flub78/gvv/blob/main/README.md" target="_blank" rel="noopener noreferrer"><?= translation("GVV") ?></a></li>
    <li><a class="dropdown-item" href="http://aeroclub-abbeville.fr/" target="_blank" rel="noopener noreferrer"><?= translation("Site club") ?></a></li>

    <li><a class="dropdown-item" href="https://moncompte.ffvp.fr/" target="_blank" rel="noopener noreferrer">Compte FFVP, GESASSO</a></li>

    <li><a class="dropdown-item" href="https://www.ffvp.fr/" target="_blank" rel="noopener noreferrer"><?= translation("FFVP") ?></a></li>
    <li><a class="dropdown-item" href="https://www.ffvp.fr/les-rex" target="_blank" rel="noopener noreferrer"><?= translation("Retours d'expérience") ?></a></li>
    <li><a class="dropdown-item" href="https://aviation.meteo.fr/login.php" target="_blank" rel="noopener noreferrer"><?= translation("Prévisions vol à voile") ?></a></li>
    <?php if (has_role('ca')) : ?>
      <li><a class="dropdown-item" href="https://heva.ffvp.fr/guard/login" target="_blank" rel="noopener noreferrer"><?= translation("Licences assurance") ?></a></li>
    <?php endif; ?>

    <?php if (has_role('tresorier')) : ?>
      <li><a class="dropdown-item" href="https://www.credit-du-nord.fr/" target="_blank" rel="noopener noreferrer"><?= translation("CDN") ?></a></li>
    <?php endif; ?>

  </ul>
</li>