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

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (!$CFG_GLPI['use_mailing']
    || !countElementsInTable('glpi_notifications',
                             "`itemtype`='User' AND `event`='passwordforget' AND `is_active`=1")) {
   exit();
}

$user = new User();

// Manage lost password
Html::simpleHeader(__('Forgotten password?'));

// REQUEST needed : GET on first access / POST on submit form
if (isset($_REQUEST['password_forget_token'])) {

   if (isset($_POST['email'])) {
      $user->updateForgottenPassword($_REQUEST);
   } else {
      User::showPasswordForgetChangeForm($_REQUEST['password_forget_token']);
   }

} else {

   if (isset($_POST['email'])) {
      $user->forgetPassword($_POST['email']);
   } else {
      User::showPasswordForgetRequestForm();
   }
}

Html::nullFooter();
exit();
?>