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

// class Preference for the current connected User
class Preference extends CommonGLPI {


   static function getTypeName($nb=0) {
      // Always plural
      return __('Settings');
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab('User', $ong, $options);
      $this->addStandardTab('Config', $ong, $options);
      $this->addStandardTab('DisplayPreference', $ong, $options);

      $ong['no_all_tab'] = true;

      return $ong;
   }
}
?>
