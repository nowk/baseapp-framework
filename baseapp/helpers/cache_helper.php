<?php
/**
 * Short description for file.
 *
 * Long description for file.
 * 
 * @version     $Id: cache_helper.php 7 2009-03-04 22:18:40Z vikaspatial1983 $
 * @package     BaseApp Framework (v0.1)
 * @link        http://code.google.com/p/baseappframework/
 * @copyright   Copyright (C) 2009 NGCoders. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
*/

define('CACHE_PATH',APP_PATH.'data/cache/');

function cache_write($file,$data)
{
	$fp=fopen($file,"w");
	if (!$fp) return false;
	fwrite($fp,$data,strlen($data));
	fclose($fp);
	return true;
}

function checkCache($cacheActionUrl)
{
    $base = getInstance();
    $Controller = $base->Controller;
    foreach ($Controller->cacheAction as $action => $timeout)
    {
        // convert wildcards to regex
        if (strpos($action, ':') !== false)
        {
            $action = str_replace(':any', '(.*)', str_replace(':num', '([0-9]+)', $action));
        }

        // does the regex match?
        if (preg_match('#^'.$action.'$#', $cacheActionUrl))
        {
            $url = str_replace('/','_',$base->controllerName.'/'.$cacheActionUrl);
            
            return array('alias'=>$url,'timeout'=>$timeout);
        }
    }
    
    return false;
            
}

function setCache($cacheActionUrl,$viewData)
{
    if (!$result = checkCache($cacheActionUrl)) 
    {
    	return false;
    }
    
    $viewData = '<?php if(!defined("BASEAPP"))die("Invalid Access"); ?>'."\n".$viewData;
       
    cache_write(CACHE_PATH.$result['alias'].'.php',$viewData);
}

function getCache($cacheActionUrl)
{        

    if (!$result = checkCache($cacheActionUrl)) 
    {
    	return false;
    }
        
    $cacheFile = CACHE_PATH.$result['alias'].'.php';

    if (is_file($cacheFile)) 
    {
        if (filemtime($cacheFile) > (TIME - $result['timeout'])) 
        {
             ob_start();
             include($cacheFile);
             $cacheContent = ob_get_contents();
             ob_end_clean();
             return $cacheContent;
        }        	
        else 
        {
            unlink($cacheFile);
        }
    }
    
    return false;
}

function clearCache($action)
{
    
}