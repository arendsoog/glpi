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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Hidden fields for ticket template class
/// since version 0.83
class TicketTemplateHiddenField extends CommonDBChild {

   // From CommonDBChild
   public $itemtype  = 'TicketTemplate';
   public $items_id  = 'tickettemplates_id';
   public $dohistory = true;


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['job'][60];
      }
      return $LANG['job'][63];
   }


   function canCreate() {
      return Session::haveRight('tickettemplate', 'w');
   }


   function canView() {
      return Session::haveRight('tickettemplate', 'r');
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      // can exists for template
      if ($item->getType() == 'TicketTemplate' && Session::haveRight("tickettemplate","r")) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry($LANG['job'][60],
                                        countElementsInTable($this->getTable(),
                                                             "`tickettemplates_id`
                                                               = '".$item->getID()."'"));
         }
         return $LANG['job'][60];
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showForTicketTemplate($item, $withtemplate);
      return true;
   }


   /**
    * Print the hidden fields
    *
    * @since version 0.83
    *
    * @param $tt Ticket Template
    * @param $withtemplate=''  boolean : Template or basic item.
    *
    * @return Nothing (call to classes members)
   **/
   static function showForTicketTemplate(TicketTemplate $tt, $withtemplate='') {

      global $DB, $LANG;

      $ID = $tt->fields['id'];

      if (!$tt->getFromDB($ID) || !$tt->can($ID, "r")) {
         return false;
      }

      $canedit = $tt->can($ID, "w");
      $fields  = $tt->getAllowedFieldsNames();
      $rand    = mt_rand();
      echo "<form name='tickettemplatehiddenfields_form$rand'
                  id='tickettemplatehiddenfields_form$rand' method='post' action='";
      echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";

      echo "<div class='center'>";

      $query = "SELECT `glpi_tickettemplatehiddenfields`.*
                FROM `glpi_tickettemplatehiddenfields`
                WHERE (`tickettemplates_id` = '$ID')";

      if ($result=$DB->query($query)) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>";
         echo self::getTypeName($DB->numrows($result));
         echo "</th></tr>";
         $used = array();
         if ($DB->numrows($result)) {
            echo "<tr><th>&nbsp;</th>";
            echo "<th>".$LANG['common'][16]."</th>";
            echo "</tr>";

            while ($data=$DB->fetch_assoc($result)) {
               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td><input type='checkbox' name='item[".$data["id"]."]' value='1'></td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }
               echo "<td>".$fields[$data['num']]."</td>";
               $used[$data['num']] = $data['num'];
            }

         } else {
            echo "<tr><th colspan='2'>".$LANG['search'][15]."</th></tr>";
         }

         if ($canedit) {
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            echo "<input type='hidden' name='tickettemplates_id' value='$ID'>";
            echo "<input type='hidden' name='entities_id' value='".$tt->getEntityID()."'>";
            echo "<input type='hidden' name='is_recursive' value='".$tt->isRecursive()."'>";
            Dropdown::showFromArray('num', $fields, array('used'  => $used));
            echo "&nbsp;<input type='submit' name='add' value=\"".$LANG['buttons'][8].
                         "\" class='submit'>";
            echo "</td></tr>";
         }
         echo "</table></div>";

         if ($canedit) {
            Html::openArrowMassives("tickettemplatehiddenfields_form$rand", true);
            Html::closeArrowMassives(array('delete' => $LANG['buttons'][6]));
         }
         echo "</form>";

      }
   }
}
?>