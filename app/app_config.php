<?php
/**
 * Application Envoirnment Config
 *
 * Set up the working envoirnment for the application
 * 
 * @version     $Id: index.php 6 2009-03-04 21:21:20Z vikaspatial1983 $
 * @package     BaseApp Framework (v0.1)
 * @link        http://code.google.com/p/baseappframework/
 * @copyright   Copyright (C) 2009 NGCoders. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
*/

/* Various Defines */

// Defines for random number and crypto. Change them in final application.
define('SALT', 'dfokgj5409jglkdfj0m9c5;'); 
// Cipher SALT can be numeric only
define('CIPHER_SALT', '456456356567567');

// Default Controller when root path is accessed
define('DEFAULT_CONTROLLER', 'default');

// Default action when root path is accessed
define('DEFAULT_ACTION', 'index');

// Admin path define {site}/admin/ will get into admin panel
define('BASEAPP_ADMIN', 'admin');

// Reads config array from data/config.php , this file is user editable
$config = Configure::readConfig();

// Overridable defines
define('SITE_URL',"http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/');

// Session handler if you want database based sessions
// define('SESSION_HELPER','session_helper.php');

// Cache helper allowing action level cache
// define('CACHE_HELPER','cache_helper.php');


// Debuggin setup , with allowance for firebug debugging. 
define('DEBUG',isset($config['debug'])?$config['debug']:1);

// Comment out if firebug debugging not required
// if (DEBUG < 3) define('FIREBUG',true);	

// The base url used when creating relative urls
define('BASE_URL',  SITE_URL.(Configure::read('url_sef')?'':'?/'));

// The path to various media
define('WEBROOT_URL', SITE_URL .'app/webroot/');

// Custom routing as required by application 
$base->addRoute('/admin/:any','/admin/$1');