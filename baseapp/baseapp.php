<?php
/**
 * Short description for file.
 *
 * Long description for file.
 *
 * @version     $Id: baseapp.php 7 2009-03-04 22:18:40Z vikaspatial1983 $
 * @package     BaseApp Framework (v0.1)
 * @link        http://code.google.com/p/baseappframework/
 * @copyright   Copyright (C) 2009 NGCoders. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
*/


define('DS','/');
define('NL',"\n");

define('APP_DIR', 'app/');
define('APP_PATH',  CORE_PATH . APP_DIR);
define('ROOT',CORE_PATH);
define('WEBROOT_DIR', CORE_PATH.'public/');
define('WWW_ROOT', ROOT . DS . APP_DIR . DS . WEBROOT_DIR . DS);

define('START_TIME',microtime(true));

// Setup Time
date_default_timezone_set('UTC');
define('TIME', time());

# 
define('CONFIG_FILE',CORE_PATH.'/config/config.php');

/**
 * Base of the framework
 */

class Base
{
    private static $instance;

    public function Base()
    {
        self::$instance = $this;
    }

    public static function &getInstance()
    {
        return self::$instance;
    }

}

/**
     * @return Dispatcher
     */

function &getInstance()
{
    return Base::getInstance();
}

/**
 * This class reads the incoming request and calls the controller accordingly , also handles routing.
 *
 */


class Dispatcher extends Base
{
    var $routes = array();
    var $requestedURL;
    var $controllerName;
    var $actionName;
    var $paths = array();

    /**
     * @var Controller
     */

    var $Controller;

    function addRoute($route, $destination=null)
    {
        if ($destination != null && !is_array($route))
        {
            $route = array($route => $destination);
        }

        $this->routes = array_merge($this->routes, $route);
    }

    function splitUrl($url)
    {
        $matches =  preg_split('/\//', $url, -1, PREG_SPLIT_NO_EMPTY);

        // Check if admin mode is requested , then everything will be admin_action

        if (count($matches) > 0 && BASEAPP_ADMIN == $matches[0]) {


            //admin mode
            array_shift($matches);
            if (!defined('ADMIN_MODE'))
            {
                define('ADMIN_MODE',true);
            }
        }

        // No following int controller/admin_index

        if(count($matches)>1 && strpos($matches[1],'admin_') === 0 )  // Hacking ??
        {
            // cannot start with admin
            $matches[1] = str_replace('admin_','',$matches[1]);
        }

        $this->controllerName =  isset($matches[0]) ? $matches[0]: DEFAULT_CONTROLLER;
        $this->actionName     =  isset($matches[1]) ? $matches[1]: DEFAULT_ACTION;
        $this->params         =  isset($matches[2]) ? array_slice($matches, 2): array();

        return $matches;
    }

    function dispatch($requestedURL=null)
    {
        if ($requestedURL === null) {

            if (Configure::read('url_sef'))
            {
                $url = parse_url(SITE_URL);
                if (empty($url['path']) || $url['path'] == '/')
                {
                    $requestedURL = $_SERVER['REQUEST_URI'];
                }
                else
                {
                    $requestedURL = str_replace($url['path'],'',$_SERVER['REQUEST_URI']);
                }

            }
            else if ($pos = strpos($_SERVER['QUERY_STRING'], '&') !== false)
            {
                $requestedURL = substr($_SERVER['QUERY_STRING'], 0, strpos($_SERVER['QUERY_STRING'],'&'));
            }
            else
            {
                $requestedURL = $_SERVER['QUERY_STRING'];
            }

        }

        // requested url MUST start with a slash (for route convention)
        if (strpos($requestedURL, '/') !== 0) {
            $requestedURL = '/' . $requestedURL;
        }


        // removing the suffix for search engine static simulations
        if (Configure::read('url_suffix')) {
            $requestedURL = str_replace(Configure::read('url_suffix'),'',$requestedURL);
        }

        $this->requestedURL = $requestedURL;

        // make the first split of the current requested_url
        $this->splitUrl($requestedURL);

        if (count($this->routes) === 0)
        {
            // Do Nothing if no routes
        }
        else if (isset($this->routes[$requestedURL]))
        {
            // Exact match in routing table ?
            $this->splitUrl($this->routes[$requestedURL]);
        }
        else
        {
            //loop through the route array looking for wildcards
            foreach ($this->routes as $route => $uri)
            {
                // convert wildcards to regex
                if (strpos($route, ':') !== false)
                {
                    $route = str_replace(':any', '(.*)', str_replace(':num', '([0-9]+)', $route));
                }

                // does the regex match?
                if (preg_match('#^'.$route.'$#', $requestedURL))
                {
                    // do we have a back-reference?
                    if (strpos($uri, '$') !== false && strpos($route, '(') !== false)
                    {
                        $uri = preg_replace('#^'.$route.'$#', $uri, $requestedURL);
                    }
                    $this->splitUrl($uri);
                    break;
                }
            }
        }

        return $this->executeAction($this->controllerName, $this->actionName, $this->params);

    }

    function executeAction($controller, $params)
    {
        // Where to find what

        $this->paths = array(BASEAPP_PATH,APP_PATH);

        $controller_class = Inflector::camelize($controller);
        $controller_class_name = $controller_class . 'Controller';

        // Is it a component
        if (is_dir(APP_PATH.'components/'.$controller))
        {
            $this->paths[] =  APP_PATH.'components/'.$controller.'/';
        }

        uses($controller_class,B_CONTROLLER);

        // get a instance of that controller
        if (class_exists($controller_class_name))
        {
            $this->Controller = new $controller_class_name();
        } else {
            //page_not_found();
        }

        if ( ! is_subclass_of($this->Controller,'Controller'))
        {
            die("Class '{$controller_class_name}' does not extends Controller class!");
        }

        // Setup database is required

        if (Configure::read('dbName'))
        {
            // connect to db
            include(CORE_PATH.'libraries/adodb/adodb.inc.php');

            extract(Configure::readConfig());
            $this->Controller->db = ADONewConnection($dbType); # eg 'mysql' or 'postgres'

            if(!$this->Controller->db->Connect($dbHostname , $dbUsername , $dbPassword , $dbName))
            {
                throw new Exception('Could not connect to database');
            }

        }


        // Various controller globals

        $this->Controller->base    = getInstance();
        $this->Controller->Session = new Session();
        $this->Controller->Cookie  = new Cookie();
        $this->Controller->data    = $this->Controller->getData();

        // load the helpers,libraries and models

        if (isset($this->Controller->helpers)) uses($this->Controller->helpers,B_HELPER);
        if (isset($this->Controller->libraries))uses($this->Controller->libraries,B_LIBRARY);
        if (isset($this->Controller->uses)) uses($this->Controller->uses,B_MODEL);

        //beforeFilter

        if (method_exists($this->Controller,'beforeFilter'))
        {
            call_user_func(array($this->Controller, 'beforeFilter'));
        }

        // load the helpers,libraries and models if any changes made by before filter

        if (isset($this->Controller->helpers)) uses($this->Controller->helpers,B_HELPER);
        if (isset($this->Controller->libraries))uses($this->Controller->libraries,B_LIBRARY);
        if (isset($this->Controller->uses)) uses($this->Controller->uses,B_MODEL);

        $this->Controller->execute($this->actionName,$this->params);

    }

}

/**
 * View Class
 *
 */

class View
{
    var $file = false;           // String of template file
    var $vars = array(); // Array of template variables

    function View($file, $vars=false)
    {

        $this->file = View::isView($file);

        if (!$this->file)
        {
            throw new Exception(" {$file} view not found");
        }

        if ($vars !== false) {
            $this->vars = $vars;
        }

    }

    public static function isView($file)
    {
        $base = getInstance();
        $retFile = false;

        // Check the default Location

        $defaultPath = APP_PATH.'views/'.$base->controllerName.'/'.$file.'.php';

        if (is_file($defaultPath) && $defaultPath) {
            return $defaultPath;
        }

        if (!is_file($file) && $file) {

            foreach ($base->paths as $path)
            {
                $viewPath = $path.(strstr($file,'layouts/') || strstr($file,'views/')?'':'views/').(strstr($file,'.php')?$file:$file.'.php');

                echo $viewPath.'<br />';

                if (is_file($viewPath)) {
                    $retFile = $viewPath;
                    break;
                }
            }

        } else {
            $retFile = $file;
        }

        return $retFile;
    }

    public static function quickRender($file, $vars=false)
    {

        $view = new View($file,$vars);
        return $view->render();
    }

    function assign($name, $value=null)
    {
        if (is_array($name))
        {
            array_merge($this->vars, $name);
        }
        else
        {
            $this->vars[$name] = $value;
        }
    } // assign

    function _set_globals()
    {
        $base = getInstance();
        foreach($base->Controller as $name => $value)
        {
            $this->$name= $value;
        }

        if (isset($base->_helpers))
        {
            foreach ($base->_helpers as $helper)
            {
                $class = $helper.'Helper';

                if (class_exists($class,false))
                {
                    $helper = strtolower($helper);
                    $this->vars[$helper] = new $class();
                }
            }
        }

    }

    function render()
    {
        $this->_set_globals();

        ob_start();

        extract($this->vars, EXTR_SKIP);
        include $this->file;
        $content = ob_get_contents();
        @ob_end_clean();
        return $content;
    }

    function display($view, $vars=array(), $exit=true)
    {

        //beforeRender

        $this->output = $this->render($view, $vars);

        echo $this->output;

    }
} // end View class

/**
 * Controller Class
 *
 */

class Controller
{

    /**
     * Will have a refrence to the base Class
     *
     * @var Dispatcher
     */
    var $base;

    /**
     * @var Mysql
     */
    var $db;
    /**
     * @var Session
     */

    var $Session;

    var $view = false;

    var $layout = false;
    var $layoutVars = array();
    var $viewVars = array();
    var $output = array();
    var $data = array();

    var $cacheAction = false;

    function execute($action, $params = array())
    {

        $base = getInstance();

        if (defined('ADMIN_MODE'))
        {
            $action = BASEAPP_ADMIN.'_'.$action;
        }

        // it's a method of the class or action is not a method of the class
        if (substr($action, 0, 1) == '_' || ! method_exists($this, $action))
        {
            throw new Exception("Action '{$action}' is not valid!");
        }

        // Action to be cached ?

        $cacheActionUrl = $action.'/'.implode('/',$params);

        if (!$this->getData() && defined('CACHE_HELPER') && $this->cacheAction && $cacheData = getCache($cacheActionUrl))
        {
            echo $cacheData;
        }
        else
        {

            $this->view = $action;

            call_user_func_array(array(&$this, $action), $params);

            // Load any helpers if required
            if (isset($this->helpers)) {
                uses($this->helpers,B_HELPER);
            }


            //beforeRender

            if (method_exists($this,'beforeRender'))
            {
                call_user_func(array($this, 'beforeRender'));
            }

            if($this->view)
            {
                ob_start();
                $this->display($this->view,$this->viewVars,false);
                $viewData = ob_get_contents();
                ob_end_clean();

                echo $viewData;

                if (!$this->getData() && defined('CACHE_HELPER') && $this->cacheAction)
                {
                    setCache($cacheActionUrl,$viewData);
                }

            }

        }


        //afterFilter

        if (method_exists($this,'afterFilter'))
        {
            call_user_func(array(&$this, 'afterFilter'));
        }

    }

    function setView($view)
    {
        $this->view = $view;
    }

    function set($var,$value = false)
    {
        if (is_array($var)) {
            $this->viewVars = array_merge($this->viewVars, $var);
        } else {
            $this->viewVars[$var] = $value;
        }
    }

    function setLayout($layout)  {

        if (!empty($layout)) {

            if (is_file($layout))
            {
              $this->layout = $layout;
            }
            else
            {
                $this->layout = 'layouts/'.$layout;
            }

        } else {

            $this->layout = '';

        }

    }

    function setLanguage($language = null)
    {
        setlocale(LC_MESSAGES,$language);
        bindtextdomain('messages', APP_PATH.'locale');
    }

    function setLayoutVars($var, $value = false)
    {
        if (is_array($var)) {
            $this->layoutVars = array_merge($this->layoutVars, $var);
        } else {
            $this->layoutVars[$var] = $value;
        }
    }

    function render($view, $vars=array())
    {
        if ($this->layout)
        {
            $content = new View($view, $vars);
            $this->layoutVars['layoutContent'] = $content->render();
            $view = new View($this->layout, $this->layoutVars);

        }
        else
        {
            $view = new View($view, $vars);
        }
        return $view->render();
    }

    function display($view, $vars=array(), $exit=true)
    {

        $this->output = $this->render($view, $vars);

        //beforeRender

        if (method_exists($this,'afterRender')) {
            call_user_func(array(&$this, 'afterRender'));
        }

        echo $this->output;

        if ($exit) exit;
    }

    function renderJSON($data_to_encode)
    {
        if (class_exists('JSON')) {
            return JSON::encode($data_to_encode);
        } else if (function_exists('json_encode')) {
            return json_encode($data_to_encode);
        } else {
            die('No function or class found to render JSON.');
        }
    }

    function getData($index = false,$fields = false)
    {
        if (isset($_POST['data'])) {
            $data = $_POST['data'];

            if ($index)
            {
                $retData = isset($data[$index])?$data[$index]:false;

                if ($fields)
                {
                    foreach ($retData as $var=>$val)  {
                        if (in_array($var,$fields)) {
                            $newRetData[$var] = $val;
                        }
                    }
                    return $newRetData;
                }

                return $retData;
            }

            return $data;
        }

        return $_POST;
    }

    function element($name,$data)
    {
        return View::quickRender('elements/'.$name,$data);
    }

} // end Controller class

/**
 * App controller implementation
 */

$app_controlller_file = APP_PATH.'app_controller.php';

if ( file_exists($app_controlller_file))
{
    include $app_controlller_file;
}
else
{
    // Empty App Controler Class
    class AppController extends Controller
    {

    }
}

$app_helper_file = APP_PATH.'app_helper.php';

if ( file_exists($app_helper_file))
{
    include $app_helper_file;
}
else
{
    // Empty App Helper Class
    class AppHelper
    {

    }
}

/**
 * Very Basic Model class mainly for MySQL with basic CRUD
 *
 */

class Model {

    /**
     * Database connection
     *
     * @var Mysql
     */
    var $db = false;
    var $useTable = false;
    var $metaColumns     = false;
    var $metaColumnNames = false;
    var $primaryKey = 'id';
    var $displayField = 'title';
    var $name = '';
    var $validate = array();
    var $recursive = 1 ;

    var $lastQuery = "";

    var $_result;
    var $_resultArray;

    function Model($name = null)
    {
        $this->name     = ($name)?$name:get_class($this);
        $this->dbmodel  = Inflector::underscore($this->name);

        if(!$this->useTable)
        {
            $this->useTable = Inflector::plural($this->dbmodel);
        }

        $this->db       = getInstance()->Controller->db;

        if (DB_PREFIX)
        {
            $this->useTable = DB_PREFIX.$this->useTable;
        }

    }

    function sync($data,$type)
    {

        $rdata = array();

        if (!$this->metaColumns)
        {
            $this->metaColumns     = $this->db->MetaColumns($this->useTable);
            Debugger::sqlLog("MetaCoulumns() for $this->useTable",$this->metaColumns);

            if (!$this->metaColumns)
            {
                throw new Exception("Table '{$this->useTable}' Columns could not be queried!");
            }

            foreach ($this->metaColumns as $field)
            {
                $this->metaColumnNames[] = $field->name;
            }
        }

        foreach ($this->metaColumnNames as $col)
        {

            if (isset($data[$col]))
            {
                $rdata[$col] = $data[$col];
            }

            if (( $col == 'created_on' || $col == 'updated_on' ) && $type=='insert' && (!isset($data[$col]) || empty($data[$col])))
            {
                $rdata[$col] = date("Y-m-d",TIME);
            }

            if (( $col == 'created_at' || $col == 'updated_at' ) && $type=='insert' && (!isset($data[$col]) || empty($data[$col])))
            {
                $rdata[$col] = date("Y-m-d H:i:s",TIME);
            }


            if ($col == 'updated_on'  && $type=='update' && (!isset($data[$col]) || empty($data[$col])))
            {
                $rdata[$col] = date("Y-m-d",TIME);
            }

            if ( $col == 'updated_at'  && $type=='update' && (!isset($data[$col]) || empty($data[$col])))
            {
                $rdata[$col] = date("Y-m-d H:i:s",TIME);
            }

        }

        return $rdata;

    }

    function create($data,$return = true)
    {

        if (!$this->db)
        {
            return false;
        }

        if (isset($data[$this->name])  && is_array($data[$this->name]))
        {
            $data = $this->name;
        }

        if (!$this->validates($data,'create'))
        {
            return false;
        }

        $data = $this->sync($data,'insert');

        $vars = '';
        $vals = '';

        foreach ($data as $var=>$val)
        {
            $val = __q($val);
            $vars.= ($vars==="")?$var:', '.$var;
            $vals.= ($vals==="")?$val:', '.$val;
        }

        $this->lastQuery = "INSERT INTO $this->useTable ($vars) VALUES ($vals);";

        $this->query($this->lastQuery);

        // get insert ID

        if($this->db->Insert_ID() && $return)
        {
            $this->recursive = false;
            return $this->find('first',array('conditions'=>array($this->name.'.'.$this->primaryKey => $this->db->Insert_ID())));
        }

        if ($this->_result)
        {
            return true;
        }

        return false;
    }

    function save($data,$conditions)
    {
        if (!$this->db) {
            return false;
        }

        if (isset($data[$this->name]) && is_array($data[$this->name]))
        {
            $data = $this->name;
        }

        doCallback('beforeSave',$this,$data);

        if (!$this->validates($data,'save'))
        {
            return false;
        }

        $data = $this->sync($data,'update');

        $update = '';

        foreach ($data as $var=>$val)
        {
            $val = __q($val);
            $update.= (empty($update)?'':',')."$this->name.$var=$val";
        }

        $sql_where = $this->_where($conditions);

        $this->lastQuery = "UPDATE $this->useTable AS $this->name SET $update $sql_where";

        $this->query($this->lastQuery);

        doCallback('afterSave',$this);

        if ($this->db->Affected_Rows())
        {
            return $this->db->Affected_Rows();
        }
        else
        {
            return false;
        }
    }

    function _where($conditions)
    {

        $rwhere = '';

        if (is_numeric($conditions))
        {
            $rwhere = 'WHERE '.$this->name.'.'.$this->primaryKey.' = '.__q($conditions);
        }
        else if (is_array($conditions) && !empty($conditions))
        {
            foreach ($conditions as $var=>$val)
            {
                $sign = '=';

                if (!empty($rwhere) && !strstr($var,'AND') && !strstr($var,'OR')) {
                    $rwhere.=" AND ";
                }


                if ( strstr($var,'=') || strstr($var,'>') || strstr($var,'<') || strstr($var,'LIKE'))
                {
                    $sign = '';
                }

                $rwhere.= " $var $sign".__q($val);
            }
            $rwhere ='WHERE '.$rwhere;
        }
        else if (!empty($conditions))
        {
            $rwhere = $conditions;
        }

        return $rwhere;

    }

    function _whereFields($fields = false)
    {
        if(!$fields)
        {
            return ' * ';
        } elseif (is_string($fields)) {
          return $fields;
        } elseif (is_array($fields))  {
            return implode(', ',$fields);
        }
    }

    function delete($conditions)
    {

        doCallback('beforeDelete',$this);

        // Do Orm processing

        $dataArray = $this->find('all',array('conditions'=>$conditions));

        $this->processORM($dataArray,'delete');

        $sql_where = $this->_where($conditions);
        $sql_where = str_replace("$this->name.",'',$sql_where);

        $this->lastQuery = "DELETE FROM $this->useTable $sql_where";
        $this->query($this->lastQuery);

        doCallback('afterDelete',$this);

        if ($this->db->Affected_Rows()) {
            return $this->db->Affected_Rows();
        } else {
            return false;
        }
    }

    function field($name,$conditions = false,$order = false)
    {
        $result = $this->find('first',array('conditions'=>$conditions,'order'=>$order));

        if ($result) {
            return $result[$this->name][$name];
        }

        return false;
    }

    function find($type = 'first' , $params = array())
    {
        $retArray = false;

        $params['type'] = $type;

        $params = doCallback('beforeFind',$this,$params);

        if (!$params) {
          return false;
        }

        switch ($params['type'])
        {
            case 'all':
                {
                    $retArray = $this->_find($params);
                }
            break;

            case 'count':
            {
                $params['recursive'] = 0;
                $this->_find($params);
                return $this->getNumRows();
            }
            break;

            case 'list':
                {
                    //
                    if (isset($params['fields'][2])) {
                      $params['group'] = $params['fields'][2];
                    }

                    $result = $this->_find($params);

                    if($this->_result && is_array($this->_resultArray))
                    {

                        $key   = str_replace($this->name.'.','',$params['fields'][0]);
                        $value = str_replace($this->name.'.','',isset($params['fields'][1])?$params['fields'][1]:'');

                        foreach ($this->_resultArray as $row)
                        {
                            $retArray[$row[$key]] = empty($value)?$row[$key]:$row[$value];
                        }
                    }
                }
            break;

            default:
            case 'first':
                {
                    $params['limit'] = 1;
                    $result = $this->_find($params);

                    if ($result && isset($result[0]))
                    {
                        $retArray = $result[0];
                    }

                }
                break;

        }

        $retArray = doCallback('afterFind',$this,$retArray);

        return $retArray;

    }

    function _find($params)
    {
        extract($params);

        $conditions = $this->_where(isset($conditions)?$conditions:false);
        $fields     = $this->_whereFields(isset($fields)?$fields:false);
        $group      = isset($group)?"GROUP BY $group":'';
        $limit      = isset($limit)?$limit:-1;
        $page       = isset($page)?$page:1;
        $order      = isset($order)?"ORDER BY $order":'';
        $this->recursive = isset($recursive)?$recursive:$this->recursive;

        $offset = ($page - 1) * $limit;

        $this->lastQuery = "SELECT $fields FROM $this->useTable AS $this->name $conditions $group $order";

        $this->_result =  $this->db->SelectLimit($this->lastQuery,$limit,$offset);

        Debugger::sqlLog($this->lastQuery." ( LIMIT: $limit PAGE: $page) ",$this->_result);

        // Format into Model Array
        $retArray = false;

        if ($this->_result) {

            $this->_resultArray =  $this->_result->GetArray();

            if (is_array($this->_resultArray))
            {
                foreach ($this->_resultArray as $row)
                {
                    $retArray[] = array($this->name => $row);
                }

                // Process Relations
                $retArray = $this->processORM($retArray);
            }

        }

        return $retArray;

    }

    /**
     * Magic functions
     */

    function __call($name,$param)
    {
        if (strstr($name,'findAllBy'))
        {
            $field = str_replace('findAllBy','',$name);
            return $this->find('all',array('conditions'=>array(Inflector::underscore($field)=>$param[0])));
        }
        elseif (strstr($name,'findBy'))
        {
            $field = str_replace('findBy','',$name);
            return $this->find('first',array('conditions'=>array(Inflector::underscore($field)=>$param[0])));
        }

        throw new Exception("Unknown function {$name} called on Model");
    }

    function getLastInsertID()
    {
        return $this->db->Insert_ID();
    }

    function getNumRows()
    {
        if ($this->_result) {
            return $this->_result->RecordCount();
        }

        return false;
    }

    function getAffectedRows()
    {
        return $this->db->Affected_Rows();
    }

    function getColumnTypes($column = false)
    {
        $meta = $this->db->MetaColumns($this->useTable);

        $return = false;
        foreach ($meta as $field=>$info)
        {
            $return[$field] = $info->type;
        }
        return  $return;
    }

    function query($sql)
    {

        $this->lastQuery = $sql;

        $this->_result = $this->db->Execute($this->lastQuery);

        Debugger::sqlLog($this->lastQuery,$this->_result);

        // Format into Model Array
        $retArray = false;

        if ($this->_result) {
            $retArray =  $this->_result->GetArray();
        }

        return $retArray;




    }

    function validates($data,$action = 'all')
    {

        doCallback('beforeValidate',$this);

        if (is_array($this->validate) && count($this->validate) > 0)
        {
            uses('Validation',B_LIBRARY);

            $base = &getInstance();

            $v = $base->Controller->Validation;

            $v->Validation($this,$data);

            $vrules = array();
            $vfields = array();

            foreach ($this->validate as $field=>$rules)
            {
                if (is_string($rules))
                {
                    $vrules[$field] = $rules;
                    $vfields[$field] = ucfirst($field);
                } else if (is_array($rules))
                {
                    if (!isset($rules['on']) || $rules['on'] == $action )
                    {
                        $vrules[$field]  = $rules['rules'];
                        $vfields[$field] = isset($rules['name'])?$rules['name']:ucfirst($field);
                    }
                }

            }

            if (count($vrules) ==0) {
              return true;
            }
            $v->setRules($vrules);
            $v->setFields($vfields);

            return $v->run();
        }
        return true;
    }

    function _findORM($meta,$key,$type = 'select',$multiple = false)
    {
        extract($meta);

        // $className ( always set )
        $classObj = new $className();

        $foreignKey = isset($foreignKey)?$foreignKey:$classObj->name.'.'.Inflector::underscore($this->name).'_id';

        $meta['conditions'] = array_merge(isset($conditions)?$conditions:array(),array($foreignKey=>$key));

        if ($type == 'select') {
            $meta['recursive'] = $this->recursive - 1;
            if ($multiple)
            {
                $classObj->find('all',$meta);
                return $classObj->_resultArray;
            }
            else
            {
                $classObj->find('first',$meta);
                return (isset($classObj->_resultArray[0]))?$classObj->_resultArray[0]:false;
            }
        } elseif ($type == 'delete' && isset($dependent) && $dependent && isset($conditions))
        {
            return $classObj->delete($conditions);
        }

    }

    function _loopORM($models,$dataArray,$type = 'select',$multiple=false,$child = false)
    {
        $models = (is_array($models))?$models:array($models=>array());

        for ($i=0;$i<count($dataArray);$i++)
        {
            foreach ($models as $model=>$meta)
            {
                $meta['className'] = isset($meta['className'])?$meta['className']:$model;
                if ($multiple) {
                     $dataArray[$i][$model] = $this->_findORM($meta,$dataArray[$i][$this->name][$this->primaryKey],$type,true);
                } else {
                    if ($child) {
                        $fKey    = isset($meta['foreignKey'])?$meta['foreignKey']:Inflector::underscore($meta['className']).'_id';
                        $meta['foreignKey']    = $meta['className'].'.id';
                      $dataArray[$i][$model] = $this->_findORM($meta,$dataArray[$i][$this->name][$fKey],$type);
                    } else {
                        $dataArray[$i][$model] = $this->_findORM($meta,$dataArray[$i][$this->name][$this->primaryKey],$type);
                    }
                }

            }
        }
        return $dataArray;

    }

    function processORM($dataArray,$type = 'select')
    {
        if (!$this->recursive) {
          return $dataArray;
        }

         // hasOne
        if (isset($this->hasOne) && !empty($this->hasOne))
        {
            $dataArray = $this->_loopORM($this->hasOne,$dataArray,$type,false);
        }

        // belongTo
        if (isset($this->belongsTo) && !empty($this->belongsTo) && $type=='select')
        {
            $dataArray = $this->_loopORM($this->belongsTo,$dataArray,$type,false,true);
        }

        // hasMany()
        if (isset($this->hasMany) && !empty($this->hasMany))
        {
            $dataArray = $this->_loopORM($this->hasMany,$dataArray,$type,true);
        }

        return $dataArray;
    }

    /**
     * Callbacks
     */

    function beforeFind($queryData)
    {
        return $queryData;
    }

    function afterFind($retArray = array())
    {
        return $retArray;
    }

}

/**
 * App Model implementation
 */

if ( file_exists(APP_PATH.'app_model.php'))
{
    include APP_PATH.'app_model.php';
}
else
{
    // Empty App Controler Class

    class AppModel extends Model
    {
        function AppModel($name = false)
        {
            parent::Model($name);
        }

    }
}



/**
 * Session
 *
 * This class allows you to use session variables.
 */

class Session
{
    function Session()
    {
        // check if session helper exists for custom session
        if (defined('SESSION_HELPER'))
        {
            include 'helpers/'.SESSION_HELPER;
        }

        session_start();
    }

    public static function  read($var)
    {
        return isset($_SESSION[$var]) ? $_SESSION[$var] : null;
    }

    public static function  write($var, $value)
    {
        $_SESSION[$var] = $value;
    }

    public static function  delete($var)
    {
        unset($_SESSION[$var]);
    }

    public static function  destroy()
    {
        session_destroy();
    }

    public static function  setFlash($message,$type = B_ERROR,$return = false)
    {
        $messages = Session::read('flash_message');

        $messages[] = $message;

        Session::write('flash_message',$messages);
        Session::write('flash_type',$type);

        if ($return) {
          return SEssion::flash();
        }
    }

    /**
     * Shows the messages set up in Views.
     *
     */
    public static function flash()
    {
        $messages = Session::read('flash_message');
        if ($messages) {
            $type    = Session::read('flash_type');

            Session::delete('flash_message');
            Session::delete('flash_type');

            return View::quickRender('elements/message',array('message'=>implode('<br />',$messages),'type'=>$type));
        }

    }

} // end Session class


class Cookie
{
    var $params = array();

    function Cookie()
    {
        $this->params['path'] = '/';
        $this->params['domain'] = '';
        $this->params['key'] = SALT;
    }

    function write($key,$value,$encrypt = true,$expire = 3600)
    {
        $key = str_replace('.','_',$key);
        if ($encrypt)
        {
            $value = base64_encode(cipher($value,SALT));
        } else {
            setcookie($key.'_p',true,TIME+$expire,$this->params['path'],$this->params['domain']);
        }
        setcookie($key,$value,TIME+$expire,$this->params['path'],$this->params['domain']);

    }

    function read($key)
    {
        $key = str_replace('.','_',$key);
        if(isset($_COOKIE[$key]))
        {
            if (isset($_COOKIE[$key.'_p'])) {
                return $_COOKIE[$key];
            }
            return cipher(base64_decode($_COOKIE[$key]),SALT);
        }
        return false;
    }

    function delete($key)
    {
        $key = str_replace('.','_',$key);
        if(isset($_COOKIE[$key]))
        {
            if (isset($_COOKIE[$key.'_p'])) {
                setcookie($key.'_p',"",TIME-3600,$this->params['path'],$this->params['domain']);
            }
            setcookie($key,"",TIME-3600,$this->params['path'],$this->params['domain']);
        }
    }

}
/**
 * Configure read config in data directory
 *
 */

class Configure
{

    private static $_config = false;

    public static function read($key = 'debug')
    {
        Configure::readConfig();
        return isset(self::$_config[$key])?self::$_config[$key]:false;
    }

    public static function write($key,$value = false)
    {
        Configure::readConfig();
        self::set($key,$value);
        return Configure::writeConfig();
    }

    public static function set($key,$value = false)
    {
        if (is_array($key))
        {
            foreach ($key as $var=>$val)
            {
                self::$_config[$var] = $val;
            }
        }
        else
        {
            self::$_config[$key] = $value;
        }
    }

    public static function delete($key)
    {
        Configure::readConfig();
        unset(self::$_config[$key]);
        Configure::writeConfig();
    }

    public static function readConfig($reset = false)
    {
        if (!self::$_config || $reset)
        {
            include(CONFIG_FILE);
            self::$_config = $config;
        }
        return self::$_config;
    }

    public static function writeConfig()
    {
        $fileData = "";

        foreach (self::$_config as $key=>$value)
        $fileData.= (!empty($fileData)?',':'')."\n'$key' => safeDecode('".safeEncode($value)."')";

        $fileData = '<?php $config = array('.$fileData.");";

        $fp =fopen(CONFIG_FILE,"w");
        if (!$fp)
        {
            return false;
        }
        fwrite($fp,$fileData);
        fclose($fp);
        return true;
    }

    function __get($var)
    {
        return Configure::read($var);
    }
}

class Inflector
{
    /**
     *  Return an CamelizeSyntaxed (LikeThisDearReader) from something like_this_dear_reader.
     *
     * @param string $string Word to camelize
     * @return string Camelized word. LikeThis.
     */
    public static function camelize($string)
    {
        return str_replace(' ','',ucwords(str_replace('_',' ', $string)));
    }

    /**
     * Return an underscore_syntaxed (like_this_dear_reader) from something LikeThisDearReader.
     *
     * @param  string $string CamelCased word to be "underscorized"
     * @return string Underscored version of the $string
     */
    public static function underscore($string)
    {
        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $string));
    }

    /**
     * Return an Humanized syntaxed (Like this dear reader) from something like_this_dear_reader.
     *
     * @param  string $string CamelCased word to be "underscorized"
     * @return string Underscored version of the $string
     */
    public static function humanize($string)
    {
        return ucfirst(str_replace('_', ' ', $string));
    }

    public static function plural($str, $force = FALSE)
    {
        $str = strtolower(trim($str));
        $end = substr($str, -1);

        if ($end == 'y')
        {
            $str = substr($str, 0, strlen($str)-1).'ies';
        }
        elseif ($end == 's')
        {
            if ($force == TRUE)
            {
                $str .= 'es';
            }
        }
        else
        {
            $str .= 's';
        }

        return $str;
    }

    public static function singular($str)
    {
        $end = substr($str, -3);

        if ($end == 'ies')
        {
            $str = substr($str, 0, strlen($str) - 3).'y';
        }
        elseif (substr($str, -4) == 'sses' OR $end == 'xes')
        {
            $str = substr($str, 0, strlen($str) - 2);
        }
        elseif (substr($str, -1) == 's')
        {
            $str = substr($str, 0, strlen($str) - 1);
        }

        return $str;
    }
}

class Debugger {

    private static $instance = false;
    private static $errorLog = array();
    private static $sqlLog   = array();

    public static function sqlLog($query,$result)
    {
        $query = str_replace("\n",'\n',$query);
        if ($result) {
            $entry = array('type'=>'info','message'=>$query);
        } else {
            $entry = array('type'=>'error','message'=>$query.'\n'.getInstance()->Controller->db->ErrorMsg());
        }

        self::$sqlLog[] = $entry;
    }

    public static function &getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Debugger();
        }
        return $instance;
    }

    public static function dump($var,$label = false,$return = false)
    {
        if (DEBUG)
        {
            $buffer = "";

            if(defined('FIREBUG')) {
                $buffer .= 'console.group("'.$label.'");'.NL;
            } else {
                $buffer .= '<h2>'.$label.'</h2>';
                $buffer .= '<table cellpadding="3" cellspacing="0" style="width: 800px; border: 1px solid #ccc">';
            }

            foreach ($var as $key => $value)
            {
                if (is_array($value) && isset($value['type']) && isset($value['message'])) {
                    if(defined('FIREBUG')) {
                        $buffer .= 'console.'.$value['type'].'("'.$value['message'].'");'.NL;
                    } else {
                        $buffer .= '<tr><td style="border-bottom: 1px dashed #ddd;" valign="top">'.$key.'</td><td style="border-bottom: 1px dashed #ddd;">'.$value['message'].'</td></tr>';
                    }
                }
                else
                {

                    if (is_null($value)) $value = 'null';
                    else if (is_array($value)) $value = 'array['.sizeof($value).'] <br />'.Debugger::dump($value,$key,true);
                    else if (is_object($value)) $value = get_class($value).' Object';
                    else if (is_bool($value)) $value = $value ? 'true' : 'false';
                    else if (is_int($value)) $value = $value;

                    if(defined('FIREBUG')) {
                        $buffer .= 'console.info("'.$key.'='.$value.'");'.NL;
                    } else {
                        $buffer .= '<tr><td style="border-bottom: 1px dashed #ddd;" valign="top">'.$key.'</td><td style="border-bottom: 1px dashed #ddd;">'.$value.'</td></tr>';
                    }
                }

            }

            if (defined('FIREBUG')) {
                $buffer .= 'console.groupEnd();'.NL;
            } else {
                $buffer .= '</table>';
            }

            if ($return) return $buffer;

            if (defined('FIREBUG')) {
                echo  '<script type="text/javascript">'.NL.$buffer.'</script>'.NL;
            } else {
                echo $buffer;
            }

        }
    }

    public static function dumpInfo($return = false)
    {
        if ( !DEBUG ) return;

        // Quick Info

        $buffer= sprintf('Execution Time : %0.4f sec, Memory Used : %f MB',microtime(true) - START_TIME, memory_get_usage() / (1024 * 1024) );


        if(defined('FIREBUG')) {
            $buffer = 'console.info("'.$buffer.'");'.NL;
        } else {
            $buffer .= '<br />';
        }

        if (!empty(self::$errorLog))
        {
            $buffer .= self::dump(self::$errorLog,'Errors',true);
        }

        if (!empty(self::$sqlLog))
        {
            $buffer .=self::dump(self::$sqlLog,'Sql Log',true);
        }

        if ( DEBUG > 2) {
            $base = getInstance();
            $Controller = $base->Controller;


            $buffer .= self::dump($base,'Dispatcher status',true);
            $buffer .= self::dump($Controller,'Controller',true);
            if ( ! empty($_GET)) $buffer .= self::dump($_GET, 'GET',true);
            if ( ! empty($_POST)) $buffer .= self::dump($_POST, 'POST',true);
            if ( ! empty($_COOKIE)) $buffer .= self::dump($_COOKIE, 'COOKIE',true);
            $buffer .= self::dump($_SERVER, 'SERVER',true);
        }

        if(defined('FIREBUG')) {
            $buffer = '<script type="text/javascript">if (("console" in window) && ("firebug" in console)) {'.NL.$buffer.'}</script>'.NL;
        }

        if ($return) return $buffer;

        echo $buffer;

    }

    public static function errorHandler($errno, $message, $filename, $line) {

        $types = array(1 => 'fatal error',
        2 => 'warning',
        4 => 'parse error',
        8 => 'notice',
        16 => 'core error',
        32 => 'core warning',
        64 => 'compile error',
        128 => 'compile warning',
        256 => 'user error',
        512 => 'user warning',
        1024 => 'user notice',
        2048 => 'strict warning',
        4096 => 'recoverable fatal error');

        $entry = __("%s : %s in %s on line %d.",ucwords($types[$errno]),$message,defined('FIREBUG')?addslashes($filename):$filename,$line);

        self::$errorLog[] = array('type'=>'error','message'=>$entry);

    }

    public static function customErrorHandler($buffer) {


        if (preg_match('%<br />[\r\n]+<b>Fatal error</b>:\s+(.+) in <b>(.+)</b> on line <b>([0-9]+)</b><br />%s',$buffer,$match))
        {
            $match[2] = addslashes($match[2]);
            Debugger::errorHandler(1,$match[1],$match[2],$match[3]);
            return preg_replace('%<br />[\r\n]+<b>Fatal error</b>:\s+(.+) in <b>(.+)</b> on line <b>([0-9]+)</b><br />%s',Debugger::dumpInfo(true),$buffer);
        }

        return $buffer.Debugger::dumpInfo(true);
    }

    public static function exceptionHandler($e)
    {
        if ( DEBUG == 0 ) pageNotFound();

        $label = 'Uncaught '.get_class($e).' ( '.$e->getMessage().')';

        $traceStack = array();
        $traces = $e->getTrace();
        if (count($traces) > 1) {

            $level = 0;
            foreach (array_reverse($traces) as $trace) {
                ++$level;

                $var = false;
                $val = false;



                $args = array();
                if ( ! empty($trace['args'])) {
                    foreach ($trace['args'] as $arg) {
                        if (is_null($arg)) $args[] = 'null';
                        else if (is_array($arg)) $args[] = 'array['.sizeof($arg).']';
                        else if (is_object($arg)) $args[] = get_class($arg).' Object';
                        else if (is_bool($arg)) $args[] = $arg ? 'true' : 'false';
                        else if (is_int($arg)) $args[] = $arg;
                        else {
                            $arg = htmlspecialchars(substr($arg, 0, 64));
                            if (strlen($arg) >= 64) $arg .= '...';
                            $args[] = "'". $arg ."'";
                        }
                    }
                }

                $message = __('%s::%s(%s) on line %s in file %s',(isset($trace['class'])?$trace['class']:''),$trace['function'],implode(', ',$args),$trace['line'],defined('FIREBUG')?addslashes($trace['file']):$trace['file']);

                $traceStack[]=array('type'=>'info','message'=>$message);
            }
        }
        ++$level;

        $message = sprintf('Exception Thrown on line %s in file %s',$e->getLine(),defined('FIREBUG')?addslashes($e->getFile()):$e->getFile());
        $traceStack[] = array('type'=>'error','message'=>$message);

        self::dump($traceStack,$label);
    }

}

// ----------------------------------------------------------------
//   global function
// ----------------------------------------------------------------

define('B_SUCCESS','success');
define('B_ERROR','error');
define('B_NOTICE','notice');


define('B_HELPER','Helper');
define('B_MODEL','Model');
define('B_LIBRARY','Library');
define('B_CONTROLLER','Controller');
define('B_COMPONENT','Component');

function uses($names,$type=B_MODEL)
{
    if (!is_array($names))
    {
        $names = array($names);
    }

    if (!isset($names[0]) || empty($names[0]))
    {
        return;
    }

    $base    = &getInstance();

    if ($type == B_COMPONENT)
    {
        // add paths
        foreach ($names as $name)
        {
            $pname = Inflector::underscore($name);
            $componentDir = APP_PATH.'components/'.$pname.'/';

            if (!is_dir($componentDir))
            {
                throw new Exception($type." dir '{$pname}' not found!");
            }
            else
            {
                $base->paths[] = $componentDir;
            }
        }

        $type = B_CONTROLLER;

    }

    $typeDir = Inflector::plural(strtolower($type));
    $gVar = "_".$typeDir;

    if (!isset($base->$gVar))
    {
        $base->{$gVar} = array();
    }

    foreach ($names as $name) {
        if (in_array($name,$base->$gVar)) continue;

        $paths = $base->paths;
        $found = false;

        foreach ($paths as $path)
        {
            $pname = Inflector::underscore($name);

            if ($type == B_HELPER)
            {
                $pname=strtolower($pname.'_helper');
            }
            if ($type == B_CONTROLLER)
            {
                $pname=strtolower($pname.'_controller');
            }

            $path = $path.$typeDir.'/'.$pname.'.php';

            if (file_exists($path))
            {
                include $path;
                $found = true;

                $base->{$gVar}[] = $name;

                if ($type == B_MODEL || $type == B_LIBRARY)
                {
                    $base->Controller->$name = new $name();

                    if ( is_subclass_of($base->Controller->$name,'AppModel'))
                    {
                        $postData = $base->Controller->getData($name);
                        if (is_array($postData) && !empty($postData))
                        {
                            $base->Controller->data[$name] = $postData;
                            $base->Controller->set($name,$postData);
                        }
                    }
                }

            }
        }

        if (!$found)
        {
            throw new Exception($type." file '{$pname}' not found!");
        }


    }

}

function __autoload($model) {
    uses($model);
}

function getURL($link = false,$linkName = false ,$linkImage = false,$imageOnly = true,$class = false)
{
    $olink = $link;
    $onClick = "";
    $target  = "_self";

    if (!$link)
    {
        $link = BASE_URL;
    }
    else if ($link[0] == '#') {
        $onClick = substr($link,1);
        if (empty($onClick)) {
          $onClick = 'return false;';
        }

        $onClick = 'onclick="'.$onClick.'"';
        $link = '#';

    }
    else if (!strstr($link,'http:')) {
        $link = ($link[0] == '/')?substr($link,1):$link;
        $link = BASE_URL.$link.URL_SUFFIX;
    }
    elseif (strstr($link,'http://'))
    {
        // Open in new page
        $target = "_blank";
    }

    $class = ($class)?" class=\"$class\" ":'';


    if ( $linkImage || $linkName )
    {
        // check if icon

        $target = ($target == '_self')?'':"target =\"$target\"";
        return "<a href=\"$link\" title=\"$linkName\" $onClick $class $target>".(($linkImage)?"<img src=\"$linkImage\" alt=\"$linkName\" border=0 >":'').(($imageOnly && $linkImage)?'':$linkName)."</a>";
    }
    else
    {
        return $link;
    }

}

function redirect($url,$message = false,$type = B_SUCCESS)
{
    // any messages there ?
    $base = getInstance();
    $controller = $base->Controller;

    if ($message) {
        Session::setFlash($message,$type);
    }

    if (!strstr($url,'http:')) // redirect internally
    {
        $url = getURL($url);
    }

    if (!DEBUG) header('Location: '.$url);

    echo View::quickRender('elements/refresh',array('debug'=>DEBUG,'location'=>$url));

    exit;
}


function doCallback($callback,$object=false,$params=false)
{
    if ($object)
    {
        if (method_exists($object,$callback))
        {
            if ($params) {
                return call_user_func_array(array($object,$callback),array($params));
            } else {
                return call_user_func(array($object,$callback));
            }
        }
    } else {

        if (function_exists($callback))
        {
            if ($params) {
                call_user_func_array($callback,array(&$params));
            } else {
                call_user_func($callback);
            }
        }
    }

}


if (!function_exists('json_encode'))
{
    function json_encode($a=false)
    {
        if (is_null($a)) return 'null';
        if ($a === false) return 'false';
        if ($a === true) return 'true';
        if (is_scalar($a))
        {
            if (is_float($a))
            {
                // Always use "." for floats.
                return floatval(str_replace(",", ".", strval($a)));
            }

            if (is_string($a))
            {
                static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
            }
            else
            return $a;
        }
        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a))
        {
            if (key($a) !== $i)
            {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList)
        {
            foreach ($a as $v) $result[] = json_encode($v);
            return '[' . join(',', $result) . ']';
        }
        else
        {
            foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
            return '{' . join(',', $result) . '}';
        }
    }
}

/**
 * Encodes HTML safely for UTF-8. Use instead of htmlentities.
 */
function html_encode($string)
{
    return htmlentities($string, ENT_QUOTES, 'UTF-8') ;
}

/**
 * Display a 404 page not found and exit
 */
function pageNotFound()
{
    header("HTTP/1.0 404 Not Found");
    echo View::quickRender('elements/404');
    exit;
}

/**
 * This function will strip slashes if magic quotes is enabled so
 * all input data ($_GET, $_POST, $_COOKIE) is free of slashes
 */
function fix_input_quotes()
{
    $in = array(&$_GET, &$_POST, &$_COOKIE);
    while (list($k,$v) = each($in)) {
        foreach ($v as $key => $val) {
            if (!is_array($val)) {
                $in[$k][$key] = stripslashes($val); continue;
            }
            $in[] = $in[$k][$key];
        }
    }
    unset($in);
} // fix_input_quotes

if (get_magic_quotes_gpc()) {
    fix_input_quotes();
}

/* For Storing varialbes in string */
function safeEncode($str) {
    $str = addslashes($str);
    $str = str_replace(array("\r","\n","\\","'","\""),array("[CR]","[NL]","[ES]","[SQ]","[DQ]"), $str);
    return $str;
}

function safeDecode($str) {
    if(is_numeric($str))return $str;
    $str = str_replace(array("[CR]","[NL]","[ES]","[SQ]","[DQ]"),array("\r","\n","\\","'","\""), $str);
    return stripslashes($str);
}

/* Localization */

function __()
{
    $argv = func_get_args();

    if (isset($argv[2]) && is_bool($argv[2])) {
        return ($argv[2])?$argv[0]:$argv[1];
    }
    else {
        $format = array_shift( $argv );
        if (isset($argv[0])) {
            return vsprintf( $format, $argv );
        } else {
            return $format;
        }
    }
}
// Quote variable to make safe
function __q($value)
{
    // Stripslashes
    if (get_magic_quotes_gpc()) {
        $value = stripslashes($value);
    }
    // Quote if not integer
    if (!is_numeric($value) || $value < 0) {
        $value = "'" . mysql_real_escape_string($value) . "'";
    }
    return $value;
}

function a() {
    $args = func_get_args();
    return $args;
}

function aa() {
    $args = func_get_args();
    for ($l = 0, $c = count($args); $l < $c; $l++) {
        if ($l + 1 < count($args)) {
            $a[$args[$l]] = $args[$l + 1];
        } else {
            $a[$args[$l]] = null;
        }
        $l++;
    }
    return $a;
}

function cipher($text, $key) {

    srand (CIPHER_SALT);
    $out = '';

    for ($i = 0; $i < strlen($text); $i++) {
        for ($j = 0; $j < ord(substr($key, $i % strlen($key), 1)); $j++) {
            $toss = rand(0, 255);
        }
        $mask = rand(0, 255);
        $out .= chr(ord(substr($text, $i, 1)) ^ $mask);
    }
    return $out;
}

function pr($var,$return = false)
{
    if (DEBUG > 0)
    {
        $pr = '<pre>'.print_r($var,true).'</pre>';

        if ($return) {
            return $pr;
        }
        echo $pr;
    }
}

/**
 * Provides a nice print out of the stack trace when an exception is thrown.
 *
 * @param Exception $e Exception object.
 */

set_error_handler('Debugger::errorHandler');
set_exception_handler('Debugger::exceptionHandler');
