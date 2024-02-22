<?php
/**
 * Page plugin to hande accounts.
 * Involved tables is account, account_role and account_log.
 * Role webmaster i required.
 */
class PluginAccountAdmin_v1{
  private $settings = null;
  private $sql = null;
  private $i18n = null;
  function __construct($buto = false) {
    if($buto){
      /**
       */
      set_time_limit(120);
      ini_set('memory_limit', '2048M');
      /**
       */
      if(!wfUser::hasRole("webadmin")){
        exit('Role webadmin is required!');
      }
      wfPlugin::includeonce('i18n/translate_v1');
      $this->i18n = new PluginI18nTranslate_v1();
      wfPlugin::enable('form/form_v1');
      wfPlugin::enable('chart/chartjs_4_4_1');
    }
  }
  private function init(){
    wfPlugin::includeonce('wf/array');
    wfPlugin::includeonce('wf/yml');
    /**
     * Enable plugins.
     */
    wfPlugin::enable('chart/amcharts_v3');
    wfPlugin::enable('datatable/datatable_1_10_18');
    wfPlugin::enable('wf/table');
    wfPlugin::enable('theme/include');
    /**
     * 
     */
    $this->settings = new PluginWfArray(wfArray::get($GLOBALS, 'sys/settings/plugin_modules/'.wfArray::get($GLOBALS, 'sys/class').'/settings'));
    /**
     * Set mysql from session if empty.
     */
    if(!$this->settings->get('mysql')){
      $user = wfUser::getSession();
      $this->settings->set('mysql', $user->get('plugin/account/admin_v1/mysql'));
    }
    /**
     * If no mysql is set.
     */
    if(!$this->settings->get('mysql')){
      exit('No database is provided.');
    }
    $this->settings->set('mysql', wfSettings::getSettingsFromYmlString($this->settings->get('mysql')));
    $this->sql = wfSettings::getSettingsAsObject("/plugin/account/admin_v1/mysql/sql.yml");
  }
  public function page_desktop(){
    $this->init();
    wfGlobals::setSys('layout_path', '/plugin/account/admin_v1/layout');
    $page = $this->getYml('page/desktop.yml');
    $page->setByTag($this->settings->get('mysql'));  
    /**
     * Insert admin layout from theme.
     */
    $page = wfDocument::insertAdminLayout($this->settings, 1, $page);
    $page->setByTag(array('script' => "var app = {class: '".wfArray::get($GLOBALS, 'sys/class')."'};"));
    wfDocument::mergeLayout($page->get());
  }
  private function get_accounts(){
    $rs = $this->executeSQL($this->sql->get('accounts'));
    wfPlugin::includeonce('browser/detection_v1');
    $browser = new PluginBrowserDetection_v1();    
    foreach ($rs->get() as $key => $value){
      $item = new PluginWfArray($value);
      $user_browser = $browser->get_browser($item->get('HTTP_USER_AGENT'), true);
      $rs->set("$key/os_name", $user_browser->get('os_name'));
      $rs->set("$key/browser_name", $user_browser->get('browser_name'));
    }
    return $rs;
  }
  public function page_accounts_data(){
    $this->init();
    $data = $this->get_accounts();
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    exit($datatable->set_table_data($data->get()));
  }
  public function page_accounts(){
    $this->init();
    /**
     * 
     */
    $page = $this->getYml('page/accounts.yml');
    wfDocument::renderElement($page->get());
  }
  private function get_browser_detection(){
    $rs = $this->executeSQL($this->sql->get('accounts'));
    wfPlugin::includeonce('browser/detection_v1');
    $browser = new PluginBrowserDetection_v1();
    foreach ($rs->get() as $key => $value) {
      $item = new PluginWfArray($value);
      $browser->get_browser($item->get('HTTP_USER_AGENT'), true);
    }
    $browser->sort();
    return $browser->stat;
  }
  private function get_element_stat($type = 'os'){
    $element = $this->getYml('page/stat_'.$type.'.yml');
    $graph = $element->getById('chart_'.$type.'', 'data/data/chart/data/graphs/0');
    $graphs = array();
    $dataProvider = array('users' => 'Users');
    /**
     * 
     */
    $browser_detection = $this->get_browser_detection();
    foreach ($browser_detection[$type] as $key => $value) {
      if(!$key){
        continue;
      }
      $graph->set('id', $key);
      $graph->set('labelText', $key);
      $graph->set('title', $key);
      $graph->set('valueField', $key);
      $graphs[] = $graph->get();
      $dataProvider[$key] = $value;
    }
    /**
     * 
     */
    $element->setById('chart_'.$type.'', 'data/data/chart/data/graphs', $graphs);
    $element->setById('chart_'.$type.'', 'data/data/chart/data/dataProvider/0', $dataProvider);
    return $element;
  }
  public function page_stat_os(){
    $this->init();
    $element = $this->get_element_stat('os');
    wfDocument::renderElement($element->get());
  }
  public function page_stat_browser(){
    $this->init();
    $element = $this->get_element_stat('browser');
    wfDocument::renderElement($element->get());
  }
  public function page_account_view(){
    $this->init();
    $page = $this->getYml('page/account_view.yml');
    $page->setById('script', 'innerHTML', "app.account_id='".wfRequest::get('id')."'");
    $rs = $this->getAccount();
    $page->setByTag($rs->get());
    wfDocument::renderElement($page->get());
  }
  public function page_account_base(){
    $this->init();
    $rs = $this->getAccount();
    $page = $this->getYml('page/account_base.yml');
    $page->setByTag($rs->get());
    wfDocument::renderElement($page->get());
  }
  public function page_account_session_content(){
    $this->init();
    $rs = $this->getAccount();
    $page = $this->getYml('page/account_session_content.yml');
    $page->setByTag($rs->get());
    wfDocument::renderElement($page->get());
  }
  public function page_account_session_delete(){
    $this->init();
    $rs = $this->getAccount();
    if($rs->get('session_file_exist')){
      wfFilesystem::delete($rs->get('session_filename'));
    }
    $page = $this->getYml('page/account_session_delete.yml');
    $page->setByTag($rs->get());
    wfDocument::renderElement($page->get());
  }
  private function getAccount(){
    $this->sql->set('account/params/account_id/value', wfRequest::get('id'));
    $rs = $this->executeSQL($this->sql->get('account'));
    if($rs){
      $rs = new PluginWfArray($rs->get('0'));
      if($this->getSessionFileExist($rs->get('session_id'))){
        $rs->set('session_file_exist', true);
        $rs->set('session_file_exist_text', 'Yes');
      }else{
        $rs->set('session_file_exist', false);
        $rs->set('session_file_exist_text', 'No');
      }
      $rs->set('session_filename', $this->getSessionFilename($rs->get('session_id')));
      if($rs->get('session_file_exist')){
        $content = file_get_contents($rs->get('session_filename'));
        wfPlugin::includeonce('session/reader');
        $content = PluginSessionReader::unserialize($content);
        $rs->set('session_content', $content);
        $rs->set('session_content_size', sizeof($content));
      }else{
        $rs->set('session_content', null);
        $rs->set('session_content_size', null);
      }
      return $rs;
    }else{
      return null;
    }
  }
  private function getSessionFilename($session_id){
    if($session_id){
      return ini_get('session.save_path').'/sess_'.$session_id;
    }else{
      return null;
    }
  }
  private function getSessionFileExist($session_id){
    if($session_id){
      $session_filename = $this->getSessionFilename($session_id);
      return wfFilesystem::fileExist($session_filename);
    }else{
      return false;
    }
  }
  private function getSessionFileTime($session_id){
    if($session_id){
      $session_filename = $this->getSessionFilename($session_id);
      return wfFilesystem::getFiletime($session_filename);
    }else{
      return null;
    }
  }
  public function page_account_base_form(){
    $widget = wfDocument::createWidget('form/form_v1', 'render', 'yml:/plugin/account/admin_v1/form/account_base_form.yml');
    wfDocument::renderElement(array($widget));
  }
  public function page_account_role_form(){
    $widget = wfDocument::createWidget('form/form_v1', 'render', 'yml:/plugin/account/admin_v1/form/account_role_form.yml');
    wfDocument::renderElement(array($widget));
    if(wfRequest::get('role_id')){
      $html_object = $this->getYml('html_object/role_delete_button.yml');
      $html_object->setById('btn_delete', 'attribute/data-role_id', wfRequest::get('role_id'));
      wfDocument::renderElement($html_object->get());
    }
  }
  public function page_account_role_capture(){
    $widget = wfDocument::createWidget('form/form_v1', 'capture', 'yml:/plugin/account/admin_v1/form/account_role_form.yml');
    wfDocument::renderElement(array($widget));
  }
  public function frm_account_base_form_render($form){
    $form = new PluginWfArray($form);
    $this->init();
    if(wfRequest::get('id')){
      /**
       * Get account data.
       */
      $rs = $this->getAccount();
      /**
       * Set form.
       */
      $form->set('items/id/default', wfRequest::get('id'));
      $form->set('items/email/default', $rs->get('email'));
      $form->set('items/username/default', $rs->get('username'));
      $form->set('items/password/default', $rs->get('password'));
      $form->set('items/phone/default', $rs->get('phone'));
      $form->set('items/language/default', $rs->get('language'));
      $form->set('items/activated/default', $rs->get('activated'));
      $form->set('items/fullname/default', $rs->get('fullname'));
      $form->set('items/pid/default', $rs->get('pid'));
    }else{
      $form->set('items/username/default', wfPhpfunc::substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 8));
      $form->set('items/password/default', wfPhpfunc::substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 8));
    }
    /**
     language options
     */
    if(wfConfig::getI18nLanguages()){
      $languages = array();
      $languages[''] = '';
      foreach(wfConfig::getI18nLanguages() as $v){
        $languages[$v] = $v;
      }
      $form->set('items/language/option', $languages);
    }
    /**
     */
    return $form->get();
  }
  public function frm_account_role_form_render($form){
    $form = new PluginWfArray($form);
    $this->init();
    $form->set('items/account_id/default', wfRequest::get('id'));
    if(wfRequest::get('role_id')){
      $form->set('items/id/default', wfRequest::get('role_id'));
      /**
       * Get account data.
       */
      $rs = $this->db_role_select_one(wfRequest::get('role_id'));
      $form->set('items/role/default', $rs->get('role'));
    }
    return $form->get();
  }
  private function db_role_select_one($id){
    $this->sql->set('account_role/params/id/value', $id);
    $rs = $this->executeSQL($this->sql->get('account_role'));
    if($rs){
      $rs = new PluginWfArray($rs->get('0'));
    }
    return $rs;
  }
  public function page_account_base_capture(){
    $widget = wfDocument::createWidget('form/form_v1', 'capture', 'yml:/plugin/account/admin_v1/form/account_base_form.yml');
    wfDocument::renderElement(array($widget));
  }
  public function frm_account_base_form_capture($form){
    $form = new PluginWfArray($form);
    $this->init();
    $new = true;
    if($form->get('items/id/post_value')){
      $new = false;
    }
    if($new){
      $id = $this->createAccount();
      $form->set('items/id/post_value', $id);
    }
    $this->sql->set('account_capture_update/params/id/value', $form->get('items/id/post_value'));
    $this->sql->set('account_capture_update/params/email/value', $form->get('items/email/post_value'));
    $this->sql->set('account_capture_update/params/username/value', $form->get('items/username/post_value'));
    $this->sql->set('account_capture_update/params/password/value', $form->get('items/password/post_value'));
    $this->sql->set('account_capture_update/params/phone/value', $form->get('items/phone/post_value'));
    $this->sql->set('account_capture_update/params/language/value', $form->get('items/language/post_value'));
    $this->sql->set('account_capture_update/params/activated/value', $form->get('items/activated/post_value'));
    $this->sql->set('account_capture_update/params/fullname/value', $form->get('items/fullname/post_value'));
    $this->sql->set('account_capture_update/params/pid/value', $form->get('items/pid/post_value'));
    $this->executeSQL($this->sql->get('account_capture_update'));
    if(!$new){
      return array("PluginWfAjax.update('tab_account_base');$('#account_base_form').modal('hide');");
    }else{
      return array("$('.modal').modal('hide');PluginAccountAdmin_v1.account_view('$id');");
    }
  }
  public function frm_account_role_form_capture($form){
    $form = new PluginWfArray($form);
    $this->init();
    if($form->get('items/id/post_value')){
      $this->sql->set('account_role_update/params/id/value', $form->get('items/id/post_value'));
      $this->sql->set('account_role_update/params/account_id/value', $form->get('items/account_id/post_value'));
      $this->sql->set('account_role_update/params/role/value', $form->get('items/role/post_value'));
      $this->executeSQL($this->sql->get('account_role_update'));
    }else{
      $this->sql->set('account_role_insert/params/account_id/value', $form->get('items/account_id/post_value'));
      $this->sql->set('account_role_insert/params/role/value', $form->get('items/role/post_value'));
      $this->executeSQL($this->sql->get('account_role_insert'));
    }
    return array("PluginWfAjax.update('tab_account_roles');$('#account_role_form').modal('hide');");
  }
  public function page_account_delete(){
    $this->init();
    $page = $this->getYml('page/account_delete.yml');
    wfDocument::renderElement($page->get());
  }
  public function page_account_delete_yes(){
    $this->init();
    $this->sql->set('account_delete/params/id/value', wfRequest::get('id'));
    $rs = $this->executeSQL($this->sql->get('account_delete'));
    exit('{success: true, message: "Account was deleted."}');
  }
  public function page_account_role_delete(){
    $this->init();
    $rs = $this->db_role_select_one(wfRequest::get('role_id'));
    if($rs && $rs->get('role')=='webmaster' && !wfUser::hasRole('webmaster')){
      exit('{success: false, message: "Account role webmaster can only be handled by Webmaster."}');
    }
    $this->sql->set('account_role_delete/params/account_id/value', wfRequest::get('id'));
    $this->sql->set('account_role_delete/params/id/value', wfRequest::get('role_id'));
    $this->executeSQL($this->sql->get('account_role_delete'));
    exit('{success: true, message: "Account role was deleted."}');
  }
  private function createAccount(){
    $uid = wfCrypt::getUid();
    $this->sql->set('account_capture_insert/params/id/value', $uid);
    $this->executeSQL($this->sql->get('account_capture_insert'));
    return $uid;
  }
  public function page_account_log(){
    $this->init();
    $page = $this->getYml('page/account_log.yml');
    $page->setByTag(array('url' => '/users/account_log_data?id='.wfRequest::get('id')));
    wfDocument::renderElement($page->get());
  }
  public function page_account_log_data(){
    $this->init();
    $this->sql->set('account_log/params/account_id/value', wfRequest::get('id'));
    $rs = $this->executeSQL($this->sql->get('account_log'));
    /**
     * 
     */
    wfPlugin::includeonce('browser/detection_v1');
    $browser = new PluginBrowserDetection_v1();
    foreach($rs->get() as $k => $v){
      $i = new PluginWfArray($v);
      $user_browser = $browser->get_browser($i->get('HTTP_USER_AGENT'), true);
      $rs->set("$k/os_name", $user_browser->get('os_name'));
      $rs->set("$k/browser_name", $user_browser->get('browser_name'));
    }
    /**
     * 
     */
    foreach ($rs->get() as $key => $value) {
      $item = new PluginWfArray($value);
      if($this->getSessionFileExist($item->get('session_id'))){
        $rs->set($key.'/session_file_exist', true);
        $rs->set($key.'/session_file_exist_text', 'Yes');
        $rs->set($key.'/session_file_time', date('Y-m-d h:i:s', $this->getSessionFileTime($item->get('session_id'))));
      }else{
        $rs->set($key.'/session_file_exist', false);
        $rs->set($key.'/session_file_exist_text', 'No');
        $rs->set($key.'/session_file_time', null);
      }
    }
    /*
    */    
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    exit($datatable->set_table_data($rs->get()));
  }
  public function page_account_roles(){
    $this->init();
    $this->sql->set('account_roles/params/account_id/value', wfRequest::get('id'));
    $rs = $this->executeSQL($this->sql->get('account_roles'));
    $trs = array();
    foreach ($rs->get() as $key => $value) {
      $item = new PluginWfArray($value);
      $trs[] = wfDocument::createHtmlElement('tr', array(
        wfDocument::createHtmlElement('td', $item->get('role'))
      ), array('style' => 'cursor:pointer', 'onclick' => "PluginWfBootstrapjs.modal({id: 'account_role_form', url: '/'+app.class+'/account_role_form/id/'+app.account_id+'/role_id/".$item->get('id')."', lable: 'Role', size: 'sm'});"));
    }
    $page = $this->getYml('page/account_roles.yml');
    $page->setById('tbody_account_role', 'innerHTML', $trs);
    wfDocument::renderElement($page->get());
  }
  public function page_stat_signin(){
    $this->init();
    $element = $this->getYml('page/stat_signin.yml');
    $account_log = $this->getAccountLog();
    $element->setByTag(array('account_log' => $account_log->get()));
    $element->setById('chart_signin', 'data/data/mysql_conn', $this->settings->get('mysql'));
    if(wfUser::hasRole('webmaster')){
      $chart_data = $this->db_chart_account_log();
      $element->setByTag(array('data' => $chart_data->get()), 'chart');
    }
    wfDocument::renderElement($element->get());
  }
  private function getAccountLog(){
    $sql = "select l.date, l.type, a.email, a.username, l.session_id from account_log as l inner join account as a on l.account_id=a.id where l.date>date_add(left(now(),10), INTERVAL -10 DAY) order by l.date desc;";
    $rs = $this->runSQL($sql);
    return $rs;
  }
  private function getYml($file){
    return new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/account/admin_v1/'.$file);
  }
  private function runSQL($sql){
    wfPlugin::includeonce('wf/mysql');
    $mysql = new PluginWfMysql();
    $mysql->open($this->settings->get('mysql'));
    $test = $mysql->runSql($sql);
    return new PluginWfArray($test['data']);
  }
  /**
   * Execute sql.
   * @param array $sql
   * @param boolean $one If one reckord should be returned.
   * @return \PluginWfArray
   */
  private function executeSQL($sql, $one = false){
    wfPlugin::includeonce('wf/mysql');
    $mysql = new PluginWfMysql();
    $mysql->open($this->settings->get('mysql'));
    $mysql->execute($sql);
    if($one){
      $record = new PluginWfArray($mysql->getStmtAsArrayOne());
    }else{
      $record = new PluginWfArray($mysql->getStmtAsArray());
    }
    return $record;
  }
  public function validate_username_or_email($field, $form, $data = array()){
    $form = new PluginWfArray($form);
    if($form->get("items/$field/is_valid") && $form->get("items/$field/post_value")){
      if($field=='username'){
        $rs = $this->db_account_username_exist($form->get("items/$field/post_value"));
      }elseif($field=='email'){
        $rs = $this->db_account_email_exist($form->get("items/id/post_value"), $form->get("items/$field/post_value"));
      }else{
        throw new Exception("wrong field...");
      }
      $id = $form->get("items/id/post_value");
      $value = $form->get("items/$field/post_value");
      $error = false;
      if($rs->get('id')){
        if($id){
          if($id != $rs->get('id')){
            $error = true;
          }
        }else{
          if($value == $rs->get($field)){
            $error = true;
          }
        }
      }
      if($error){
        $form->set("items/$field/is_valid", false);
        $form->set("items/$field/errors/", $this->i18n->translateFromTheme('?label is already in use by other account!', array('?label' => $this->i18n->translateFromTheme($form->get("items/$field/label")))));
      }
    }
    return $form->get();
  }
  /**
   * Form validator.
   * Validate if role webmaster are handled by user with no webmaster role.
   */
  public function validate_role($field, $form, $data = array()){
    $this->init();
    $form = new PluginWfArray($form);
    if($form->get("items/$field/is_valid") && $form->get("items/$field/post_value")){
      $error = false;
      if(!wfRequest::get('id')){
        if(!wfUser::hasRole('webmaster') && wfRequest::get('role') == 'webmaster'){
          $error = true;
        }
      }else{
        $rs = $this->db_role_select_one(wfRequest::get('id'));
        if($rs && $rs->get('role')=='webmaster'){
          $error = true;
        }
      }
      if($error){
        $form->set("items/$field/is_valid", false);
        $form->set("items/$field/errors/", $this->i18n->translateFromTheme('?label webmaster can only be handled by Webmaster!', array('?label' => $this->i18n->translateFromTheme($form->get("items/$field/label")))));
      }
    }
    return $form->get();
  }
  private function db_account_username_exist($username){
    $this->init();
    $this->sql->set('account_select_one_by_username/params/username/value', $username);
    $rs = $this->executeSQL($this->sql->get('account_select_one_by_username'), true);
    return $rs;
  }
  private function db_account_email_exist($id, $email){
    $this->init();
    $this->sql->set('account_select_one_by_email/params/email/value', $email);
    $rs = $this->executeSQL($this->sql->get('account_select_one_by_email'), true);
    return $rs;
  }
  private function db_chart_account_log(){
    $this->init();
    $sql = new PluginWfYml(__DIR__.'/mysql/sql.yml', 'chart_account_log');
    $rs = $this->executeSQL($sql->get(), false);
    return $rs;
  }
}
