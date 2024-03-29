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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// CLASSES Monitors

class Monitor extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;
   static protected $forward_entity_to = array('Infocom', 'ReservationItem');


   /**
    * Name of the type
    *
    * @param $nb  string   number of item in the type
   **/
   static function getTypeName($nb=0) {
      return _n('Monitor', 'Monitors', $nb);
   }


   static function canCreate() {
      return Session::haveRight('monitor', 'w');
   }


   static function canView() {
      return Session::haveRight('monitor', 'r');
   }


   /**
    * @see CommonGLPI::defineTabs()
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab('Computer_Item', $ong, $options);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Contract_Item', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);
      $this->addStandardTab('Item_Problem', $ong, $options);
      $this->addStandardTab('Link', $ong, $options);
      $this->addStandardTab('Note', $ong, $options);
      $this->addStandardTab('Reservation', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      if (isset($input["id"]) && ($input["id"] > 0)) {
         $input["_oldID"] = $input["id"];
      }
      if (isset($input["size"]) && ($input["size"] == '')) {
         unset($input["size"]);
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }


   function post_addItem() {
      global $DB;


      // Manage add from template
      if (isset($this->input["_oldID"])) {
         // ADD Infocoms
         Infocom::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Contract
         Contract_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Documents
         Document_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Computers
         Computer_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

      }

   }


   function cleanDBonPurge() {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_computers_items`
                WHERE `itemtype` = '".$this->getType()."'
                      AND `items_id` = '".$this->fields['id']."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $conn = new Computer_Item();

            while ($data = $DB->fetch_assoc($result)) {
               $data['_no_auto_action'] = true;
               $conn->delete($data);
            }
         }
      }
   }


   /**
    * Print the monitor form
    *
    * @param $ID        integer  ID of the item
    * @param $options   array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return boolean item found
    **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      $target       = $this->getFormURL();
      $withtemplate = $this->initForm($ID, $options);
      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      //TRANS: %1$s is a string, %2$s a second one without spaces between them : to change for RTL
      echo "<td>".sprintf(__('%1$s%2$s'), __('Name'),
                          (isset($options['withtemplate']) && $options['withtemplate']?"*":""));
      echo "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, "name", array('value' => $objectName));
      echo "</td>";
      echo "<td>".__('Status')."</td>";
      echo "<td>";
      State::dropdown(array('value' => $this->fields["states_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Location')."</td>";
      echo "<td>";
      Location::dropdown(array('value'  => $this->fields["locations_id"],
                               'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      MonitorType::dropdown(array('value' => $this->fields["monitortypes_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td>";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td>".__('Manufacturer')."</td>";
      echo "<td>";
      Manufacturer::dropdown(array('value' => $this->fields["manufacturers_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group in charge of the hardware')."</td>";
      echo "<td>";
      Group::dropdown(array('name'      => 'groups_id_tech',
                            'value'     => $this->fields['groups_id_tech'],
                            'entity'    => $this->fields['entities_id'],
                            'condition' => '`is_assign`'));
      echo "</td>";
      echo "<td>".__('Model')."</td>";
      echo "<td>";
      MonitorModel::dropdown(array('value' => $this->fields["monitormodels_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alternate username number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "contact_num");
      echo "</td>";
      echo "<td>".__('Serial number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "serial");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alternate username')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "contact");
      echo "</td>";
      echo "<td>".sprintf(__('%1$s%2$s'), __('Inventory number'),
                          (isset($options['withtemplate']) && $options['withtemplate']?"*":"")).
           "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, "otherserial", array('value' => $objectName));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('User')."</td>";
      echo "<td>";
      User::dropdown(array('value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all'));
      echo "</td>";
      echo "<td>".__('Management type')."</td>";
      echo "<td>";
      Dropdown::showGlobalSwitch($this->fields["id"],
                                 array('withtemplate' => $withtemplate,
                                       'value'        => $this->fields["is_global"],
                                       'management_restrict'
                                                      => $CFG_GLPI["monitors_management_restrict"],
                                       'target'       => $target));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group')."</td>";
      echo "<td>";
      Group::dropdown(array('value'     => $this->fields["groups_id"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => '`is_itemgroup`'));
      echo "</td>";
      echo "<td rowspan='4'>" . __('Comments')."</td>";
      echo "<td rowspan='4'>
            <textarea cols='45' rows='10' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Size')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "size");
      echo "\"</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".('Flags')."</td>";
      echo "<td><table>";
      // micro?
      echo "<tr><td>".__('Microphone')."</td><td>";
      Dropdown::showYesNo("have_micro", $this->fields["have_micro"]);
      // speakers?
      echo "</td><td>".__('Speakers')."</td><td>";
      Dropdown::showYesNo("have_speaker", $this->fields["have_speaker"]);
      echo "</td></tr>";

     // sub-d?
      echo "<tr><td>".__('Sub-D')."</td><td>";
      Dropdown::showYesNo("have_subd", $this->fields["have_subd"]);
      // bnc?
      echo "</td><td>".__('BNC')."</td><td>";
      Dropdown::showYesNo("have_bnc", $this->fields["have_bnc"]);
      echo "</td></tr>";

      // dvi?
      echo "<tr><td>".__('DVI')."</td><td>";
      Dropdown::showYesNo("have_dvi", $this->fields["have_dvi"]);
      // pivot ?
      echo "</td><td>".__('Pivot')."</td><td>";
      Dropdown::showYesNo("have_pivot", $this->fields["have_pivot"]);
      echo "</td></tr>";
      // hdmi?
      echo "<tr><td>".__('HDMI')."</td><td>";
      Dropdown::showYesNo("have_hdmi", $this->fields["have_hdmi"]);
      echo "</td>";
      //Displayport
      echo "</td><td>".__('DisplayPort')."</td><td>";
      Dropdown::showYesNo("have_displayport", $this->fields["have_displayport"]);
      echo "</td></tr>";
      echo "</table></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      if ((!isset($options['withtemplate']) || ($options['withtemplate'] == 0))
          && !empty($this->fields['template_name'])) {
         echo "<span class='small_space'>";
         printf(__('Created from the template %s'), $this->fields['template_name']);
         echo "</span>";
      } else {
         echo "&nbsp;";
      }
      echo "</td><td>";
      if (isset($options['withtemplate']) && $options['withtemplate']) {
         //TRANS: %s is the datetime of insertion
         printf(__('Created on %s'), Html::convDateTime($_SESSION["glpi_currenttime"]));
      } else {
         //TRANS: %s is the datetime of insertion
         printf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));
      }
      echo "</td></tr>\n";

      $this->showFormButtons($options);
      $this->addDivForTabs();
      return true;
   }


   /**
    * Return the SQL command to retrieve linked object
    *
    * @return a SQL command which return a set of (itemtype, items_id)
   **/
   function getSelectLinkedItem() {

      return "SELECT 'Computer', `computers_id`
              FROM `glpi_computers_items`
              WHERE `itemtype` = '".$this->getType()."'
                    AND `items_id` = '" . $this->fields['id']."'";
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);
      if ($isadmin) {
         $actions['connect']    = _x('button', 'Connect');
         $actions['disconnect'] = _x('button', 'Disconnect');
      }
      if (Session::haveRight('transfer','r')
          && Session::isMultiEntitiesMode()
          && $isadmin) {
         $actions['add_transfer_list'] = _x('button', 'Add to transfer list');
      }
      return $actions;
   }

   /**
    * @see CommonDBTM::showSpecificMassiveActionsParameters()
   **/
   function showSpecificMassiveActionsParameters($input=array()) {

      switch ($input['action']) {
         case "connect" :
         case "disconnect" :
            $ci = new Computer_Item();
            return $ci->showSpecificMassiveActionsParameters($input);

         default :
            return parent::showSpecificMassiveActionsParameters($input);
      }
      return false;
   }


   /**
    * @see CommonDBTM::doSpecificMassiveActions()
   **/
   function doSpecificMassiveActions($input=array()) {

      $res = array('ok'      => 0,
                   'ko'      => 0,
                   'noright' => 0);

      switch ($input['action']) {
         case "connect" :
         case "disconnect" :
            $ci = new Computer_Item();
            return $ci->doSpecificMassiveActions($input);

         default :
            return parent::doSpecificMassiveActions($input);
      }
      return $res;
   }


   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false;

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false;
      $tab[2]['datatype']        = 'number';

      $tab+=Location::getSearchOptionsToAdd();

      $tab[4]['table']           = 'glpi_monitortypes';
      $tab[4]['field']           = 'name';
      $tab[4]['name']            = __('Type');
      $tab[4]['datatype']        = 'dropdown';

      $tab[40]['table']          = 'glpi_monitormodels';
      $tab[40]['field']          = 'name';
      $tab[40]['name']           = __('Model');
      $tab[40]['datatype']       = 'dropdown';

      $tab[31]['table']          = 'glpi_states';
      $tab[31]['field']          = 'completename';
      $tab[31]['name']           = __('Status');
      $tab[31]['datatype']        = 'dropdown';

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'serial';
      $tab[5]['name']            = __('Serial number');
      $tab[5]['datatype']        = 'string';

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'otherserial';
      $tab[6]['name']            = __('Inventory number');
      $tab[6]['datatype']        = 'string';

      $tab[7]['table']           = $this->getTable();
      $tab[7]['field']           = 'contact';
      $tab[7]['name']            = __('Alternate username');
      $tab[7]['datatype']        = 'string';

      $tab[8]['table']           = $this->getTable();
      $tab[8]['field']           = 'contact_num';
      $tab[8]['name']            = __('Alternate username number');
      $tab[8]['datatype']        = 'string';

      $tab[70]['table']          = 'glpi_users';
      $tab[70]['field']          = 'name';
      $tab[70]['name']           = __('User');
      $tab[70]['datatype']       = 'dropdown';
      $tab[70]['right']          = 'all';

      $tab[71]['table']          = 'glpi_groups';
      $tab[71]['field']          = 'completename';
      $tab[71]['name']           = __('Group');
      $tab[71]['condition']      = '`is_itemgroup`';
      $tab[71]['datatype']       = 'dropdown';

      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

      $tab[90]['table']          = $this->getTable();
      $tab[90]['field']          = 'notepad';
      $tab[90]['name']           = __('Notes');
      $tab[90]['massiveaction']  = false;
      $tab[90]['datatype']       = 'text';

      $tab[11]['table']          = $this->getTable();
      $tab[11]['field']          = 'size';
      $tab[11]['name']           = __('Size');
      $tab[11]['datatype']       = 'number';

      $tab[41]['table']          = $this->getTable();
      $tab[41]['field']          = 'have_micro';
      $tab[41]['name']           = __('Microphone');
      $tab[41]['datatype']       = 'bool';

      $tab[42]['table']          = $this->getTable();
      $tab[42]['field']          = 'have_speaker';
      $tab[42]['name']           = __('Speakers');
      $tab[42]['datatype']       = 'bool';

      $tab[43]['table']          = $this->getTable();
      $tab[43]['field']          = 'have_subd';
      $tab[43]['name']           = __('Sub-D');
      $tab[43]['datatype']       = 'bool';

      $tab[44]['table']          = $this->getTable();
      $tab[44]['field']          = 'have_bnc';
      $tab[44]['name']           = ('BNC');
      $tab[44]['datatype']       = 'bool';

      $tab[45]['table']          = $this->getTable();
      $tab[45]['field']          = 'have_dvi';
      $tab[45]['name']           = __('DVI');
      $tab[45]['datatype']       = 'bool';

      $tab[46]['table']          = $this->getTable();
      $tab[46]['field']          = 'have_pivot';
      $tab[46]['name']           = __('Pivot');
      $tab[46]['datatype']       = 'bool';

      $tab[47]['table']          = $this->getTable();
      $tab[47]['field']          = 'have_hdmi';
      $tab[47]['name']           = __('HDMI');
      $tab[47]['datatype']       = 'bool';

      $tab[48]['table']          = $this->getTable();
      $tab[48]['field']          = 'have_displayport';
      $tab[48]['name']           = __('DisplayPort');
      $tab[48]['datatype']       = 'bool';

      $tab[23]['table']          = 'glpi_manufacturers';
      $tab[23]['field']          = 'name';
      $tab[23]['name']           = __('Manufacturer');

      $tab[24]['table']          = 'glpi_users';
      $tab[24]['field']          = 'name';
      $tab[24]['linkfield']      = 'users_id_tech';
      $tab[24]['name']           = __('Technician in charge of the hardware');
      $tab[24]['datatype']       = 'dropdown';
      $tab[24]['right']          = 'own_ticket';

      $tab[49]['table']          = 'glpi_groups';
      $tab[49]['field']          = 'completename';
      $tab[49]['linkfield']      = 'groups_id_tech';
      $tab[49]['name']           = __('Group in charge of the hardware');
      $tab[49]['condition']      = '`is_assign`';
      $tab[49]['datatype']       = 'dropdown';

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['massiveaction']  = false;
      $tab[80]['datatype']       = 'dropdown';

      $tab[82]['table']          = $this->getTable();
      $tab[82]['field']          = 'is_global';
      $tab[82]['name']           = __('Global management');
      $tab[82]['datatype']       = 'bool';
      $tab[82]['massiveaction']  = false;

      return $tab;
   }

}
?>