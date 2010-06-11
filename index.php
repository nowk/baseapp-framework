<?php
/**
 * Main File
 *
 * This is the main file which is executed every time.
 * 
 * @version     $Id: index.php 8 2009-03-04 22:35:07Z vikaspatial1983 $
 * @package     BaseApp Framework (v0.1)
 * @link        http://code.google.com/p/baseappframework/
 * @copyright   Copyright (C) 2009 NGCoders. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
*/


error_reporting(E_ALL);

define('BASEAPP',true);

/* Root Directory where th file is residing */
define('CORE_PATH', dirname(__FILE__).'/');
define('BASEAPP_PATH', CORE_PATH.'baseapp/');

/* Include the framework file */
include BASEAPP_PATH.'baseapp.php';

/* Start Execution , setup envoirnment and execute */
ob_start("Debugger::customErrorHandler");
$base = new Dispatcher();
include APP_PATH.'app_config.php'; 
$base->dispatch(isset($requestedURL)?$requestedURL:null);
ob_end_flush();