<?php

// ----------------------------------------------------------------------
// Original Author of file: Olivier Moron
// Purpose of file:
// ----------------------------------------------------------------------

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"task_users.php")) {
    $AJAX_INCLUDE = 1;
    define('GLPI_ROOT','../../..');
    include (GLPI_ROOT."/inc/includes.php");
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

if (!defined('GLPI_ROOT')) {
    die("Can not acces directly to this file");
}

include_once dirname(__FILE__)."/../inc/processmaker.class.php" ;
include_once dirname(__FILE__)."/../inc/users.class.php" ;

Session::checkLoginUser();

$rand = rand();

echo "<form style='margin-bottom: 0px' name='processmaker_form_task$rand-".$_REQUEST['delIndex']."' id='processmaker_form_task$rand-".$_REQUEST['delIndex']."' method='post' action='".Toolbox::getItemTypeFormURL("PluginProcessmakerProcessmaker")."'>";
    echo $LANG['processmaker']['item']['reassigncase']."&nbsp;"; 
    echo "<input type='hidden' name='action' value='unpausecase_or_reassign_or_delete'>";
    echo "<input type='hidden' name='id' value='".$_REQUEST['itemId']."'>";
    echo "<input type='hidden' name='itemtype' value='".$_REQUEST['itemType']."'>";
    echo "<input type='hidden' name='plugin_processmaker_caseId' value='".$_REQUEST['caseId']."'>";
    echo "<input type='hidden' name='plugin_processmaker_delIndex' value='".$_REQUEST['delIndex']."'>";
    echo "<input type='hidden' name='plugin_processmaker_userId' value='".$_REQUEST['userId']."'>";
    echo "<input type='hidden' name='plugin_processmaker_taskId' value='".$_REQUEST['taskId']."'>";
    echo "<input type='hidden' name='plugin_processmaker_delThread' value='".$_REQUEST['delThread']."'>";

    PluginProcessmakerUsers::dropdown( array('name'   => 'users_id_recipient',
                                                                    'value'  => PluginProcessmakerProcessmaker::getGLPIUserId( $_REQUEST['userId'] ),
                                                                    'entity' => 0, //$item->fields["entities_id"],
                                                                    'entity_sons' => true,
                                                                    'right'  => 'all',  
                                                                    'rand'  => $rand,
                                                                    'pmTaskId' => $_REQUEST['taskId']));
    echo "&nbsp;&nbsp;";
    echo "<input type='submit' name='reassign' value='".$LANG['processmaker']['item']['buttonreassigncase']."' class='submit'>";
echo "</form>";
    
?>