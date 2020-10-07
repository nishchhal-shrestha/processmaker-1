<?php
/**
 */

class PluginProcessmakerConfig extends CommonDBTM {

   static $rightname = '';

   static private $_instance = null;

   /**
    * Summary of canCreate
    * @return boolean
    */
   static function canCreate() {
      return Session::haveRight('config', UPDATE);
   }

   /**
    * Summary of canView
    * @return boolean
    */
   static function canView() {
      return Session::haveRight('config', READ);
   }

   /**
    * Summary of canUpdate
    * @return boolean
    */
   static function canUpdate() {
      return Session::haveRight('config', UPDATE);
   }

   /**
    * Summary of getTypeName
    * @param mixed $nb plural
    * @return mixed
    */
   static function getTypeName($nb = 0) {
      return __('ProcessMaker setup', 'processmaker');
   }

   /**
    * Summary of getName
    * @param mixed $with_comment with comment
    * @return mixed
    */
   function getName($with_comment = 0) {
      return __('ProcessMaker', 'processmaker');
   }

   /**
    * Summary of getInstance
    * @return PluginProcessmakerConfig
    */
   static function getInstance() {

      if (!isset(self::$_instance)) {
         self::$_instance = new self();
         if (!self::$_instance->getFromDB(1)) {
            self::$_instance->getEmpty();
         }
      }
      return self::$_instance;
   }

   /**
   * Prepare input datas for updating the item
   * @param array $input used to update the item
   * @return array the modified $input array
   **/
   function prepareInputForUpdate($input) {
      global $CFG_GLPI;

      if (!isset($input["maintenance"])) {
         $input["maintenance"] = 0;
      }

      if (isset($input["pm_dbserver_passwd"])) {
         if (empty($input["pm_dbserver_passwd"])) {
            unset($input["pm_dbserver_passwd"]);
         } else {
            $input["pm_dbserver_passwd"] = Toolbox::encrypt(stripslashes($input["pm_dbserver_passwd"]), GLPIKEY);
         }
      }

      if (isset($input["_blank_pm_dbserver_passwd"]) && $input["_blank_pm_dbserver_passwd"]) {
         $input['pm_dbserver_passwd'] = '';
      }

      if (isset($input["pm_admin_passwd"])) {
         if (empty($input["pm_admin_passwd"])) {
            unset($input["pm_admin_passwd"]);
         } else {
            $input["pm_admin_passwd"] = Toolbox::encrypt(stripslashes($input["pm_admin_passwd"]), GLPIKEY);
         }
      }

      if (isset($input["_blank_pm_admin_passwd"]) && $input["_blank_pm_admin_passwd"]) {
         $input['pm_admin_passwd'] = '';
      }

      if (isset($input['pm_server_URL'])) {
         $input['domain'] = self::getCommonDomain( $CFG_GLPI['url_base'], $input['pm_server_URL'] );
      }

      return $input;
   }

   /**
    * Summary of getCommonDomain
    * @param mixed $url1 first url
    * @param mixed $url2 second url
    * @return string the common domain part of the given urls
    */
   static function getCommonDomain($url1, $url2) {
      $domain = '';
      try {
         $glpi = explode(".", parse_url($url1, PHP_URL_HOST));
         $pm = explode( ".", parse_url($url2, PHP_URL_HOST));
         $cglpi = array_pop( $glpi );
         $cpm = array_pop( $pm );
         while ($cglpi && $cpm && $cglpi == $cpm) {
            $domain = $cglpi.($domain==''?'':'.'.$domain);
            $cglpi = array_pop( $glpi );
            $cpm = array_pop( $pm );
         }
         if ($domain != '') {
            return $domain;
         }
      } catch (Exception $e) {
         $domain = '';
      }
      return $domain;
   }

   /**
    * Summary of showConfigForm
    * @param mixed $item is the config
    * @return boolean
    */
   static function showConfigForm($item) {
      global $PM_DB, $CFG_GLPI, $PM_SOAP;

      $setup_ok = false;

      $ui_theme = [
        'glpi_classic' => 'glpi_classic',
        'glpi_neoclassic' => 'glpi_neoclassic'
      ];

      $config = $PM_SOAP->config;
      $config->showFormHeader(['colspan' => 4]);

      if (!$config->fields['maintenance']) {

         echo "<tr class='tab_bg_1'>";
         echo "<td >".__('Server URL (must be in same domain than GLPI, if GLPI is using HTTPS, PM server must also use HTTPS)', 'processmaker')."</td><td >";
         echo "<input size='50' type='text' name='pm_server_URL' value='".$config->fields['pm_server_URL']."'>";
         echo "</td>";

         echo "<td>".__('Common domain with GLPI', 'processmaker')."</td>";
         echo "<td><font color='red'><div name='domain'>".$config->fields['domain']."</div></font>";

         echo Html::scriptBlock("
            function setCommonDomain() {

               function parseUrl( url ) {
                  var a = document.createElement('a');
                  a.href = url;
//               debugger;
                  return { host: a.hostname, port: a.port, scheme: a.protocol.slice(0, -1), path: a.pathname, query: a.search.slice(1), fragment: a.hash.slice(1)  } ;
               }
               var domain = '';
               try {
                  var glpi = parseUrl( '".$CFG_GLPI['url_base']."' ).host.split('.') ;
                  var pm = parseUrl( $('input[name=pm_server_URL]').val()).host.split('.');
                  var cglpi = glpi.pop() ;
                  var cpm = pm.pop() ;
                  while( cglpi && cpm && cglpi == cpm ) {
                     domain = cglpi + (domain==''?'':'.' + domain) ;
                     cglpi = glpi.pop() ;
                     cpm = pm.pop() ;
                  }
                  if (domain != '' && domain.split('.').length > 1) { // common domain must be at least 'domain.com' and not 'com', otherwise some browser will not accept the CORS javascript
                     $('div[name=domain]').text(domain) ;
                     $('div[name=domain]').parent().attr('color', 'green');
                     return;
                  }
               } catch(ex) {}
               $('div[name=domain]').text('".__('None!', 'processmaker')."') ;
               $('div[name=domain]').parent().attr('color', 'red');
            };
            $('input[name=pm_server_URL]').on('keyup', setCommonDomain ) ;
            setCommonDomain() ;
        ");
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td >".__('Verify SSL certificate', 'processmaker')."</td><td >";
         Dropdown::showYesNo("ssl_verify", $config->fields['ssl_verify']);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td >".__('Workspace Name', 'processmaker')."</td><td >";
         echo "<input type='text' name='pm_workspace' value='".$config->fields['pm_workspace']."'>";
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td >".__('Server administrator name', 'processmaker')."</td>";
         echo "<td ><input type='text' name='pm_admin_user' value='".$config->fields["pm_admin_user"]."'>";
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td >".__('Server administrator password', 'processmaker')."</td>";
         echo "<td ><input type='password' name='pm_admin_passwd' value='' autocomplete='off'>";
         echo "&nbsp;<input type='checkbox' name='_blank_pm_admin_passwd'>&nbsp;".__('Clear');
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td >".__('Connection status', 'processmaker')."</td><td >";
         //$pm = new PluginProcessmakerProcessmaker;

         if ($config->fields['pm_server_URL'] != ''
            && $config->fields['pm_workspace'] != ''
            && $config->fields["pm_admin_user"] != ''
         //         && ($pm->login(true))) {
            && ($PM_SOAP->login(true))) {
            echo "<font color='green'>".__('Test successful');
            $setup_ok = true;
         } else {
            //         echo "<font color='red'>".__('Test failed')."<br>".print_r($pm->lasterror, true);
            echo "<font color='red'>".__('Test failed')."<br>".print_r($PM_SOAP->lasterror, true);
         }
         echo "</font></span></td></tr>\n";

         echo "<tr><td  colspan='4' class='center b'>".__('SQL server setup', 'processmaker')."</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td >" . __('SQL server (MariaDB or MySQL)', 'processmaker') . "</td>";
         echo "<td ><input type='text' size=50 name='pm_dbserver_name' value='".$config->fields["pm_dbserver_name"]."'>";
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td >" . __('Database name', 'processmaker') . "</td>";
         echo "<td ><input type='text' size=50 name='pm_dbname' value='".$config->fields['pm_dbname']."'>";
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td >" . __('SQL user', 'processmaker') . "</td>";
         echo "<td ><input type='text' name='pm_dbserver_user' value='".$config->fields["pm_dbserver_user"]."'>";
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td >" . __('SQL password', 'processmaker') . "</td>";
         echo "<td ><input type='password' name='pm_dbserver_passwd' value='' autocomplete='off'>";
         echo "&nbsp;<input type='checkbox' name='_blank_pm_dbserver_passwd'>&nbsp;".__('Clear');
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td >".__('Connection status', 'processmaker')."</td><td >";
         if ($PM_DB->connected && isset($PM_DB->dbdefault) && $PM_DB->dbdefault != '') {
            echo "<font color='green'>".__('Test successful');
         } else {
            echo "<font color='red'>".__('Test failed');
         }
         echo "</font></span></td></tr>\n";

         echo "<tr><td  colspan='4' class='center b'>".__('Settings')."</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td >".__('Theme Name', 'processmaker')."</td><td >";
         Dropdown::showFromArray('pm_theme', $ui_theme,
                         ['value' => $config->fields['pm_theme']]);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td >".__('Main Task Category (edit to change name)', 'processmaker')."</td><td >";
         TaskCategory::dropdown(['name'              => 'taskcategories_id',
                                  'display_emptychoice'   => true,
                                  'value'                 => $config->fields['taskcategories_id']]);
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td >".__('Task Writer (edit to change name)', 'processmaker')."</td><td >";
         $rand = mt_rand();
         User::dropdown(['name'             => 'users_id',
                          'display_emptychoice'  => true,
                          'right'                => 'all',
                          'rand'                 => $rand,
                          'value'                => $config->fields['users_id']]);

         // this code adds the + sign to the form
         echo "<img alt='' title=\"".__s('Add')."\" src='".$CFG_GLPI["root_doc"].
         "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'
                            onClick=\"".Html::jsGetElementbyID('add_dropdown'.$rand).".dialog('open');\">";
         echo Ajax::createIframeModalWindow('add_dropdown'.$rand,
                                                  User::getFormURL(),
                                                  ['display' => false]);
         // end of + sign

         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td >".__('Group in ProcessMaker which will contain all GLPI users', 'processmaker')."</td><td >";

         $pmGroups = [ 0 => Dropdown::EMPTY_VALUE ];
         $query = "SELECT DISTINCT CON_ID, CON_VALUE FROM CONTENT WHERE CON_CATEGORY='GRP_TITLE' ORDER BY CON_VALUE;";
         if ($PM_DB->connected) {
            foreach ($PM_DB->request( $query ) as $row) {
               $pmGroups[ $row['CON_ID'] ] = $row['CON_VALUE'];
            }
            Dropdown::showFromArray( 'pm_group_guid', $pmGroups, ['value' => $config->fields['pm_group_guid']] );
         } else {
            echo "<font color='red'>".__('Not connected');
         }
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td >" . __('Max cases per item (0=unlimited)', 'processmaker') . "</td>";
         echo "<td ><input type='text' name='max_cases_per_item' value='".$config->fields["max_cases_per_item"]."'>";
         echo "</td></tr>\n";

      } else {
         echo "<tr><td  colspan='4' class='center b'>";
         PluginProcessmakerProcessmaker::showUnderMaintenance();
         echo "</td></tr>";
      }

      echo "<tr><td  colspan='4' class='center b'>".__('Maintenance')."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__('Maintenance mode')."</td><td >";
      Dropdown::showYesNo("maintenance", $config->fields['maintenance']);
      echo "</td></tr>";

      echo "<tr><td colspan='4'></td></tr>";

      echo "<tr><th  colspan='4'>".__('Processmaker system information', 'processmaker')."</th></tr>";
      if ($setup_ok) {
         $info = $PM_SOAP->systemInformation( );
         echo '<tr><td>'.__('Version', 'processmaker').'</td><td>'.$info->version.'</td></tr>';
         echo '<tr><td>'.__('Web server', 'processmaker').'</td><td>'.$info->webServer.'</td></tr>';
         echo '<tr><td>'.__('Server name', 'processmaker').'</td><td>'.$info->serverName.'</td></tr>';
         echo '<tr><td>'.__('PHP version', 'processmaker').'</td><td>'.$info->phpVersion.'</td></tr>';
         echo '<tr><td>'.__('DB version', 'processmaker').'</td><td>'.$info->databaseVersion.'</td></tr>';
         echo '<tr><td>'.__('DB server IP', 'processmaker').'</td><td>'.$info->databaseServerIp.'</td></tr>';
         echo '<tr><td>'.__('DB name', 'processmaker').'</td><td>'.$info->databaseName.'</td></tr>';
         echo '<tr><td>'.__('User browser', 'processmaker').'</td><td>'.$info->userBrowser.'</td></tr>';
         echo '<tr><td>'.__('User IP', 'processmaker').'</td><td>'.$info->userIp.'</td></tr>';
      } else {
         echo '<tr><td>'.__('Version', 'processmaker').'</td><td>'.__('Not yet!', 'processmaker').'</td></tr>';
      }
      $config->showFormButtons(['candel' => false]);

      return false;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType()=='Config') {
         return __('ProcessMaker', 'processmaker');
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType()=='Config') {
         self::showConfigForm($item);
      }
      return true;
   }

}
