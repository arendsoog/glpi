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

define('GLPI_ROOT','..');
$AJAX_INCLUDE = 1;
include (GLPI_ROOT."/inc/includes.php");

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['duration']) && ($_POST['duration'] == 0)
    && isset($_POST['name'])) {
   if (!isset($_POST['global_begin'])) {
      $_POST['global_begin'] = '';
   }
   if (!isset($_POST['global_end'])) {
      $_POST['global_end'] = '';
   }
   Html::showDateTimeFormItem($_POST['name'], $_POST['end'], -1, false, true, '', '',
                              $_POST['global_begin'], $_POST['global_end']);
}
?>