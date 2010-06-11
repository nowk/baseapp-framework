<?php
/**
 * Short description for file.
 *
 * Long description for file.
 * 
 * @version     $Id: file.php 7 2009-03-04 22:18:40Z vikaspatial1983 $
 * @package     BaseApp Framework (v0.1)
 * @link        http://code.google.com/p/baseappframework/
 * @copyright   Copyright (C) 2009 NGCoders. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
*/


class File {
    
    public static function read($path)
    {
        $fp=fopen($path,"rb");
        if (!$fp) return false;
        $data = false;
        if (filesize($path))
        {
            $data=fread($fp,filesize($path));
        } elseif (filesize($path) == 0)
        {
            return "";
        }
        
        fclose($fp);
        return $data;
    }

    public static function write($file,$data)
    {
        $fp=fopen($file,"w");
        if (!$fp) return false;
        fwrite($fp,$data,strlen($data));
        fclose($fp);
        return true;
    }
    
    public static function append($file,$data)
    {
        $fp=fopen($file,"a+");
        if (!$fp) return false;
        fwrite($fp,$data);
        fclose($fp);
        return true;
    }
    
    public static function exists($path)
    {
        // Remote file check
        if (strstr($path,'http://')) 
        {
        	
            
            return false;
        }
        
        if (is_file($path)) 
        {
          	return true;
        }  
        
        
    }
    
    public static function dir($dir,$type='both',$extra=false) {
        $info=array();
        $infod=array();
        $infof=array();

        $dh=opendir($dir);
        while ( $name = readdir( $dh )) {
            if( $name=="." || $name==".." ) continue;
            if ( is_dir( "$dir/$name" ) && ( $type=='dir' || $type=='both') ){
                if($extra) {
                    $infod[]=array('id'=>$name.'/',
                    'path'=>$dir.$name.'/',
                    'size'=>'NA',
                    'created'=>filectime("$dir/$name"),
                    'modified'=>filemtime("$dir/$name"),
                    'perms'=>fileperms("$dir/$name"),
                    'permissions'=>File::perms("$dir/$name")
                    );
                } else {
                    $infod[]=$name;
                }
            }
            if ( is_file( "$dir/$name" ) && ( $type=='file' || $type=='both')  ){
                if($extra) {
                    $infof[]=array('id'=>$name,
                    'path'=>$dir.$name,
                    'size'=>filesize($dir.'/'.$name),
                    'created'=>filectime("$dir/$name"),
                    'modified'=>filemtime("$dir/$name"),
                    'perms'=>fileperms("$dir/$name"),
                    'permissions'=>File::perms("$dir/$name")
                    );
                    
                } else {
                    $infof[]=$name;
                }
            }
        }
        closedir($dh);
        $info=array_merge($infod,$infof);
        return $info;
    }

    public static function rmdir($path)
    {

        if (substr($path, -1, 1) != "/") {
            $path .= "/";
        }
        foreach (glob($path . "*") as $file) {
            if (is_file($file) === TRUE)
            {
                unlink($file);
            }
            else if (is_dir($file) === TRUE)
            {
                remove_dir($file);
            }
        }
        if (is_dir($path) === TRUE)
        {
            rmdir($path);
        }
        return is_dir($path);
    }
    
    public static function upload($field = '', $dirPath = '', $maxSize = 10, $allowed = array())
    {

        $maxSize = 1024 * 1024 * $maxSize;

        foreach ($_FILES[$field] as $key => $val)
        $$key = $val;

        if ((!is_uploaded_file($tmp_name)) || ($error != 0) || ($size == 0) || ($size > $maxSize))
        {
            Session::setFlash(__('Error uploading file.'));
            return false;    // file failed basic validation checks
        }

        if ((is_array($allowed)) && (!empty($allowed)))
        if (!in_array($type, $allowed))
        {
            Session::setFlash(__('File type upload not allowed.'));
            return false;    // file is not an allowed type
        }

        do $path = $dirPath .((isset($path))?rand(1, 9999):''). strtolower(basename($name));
        while (file_exists($path));

        if (move_uploaded_file($tmp_name, $path))
        return $path;

        Session::setFlash(__('File upload could not be moved to destination.'));
        return false;
    }
    
    function perms($path)
    {
        $perms = fileperms($path);

        if (($perms & 0xC000) == 0xC000) {
            // Socket
            $info = 's';
        } elseif (($perms & 0xA000) == 0xA000) {
            // Symbolic Link
            $info = 'l';
        } elseif (($perms & 0x8000) == 0x8000) {
            // Regular
            $info = '-';
        } elseif (($perms & 0x6000) == 0x6000) {
            // Block special
            $info = 'b';
        } elseif (($perms & 0x4000) == 0x4000) {
            // Directory
            $info = 'd';
        } elseif (($perms & 0x2000) == 0x2000) {
            // Character special
            $info = 'c';
        } elseif (($perms & 0x1000) == 0x1000) {
            // FIFO pipe
            $info = 'p';
        } else {
            // Unknown
            $info = 'u';
        }

        // Owner
        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        $info .= (($perms & 0x0040) ?
        (($perms & 0x0800) ? 's' : 'x' ) :
        (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        $info .= (($perms & 0x0008) ?
        (($perms & 0x0400) ? 's' : 'x' ) :
        (($perms & 0x0400) ? 'S' : '-'));

        // World
        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        $info .= (($perms & 0x0001) ?
        (($perms & 0x0200) ? 't' : 'x' ) :
        (($perms & 0x0200) ? 'T' : '-'));

        return $info;
    }
    
}

?>