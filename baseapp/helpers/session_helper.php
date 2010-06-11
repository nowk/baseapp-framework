<?php
/**
 * Short description for file.
 *
 * Long description for file.
 * 
 * @version     $Id: session_helper.php 7 2009-03-04 22:18:40Z vikaspatial1983 $
 * @package     BaseApp Framework (v0.1)
 * @link        http://code.google.com/p/baseappframework/
 * @copyright   Copyright (C) 2009 NGCoders. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
*/




session_set_save_handler('sess_open', 'sess_close', 'sess_read', 'sess_write', 'sess_destroy_sid', 'sess_gc');


function sess_open($save_path, $session_name) {
	return TRUE;
}

function sess_close() {
	return TRUE;
}

function sess_read($key) {

    $Session = new AppModel('Session');

	register_shutdown_function('session_write_close');

	// Handle the case of first time visitors and clients that don't store cookies (eg. web crawlers).
	if (!isset($_COOKIE[session_name()])) {
		return '';
	}
	
	$result = $Session->findById($key);
		
	return 	(isset($result['Session']))?$result['Session']['session']:false;
}

function sess_write($key, $value) {

	// If the client doesn't have a session, and one isn't being created ($value), do nothing.
	if (empty($_COOKIE[session_name()]) && empty($value))
	{
		return TRUE;
	}
	
	$Controller = getInstance()->Controller;
	
	$Session = new AppModel('Session');
	
	$result = $Session->findById($key);
	
	$user_id = 0;
	
	if (isset($Controller->User->user['id'])) 
	{
		$user_id = $Controller->User->user['id'];
	}
	
	if (isset($result['Session']['id'])) 
	{
		$Session->save(array('hostname'=>$_SERVER['REMOTE_ADDR'],'session'=>$value),array('id'=>$key));
	}
	else 
	{
	    $Session->create(array('id'=>$key,'hostname'=>$_SERVER['REMOTE_ADDR'],'session'=>$value));
	}
	
	return TRUE;
}

function sess_regenerate() 
{
	$oldSessionID = session_id();

	if (isset($_COOKIE[session_name()])) {
		setcookie(session_name(), '', time() - 42000, '/');
	}

	session_regenerate_id();
	
	$Session = new AppModel('Session');
	$Session->save(array('id'=>session_id()),$oldSessionID);
}

function sess_destroy_sid($sid) 
{
    $Session = new AppModel('Session');
    $Session->delete($sid);
}

function sess_destroy_uid($user_id) 
{
    $Session = new AppModel('Session');
    $Session->delete(array('created_by_id'=>$user_id));
}

function sess_gc($lifetime) 
{
    $Session = new AppModel('Session');
    $Session->delete(array('updated_at < '=>date('Y-m-d H:i:s',time() - $lifetime)));
    return TRUE;
}
