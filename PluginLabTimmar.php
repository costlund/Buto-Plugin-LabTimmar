<?php
class PluginLabTimmar{
  private $settings = null;
  private $mysql;
  function __construct($buto) {
    if($buto){
      /**¨
       * ¨Include.
       */
      wfPlugin::includeonce('wf/form_v2');
      wfPlugin::includeonce('wf/array');
      wfPlugin::includeonce('wf/yml');
      /**
       * Enable.
       */
      wfPlugin::enable('wf/bootstrap');
      wfPlugin::enable('form/form_v1');
      wfPlugin::enable('wf/table');
      wfPlugin::enable('wf/ajax');
      wfPlugin::enable('wf/bootstrapjs');
      wfPlugin::enable('wf/dom');
      wfPlugin::enable('wf/callbackjson');
      wfPlugin::enable('eternicode/bootstrapdatepicker');
      
      if(!wfUser::hasRole('client')){
        exit('Role issue.');
      }
      /**
       * MySql.
       */
      wfPlugin::includeonce('wf/mysql');
      $this->mysql =new PluginWfMysql();
      /**
       * Layout path.
       */
      wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/lab/timmar/layout');
      /**
       * Settings.
       */
      $this->settings = new PluginWfArray(wfArray::get($GLOBALS, 'sys/settings/plugin_modules/'.wfArray::get($GLOBALS, 'sys/class').'/settings'));
      //$this->settings = wfPlugin::getPluginSettings('lab/timmar', true);
    }
  }
  private function db_open(){
    $this->mysql->open($this->settings->get('mysql'));
  }
  private function db_lab_timmar_calendar_select(){
    $sql = $this->getSql('lab_timmar_calendar_select');
    $this->db_open();
    $this->mysql->execute($sql->get());
    $rs = ($this->mysql->getStmtAsArray());
    return $rs;
  }
  private function db_lab_timmar_calendar_select_one($id){
    $sql = $this->getSql('lab_timmar_calendar_select_one');
    $sql->setByTag(array('id' => $id));
    $this->db_open();
    $this->mysql->execute($sql->get());
    $rs = new PluginWfArray($this->mysql->getStmtAsArrayOne());
    return $rs;
  }
  private function db_lab_timmar_calendar_update_one($id){
    $sql = $this->getSql('lab_timmar_calendar_update_one');
    $sql->setByTag(array('id' => $id));
    $sql->setByTag(wfRequest::getAll());
    $this->db_open();
    $this->mysql->execute($sql->get());
    return null;
  }
  private function db_lab_timmar_calendar_insert_one(){
    $id = wfCrypt::getUid();
    $sql = $this->getSql('lab_timmar_calendar_insert_one');
    $sql->setByTag(array('id' => $id));
    $this->db_open();
    $this->mysql->execute($sql->get());
    return $id;
  }
  private function db_lab_timmar_calendar_delete_one($id){
    $sql = $this->getSql('lab_timmar_calendar_delete_one');
    $sql->setByTag(array('id' => $id));
    $this->db_open();
    $this->mysql->execute($sql->get());
    return null;
  }
  private function getReportAsObject($filename, $start = null, $end = null){
    wfPlugin::includeonce('wf/array');
    return new PluginWfArray($this->getReport($filename, $start, $end));
  }
  private function getReport($filename, $start = null, $end = null){
      /**
       * Date filter.
       * Uppercase are dates from settings.
       * Lowercase are dates from projects.
       * One of three checks must match for project to show up.
       ********************************************************
       *          *  START   *          *   END    *          *
       ********************************************************
       *          *          *   end    *          *          *
       ********************************************************
       *          *          *  start   *          *          *
       ********************************************************
       *   start  *          *          *          *   end    *
       ********************************************************
       */
    wfPlugin::includeonce('google/calendar');
    wfPlugin::includeonce('wf/array');
    if(is_null($start)){
      $start = date('Y-m-d', strtotime(date('Y-m-d'). ' - 365 days'));
    }
    if(is_null($end)){
      $end = date('Y-m-d', strtotime(date('Y-m-d'). ' + 365 days'));
    }
    $data = new PluginWfArray();
    $data->set('filename', $filename);
    $data->set('start', $start);
    $data->set('end', $end);
    $data->set('start_stt', strtotime($data->get('start')));
    $data->set('end_stt', strtotime($data->get('end')));
    $google = new PluginGoogleCalendar(true);
    $google->filename = $data->get('filename');
    $google->init();
    if($google->failure){
      return array('success' => false);
    }
    $times = new PluginWfArray();
    foreach ($google->getAllTimeEvents() as $key => $value) {
      $item = new PluginWfArray($value);
      $time = new PluginWfArray();
      $time->set('summary',      $this->clean($item->get('summary')));
      $time->set('key',          $this->key($item->get('summary')));
      $time->set('description',  $this->clean($item->get('description')));
      $time->set('start',        $this->date($item->get('start')));
      $time->set('end',          $this->date($item->get('end')));
      $time->set('date',          $this->date(date('Y-m-d', strtotime($item->get('start')))));
      $time->set('time',          $this->date(date('H:i', strtotime($item->get('start')))).'-'.$this->date(date('H:i', strtotime($item->get('end'))))  );
      $time->set('minutes',      $this->minutes($item->get('start'), $item->get('end')));
      $time->set('hours',        $this->hours($time->get('minutes')));
      $time->set('match',        false); //Sets to true if match a project.
      $time->set('start_stt',   strtotime($time->get('start')));
      $time->set('end_stt',     strtotime($time->get('end')));
      /**
       * Date filter.
       */
      $filter = false;
      if(     $time->get('end_stt')   >= $data->get('start_stt') && $time->get('end_stt')   <= $data->get('end_stt')){
        $filter = true;
      }elseif($time->get('start_stt') >= $data->get('start_stt') && $time->get('start_stt') <= $data->get('end_stt')){
        $filter = true;
      }elseif($time->get('start_stt') <= $data->get('start_stt') && $time->get('end_stt')   >= $data->get('end_stt')){
        $filter = true;
      }
      if(!$filter){
        continue;
      }
      /**
       * 
       */
      $times->set($key, $time->get());
    }
    //wfHelp::yml_dump($times, true);
    $projects = new PluginWfArray();
    foreach ($google->getAllDayEvents() as $key => $value) {
      /**
       * Array convert.
       */
      $item = new PluginWfArray($value);
      /**
       * Project.
       */
      $project = new PluginWfArray();
      $project->set('summary', $this->clean($item->get('summary')));
      $project->set('key', $this->key($item->get('summary')));
      $project->set('description', $this->clean($item->get('description')));
      $project->set('start', $this->date($item->get('start')));
      $project->set('end', $this->date($item->get('end')));
      $project->set('start_stt', strtotime($project->get('start')));
      $project->set('end_stt', strtotime($project->get('end')));
      /**
       * Date filter.
       */
      $filter = false;
      if(     $project->get('end_stt')   >= $data->get('start_stt') && $project->get('end_stt')   <= $data->get('end_stt')){
        $filter = true;
      }elseif($project->get('start_stt') >= $data->get('start_stt') && $project->get('start_stt') <= $data->get('end_stt')){
        $filter = true;
      }elseif($project->get('start_stt') <= $data->get('start_stt') && $project->get('end_stt')   >= $data->get('end_stt')){
        $filter = true;
      }
      if(!$filter){
        continue;
      }
      /**
       * Set time.
       * Set project minutes and hours.
       */
      $project->set('minutes', null);
      $project->set('hours', null);
      $time = new PluginWfArray();
      $minutes = null;
      $hours = null;
      foreach ($times->get() as $key2 => $value2) {
        $item2 = new PluginWfArray($value2);
        /**
         * Key check.
         */
        if($item2->get('key') == $project->get('key')){
          $times->set("$key2/match", true);
          /**
           * Date check.
           */
          if(
                  $data->get('start_stt') <= strtotime($item2->get('start')) && 
                  $data->get('end_stt') >= strtotime($item2->get('end'))
                  ){
            $time->set(true, $value2);
            $minutes += $item2->get('minutes');
            $hours += $item2->get('hours');
          }
        }
      }
      $project->set('minutes', $minutes);
      $project->set('hours', $hours);
      $time->sort('start');
      $project->set('time', $time->get());
      $projects->set($key, $project->get());
    }
    /**
     * Time not match any project.
     */
    $time_no_match = array();
    foreach ($times->get() as $key => $value) {
      $item = new PluginWfArray($value);
      /**
       * Match check.
       */
      if(!$item->get('match')){
        $time_no_match[] = $value;
      }
    }
    /**
     * Add project to projects.
     */
    $projects->sort('start');
    $report = array();
    $report['calendar'] = $google->getCalendar();
    $report['data'] = $data->get();
    $report['projects'] = $projects->get();
    $report['time_no_match'] = $time_no_match;
    $report['success'] = true;
    return $report;
  }
  /**
    type: widget
    data:
      plugin: 'lab/timmar'
      method: report
      data:
        filename: 'https://calendar.google.com/calendar/ical/.........../basic.ics'
        start: '2018-09-01'
        end: '2018-09-30'
   */
  public function widget_report($data){
    wfPlugin::includeonce('wf/array');
    $data = new PluginWfArray($data);
    $element = $this->getElementCalendar($data->get('data'));
    wfDocument::renderElement($element->get());
  }
  private function getElementCalendar($data){
    $data = new PluginWfArray($data);
    $report = $this->getReportAsObject($data->get('filename'), $data->get('start'), $data->get('end'));
    if(!$report->get('success')){
      exit("Could not find goolge calender via link $filename.");
    }
    /**
     * Replace.
     */
    foreach ($report->get('projects') as $key => $value) {
      /**
       * Add params from description.
       */
      $description_array = explode("\n", $report->get("projects/$key/description"));
      foreach ($description_array as $key2 => $value2) {
        $yaml_load = sfYaml::load($value2);
        if(is_array($yaml_load)){
          foreach ($yaml_load as $key3 => $value3) {
            $report->set("projects/$key/params/$key3", $value3);
          }
        }
      }
      /**
       * Replace in description.
       */
      $report->set("projects/$key/description", str_replace("\n", '<br>', $report->get("projects/$key/description")));
    }
    $projects = array();
    foreach ($report->get('projects') as $key => $value) {
      $item = new PluginWfArray($value);
      if($item->get('params/hours_approx')){
        $item->set('params/hours_percentage', round($item->get('hours') / $item->get('params/hours_approx') * 100));
      }
      $project = wfSettings::getSettingsAsObject('/plugin/lab/timmar/element/project.yml');
      $project->setByTag($value);
      $times_tbody = array();
      foreach ($value['time'] as $key2 => $value2) {
        $time_tbody = wfSettings::getSettingsAsObject('/plugin/lab/timmar/element/time_tbody.yml');
        $time_tbody->setByTag($value2);
        $times_tbody[] = $time_tbody->get();
      }
      $project->setByTag(array('table_tbody_tr' => $times_tbody), 'time');
      $project->setByTag($item->get('params'), 'params', true);
      $projects[] = $project->get();
    }
    /**
     * Time no match.
     */
    $times_tbody = array();
    foreach ($report->get('time_no_match') as $key => $value) {
      $time_tbody = wfSettings::getSettingsAsObject('/plugin/lab/timmar/element/time_tbody.yml');
      $time_tbody->setByTag($value);
      $times_tbody[] = $time_tbody->get();
    }
    /**
     * Calendar fix.
     */
    $report->set('calendar/X-WR-CALDESC', $this->clean($report->get('calendar/X-WR-CALDESC')));
    $report->set('calendar/X-WR-CALDESC', str_replace("\n", '<br>', $report->get('calendar/X-WR-CALDESC')));
    /**
     * Element.
     */
    $element = wfSettings::getSettingsAsObject('/plugin/lab/timmar/element/view.yml');
    $element->setByTag($report->get('data'), 'data');
    $element->setByTag(array('list' => $projects), 'projects');
    $element->setByTag(array('table_tbody_tr' => $times_tbody), 'time_no_match');
    $element->setByTag($report->get('calendar'), 'calendar');
    return $element;
  }
  private function clean($str){
    $str = substr($str, 0, strlen($str)-1);
    $str = str_replace("\\n", PHP_EOL, $str);    
    $str = preg_replace("'\r '","", $str);
    $str = stripslashes($str);
    return $str;
  }
  private function date($date){
    $len = strlen($date);
    if($len == 9){
      $date = date('Y-m-d', strtotime($date));
    }elseif($len == 17){
      $date = date('Y-m-d H:i', strtotime($date));
    }
    return $date;
  }
  private function key($summary){
    $summary = $this->clean($summary);
    $array = preg_split("/,/", $summary);
    return $array[0];
  }
  private function minutes($start, $end){
    return (strtotime($end) - strtotime($start)) / 60;
  }
  private function hours($minutes){
    return number_format(($minutes)/60, 2);;
  }
  public function getYml($dir){
    return new PluginWfYml(__DIR__.'/'.$dir.".yml");
  }
  private function getElement($name){
    return new PluginWfYml(__DIR__."/element/$name.yml");
  }
  private function getForm($name){
    return new PluginWfYml(__DIR__."/form/$name.yml");
  }
  private function getSql($key){
    return new PluginWfYml(__DIR__.'/mysql/sql.yml', $key);
  }
  public function page_start(){
    
    
    $page = $this->getYml('page/start');
    $json = json_encode(array('class' => wfGlobals::get('class')));
    $page->setByTag(array('json' => 'var app = '.$json));
    /**
     * Insert admin layout from theme.
     */
    $page = wfDocument::insertAdminLayout($this->settings, 1, $page);
    /**
     * 
     */
    wfDocument::mergeLayout($page->get());
    
  }
  public function page_list(){
    $class = wfGlobals::get('class');
    $rs = $this->db_lab_timmar_calendar_select();
    $items = array();
    foreach ($rs as $key => $value) {
      $id = $value['id'];
      $item = $this->getElement('list_item');
      $item->setByTag($value);
      $item->setByTag(array('onclick' => "PluginWfAjax.load('start', '/$class/view/id/$id');"), 'view');
      $item->setByTag(array('onclick' => "PluginWfBootstrapjs.modal({id: 'lab_timmar_form', url: '/'+app.class+'/form/id/$id', lable: 'Edit'});"), 'edit');
      $item->setByTag(array('onclick' => "if(confirm('Are you sure?')){ $.get( 'delete/id/$id', function( data ) { location.reload(); }); }"), 'delete');
      $items[] = $item->get();
    }
    $element = $this->getElement('list');
    $element->setByTag(array('items' => $items));
    wfDocument::renderElement($element->get());
  }
  public function page_view(){
    $rs = $this->db_lab_timmar_calendar_select_one(wfRequest::get('id'));
    $widget = $this->getElement('widget_view');
    $widget->setByTag($rs->get());
    wfDocument::renderElement(array($widget->get()));
  }
  public function page_form(){
    $form = $this->getForm('edit');
    if(wfRequest::get('id')){
      $rs = $this->db_lab_timmar_calendar_select_one(wfRequest::get('id'));
      $form->setByTag($rs->get());
    }else{
      $form->setByTag(array(), 'rs', true);
    }
    $widget = wfDocument::createWidget('form/form_v1', 'render', $form->get());
    wfDocument::renderElement(array($widget));
  }
  public function page_capture(){
    $form = $this->getForm('edit');
    $widget = wfDocument::createWidget('form/form_v1', 'capture', $form->get());
    wfDocument::renderElement(array($widget));
  }
  public function page_delete(){
    $rs = $this->db_lab_timmar_calendar_select_one(wfRequest::get('id'));
    if($rs->get('id')){
      $this->db_lab_timmar_calendar_delete_one($rs->get('id'));
    }
    exit('delete...');
  }
  public function capture(){
    if(wfRequest::get('id')){
      $this->db_lab_timmar_calendar_update_one(wfRequest::get('id'));
    }else{
      $id = $this->db_lab_timmar_calendar_insert_one();
      $this->db_lab_timmar_calendar_update_one($id);
    }
    return array("location.reload()");
  }
}














