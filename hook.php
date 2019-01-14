<?php

include_once 'inc/processmaker.class.php';

if (!function_exists('arTableExists')) {
   function arTableExists($table) {
      global $DB;
      if (method_exists( $DB, 'tableExists')) {
         return $DB->tableExists($table);
      } else {
         return TableExists($table);
      }
   }
}

if (!function_exists('arFieldExists')) {
   function arFieldExists($table, $field, $usecache = true) {
      global $DB;
      if (method_exists( $DB, 'fieldExists')) {
         return $DB->fieldExists($table, $field, $usecache);
      } else {
         return FieldExists($table, $field, $usecache);
      }
   }
}

function plugin_processmaker_MassiveActions($type) {
   switch ($type) {
      case 'PluginProcessmakerProcess' :
         if (plugin_processmaker_haveRight('config', UPDATE)) {
            return array('plugin_processmaker_taskrefresh' => __('Synchronize Task List', 'processmaker'));
         }
          break;
      case 'PluginProcessmakerProcess_Profile' :
         if (plugin_processmaker_haveRight('config', UPDATE)) {
            return array('purge' => __('Delete permanently'));
         }
         break;
      //case 'PluginProcessmakerCase' :
      //   if (plugin_processmaker_haveRight("case", DELETE)) {
      //      return array('purge' => __('Delete permanently'));
      //   }
      //break;
   }
   return array();
}


//function plugin_processmaker_MassiveActionsDisplay($options) {

//   switch ($options['itemtype']) {
//      case 'PluginProcessmakerProcess' :
//      //case 'PluginProcessmakerCase' :
//         switch ($options['action']) {
//            case "plugin_processmaker_taskrefresh" :
//            //case "plugin_processmaker_purgecase" :
//               echo "<input type='submit' name='massiveaction' class='submit' ".
//                     "value='".__('Post')."'>";
//               break;

//         }
//         break;
//   }
//   return "";
//}


//function plugin_processmaker_MassiveActionsProcess($data) {

//   switch ($data['action']) {

//      case "plugin_processmaker_taskrefresh" :
//         if ($data['itemtype'] == 'PluginProcessmakerProcess') {
//            foreach ($data["item"] as $key => $val) {
//               if ($val == 1) {
//                  $process = new PluginProcessmakerProcess;
//                  $process->refreshTasks( array( 'id' => $key ) );

//               }
//            }
//         }
//         break;
//      //case "plugin_processmaker_purgecase":
//      //   if ($data['itemtype'] == 'PluginProcessmakerCase') {
//      //      foreach ($data["item"] as $key => $val) {
//      //         if ($val == 1) {
//      //            $locCase= new PluginProcessmakerCase;
//      //            //$locCase->( array( 'id' => $key ) );

//      //         }
//      //      }
//      //   }
//      //   break;
//      //case 'plugin_processmaker_process_profile_delete' :
//      //   if ($data['itemtype'] == 'PluginProcessmakerProcess_Profile') {
//      //      foreach ($data["item"] as $key => $val) {
//      //         if ($val == 1) {
//      //            $process_profile = new PluginProcessmakerProcess_Profile;
//      //            $process_profile->delete( array( 'id' => $key ), true );

//      //         }
//      //      }
//      //   }
//      //    break;

//   }
//}

/**
 * Summary of plugin_processmaker_install
 *      Creates tables and initializes tasks, "GLPI Requesters" group
 *      and so on
 * @return true or die!
 */
function plugin_processmaker_install() {

   if (!arTableExists("glpi_plugin_processmaker_cases")) {
      // new installation
      include_once(GLPI_ROOT."/plugins/processmaker/install/install.php");
      processmaker_install();

   } else {
      // upgrade installation
      include_once(GLPI_ROOT."/plugins/processmaker/install/update.php");
      processmaker_update();
   }

   // To be called for each task managed by the plugin
   // task in class
   CronTask::Register('PluginProcessmakerProcessmaker', 'pmusers', DAY_TIMESTAMP, array( 'state' => CronTask::STATE_DISABLE, 'mode' => CronTask::MODE_EXTERNAL));
   CronTask::Register('PluginProcessmakerProcessmaker', 'pmorphancases', DAY_TIMESTAMP, array('param' => 10, 'state' => CronTask::STATE_DISABLE, 'mode' => CronTask::MODE_EXTERNAL));
   CronTask::Register('PluginProcessmakerProcessmaker', 'pmtaskactions', MINUTE_TIMESTAMP, array('state' => CronTask::STATE_DISABLE, 'mode' => CronTask::MODE_EXTERNAL));

   // required because autoload doesn't work for unactive plugin'
   include_once(GLPI_ROOT."/plugins/processmaker/inc/profile.class.php");
   PluginProcessmakerProfile::createAdminAccess($_SESSION['glpiactiveprofile']['id']);

   return true;
}

function plugin_processmaker_uninstall() {

   CronTask::Unregister('PluginProcessmakerProcessmaker');

   return true;
}


function plugin_processmaker_getAddSearchOptions($itemtype) {

   $sopt = array();
   // TODO add Change and Problem + other fields to the search
   if ($itemtype == 'Ticket') {
      $sopt[10001]['table']     = 'glpi_plugin_processmaker_cases';
      $sopt[10001]['field']     = 'case_status';
      //$sopt[1001]['linkfield'] = 'id';
      $sopt[10001]['massiveaction'] = false;
      $sopt[10001]['name']      = __('Case', 'processmaker').' - '.__('Status', 'processmaker');
      $sopt[10001]['datatype']       = 'text';
      $sopt[10001]['forcegroupby'] = true;
      //$sopt[10001]['searchtype'] = 'equals';

      //$sopt[1001]['itemlink_type'] = 'PluginProcessmakerTicketcase';

      //$sopt[1001]['table']          = 'glpi_plugin_processmaker_ticketcase';
      //$sopt[1001]['field']          = 'case_status';
      //$sopt[1001]['massiveaction']  = false;
      //$sopt[1001]['name']           = 'Case - Status';
      //$sopt[1001]['forcegroupby']   = true;
      //$sopt[1001]['datatype']       = 'itemlink';
      // $sopt[1001]['itemlink_type']  = 'PluginProcessmakerProcessmaker';
      //$sopt[1001]['joinparams']     = array('beforejoin'
      //                                       => array('table'      => 'glpi_plugin_processmaker_ticketcase',
      //                                                'linkfield' => 'ticket_id'));

      //$sopt[1001]['joinparams']['jointype'] = "itemtype_id";
      //$sopt[1001]['pfields_type']  = ;
   }
    return $sopt;
}

function plugin_processmaker_addLeftJoin($type,$ref_table,$new_table,$linkfield,&$already_link_tables) {

   switch ($type) {

      case 'Ticket':
         switch ($new_table) {

            case "glpi_plugin_processmaker_cases" :
               $out= " LEFT JOIN `glpi_plugin_processmaker_cases`
                        ON (`$ref_table`.`id` = `glpi_plugin_processmaker_cases`.`items_id` AND `glpi_plugin_processmaker_cases`.`itemtype` like 'Ticket') ";
               return $out;

         }

      return "";

   }

    return "";
}

/**
 * Summary of plugin_pre_item_update_processmaker
 * @param CommonITILObject $parm is an object
 * @return void
 */
function plugin_pre_item_update_processmaker(CommonITILObject $parm) {
   global $DB;//, $PM_SOAP;

      if (isset($_SESSION['glpiname'])) { // && $parm->getType() == 'Ticket') {
      $locVar = array( );
      foreach ($parm->input as $key => $val) {
         switch ($key) {
            case 'global_validation' :
               $locVar[ 'GLPI_TICKET_GLOBAL_VALIDATION' ] = $val;
               break;
            case 'itilcategories_id' :
               $locVar[ 'GLPI_ITEM_ITIL_CATEGORY_ID' ] = $val;
               break;
            case 'date' :
               $locVar[ 'GLPI_ITEM_OPENING_DATE' ] = $val;
               break;
            case 'due_date' :
               $locVar[ 'GLPI_TICKET_DUE_DATE' ] = $val;
               $locVar[ 'GLPI_ITEM_DUE_DATE' ] = $val;
               break;
            case 'urgency' :
               $locVar[ 'GLPI_TICKET_URGENCY' ] = $val;
               $locVar[ 'GLPI_ITEM_URGENCY' ] = $val;
               break;
            case 'impact' :
               $locVar[ 'GLPI_ITEM_IMPACT' ] = $val;
               break;
            case 'priority' :
               $locVar[ 'GLPI_ITEM_PRIORITY' ] = $val;
               break;
         }
      }

      $itemId = $parm->getID();
      $itemType = $parm->getType();

      $locCase = new PluginProcessmakerCase;
      foreach(PluginProcessmakerCase::getIDsFromItem($itemType, $itemId ) as $cases_id){
         $locCase->getFromDB($cases_id);
         $locCase->sendVariables($locVar);

         // if entities_id of item has been changed, then must update case
         if (isset($parm->input['entities_id']) && $parm->input['entities_id'] != $parm->fields['entities_id']) {
            $locCase->update(['id' => $cases_id, 'entities_id' => $parm->input['entities_id']]);
         }
      }
   }


}

/**
 * Summary of plugin_item_update_processmaker_satisfaction
 * inject satisfaction survey into case
 * @param mixed $parm is the object
 */
function plugin_item_update_processmaker_satisfaction($parm) {

   $cases = PluginProcessmakerCase::getIDsFromItem('Ticket', $parm->fields['tickets_id']);
   foreach ($cases as $cases_id) {
      $locCase = new PluginProcessmakerCase;
      if ($locCase->getFromDB($cases_id)) {
         // case is existing for this item
         $locCase->sendVariables( ['GLPI_SATISFACTION_QUALITY' => $parm->fields['satisfaction']] );
      }
   }
}

///**
// * Summary of plugin_pre_item_purge_processmaker
// * @param mixed $parm is the object
// */
//function plugin_pre_item_purge_processmaker ( $parm ) {

//   if ($parm->getType() == 'Ticket_User' && is_array( $parm->fields ) && isset( $parm->fields['type'] )  && $parm->fields['type'] == 2) {
//      $itemId = $parm->fields['tickets_id'];
//      $itemType = 'Ticket';
//      $technicians = PluginProcessmakerProcessmaker::getItemUsers( $itemType, $itemId, 2 ); // 2 for technicians

//      if (PluginProcessmakerCase::getIDFromItem($itemType, $itemId) && count($technicians) == 1) {
//         $parm->input = null; // to cancel deletion of the last tech in the ticket
//      }
//   }
//}

///**
// * Summary of plugin_item_purge_processmaker
// * @param mixed $parm is the object
// */
//function plugin_item_purge_processmaker($parm) {
//   global $DB, $PM_SOAP;

//   //$objects = ['Ticket', 'Change', 'Problem'];
//   $object_users = ['Ticket_User', 'Change_User', 'Problem_User'];

//   if (in_array($parm->getType(), $object_users) && is_array( $parm->fields ) && isset( $parm->fields['type'] )  && $parm->fields['type'] == 2) {

//      // We just deleted a tech from this ticket then we must if needed "de-assign" the tasks assigned to this tech
//      // and re-assign them to the first tech in the list !!!!

//      $itemType = strtolower(explode('_', $parm->getType())[0]); // $parm->getType() returns 'Ticket_User';
//      $itemId = $parm->fields[$itemType.'s_id'];
//      $cases = PluginProcessmakerCase::getIDsFromItem($itemType, $itemId);
//      foreach ($cases as $cases_id) {
//         // cases are existing for this item
//         $locCase = new PluginProcessmakerCase;
//         if ($locCase->getFromDB($cases_id)) {
//            $technicians = PluginProcessmakerProcessmaker::getItemUsers($itemType, $itemId, CommonITILActor::ASSIGN);

//            $locVars = array( 'GLPI_TICKET_TECHNICIAN_GLPI_ID' => $technicians[0]['glpi_id'],
//                              'GLPI_ITEM_TECHNICIAN_GLPI_ID'   => $technicians[0]['glpi_id'],
//                              'GLPI_TICKET_TECHNICIAN_PM_ID'   => $technicians[0]['pm_id'],
//                              'GLPI_ITEM_TECHNICIAN_PM_ID'     => $technicians[0]['pm_id']
//                            );

//            // and we must find all tasks assigned to this former user and re-assigned them to new user (if any :))!
//            $caseInfo = $locCase->getCaseInfo( );
//            if ($caseInfo !== false) {
//               $locCase->sendVariables( $locVars);
//               // need to get info on the thread of the GLPI current user
//               // we must retreive currentGLPI user from this array
//               $GLPICurrentPMUserId = PluginProcessmakerUser::getPMUserId( $parm->fields['users_id'] );
//               if (property_exists($caseInfo, 'currentUsers') && is_array( $caseInfo->currentUsers )) {
//                  foreach ($caseInfo->currentUsers as $caseUser) {
//                     if ($caseUser->userId == $GLPICurrentPMUserId && in_array( $caseUser->delThreadStatus, array('DRAFT', 'OPEN', 'PAUSE' ) )) {
//                        $locCase->reassignCase($caseUser->delIndex, $caseUser->taskId, $caseUser->delThread, $parm->fields['users_id'], $technicians[0]['pm_id'] );
//                     }
//                  }
//               }
//            }

//         }
//      }
//   }
//}

function plugin_processmaker_post_init() {
   global $PM_DB, $PM_SOAP;
   if (!isset($PM_DB)) {
      $PM_DB = new PluginProcessmakerDB;
   }
   if (!isset($PM_SOAP)) {
      $PM_SOAP = new PluginProcessmakerProcessmaker;
      // and default login is current running user if any
      if (Session::getLoginUserID() ) {
         $PM_SOAP->login();
      }
   }
}


function plugin_processmaker_giveItem($itemtype,$ID,$data,$num) {

   return;
}

function plugin_processmaker_change_profile($parm) {
   if ($_SESSION['glpiactiveprofile']['interface'] == "helpdesk") {
      // must add the rights for simplified interface
      $_SESSION['glpiactiveprofile']['plugin_processmaker_case'] = READ;
   }
}

/**
   * Summary of plugin_item_add_update_processmaker_tasks
   * @param mixed $parm
   */
function plugin_item_update_processmaker_tasks($parm) {
   global $DB, $CFG_GLPI, $PM_SOAP;

   // we need to test if a specific case is completed, and if so
   // we should complete the linked cases (via linked tickets)
   $pmTaskCat = new PluginProcessmakerTaskCategory;
   if ($pmTaskCat->getFromDBbyCategory( $parm->fields['taskcategories_id'] )
            && in_array( 'state', $parm->updates )
            && $parm->input['state'] == 2) {  // the task has just been set to DONE state

      $itemtype = str_replace( 'Task', '', $parm->getType() );

      $pmTask = new PluginProcessmakerTask($parm->getType());
      $pmTask->getFromDB($parm->fields['id']);

      $locCase = new PluginProcessmakerCase;
      $locCase->getFromDB($pmTask->fields['plugin_processmaker_cases_id']); //Item($itemtype, $parm->fields['tickets_id']);
      $srccase_guid = $locCase->fields['case_guid'];

      foreach ($DB->request( 'glpi_plugin_processmaker_caselinks', "is_active = 1 AND sourcetask_guid='".$pmTaskCat->fields['pm_task_guid']."'") as $targetTask) {

         // Must check the condition
         $casevariables = array();

         $matches = array();
         if (preg_match_all( "/@@(\w+)/u", $targetTask['sourcecondition'], $matches  )) {
            $casevariables = $matches[1];
         }

         $targetTask['targetactions'] = array(); // empty array by default
         foreach ($DB->request( 'glpi_plugin_processmaker_caselinkactions', 'plugin_processmaker_caselinks_id = '.$targetTask['id']) as $actionvalue) {
            $targetTask['targetactions'][$actionvalue['name']] = $actionvalue['value'];
            if (preg_match_all( "/@@(\w+)/u", $actionvalue['value'], $matches  )) {
               $casevariables = array_merge( $casevariables, $matches[1] );
            }
         }
         $externalapplication = false; // by default
         if ($targetTask['is_externaldata'] && isset($targetTask['externalapplication'])) {
            // must read some values
            $externalapplication = json_decode( $targetTask['externalapplication'], true );
            // must be of the form
            // {"method":"POST","url":"http://arsupd201.ar.ray.group:8000/search_by_userid/","params":{"user":"@@USER_ID","system":"GPP","list":"@@ROLE_LIST"}}
            // Where method is the POST, GET, ... method
            // url is the URL to be called
            // params is a list of parameters to get from running case
            foreach ($externalapplication['params'] as $paramname => $variable) {
               if (preg_match_all( "/@@(\w+)/u", $variable, $matches  )) {
                  $casevariables = array_merge( $casevariables, $matches[1] );
               }
            }
         }

         // ask for those case variables
         //$PM_SOAP = new PluginProcessmakerProcessmaker();
         //$PM_SOAP->login( );
         // now tries to get the variables to check condition
         $infoForTasks = $locCase->getVariables($casevariables);
         foreach ($infoForTasks as $casevar => $varval) {
            $infoForTasks[ "@@$casevar" ] = "'$varval'";
            unset( $infoForTasks[ $casevar ] );
         }
         $targetTask['sourcecondition'] = str_replace( array_keys($infoForTasks), $infoForTasks, $targetTask['sourcecondition'] );

         if (eval( "return ".$targetTask['sourcecondition'].";" )) {
            // look at each linked ticket if a case is attached and then if a task like $val is TO_DO
            // then will try to routeCase for each tasks in $val

            $postdata = array();
            foreach ($targetTask['targetactions'] as $action => $actionvalue) {
               $postdata['form'][$action] = eval( "return ".str_replace( array_keys($infoForTasks), $infoForTasks, $actionvalue)." ;" );
            }
            $postdata['UID']                        = $targetTask['targetdynaform_guid'];
            $postdata['__DynaformName__']           = $targetTask['targetprocess_guid']."_".$targetTask['targetdynaform_guid'];
            $postdata['__notValidateThisFields__']  = '[]';
            $postdata['DynaformRequiredFields']     = '[]';
            $postdata['form']['btnGLPISendRequest'] = 'submit';

            $externalapplicationparams = array();
            if ($externalapplication) {
               // must call curl
               foreach ($externalapplication['params'] as $paramname => $variable) {
                  $externalapplicationparams[$paramname] = eval( "return ".str_replace( array_keys($infoForTasks), $infoForTasks, $variable)." ;" );
               }
               $externalapplicationparams['callback'] = $CFG_GLPI["url_base"]."/plugins/processmaker/ajax/asynchronousdatas.php";
               $ch = curl_init();
               $externalapplication['url'] = eval( "return '".str_replace( array_keys($infoForTasks), $infoForTasks, $externalapplication['url'])."' ;" ); // '???
               curl_setopt($ch, CURLOPT_URL, $externalapplication['url'] );
            }

            if ($targetTask['is_self']) {
               $taskCase = $PM_SOAP->taskCase( $srccase_guid );
               foreach ($taskCase as $task) {
                  // search for target task guid
                  if ($task->guid == $targetTask['targettask_guid']) {
                     break;
                  }
               }

               $postdata['APP_UID']                    = $srccase_guid;
               $postdata['DEL_INDEX']                  = $task->delegate;

               //need to get the 'ProcessMaker' user
               $pmconfig = $PM_SOAP->config; //PluginProcessmakerConfig::getInstance();

               $cronaction = new PluginProcessmakerCrontaskaction;
               $cronaction->add( array( 'plugin_processmaker_caselinks_id' => $targetTask['id'],
                                        'plugin_processmaker_cases_id' => $locCase->getID(),
                                          //'itemtype'         => $itemtype,
                                          //'items_id'         => $parm->fields['tickets_id'],
                                          'users_id'         => $pmconfig->fields['users_id'],
                                          'is_targettoclaim' => $targetTask['is_targettoclaim'],
                                          'state'            => ($targetTask['is_externaldata'] ? PluginProcessmakerCrontaskaction::WAITING_DATA : PluginProcessmakerCrontaskaction::DATA_READY),
                                          'postdata'         => json_encode( $postdata, JSON_HEX_APOS | JSON_HEX_QUOT),
                                          'logs_out'         => json_encode( $externalapplicationparams, JSON_HEX_APOS | JSON_HEX_QUOT)
                                          ),
                                 null,
                                 false);

               if ($externalapplication) {
                  // must call external application in order to get the needed data asynchroneously
                  // must be of the form
                  // {"url":"http://arsupd201.ar.ray.group:8000/search_by_userid/","params":{"user":"@@USER_ID","system":"GPP","list":"@@ROLE_LIST"}}
                  // url is the URL to be called

                  $externalapplicationparams['id'] = $cronaction->getID();

                  $externalapplicationparams = json_encode( $externalapplicationparams, JSON_HEX_APOS | JSON_HEX_QUOT);

                  curl_setopt($ch, CURLOPT_POST, 1);
                  curl_setopt($ch, CURLOPT_POSTFIELDS, $externalapplicationparams);
                  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($externalapplicationparams), 'Expect:']);
                  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );

                   //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1 ) ;
                   //curl_setopt($ch, CURLOPT_PROXY, "localhost:8889");

                  $response = curl_exec ($ch);

                  //Toolbox::logDebug( $response ) ;

                  curl_close ($ch);
               }
               //               }
            } else {
               foreach (Ticket_Ticket::getLinkedTicketsTo( $parm->fields['tickets_id'] ) as $tlink) {
                  if ($tlink['link'] == Ticket_Ticket::LINK_TO) {
                     $query = "SELECT glpi_plugin_processmaker_cases.id, MAX(glpi_plugin_processmaker_tasks.del_index) AS del_index FROM glpi_tickettasks
                           JOIN glpi_plugin_processmaker_taskcategories ON glpi_plugin_processmaker_taskcategories.taskcategories_id=glpi_tickettasks.taskcategories_id
                           JOIN glpi_plugin_processmaker_cases ON glpi_plugin_processmaker_cases.processes_id=glpi_plugin_processmaker_taskcategories.processes_id
                           RIGHT JOIN glpi_plugin_processmaker_tasks ON glpi_plugin_processmaker_tasks.items_id=glpi_tickettasks.id AND glpi_plugin_processmaker_tasks.case_id=glpi_plugin_processmaker_cases.id
                           WHERE glpi_plugin_processmaker_taskcategories.pm_task_guid = '".$targetTask['targettask_guid']."' AND glpi_tickettasks.state = 1 AND glpi_tickettasks.tickets_id=".$tlink['tickets_id'];
                     foreach ($DB->request($query) as $case) {
                        // must be only one row

                        $postdata['APP_UID']                    = $case['id'];
                        $postdata['DEL_INDEX']                  = $case['del_index'];

                        $cronaction = new PluginProcessmakerCrontaskaction;
                        $cronaction->add( array( 'plugin_processmaker_caselinks_id' => $targetTask['id'],
                                                 'plugin_processmaker_cases_id' => $locCase->getID(),
                                                   //'itemtype'         => $itemtype,
                                                   //'items_id'         => $parm->fields['tickets_id'],
                                                   'users_id'         => Session::getLoginUserID(),
                                                   'is_targettoclaim' => $targetTask['is_targettoclaim'],
                                                   'state'            => ($targetTask['is_externaldata'] ? PluginProcessmakerCrontaskaction::WAITING_DATA : PluginProcessmakerCrontaskaction::DATA_READY),
                                                   'postdata'         => json_encode( $postdata, JSON_HEX_APOS | JSON_HEX_QUOT),
                                                   'logs_out'         => json_encode( $externalapplicationparams, JSON_HEX_APOS | JSON_HEX_QUOT)
                                                   ),
                                          null,
                                          false);
                     }
                  }
               }
            }

            if ($targetTask['is_synchronous']) {
               // must call PluginProcessmakerProcessmaker::cronPMTaskActions()
               PluginProcessmakerProcessmaker::cronPMTaskActions();
            }
         }

      }
   }
}
