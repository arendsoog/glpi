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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class TicketSolutionTemplate
class TicketSolutionTemplate extends CommonDropdown {

   static function getTypeName() {
      global $LANG;

      return $LANG['jobresolution'][6];
   }


   function canCreate() {
      return haveRight('entity_dropdown', 'w');
   }


   function canView() {
      return haveRight('entity_dropdown', 'r');
   }


   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => 'ticketsolutiontypes_id',
                         'label' => $LANG['job'][48],
                         'type'  => 'dropdownValue',
                         'list'  => true),
                   array('name'  => 'content',
                         'label' => $LANG['knowbase'][15],
                         'type'  => 'tinymce'));
   }


   function displaySpecificTypeField($ID, $field = array()) {

      switch ($field['type']) {
         case 'tinymce' :
            // Display empty field
            echo "&nbsp;</td></tr>";
            // And a new line to have a complete display
            echo "<tr class='center'><td colspan='5'>";
            $rand = mt_rand();
            initEditorSystem($field['name'].$rand);
            echo "<textarea id='".$field['name']."$rand' name='".$field['name']."' rows='3'>".$this->fields[$field['name']].
                 "</textarea>";
            break;
      }
   }


}

?>
