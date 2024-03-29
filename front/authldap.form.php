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

Session::checkRight("config", "w");

$config      = new Config();
$config_ldap = new AuthLDAP();

if (!isset($_GET['id'])) {
   $_GET['id'] = "";
}
//LDAP Server add/update/delete
if (isset($_POST["update"])) {
   $config_ldap->update($_POST);
   Html::back();

} else if (isset($_POST["add"])) {
   //If no name has been given to this configuration, then go back to the page without adding
   if ($_POST["name"] != "") {
      if ($newID = $config_ldap->add($_POST)) {
         if (AuthLdap::testLDAPConnection($newID)) {
            Session::addMessageAfterRedirect(__('Test successful'));
         } else {
            Session::addMessageAfterRedirect(__('Test failed'), false, ERROR);
         }
         Html::redirect($CFG_GLPI["root_doc"] . "/front/authldap.php?next=extauth_ldap&id=".$newID);
      }
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $config_ldap->delete($_POST);
   $_SESSION['glpi_authconfig'] = 1;
   $config_ldap->redirectToList();

} else if (isset($_POST["test_ldap"])) {
   $config_ldap->getFromDB($_POST["id"]);

   if (AuthLdap::testLDAPConnection($_POST["id"])) {
                                       //TRANS: %s is the description of the test
      $_SESSION["LDAP_TEST_MESSAGE"] = sprintf(__('Test successful: %s'),
                                               //TRANS: %s is the name of the LDAP main server
                                               sprintf(__('Main server %s'), $config_ldap->fields["name"]));
   } else {
                                       //TRANS: %s is the description of the test
      $_SESSION["LDAP_TEST_MESSAGE"] = sprintf(__('Test failed: %s'),
                                               //TRANS: %s is the name of the LDAP main server
                                               sprintf(__('Main server %s'), $config_ldap->fields["name"]));
   }
   Html::back();

} else if (isset($_POST["test_ldap_replicate"])) {
   foreach ($_POST["test_ldap_replicate"] as $replicate_id => $value) {
      $replicate = new AuthLdapReplicate();
      $replicate->getFromDB($replicate_id);

      if (AuthLdap::testLDAPConnection($_POST["id"],$replicate_id)) {
                                          //TRANS: %s is the description of the test
         $_SESSION["LDAP_TEST_MESSAGE"] = sprintf(__('Test successful: %s'),
                                                  //TRANS: %s is the name of the LDAP main server
                                                  sprintf(__('Replicate %s'), $ldap->fields["name"]));
      } else {
                                           //TRANS: %s is the description of the test
         $_SESSION["LDAP_TEST_MESSAGE"] = sprintf(__('Test failed: %s'),
                                                  //TRANS: %s is the name of the LDAP main server
                                                  sprintf(__('Replicate %s'), $ldap->fields["name"]));
      }
   }
   Html::back();

} else if (isset($_POST["add_replicate"])) {
   $replicate = new AuthLdapReplicate();
   unset($_POST["next"]);
   unset($_POST["id"]);
   $replicate->add($_POST);
   Html::back();
}

Html::header(AuthLDAP::getTypeName(1), $_SERVER['PHP_SELF'], 'config', 'extauth', 'ldap');
$config_ldap->showForm($_GET["id"], $_GET);

Html::footer();
?>