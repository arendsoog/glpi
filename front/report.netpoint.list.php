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

Session::checkRight("reports", "r");

if (isset($_POST["prise"]) && $_POST["prise"]) {
   Html::header(Report::getTypeName(2), $_SERVER['PHP_SELF'], "utils", "report");

   Report::title();

   $name = Dropdown::getDropdownName("glpi_netpoints", $_POST["prise"]);

   // Titre
   echo "<div class='center spaced'><h2>".sprintf(__('Network report by outlet: %s'), $name).
        "</h2></div>";

   // TODO : must be review at the end of Damien's work
   $query = "SELECT `glpi_locations`.`name`, `glpi_locations`.`id`,
                    `glpi_netpoints`.`name` AS prise, `glpi_networkports`.`name` AS port,
                    `glpi_networkports`.`ip`, `glpi_networkports`.`mac`,
                    `glpi_networkports`.`id` AS IDport
             FROM `glpi_netpoints`
             LEFT JOIN `glpi_locations`
                  ON `glpi_locations`.`id` = `glpi_netpoints`.`locations_id`
             LEFT JOIN `glpi_networkports`
                  ON `glpi_networkports`.`netpoints_id` = `glpi_netpoints`.`id`
             WHERE `glpi_netpoints`.`id` = '".$_POST["prise"]."'
                   AND `glpi_networkports`.`itemtype` = 'NetworkEquipment'";

   /*!
      on envoie la requete de selection qui varie selon le choix fait dans la dropdown
      a la fonction report perso qui affiche un rapport en fonction de la prise choisie
    */

   $result = $DB->query($query);
   if ($result && $DB->numrows($result)) {
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th>".__('Location')."</th>";
      echo "<th>".__('Switch')."</th>";
      echo "<th>".__('IP')."</th>";
      echo "<th>".__('Hardware ports')."</th>";
      echo "<th>".__('MAC address')."</th>";
      echo "<th>".__('Device ports')."</th>";
      echo "<th>".__('IP')."</th>";
      echo "<th>".__('MAC address')."</th>";
      echo "<th>".__('Connected devices')."</th>";
      echo "</tr>";

      while ($ligne = $DB->fetch_assoc($result)) {
         $prise             = $ligne['prise'];
         $ID                = $ligne['id'];
         $lieu              = Dropdown::getDropdownName("glpi_locations",$ID);
         $nw                = new NetworkPort_NetworkPort();
         $networkports_id_1 = $nw->getOppositeContact($ligne['IDport']);
         $np                = new NetworkPort();
         $ordi              = "";
         $ip2               = "";
         $mac2              = "";
         $portordi          = "";

         if ($networkports_id_1) {
            $np->getFromDB($networkports_id_1);
            $ordi = '';
            if ($item = getItemForItemtype($np->fields["itemtype"])) {
               if ($item->getFromDB($np->fields["items_id"])) {
                  $ordi = $item->getName();
               }
            }
            $ip2      = $np->fields['ip'];
            $mac2     = $np->fields['mac'];
            $portordi = $np->fields['name'];
         }

         $ip   = $ligne['ip'];
         $mac  = $ligne['mac'];
         $port = $ligne['port'];
         $np->getFromDB($ligne['IDport']);

         $nd     = new NetworkEquipment();
         $nd->getFromDB($np->fields["items_id"]);
         $switch = $nd->fields["name"];

         //inserer ces valeures dans un tableau
         echo "<tr class='tab_bg_1'>";
         if ($lieu) {
            echo "<td>$lieu</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($switch) {
            echo "<td>$switch</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($ip) {
            echo "<td>$ip</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($port) {
            echo "<td>$port</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($mac) {
            echo "<td>$mac</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($portordi) {
            echo "<td>$portordi</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($ip2) {
            echo "<td>$ip2</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($mac2) {
            echo "<td>$mac2</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($ordi) {
            echo "<td>$ordi</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }
         echo "</tr>\n";
      }
      echo "</table><br><hr><br>";
   }
   Html::footer();

} else  {
   Html::redirect($CFG_GLPI['root_doc']."/front/report.networking.php");
}
?>