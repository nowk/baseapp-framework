<?php
/**
 * Short description for file.
 *
 * Long description for file.
 * 
 * @version     $Id: adodb.inc.php 7 2009-03-04 22:18:40Z vikaspatial1983 $
 * @package     BaseApp Framework (v0.1)
 * @link        http://code.google.com/p/baseappframework/
 * @copyright   Copyright (C) 2009 NGCoders. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
*/


class dbmysql {
    var $link = false;
    var $result = false;
    var $prefix = '';
    var $rowset = false;
	var $dbcoding=true;
    var $dbname = '';
	var $connect = false;

    /* PConnect make a new connection to db dummy; set the database name here */
    function Connect($db_host , $db_user , $db_pass , $db_name)
    {
        $this->dbname = $db_name;
        $this->link = mysql_connect($db_host, $db_user, $db_pass);
		if(!$this->link)return false;
        if(!mysql_select_db($this->dbname , $this->link))return false;
		$this->connect=true;
		return true;
    } 

    /* general function to execute sql-code */
   function Execute($sqlstring)
    {
        $this->result = new ADORecordSet();
        
        $this->result->result = mysql_query($sqlstring, $this->link);
        if ($this->result->result === false) {
            return false;
        } 
        
        return $this->result;
    }
	
    //return a single row 
    function GetRow($sql)
    {
        $this->SelectLimit($sql, 1);
        return $this->rowset[0];
    } 

    function SelectLimit($sql, $numrows = -1 , $offset = false)
    {
        if ($numrows >= 0 )$sql .= " LIMIT";
        if ($offset)$sql .= " $offset,";
        if ($numrows >= 0)$sql .= " $numrows";
        
        return $this->Execute($sql);
    } 
   // return autoincremented id 
    function Insert_ID()
    {
        return mysql_insert_id($this->link);
    } 
    /*return last error message */
    function ErrorMsg()
    {
        return mysql_error($this->link);
    } 

    /* return affected rows from update or delete */
    function Affected_Rows()
    {
        return mysql_affected_rows($this->link);
    } 

    /* return number of rows in recordset */
    function RowCount()
    {
    } 

    /* return array of table names in database */
    function MetaTables()
    {
        $result = mysql_list_tables($this->dbname, $this->link);
        $i = 0;
        $tb_names = false;
        while ($i < mysql_num_rows ($result)) {
            $tb_names[] = mysql_tablename ($result, $i);
            $i++;
        } 
        return $tb_names;
    } 

    /* return array of ADOFieldObjects, one object per table column */
    function MetaColumns($table, $upper = true)
    {
        //$fields = mysql_list_fields($this->dbname, $table, $this->link);
        $rs = $this->Execute("SHOW FIELDS FROM $table");
        
        if (!$rs) {
        	return false;
        }
        
        $sqlfields = $rs->GetArray();
        
        foreach ($sqlfields as $index=>$meta)
        {
            $field = new ADOFieldObject();
            $field->name = $meta['Field'];
            $colInfo = str_replace(array("(", ")"," "), array("|", "","|"),$meta['Field']);
            $infoArr = explode("|", $colInfo, 2);
            
            if (strstr($infoArr[0], "text")) {
                $field->type = $infoArr[0];
                $field->max_length = "";
            } else {
                $field->type = $infoArr[0];
                $field->max_length = isset($infoArr[1])?$infoArr[1]:0;
            } 
            
            if (strstr($meta['Null'],'NO')) {
            	$field->not_null = false;
            } else {
                $field->not_null = true;
            }
            
            if ($field->not_null) 
            {
            	$field->has_default = true;
            	$field->default_value = $meta['Default'];
            	
            }
            
             $cols[$field->name] = $field;
        }
             
        return $cols;
    } 

    /* return array of column names i table */
    function MetaColumnNames($table)
    {
        $fields = $this->MetaColumns($table);
        
        foreach ($fields as $field) {
            $cols[] = $field->name;
        } 
        
        return $cols;
    } 
	
} 

class ADORecordSet {
    
    var $result = false;
    
    /* return recordset array */
    function GetArray($num = 0)
    {      
        $trowset = false;
        

        if (!is_resource($this->result)) {
        	return $trowset;
        }
        
        while ($row = mysql_fetch_assoc ($this->result)) {
            $trowset[] = $row;
        }
        
        if ($num > 0) {
            return array_slice ($trowset, 0, $num);
        } 
            
        return $trowset;

    }

    
    function RecordCount()
    {
        if ($this->result && is_resource($this->result)) {
            return mysql_num_rows($this->result);
        } 
        
        return false;
         
    } 
    
    /* return number of fileds (columns) in recordset */
    function FieldCount()
    {
        if ($this->result && is_resource($this->result)) {
            return mysql_num_fields($this->result);
        } 
        
        return false; 
    } 
    
    
    
}

class ADOFieldObject {
    var $lm_name = '';
    var $max_length = 'n/a';
    var $type = "char";
    var $not_null = false;
    var $has_default = false;
    var $default_value = "";
} 

function &ADONewConnection($db = 'mysql')
{
    if  ($db == 'mysql') {
		$obj = new dbmysql();
	} else {
	    throw new Exception("Error : Invalid Database type ");
	}
		
    return $obj;
} 


?>