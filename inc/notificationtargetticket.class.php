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

// Class NotificationTarget
class NotificationTargetTicket extends NotificationTargetCommonITILObject {

   var $private_profiles = array();

   public $html_tags = array('##ticket.solution.description##');


   /**
    * @param $entity          (default '')
    * @param $event           (default '')
    * @param $object          (default null)
    * @param $options   array
    */
   function __construct($entity='', $event='', $object=null, $options=array()) {

      parent::__construct($entity, $event, $object, $options);

      $this->options['sendprivate'] = false;

      if (isset($options['followup_id'])) {
         $this->options['sendprivate'] = $options['is_private'];
      }

      if (isset($options['task_id'])) {
         $this->options['sendprivate'] = $options['is_private'];
      }
   }


   /**
    * @see NotificationTarget::validateSendTo()
   **/
   function validateSendTo(array $infos, $notify_me=false) {

      // Check global ones for notification to myself
      if (!parent::validateSendTo($infos, $notify_me)) {
         return false;
      }

      // Private object and no right to see private items : do not send
      if ($this->isPrivate()
          && (!isset($infos['additionnaloption']['show_private'])
              || !$infos['additionnaloption']['show_private'])) {
         return false;
      }
      return true;
   }


   /**
    * @param $event  (default '')
   **/
   function getSubjectPrefix($event='') {

      if ($event !='alertnotclosed') {
         $perso_tag = trim(Entity::getUsedConfig('notification_subject_tag', $this->getEntity(),
                                                 '', ''));

         if (empty($perso_tag)) {
            $perso_tag = 'GLPI';
         }
         return sprintf("[$perso_tag #%07d] ", $this->obj->getField('id'));
      }
      return parent::getSubjectPrefix();
   }


   /**
    * @since version 0.84
    *
    * @return string
   **/
   function getMessageID() {
      return "GLPI-".$this->obj->getField('id').".".time().".".rand(). "@".php_uname('n');
   }


   function addAdditionnalInfosForTarget() {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_profiles`
                WHERE `glpi_profiles`.`show_full_ticket` = '1'";

      foreach ($DB->request($query) as $data) {
         $this->private_profiles[$data['id']] = $data['id'];
      }
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
         $itemtype = $this->obj->getField('itemtype');

         if (($itemtype != NOT_AVAILABLE) && ($itemtype != '')
             && ($item = getItemForItemtype($itemtype))) {
            $item->getFromDB($this->obj->getField('items_id'));
            $this->target_object = $item;
         }
      }
   }


   /**
    * @param $data   array
   **/
   function addAdditionnalUserInfo(array $data) {
      global $DB;

      if (!isset($data['id'])) {
         return array('show_private' => 0);
      }

      $query = "SELECT COUNT(*) AS cpt
                FROM `glpi_profiles_users`
                WHERE `users_id`='".$data['id']."' ".
                      getEntitiesRestrictRequest("AND", "glpi_profiles_users", "entities_id",
                                                 $this->getEntity(), true)."
                      AND profiles_id IN (".implode(',',$this->private_profiles).")";
      $result = $DB->query($query);

      if ($DB->result($result,0,'cpt')) {
         return array('show_private' => 1);
      }
      return array('show_private' => 0);
   }


   /**
    *Get events related to tickets
   **/
   function getEvents() {

      $events = array('new'               => __('New ticket'),
                      'update'            => __('Update of a ticket'),
                      'solved'            => __('Ticket solved'),
                      'validation'        => __('Validation request'),
                      'validation_answer' => __('Validation request answer'),
                      'add_followup'      => __("New followup"),
                      'update_followup'   => __('Update of a followup'),
                      'delete_followup'   => __('Deletion of a followup'),
                      'add_task'          => __('New task'),
                      'update_task'       => __('Update of a task'),
                      'delete_task'       => __('Deletion of a task'),
                      'closed'            => __('Closing of the ticket'),
                      'delete'            => __('Deletion of a ticket'),
                      'alertnotclosed'    => __('Not solved tickets'),
                      'recall'            => __('Automatic reminders of SLAs'),
                      'satisfaction'      => __('Satisfaction survey'));
      asort($events);
      return $events;
   }


   /**
    * Restrict by profile and by config
    * to avoid send notification to a user without rights
   **/
   function getProfileJoinSql() {

      $query = " INNER JOIN `glpi_profiles_users`
                     ON (`glpi_profiles_users`.`users_id` = `glpi_users`.`id` ".
                         getEntitiesRestrictRequest("AND", "glpi_profiles_users", "entities_id",
                                                    $this->getEntity(), true).")";

      if ($this->isPrivate()) {
         $query .= " INNER JOIN `glpi_profiles`
                     ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`
                         AND `glpi_profiles`.`interface` = 'central'
                         AND `glpi_profiles`.`show_full_ticket` = '1') ";
      }
      return $query;
   }


   function isPrivate() {

      if (isset($this->options['sendprivate']) && ($this->options['sendprivate'] == 1)) {
         return true;
      }
      return false;
   }


   /**
    * @see NotificationTargetCommonITILObject::getDatasForObject()
   **/
   function getDatasForObject(CommonDBTM $item, array $options, $simple=false) {
      global $CFG_GLPI;

      // Common ITIL datas
      $datas                               = parent::getDatasForObject($item, $options, $simple);

      // Specific datas
      $datas["##ticket.costfixed"]         = $item->getField('cost_fixed');
      $datas["##ticket.costmaterial"]      = $item->getField('cost_material');
      $datas["##ticket.costtime"]          = $item->getField('cost_time');


      $datas['##ticket.urlvalidation##']
                        = $this->formatURL($options['additionnaloption']['usertype'],
                                           "ticket_".$item->getField("id")."_TicketValidation$1");
      $datas['##ticket.globalvalidation##']
                        = TicketValidation::getStatus($item->getField('global_validation'));
      $datas['##ticket.type##']
                        = Ticket::getTicketTypeName($item->getField('type'));
      $datas['##ticket.requesttype##']
                        = Dropdown::getDropdownName('glpi_requesttypes',
                                                    $item->getField('requesttypes_id'));

      $autoclose_value  = Entity::getUsedConfig('autoclose_delay', $this->getEntity(), '',
                                                Entity::CONFIG_NEVER);

      $datas['##ticket.autoclose##']             = __('Never');
      $datas['##lang.ticket.autoclosewarning##'] = "";
      if ($autoclose_value > 0) {
         $datas['##ticket.autoclose##'] = $autoclose_value;
         $datas['##lang.ticket.autoclosewarning##']
                     //TRANS: %s is the number of days before auto closing
            = sprintf(_n('Without a reply, the ticket will be automatically closed after %s day',
                         'Without a reply, the ticket will be automatically closed after %s days',
                         $autoclose_value),
                      $autoclose_value);
      }

      $datas['##ticket.sla##'] = '';
      if ($item->getField('slas_id')) {
         $datas['##ticket.sla##']
                     = Dropdown::getDropdownName('glpi_slas', $item->getField('slas_id'));
      }

      // is ticket deleted
      $datas['##ticket.isdeleted##'] = Dropdown::getYesNo($item->getField('is_deleted'));


      //Tags associated with the object linked to the ticket
      $datas['##ticket.itemtype##']           = '';
      $datas['##ticket.item.name##']          = '';
      $datas['##ticket.item.serial##']        = '';
      $datas['##ticket.item.otherserial##']   = '';
      $datas['##ticket.item.location##']      = '';
      $datas['##ticket.item.contact']         = '';
      $datas['##ticket.item.contactnumber##'] = '';
      $datas['##ticket.item.user##']          = '';
      $datas['##ticket.item.group##']         = '';
      $datas['##ticket.item.model##']         = '';

      if (isset($item->fields['itemtype'])
          && ($hardware = getItemForItemtype($item->fields['itemtype']))
          && isset($item->fields["items_id"])
          && $hardware->getFromDB($item->fields["items_id"])) {
         //Object type
         $datas['##ticket.itemtype##']  = $hardware->getTypeName();

         //Object name
         $datas['##ticket.item.name##'] = $hardware->getField('name');

         //Object serial
         if ($hardware->isField('serial')) {
            $datas['##ticket.item.serial##'] = $hardware->getField('serial');
         }

         //Object contact
         if ($hardware->isField('contact')) {
            $datas['##ticket.item.contact##'] = $hardware->getField('contact');
         }

         //Object contact num
         if ($hardware->isField('contact_num')) {
            $datas['##ticket.item.contactnumber##']
                        = $hardware->getField('contact_num');
         }

         //Object otherserial
         if ($hardware->isField('otherserial')) {
            $datas['##ticket.item.otherserial##']
                        = $hardware->getField('otherserial');
         }

         //Object location
         if ($hardware->isField('locations_id')) {
            $datas['##ticket.item.location##']
                        = Dropdown::getDropdownName('glpi_locations',
                                                    $hardware->getField('locations_id'));
         }

         //Object user
         if ($hardware->getField('users_id')) {
            $user_tmp = new User();
            if ($user_tmp->getFromDB($hardware->getField('users_id'))) {
               $datas['##ticket.item.user##'] = $user_tmp->getName();
            }
         }

         //Object group
         if ($hardware->getField('groups_id')) {
            $datas['##ticket.item.group##']
                        = Dropdown::getDropdownName('glpi_groups',
                                                    $hardware->getField('groups_id'));
         }

         $modeltable = getSingular($this->getTable())."models";
         $modelfield = getForeignKeyFieldForTable($modeltable);

         if ($hardware->isField($modelfield)) {
            $datas['##ticket.item.model##'] = $hardware->getField($modelfield);
         }

      }

      // Get tasks, followups, log, validation, satisfaction, linked tickets
      if (!$simple) {
         // Linked tickets
         $linked_tickets         = Ticket_Ticket::getLinkedTicketsTo($item->getField('id'));
         $datas['linkedtickets'] = array();
         if (count($linked_tickets)) {
            $linkedticket = new Ticket();
            foreach ($linked_tickets as $data) {
               if ($linkedticket->getFromDB($data['tickets_id'])) {
                  $tmp                          = array();
                  $tmp['##linkedticket.id##']   = $data['tickets_id'];
                  $tmp['##linkedticket.link##'] = Ticket_Ticket::getLinkName($data['link']);
                  $tmp['##linkedticket.url##']  = $this->formatURL($options['additionnaloption']['usertype'],
                                                                   "ticket_".$data['tickets_id']);


                  $tmp['##linkedticket.title##']   = $linkedticket->getField('name');
                  $tmp['##linkedticket.content##'] = $linkedticket->getField('content');

                  $datas['linkedtickets'][] = $tmp;
               }
            }
         }

         $datas['##ticket.numberoflinkedtickets##'] = count($datas['linkedtickets']);

         $restrict = "`tickets_id`='".$item->getField('id')."'";
         $problems = getAllDatasFromTable('glpi_problems_tickets',$restrict);
         $datas['problems'] = array();
         if (count($problems)) {
            $problem = new Problem();
            foreach ($problems as $data) {
               if ($problem->getFromDB($data['problems_id'])) {
                  $tmp                       = array();
                  $tmp['##problem.id##']     = $data['problems_id'];
                  $tmp['##problem.date##']   = $problem->getField('date');
                  $tmp['##problem.title##']  = $problem->getField('name');
                  $tmp['##problem.url##']    = $this->formatURL($options['additionnaloption']['usertype'],
                                                                "problem_".$data['problems_id']);
                  $tmp['##problem.content##'] = $problem->getField('content');

                  $datas['problems'][] = $tmp;
               }
            }
         }

         $datas['##ticket.numberofproblems##'] = count($datas['problems']);

         $restrict = "`tickets_id`='".$item->getField('id')."'";
         if (!isset($options['additionnaloption']['show_private'])
             || !$options['additionnaloption']['show_private']) {
            $restrict .= " AND `is_private` = '0'";
         }
         $restrict .= " ORDER BY `date` DESC, `id` ASC";

         //Task infos
         $tasks = getAllDatasFromTable('glpi_tickettasks',$restrict);

         foreach ($tasks as $task) {
            $tmp                          = array();
            $tmp['##task.isprivate##']    = Dropdown::getYesNo($task['is_private']);
            $tmp['##task.author##']       = Html::clean(getUserName($task['users_id']));
            $tmp['##task.category##']     = Dropdown::getDropdownName('glpi_taskcategories',
                                                                      $task['taskcategories_id']);
            $tmp['##task.date##']         = Html::convDateTime($task['date']);
            $tmp['##task.description##']  = $task['content'];
            $tmp['##task.time##']         = Ticket::getActionTime($task['actiontime']);


            $tmp['##task.user##']         = "";
            $tmp['##task.begin##']        = "";
            $tmp['##task.end##']          = "";
            $tmp['##task.status##']       = "";
            if (!is_null($task['begin'])) {
               $tmp['##task.user##']      = Html::clean(getUserName($task['users_id_tech']));
               $tmp['##task.begin##']     = Html::convDateTime($task['begin']);
               $tmp['##task.end##']       = Html::convDateTime($task['end']);
               $tmp['##task.status##']    = Planning::getState($task['state']);
            }

            $datas['tasks'][] = $tmp;
         }

         $datas['##ticket.numberoftasks##'] = 0;
         if (!empty($datas['tasks'])) {
            $datas['##ticket.numberoftasks##'] = count($datas['tasks']);
         }

         //Followup infos
         $followups = getAllDatasFromTable('glpi_ticketfollowups',$restrict);
         foreach ($followups as $followup) {
            $tmp                             = array();
            $tmp['##followup.isprivate##']   = Dropdown::getYesNo($followup['is_private']);
            $tmp['##followup.author##']      = Html::clean(getUserName($followup['users_id']));
            $tmp['##followup.requesttype##'] = Dropdown::getDropdownName('glpi_requesttypes',
                                                                         $followup['requesttypes_id']);
            $tmp['##followup.date##']        = Html::convDateTime($followup['date']);
            $tmp['##followup.description##'] = $followup['content'];
            $datas['followups'][]            = $tmp;
         }

         $datas['##ticket.numberoffollowups##'] = 0;
         if (isset($datas['followups'])) {
            $datas['##ticket.numberoffollowups##'] = count($datas['followups']);
         }

         //Validation infos
         $restrict = "`tickets_id`='".$item->getField('id')."'";

         if (isset($options['validation_id']) && $options['validation_id']) {
            $restrict .= " AND `glpi_ticketvalidations`.`id` = '".$options['validation_id']."'";
         }

         $restrict .= " ORDER BY `submission_date` DESC, `id` ASC";

         $validations = getAllDatasFromTable('glpi_ticketvalidations',$restrict);

         foreach ($validations as $validation) {
            $tmp = array();
            $tmp['##validation.submission.title##']
                  //TRANS: %s is the user name
                  = sprintf(__('An approval request has been submitted by %s'),
                            Html::clean(getUserName($validation['users_id'])));
            $tmp['##validation.answer.title##']
                  //TRANS: %s is the user name
                  = sprintf(__('An answer to an an approval request was produced by %s'),
                            Html::clean(getUserName($validation['users_id_validate'])));

            $tmp['##validation.author##'] = Html::clean(getUserName($validation['users_id']));

            $tmp['##validation.status##'] = TicketValidation::getStatus($validation['status']);
            $tmp['##validation.storestatus##']
                                          = $validation['status'];
            $tmp['##validation.submissiondate##']
                                          = Html::convDateTime($validation['submission_date']);
            $tmp['##validation.commentsubmission##']
                                          = $validation['comment_submission'];
            $tmp['##validation.validationdate##']
                                          = Html::convDateTime($validation['validation_date']);
            $tmp['##validation.validator##']
                                          =  Html::clean(getUserName($validation['users_id_validate']));
            $tmp['##validation.commentvalidation##']
                                          = $validation['comment_validation'];
            $datas['validations'][]       = $tmp;
         }

         // Ticket Satisfaction
         $inquest                                = new TicketSatisfaction();
         $datas['##satisfaction.type##']         = '';
         $datas['##satisfaction.datebegin##']    = '';
         $datas['##satisfaction.dateanswered##'] = '';
         $datas['##satisfaction.satisfaction##'] = '';
         $datas['##satisfaction.description##']  = '';

         if ($inquest->getFromDB($item->getField('id'))) {
            // internal inquest
            if ($inquest->fields['type'] == 1) {
               $datas['##ticket.urlsatisfaction##']
                           = $this->formatURL($options['additionnaloption']['usertype'],
                                              "ticket_".$item->getField("id").'_Ticket$3');
            // external inquest
            } else if ($inquest->fields['type'] == 2) {
               $datas['##ticket.urlsatisfaction##'] = Entity::generateLinkSatisfaction($item);
            }

            $datas['##satisfaction.type##'] = $inquest->getTypeInquestName($inquest->getfield('type'));
            $datas['##satisfaction.datebegin##']
                                            = Html::convDateTime($inquest->fields['date_begin']);
            $datas['##satisfaction.dateanswered##']
                                            = Html::convDateTime($inquest->fields['date_answered']);
            $datas['##satisfaction.satisfaction##']
                                             = $inquest->fields['satisfaction'];
            $datas['##satisfaction.description##']
                                             = $inquest->fields['comment'];
         }
      }

      return $datas;
   }


   static function isAuthorMailingActivatedForHelpdesk() {
      global $DB,$CFG_GLPI;

      if ($CFG_GLPI['use_mailing']) {
         $query = "SELECT COUNT(`glpi_notifications`.`id`)
                   FROM `glpi_notifications`
                   INNER JOIN `glpi_notificationtargets`
                     ON (`glpi_notifications`.`id` = `glpi_notificationtargets`.`notifications_id`)
                   WHERE `glpi_notifications`.`itemtype` = 'Ticket'
                         AND `glpi_notifications`.`mode` = 'mail'
                         AND `glpi_notificationtargets`.`type` = '".Notification::USER_TYPE."'
                         AND `glpi_notificationtargets`.`items_id` = '".Notification::AUTHOR."'";

         if ($result = $DB->query($query)) {
            if ($DB->result($result,0,0) > 0) {
               return true;
            }
         }
      }
      return false;
   }


   function getTags() {

      parent::getTags();

      //Locales
      $tags = array('ticket.type'                  => __('Type'),
                    'ticket.sla'                   => __('SLA'),
                    'ticket.requesttype'           => __('Request source'),
                    'ticket.itemtype'              => __('Item type'),
                    'ticket.item.name'             => __('Associated item'),
                    'ticket.item.serial'           => __('Serial number'),
                    'ticket.item.otherserial'      => __('Inventory number'),
                    'ticket.item.location'         => __('Location'),
                    'ticket.item.model'            => __('Model'),
                    'ticket.item.contact'          => __('Alternate username'),
                    'ticket.item.contactnumber'    => __('Alternate username number'),
                    'ticket.item.user'             => __('User'),
                    'ticket.item.group'            => __('Group'),
                    'ticket.costtime'              => __('Time cost'),
                    'ticket.costfixed'             => __('Fixed cost'),
                    'ticket.costmaterial'          => __('Material cost'),
                    'ticket.isdeleted'             => __('Deleted'),
                    'task.author'                  => __('Writer'),
                    'task.isprivate'               => __('Private'),
                    'task.date'                    => __('Opening date'),
                    'task.description'             => __('Description'),
                    'task.category'                => __('Category'),
                    'task.time'                    => __('Total duration'),
                    'task.user'                    => __('By'),
                    'task.begin'                   => __('Start date'),
                    'task.end'                     => __('End date'),
                    'task.status'                  => __('Status'),
                    'followup.date'                => __('Opening date'),
                    'followup.isprivate'           => __('Private'),
                    'followup.author'              => __('Writer'),
                    'followup.description'         => __('Description'),
                    'followup.requesttype'         => __('Request source'),
                    'ticket.numberoffollowups'     => __('Number of followups'),
                    'ticket.numberoftasks'         => __('Number of tasks'),
                    'ticket.numberoflinkedtickets' => __('Number of linked tickets'),
                    'ticket.numberofproblems'      => __('Number of problems'),
                    'ticket.autoclose'             => __('Automatic closing of solved tickets after'),
                    'ticket.globalvalidation'      => __('Global approval status'),
                  );

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => NotificationTarget::TAG_FOR_ALL_EVENTS));
      }

     //Events specific for validation
     $tags = array('validation.author'            => __('Requester'),
                   'validation.status'            => __('Status of the approval request'),
                   'validation.submissiondate'    => sprintf(__('%1$s: %2$s'), __('Request'),
                                                             __('Date')),
                   'validation.commentsubmission' => sprintf(__('%1$s: %2$s'), __('Request'),
                                                             __('Comments')),
                   'validation.validationdate'    => sprintf(__('%1$s: %2$s'), __('Validation'),
                                                             __('Date')),
                   'validation.validator'         => __('Decision-maker'),
                   'validation.commentvalidation' => sprintf(__('%1$s: %2$s'), __('Validation'),
                                                             __('Comments'))
                   );

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => array('validation', 'validation_answer')));
      }
      //Tags without lang for validation
      $tags = array('validation.submission.title' => __('A validation request has been submitted'),
                    'validation.answer.title'     => __('An answer to a validation request was produced')
                    );

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false,
                                   'events' => array('validation', 'validation_answer')));
      }



      // Events for ticket satisfaction
      $tags = array('satisfaction.datebegin'           => __('Creation date of the satisfaction survey'),
                    'satisfaction.dateanswered'        => __('Response date to the satisfaction survey'),
                    'satisfaction.satisfaction'        => __('Satisfaction'),
                    'satisfaction.description'         => __('Comments to the satisfaction survey'));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => array('satisfaction')));
      }

      $tags = array('satisfaction.type'  => __('Survey type'),);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'lang'   => false,
                                   'events' => array('satisfaction')));
      }

      $tags = array('satisfaction.text' => __('Invitation to fill out the survey'));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => false,
                                   'lang'   => true,
                                   'events' => array('satisfaction')));
      }

     //Foreach global tags
     $tags = array('followups'     => _n('Followup', 'Followups', 2),
                   'tasks'         => _n('Task', 'Tasks', 2),
                   'validations'   => _n('Validation','Validations',2),
                   'linkedtickets' => _n('Linked ticket', 'Linked tickets', 2),
                   'problems'      => _n('Problem', 'Problems', 2));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true));
      }


      //Tags with just lang
      $tags = array('ticket.linkedtickets'    => _n('Linked ticket', 'Linked tickets', 2),
                    'ticket.problems'         => _n('Problem', 'Problems', 2),
                    'ticket.autoclosewarning'
                     => sprintf(_n('Without a reply, the ticket will be automatically closed after %s day',
                                   'Without a reply, the ticket will be automatically closed after %s days',
                                   2),
                                '?'));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true));
      }


      //Foreach tag for alertnotclosed
      $this->addTagToList(array('tag'     => 'tickets',
                                'label'   => __('Not solved tickets'),
                                'value'   => false,
                                'foreach' => true,
                                'events'  => array('alertnotclosed')));

      //Tags without lang
      $tags = array('ticket.urlvalidation'    => sprintf(__('%1$s: %2$s'), __('Validation request'),
                                                         __('URL')),
                    'ticket.urlsatisfaction'  => sprintf(__('%1$s: %2$s'), __('Satisfaction'),
                                                         __('URL')),
                    'linkedticket.id'         => sprintf(__('%1$s: %2$s'),
                                                         _n('Linked ticket', 'Linked tickets', 1),
                                                         __('ID')),
                    'linkedticket.link'       => sprintf(__('%1$s: %2$s'),
                                                         _n('Linked ticket', 'Linked tickets', 1),
                                                         __('Link')),
                    'linkedticket.url'        => sprintf(__('%1$s: %2$s'),
                                                         _n('Linked ticket', 'Linked tickets', 1),
                                                         __('URL')),
                    'linkedticket.title'      => sprintf(__('%1$s: %2$s'),
                                                         _n('Linked ticket', 'Linked tickets', 1),
                                                         __('Title')),
                    'linkedticket.content'    => sprintf(__('%1$s: %2$s'),
                                                         _n('Linked ticket', 'Linked tickets', 1),
                                                         __('Description')),
                    'problem.id'              => sprintf(__('%1$s: %2$s'), __('Problem'), __('ID')),
                    'problem.date'            => sprintf(__('%1$s: %2$s'), __('Problem'), __('Date')),
                    'problem.url'             => sprintf(__('%1$s: %2$s'), __('Problem'), ('URL')),
                    'problem.title'           => sprintf(__('%1$s: %2$s'), __('Problem'),
                                                         __('Title')),
                    'problem.content'         => sprintf(__('%1$s: %2$s'), __('Problem'),
                                                         __('Description'))
                   );

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false));
      }

      //Tickets with a fixed set of values
      $allowed_validation = array();
      $status = TicketValidation::getAllStatusArray(false,true);
      foreach ($status as $key => $value) {
         $allowed_validation[] = $key;
      }

      $tags = array('validation.validationstatus'
                     => array('text'           => __('Status value in database'),
                              'allowed_values' => $allowed_validation));

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'            => $tag,
                                   'label'          => $label['text'],
                                   'value'          => true,
                                   'lang'           => false,
                                   'allowed_values' => $label['allowed_values']));
      }

      asort($this->tag_descriptions);
   }

}
?>
