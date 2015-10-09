<?php
/**
 */

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

// No autoload when plugin is not activated
require_once('../inc/config.class.php');

$config = new PluginProcessmakerConfig();
if (isset($_POST["update"])) {
    $config->check($_POST['id'],'w');

    $config->update($_POST);

    Html::back();
} elseif (isset($_POST["refresh"])) {
    $config->refresh($_POST); // used to refresh process list, task category list
    Html::back();
}   
Html::redirect($CFG_GLPI["root_doc"]."/front/config.form.php?forcetab=".
             urlencode('PluginProcessmakerConfig$1'));