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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// Class NotificationTargetPlanningRecall
class NotificationTargetPlanningRecall extends NotificationTarget {


   function getEvents() {

      return array('reminder' => __('Planning recall'));
   }


   function getNotificationTargets($entity) {
      $this->addTarget(Notification::AUTHOR, __('Requester'));
   }


   function getDatasForTemplate($event, $options=array()) {
      global $CFG_GLPI;

      //----------- Reservation infos -------------- //
      $events = $this->getAllEvents();

      $this->datas['##recall.action##'] = $events[$event];


//       $this->datas['##reservation.user##'] = "";
//       $user_tmp = new User();
//       if ($user_tmp->getFromDB($this->obj->getField('users_id'))) {
//          $this->datas['##reservation.user##'] = $user_tmp->getName();
//       }
//       $this->datas['##reservation.begin##']   = Html::convDateTime($this->obj->getField('begin'));
//       $this->datas['##reservation.end##']     = Html::convDateTime($this->obj->getField('end'));
//       $this->datas['##reservation.comment##'] = $this->obj->getField('comment');
// 
//       $reservationitem = new ReservationItem();
//       $reservationitem->getFromDB($this->obj->getField('reservationitems_id'));
//       $itemtype = $reservationitem->getField('itemtype');
// 
//       if ($item = getItemForItemtype($itemtype)) {
//          $item->getFromDB($reservationitem->getField('items_id'));
//          $this->datas['##reservation.itemtype##']    = $item->getTypeName(1);
//          $this->datas['##reservation.item.name##']   = $item->getField('name');
//          $this->datas['##reservation.item.entity##']
//                      = Dropdown::getDropdownName('glpi_entities', $item->getField('entities_id'));
// 
//          if ($item->isField('users_id_tech')) {
//                $this->datas['##reservation.item.tech##']
//                            = Dropdown::getDropdownName('glpi_users',
//                                                          $item->getField('users_id_tech'));
//          }
//          $this->datas['##reservation.url##']
//                      = urldecode($CFG_GLPI["url_base"]."/index.php?redirect=".
//                                  strtolower($itemtype)."_".$reservationitem->getField('id'));
//       }



      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   function getTags() {

      $tags_all = array('recall.action'   => _n('Event', 'Events', 1),);

      foreach ($tags_all as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true));
      }

//       $tags_except_alert = array('reservation.user'        => __('Writer'),
//                                  'reservation.begin'       => __('Start date'),
//                                  'reservation.end'         => __('End date'),
//                                  'reservation.comment'     => __('Comments'),
//                                  'reservation.item.entity' => __('Entity'),
//                                  'reservation.item.name'   => __('Associated item'),
//                                  'reservation.item.tech'   => __('Technician in charge of the hardware'));
// 
//       foreach ($tags_except_alert as $tag => $label) {
//          $this->addTagToList(array('tag'    => $tag,
//                                    'label'  => $label,
//                                    'value'  => true,
//                                    'events' => array('new', 'update', 'delete')));
//       }
// 
//       $this->addTagToList(array('tag'     => 'items',
//                                 'label'   => __('Device list'),
//                                 'value'   => false,
//                                 'foreach' => true,
//                                 'events'  => array('alert')));
// 
//       $tag_alert = array('reservation.expirationdate' => __('End date'),
//                          'reservation.entity'         => __('Entity'));
// 
//       foreach ($tag_alert as $tag => $label) {
//          $this->addTagToList(array('tag'    => $tag,
//                                    'label'  => $label,
//                                    'value'  => true,
//                                    'events' => array('alert')));
//       }

      asort($this->tag_descriptions);
   }


   /**
    * Get item associated with the object on which the event was raised
    *
    * @param $event  (default '')
    *
    * @return the object associated with the itemtype
   **/
   function getObjectItem($event='') {

      if ($this->obj) {
         $ri = new ReservationItem();

         if ($ri->getFromDB($this->obj->getField('reservationitems_id'))) {
            $itemtype = $ri->getField('itemtype');

            if ($itemtype != NOT_AVAILABLE
                && $itemtype != ''
                && ($item = getItemForItemtype($itemtype))) {
               $item->getFromDB($ri->getField('items_id'));
               $this->target_object = $item;
            }
         }
      }
   }

}
?>