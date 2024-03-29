<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief 
*/

define('GLPI_ROOT', '.');
include (GLPI_ROOT . "/inc/includes.php");

//@session_start();

if (!isset($_SESSION["noAUTO"])
    && isset($_SESSION["glpiauthtype"])
    && ($_SESSION["glpiauthtype"] == Auth::CAS)) {

   include (GLPI_PHPCAS);
   phpCAS::client(CAS_VERSION_2_0, $CFG_GLPI["cas_host"], intval($CFG_GLPI["cas_port"]),
                  $CFG_GLPI["cas_uri"], false);
   phpCAS::setServerLogoutURL(strval($CFG_GLPI["cas_logout"]));
   phpCAS::logout();
}

$toADD = "";

// Redirect management
if (isset($_POST['redirect']) && (strlen($_POST['redirect']) > 0)) {
   $toADD = "?redirect=" .$_POST['redirect'];

} else if (isset($_GET['redirect']) && (strlen($_GET['redirect']) > 0)) {
   $toADD = "?redirect=" .$_GET['redirect'];
}

if (isset($_SESSION["noAUTO"]) || isset($_GET['noAUTO'])) {
   if (empty($toADD)) {
      $toADD .= "?";
   } else {
      $toADD .= "&";
   }
   $toADD .= "noAUTO=1";
}

Session::destroy();

// Redirect to the login-page

Html::redirect($CFG_GLPI["root_doc"]."/index.php".$toADD);
?>
