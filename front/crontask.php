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
* @brief Search engine from cron tasks
*/

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("config", "w");

Html::header(Crontask::getTypeName(2), $_SERVER['PHP_SELF'], 'config', 'crontask');

$crontask = new CronTask();
if ($crontask->getNeedToRun(CronTask::MODE_INTERNAL)) {
   $name = sprintf(__('%1$s %2$s'), $crontask->fields['name'],
                   Html::getSimpleForm($crontask->getFormURL(),
                                       array('execute' => $crontask->fields['name']),
                                             __('Execute')));
   Html::displayTitle(GLPI_ROOT.'/pics/warning.png', __('Next run'),
                      sprintf(__('Next task to run: %s'), $name));
} else {
   Html::displayTitle(GLPI_ROOT.'/pics/ok.png', __('No action pending'), __('No action pending'));
}

Search::show('CronTask');

Html::footer();
?>