<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkCentralAccess();

// Make a select box
if (isset($_POST["type"]) && isset($_POST["actortype"])) {
   $rand = mt_rand();

   switch ($_POST["type"]) {
      case "user" :
         $right = 'all';
         // Only steal or own ticket whit empty assign
         if ($_POST["actortype"]=='assign') {
            $right = "own_ticket";
            if (!haveRight("assign_ticket","1")) {
               $right = 'id';
            }
         }

         $options = array('name'        => '_ticket_'.$_POST["actortype"].'[users_id]',
                          'entity'      => $_POST['entity_restrict'],
                          'right'       => $right,
                          'ldap_import' => true);
         if ($CFG_GLPI["use_mailing"]) {
            // Fix rand value
            $options['rand']     = $rand;
            $options['toupdate'] = array('value_fieldname' => 'value',
                                         'to_update'  => "notif_user_$rand",
                                         'url'        => $CFG_GLPI["root_doc"]."/ajax/uemailUpdate.php",
                                         'moreparams' => array('value' => '__VALUE__',
                                                               'field' => "_ticket_".
                                                                          $_POST["actortype"]));
         }

         $rand = User::dropdown($options);
         if ($CFG_GLPI["use_mailing"]) {
            echo "<br><span id='notif_user_$rand'>";
            echo "</span>";
         }
         break;

      case "group" :
         Dropdown::show('Group', array('name'   => '_ticket_'.$_POST["actortype"].'[groups_id]',
                                       'entity' => $_POST['entity_restrict']));
         break;
   }
}

?>
