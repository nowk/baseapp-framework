<?php

/* Form helper */

class FormHelper extends AppHelper 
{
    var $base = false;
    var $model = false;
    var $modelObj = false;

    function create($model = null,$options = array())
    {
        $this->base = getInstance();

        if ($model)
        {
            $this->model    = $model;
            if (class_exists($model)) {
            	$this->modelObj = new $model();
            } else {
                $this->modelObj = new AppModel($model);
            }
        }

        $this->model       = isset($options['model'])?$options['model']:$this->model;
        $options['id']     = isset($options['id'])?$options['id']:$model.$this->base->actionName.'Form';
        $options['method'] = isset($options['method'])?$options['method']:'post';
        $options['action'] = isset($options['action'])?$options['action']:getURL($this->base->requestedURL);
        $options['class']  = isset($options['class'])?$options['class']:'content-form';
        if (isset($options['enctype'])) {
        	
        }
        
        $flash = '';
        if (!isset($options['flash']) || (isset($options['flash']) && $options['flash'])) {
        	$flash = Session::flash();
        	unset($options['flash']);
        }
        
        $buffer = '<form '.$this->_paramString($options).'>'.NL.$flash;
        
        return $buffer;

    }

    function end($submit = false)
    {
        $retString = '</form>';

        if ($submit)
        {
            $retString ='<div class="submit"><input type="submit" value="'.$submit.'" class="button" /></div>'.NL.$retString;
        }
        return $retString;
    }

    function input($field,$options = array())
    {
        // Get field Type by Model

        if (!isset($options['type']) && $this->modelObj)
        {
            if (isset($this->modelObj->metaColumns[strtoupper($field)])) {

                switch ($this->modelObj->metaColumns[strtoupper($field)]->type)
                {
                    case 'boolean':
                    case 'tinyint':
                        $options['type'] = 'checkbox';
                        break;

                    case 'date':
                    case 'time':
                    case 'datetime':

                    case 'int':
                    case 'char':
                    case 'varchar':
                        if ($field == 'password' || $field == 'passwd')
                        {
                            $options['type'] = 'password';
                        }
                        else
                        {
                            $options['type'] = 'text';
                        }
                        break;

                    case 'text':
                        $options['type'] = 'textarea';
                        break;

                }

            }

        }

        if (isset($options['options']))
        {
            $options['type'] = 'select';

            if (isset($options['multiple']))
            {

            }
        }
        
        if (strstr($field,'_id') && !isset($options['options']))
        {
            
            $fieldArray = strtolower(str_replace('_id','',$field));
            $fieldArray = Inflector::plural($fieldArray);
            
            $staticVar = "$this->model::$fieldArray";
            
            if (isset($this->modelObj->$fieldArray)) 
            {
                $options['options'] = $this->modelObj->$fieldArray;           	
            }    

            if (isset($options['options'])) $options['type'] = 'select';
                       	    	
        }
        
        $before = isset($options['before'])?$options['before']:'';
        $between = isset($options['between'])?$options['between']:'';
        $separator = isset($options['separator'])?$options['separator']:'';
        $after  = isset($options['after'])?$options['after']:'';

        unset($options['before']);
        unset($options['between']);
        unset($options['separator']);
        unset($options['after']);
        
        $input = $options;
                
        unset($input['div']);
        unset($input['label']);

        $input['type'] = isset($options['type'])?$options['type']:'text';
        $input['id']   = isset($options['id'])?$options['id']:$this->model.Inflector::camelize($field);
        $input['name'] = isset($options['name'])?$options['name']: (($this->model)?'data['.$this->model.']['.$field.']':'data['.$field.']');
        
        $input['value'] = isset($options['default'])?$options['default']:'';
        
                 
        if (isset($this->base->Controller->data[$this->model][$field])) 
        {
            $input['value'] = $this->base->Controller->data[$this->model][$field];
        }
        
                
        $input['selected'] = isset($options['selected'])?$options['selected']:'';
        $input['maxLength'] = isset($options['maxLength'])?$options['maxLength']:'';
        $input['options'] = isset($options['options'])?$options['options']:false;
        $input['class'] = isset($options['class'])?$options['class']:'text';

        $input['rows'] = isset($options['rows'])?$options['rows']:'';
        $input['cols'] = isset($options['cols'])?$options['cols']:'';


        if (isset($options['div']))
        {
            
            if (is_array($options['div']))
            {
                $div = $options['div'];
                if (!isset($div['class']))
                {
                    $div['class'] = 'input';
                }
            }
            else if ($options['div'])
            {
                $div['class'] = $options['div'];
            }
            else
            {
                $div = false;
            }
        }
        else
        {
            $div['class'] = 'input';
        }
        
        // check if any error on the field ?? 
        
        if (isset($this->base->Controller->Validation)) 
        {
            	if (isset($this->base->Controller->Validation->{$field.'_error'}) && !empty($this->base->Controller->Validation->{$field.'_error'})) 
            	{
            	    $div['class'] .= ' invalid';
            	}
        }

        $label['for'] = $input['id'];

        if (isset($options['label']))
        {
            if (is_array($options['label']))
            {
                $label = $options['label'];
                if (!isset($label['text'])) {
                	$label['text'] = Inflector::humanize(Inflector::underscore($field));
                }
            }
            else if ($options['label'])
            {
                $label['text'] = $options['label'];
            }
            else
            {
                $label = false;
            }
        }
        else
        {
            $label['text'] = Inflector::humanize(Inflector::underscore($field));
        }
        
        $params = $label;
        unset($params['text']);
        $labelString = ($label)?'<label '.$this->_paramString($params).'>'.$label['text'].'</label>':'';

        $params = $div;
        $divString = ($div)?'<div '.$this->_paramString($params).' >'.NL.'%s'.NL.'</div>':'%s';
        
        
        $retString = '';
              
        switch ($input['type'])
        {
            case 'password':
                unset($input['value']);
            case 'file':                
            case 'checkbox':          
            case 'text':
                if ($input['type'] == 'checkbox') $input['class'] = 'checkbox';
                $retString = '<input '.$this->_paramString($input).' >';
                break;
            case 'hidden':
                return '<input '.$this->_paramString($input).' >';
                break;
                   
            case 'select':

                $params = $input;

                unset($params['selected']);
                unset($params['type']);
                unset($params['value']);
                unset($params['options']);

                $retString = '<select '.$this->_paramString($params).' >';

                foreach ($input['options'] as $key=>$val)
                {
                    if (empty($key) && empty($val)) continue;
                    $retString .='<option value="'.$key.'" '.(($key == $input['value'])?'selected = "true"':'').' >'.$val.'</option>';
                }
                
                $retString .= '</select>';

                break;
            case 'htmlarea':
                $input['class'] = $input['class'].' htmlarea';
                if(!defined('WYSIWYG')) define('WYSIWYG',1);
                $input['type'] = 'textarea';
            case 'textarea':

                $params = $input;
                unset($params['default']);
                unset($params['value']);

                $retString = '<textarea '.$this->_paramString($params).' >'.html_encode($input['value']).'</textarea>';
                break;
                
            case 'content':       
            
                $retString = $input['value'];
                break;
        }
        
        $retString = sprintf($divString,$before.$labelString.$between.$retString.$after);

        return $retString;
    }
        
    function _paramString($options = array())
    {
        $params = "";

        foreach ($options as $var => $val)
        {
            if ($val === false) {
                continue;
            }
            $params .= "$var=\"$val\" ";
        }

        return $params;
    }
              
    function iconButton($field,$options = array())
    {       
        // Base on field type decide button 
        
        $parse = explode('_',$field);
        
        $state   = 'positive';
        $icon    = $parse[0].'.png';
        $title   = ucwords(str_replace('_',' ',$field));
        $action  = false;
        $label   = false;
                 
        switch ($parse[0])
        {
            case 'create':
                $icon = 'add.png';
                break;
                
            case 'add':
            case 'save':
            case 'search':    
                break;                
            
            case 'delete':
                    $state = 'negative';
                    break;                
            case 'cancel':
                
                    $state = 'negative';
                    $action = (defined('ADMIN_MODE')?BASEAPP_ADMIN.'/':'').getInstance()->controllerName;
                    break;
                    
            default:
                    $state = 'neutral';
                    $icon  = $icon;
                    break;                    
                                        
        }
        
        $class   = 'button '.$state;
                
        extract($options);
        
        
        $iconURL = WEBROOT_URL.'icons/'.$icon;
        $action  = isset($options['action'])?$options['action']:$action;
                
        if (!$action) {
        	$button =  '<button type="submit" class="'.$class.'"><img src="'.$iconURL.'" alt="'.$title.'"/> '.$title.'</button>';
        } else {
            $button = getURL($action,$title,$iconURL,false,$class);
        }
          
        
        $noptions['default'] = $button;
        $noptions['label']   = isset($options['label'])?$options['label']:false;
        $noptions['div']     = isset($options['div'])?$options['div']:array('class'=>'input buttons');
                
        return $this->content(substr(md5(time()),0,10),$noptions);        
    }
    
    function iconButtons($buttonsArray , $options = array())
    {        
        $buttons = "";
        
        foreach ($buttonsArray as $button)
        {
            if (isset($button[1]) && is_string($button[1])) {
                $action = $button[1];
                $button[1] = array();
            	$button[1]['action'] = $action;
            }
            
            $button[1]['div'] = isset($button[1]['div'])?$button[1]['div']:false;
            
            $buttons .= $this->iconButton($button[0],isset($button[1])?$button[1]:array());
        }
        
        $options['default'] = $buttons;
        $options['label']  = isset($options['label'])?$options['label']:false;
        $options['div']    = isset($options['div'])?$options['div']:array('class'=>'input buttons');
                
        return $this->content(substr(md5(time()),0,10),$options);
    }
    
    function __call($method,$params)
    {
        // send to form
        $options['type'] = $method;

        if (isset($params[1]) && count($params[1])) 
        {
            $options = array_merge($options,$params[1]);       	
        }       
        
        return $this->input($params[0],$options);
        
    }

}