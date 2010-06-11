<?php  
/**
 * Short description for file.
 *
 * Long description for file.
 * 
 * @version     $Id: validation.php 7 2009-03-04 22:18:40Z vikaspatial1983 $
 * @package     BaseApp Framework (v0.1)
 * @link        http://code.google.com/p/baseappframework/
 * @copyright   Copyright (C) 2009 NGCoders. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
*/

function line($key)
{

	$lang['required'] 		= "The %s field is required.";
	$lang['isset']			= "The %s field must have a value.";
	$lang['notnull']		= "The %s field must have a value.";
	$lang['valid_email']	= "The %s field must contain a valid email address.";
	$lang['valid_url'] 		= "The %s field must contain a valid URL.";
	$lang['valid_ip'] 		= "The %s field must contain a valid IP.";
	$lang['min_length']		= "The %s field must be at least %s characters in length.";
	$lang['max_length']		= "The %s field can not exceed %s characters in length.";
	$lang['exact_length']	= "The %s field must be exactly %s characters in length.";
	$lang['alpha']			= "The %s field may only contain alphabetical characters.";
	$lang['alpha_numeric']	= "The %s field may only contain alpha-numeric characters.";
	$lang['alpha_dash']		= "The %s field may only contain alpha-numeric characters, underscores, and dashes.";
	$lang['numeric']		= "The %s field must contain a number.";
	$lang['integer']		= "The %s field must contain an integer.";
	$lang['matches']		= "The %s field does not match the %s field.";
	$lang['unique']		    = "The %s field is already taken,please select another one.";

	if (isset($lang[$key])) {
		return $lang[$key];		
	} 
	
	return false;
	
}

/**
 * Validation Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Validation
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/validation.html
 */
class Validation {
	
	var $CI;
	var $error_string		= '';
	var $_error_array		= array();
	var $_rules				= array();
	var $_fields			= array();
	var $_error_messages	= array();
	var $_current_field  	= '';
	var $_safe_form_data 	= FALSE;
	var $_error_prefix		= '<p>';
	var $_error_suffix		= '</p>';
	
	var $_data = array();
	var $_object = false;

	
    var $never_allowed_str = array(
                                    'document.cookie'    => '[removed]',
                                    'document.write'    => '[removed]',
                                    '.parentNode'        => '[removed]',
                                    '.innerHTML'        => '[removed]',
                                    'window.location'    => '[removed]',
                                    '-moz-binding'        => '[removed]',
                                    '<!--'                => '&lt;!--',
                                    '-->'                => '--&gt;',
                                    '<![CDATA['            => '&lt;![CDATA['
                                    );
    /* never allowed, regex replacement */
    var $never_allowed_regex = array(
                                        "javascript\s*:"    => '[removed]',
                                        "expression\s*\("    => '[removed]', // CSS and IE
                                        "Redirect\s+302"    => '[removed]'
                                    );
                                    

	/**
	 * Constructor
	 *
	 */	
	function Validation($object = false,$data = false)
	{	
        $this->init($object,$data);
	}
	
	function init($object = false,$data = false)
	{
	    $this->reset();
	    if ($object) {
	    	$this->_object = $object;
	    } else {
	        $base = getInstance();
	        $this->_object = $base->Controller;
	    }
	    
		if ($data) {
			$this->_data = $data;
		} else {
		    $this->_data = $_POST;
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set Fields
	 *
	 * This function takes an array of field names as input
	 * and generates class variables with the same name, which will
	 * either be blank or contain the $_POST value corresponding to it
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	function setFields($data = '', $field = '')
	{	
		if ($data == '')
		{
			if (count($this->_fields) == 0)
			{
				return FALSE;
			}
		}
		else
		{
			if ( ! is_array($data))
			{
				$data = array($data => $field);
			}
			
			if (count($data) > 0)
			{
				$this->_fields = $data;
			}
		}		
			
		foreach($this->_fields as $key => $val)
		{
			$this->$key = ( ! isset($this->_data[$key])) ? '' : $this->prep_for_form($this->_data[$key]);
			
			$error = $key.'_error';
			if ( ! isset($this->$error))
			{
				$this->$error = '';
			}
		}		
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set Rules
	 *
	 * This function takes an array of field names and validation
	 * rules as input ad simply stores is for use later.
	 *
	 * @access	public
	 * @param	mixed
	 * @param	string
	 * @return	void
	 */
	function setRules($data, $rules = '')
	{
		if ( ! is_array($data))
		{
			if ($rules == '')
				return;
				
			$data = array($data => $rules);
		}
	
		foreach ($data as $key => $val)
		{
			$this->_rules[$key] = $val;
		}
	}
	/**
	 * Reset
	 *
	 * Reset all rule set previously
	 */
	function reset()
	{
	    $this->_rules = array();
	    $this->_fields = array();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set Error Message
	 *
	 * Lets users set their own error messages on the fly.  Note:  The key
	 * name has to match the  function name that it corresponds to.
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	function set_message($lang, $val = '')
	{
		if ( ! is_array($lang))
		{
			$lang = array($lang => $val);
		}
	
		$this->_error_messages = array_merge($this->_error_messages, $lang);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set The Error Delimiter
	 *
	 * Permits a prefix/suffix to be added to each error message
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */	
	function set_error_delimiters($prefix = '<p>', $suffix = '</p>')
	{
		$this->_error_prefix = $prefix;
		$this->_error_suffix = $suffix;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Run the Validator
	 *
	 * This function does all the work.
	 *
	 * @access	public
	 * @return	bool
	 */		
	function run()
	{    
		// Do we even have any data to process?  Mm?
				
		if (count($this->_data) == 0 || count($this->_rules) == 0 )
		{
			return FALSE;
		}
	
						
		// Cycle through the rules and test for errors
		foreach ($this->_rules as $field => $rules)
		{
			//Explode out the rules!
			$ex = explode('|', $rules);
			
			// Is the field required?  If not, if the field is blank  we'll move on to the next test
			if ( ! in_array('required', $ex, TRUE))
			{
				if ( ! isset($this->_data[$field]) )
				{
					continue;
				}
			}
			
			/*
			 * Are we dealing with an "isset" rule?
			 *
			 * Before going further, we'll see if one of the rules
			 * is to check whether the item is set (typically this
			 * applies only to checkboxes).  If so, we'll
			 * test for it here since there's not reason to go
			 * further
			 */
			if ( ! isset($this->_data[$field]))
			{			
				if (in_array('isset', $ex, TRUE) OR in_array('required', $ex))
				{

					$line = 'The %s field was not set';

					// Build the error message
					$mfield = ( ! isset($this->_fields[$field])) ? $field : $this->_fields[$field];
					$message = sprintf($line, $mfield);

					// Set the error variable.  Example: $this->username_error
					$error = $field.'_error';
					$this->$error = $this->_error_prefix.$message.$this->_error_suffix;
					$this->_error_array[] = $message;
				}
						
				continue;
			}
			
			
			/*
			 * Set the current field
			 *
			 * The various prepping functions need to know the
			 * current field name so they can do this:
			 *
			 * $_POST[$this->_current_field] == 'bla bla';
			 */
			$this->_current_field = $field;

			// Cycle through the rules!
			foreach ($ex As $rule)
			{
				// Is the rule a callback?			
				$callback = FALSE;
				if (substr($rule, 0, 9) == 'callback_')
				{
					$rule = substr($rule, 9);
					$callback = TRUE;
				}
				
				// Strip the parameter (if exists) from the rule
				// Rules can contain a parameter: max_length[5]
				$param = FALSE;
				if (preg_match("/(.*?)\[(.*?)\]/", $rule, $match))
				{
					$rule	= $match[1];
					$param	= $match[2];
				}
				
				// Call the function that corresponds to the rule
				if ($callback === TRUE)
				{
										
					if ( ! method_exists($this->_object, $rule))
					{ 		
						continue;
					}
					
					$result = $this->_object->$rule($this->_data[$field], $param);	
					
					// If the field isn't required and we just processed a callback we'll move on...
					if ( ! in_array('required', $ex, TRUE) AND $result !== FALSE)
					{
						continue 2;
					}
					
				}
				else
				{				
					if ( ! method_exists($this, $rule))
					{
						/*
						 * Run the native PHP function if called for
						 *
						 * If our own wrapper function doesn't exist we see
						 * if a native PHP function does. Users can use
						 * any native PHP function call that has one param.
						 */
						if (function_exists($rule))
						{
							$this->_data[$field] = $rule($this->_data[$field]);
							$this->$field = $this->_data[$field];
						}
											
						continue;
					}
					
					$result = $this->$rule($this->_data[$field], $param);
				}
								
				// Did the rule test negatively?  If so, grab the error.
				if ($result === FALSE)
				{

					if ( ! isset($this->_error_messages[$rule]))
					{
						if (FALSE === ($line = line($rule)))
						{
							$line = 'Unable to access an error message corresponding to your field name.';
						}						
					}
					else
					{
						$line = $this->_error_messages[$rule];
					}	
								
					
					// Build the error message
					$mfield = ( ! isset($this->_fields[$field])) ? $field : $this->_fields[$field];
					$mparam = ( ! isset($this->_fields[$param])) ? $param : $this->_fields[$param];
					$message = sprintf($line, $mfield, $mparam);
					
					// Set the error variable.  Example: $this->username_error
					$error = $field.'_error';
					$this->$error = $this->_error_prefix.$message.$this->_error_suffix;

					// Add the error to the error array
					$this->_error_array[] = $message;				
					continue 2;
				}				
			}
			
		}
		
		
		$total_errors = count($this->_error_array);

		/*
		 * Recompile the class variables
		 *
		 * If any prepping functions were called the $_POST data
		 * might now be different then the corresponding class
		 * variables so we'll set them anew.
		 */	
		if ($total_errors > 0)
		{
			$this->_safe_form_data = TRUE;
		}
		
		$this->setFields();

		// Did we end up with any errors?
		if ($total_errors == 0)
		{
			return TRUE;
		}
		
		
		foreach ($this->_error_array as $val)
		{
		    Session::setFlash($val,B_ERROR);
			$this->error_string .= $this->_error_prefix.$val.$this->_error_suffix."\n";	
		}
		
		// set errors in controller 
		
		
		return FALSE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Required
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function required($str)
	{
		if ( ! is_array($str))
		{
			return (trim($str) == '') ? FALSE : TRUE;
		}
		else
		{
			return ( ! empty($str));
		}
	}
	
	// --------------------------------------------------------------------
	
	
	/**
	 * 
	 */
	
	function unique($str,$model = false)
	{
	    // Let do a query on the model
	    
	    if ($model) {
	    	uses($model);
	    	$modelObj = getInstance()->Controller->$model;
	    } else {
	        $modelObj = $this->_object;
	    }
	    
	    
	    $conditions[$modelObj->name.'.'.$this->_current_field] = $str;
	    
	    if (isset($this->_data[$modelObj->primaryKey]) && $this->_data[$modelObj->primaryKey]) {
	    	$conditions[$modelObj->name.'.'.$modelObj->primaryKey.' <>'] = $this->_data[$modelObj->primaryKey];
	    }
	    	    
	    $result = $modelObj->find('first',array('conditions'=>$conditions));
	    	    
	    if ($result) {
	    	return false;
	    }
	    
	    return true;
	}
	
	/**
	 * Match one field to another
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function matches($str, $field)
	{
		if ( ! isset($this->_data[$field]))
		{
			return FALSE;
		}
		
		return ($str !== $this->_data[$field]) ? FALSE : TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Minimum Length
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function min_length($str, $val)
	{
		if (preg_match("/[^0-9]/", $val))
		{
			return FALSE;
		}
	
		return (strlen($str) < $val) ? FALSE : TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Max Length
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function max_length($str, $val)
	{
		if (preg_match("/[^0-9]/", $val))
		{
			return FALSE;
		}
	
		return (strlen($str) > $val) ? FALSE : TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Exact Length
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function exact_length($str, $val)
	{
		if (preg_match("/[^0-9]/", $val))
		{
			return FALSE;
		}
	
		return (strlen($str) != $val) ? FALSE : TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Valid Email
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function valid_email($str)
	{
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Valid Emails
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function valid_emails($str)
	{
		if (strpos($str, ',') === FALSE)
		{
			return $this->valid_email(trim($str));
		}
		
		foreach(explode(',', $str) as $email)
		{
			if (trim($email) != '' && $this->valid_email(trim($email)) === FALSE)
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Validate IP Address
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function valid_ip($ip)
	{
		return $this->CI->input->valid_ip($ip);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Alpha
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */		
	function alpha($str)
	{
		return ( ! preg_match("/^([a-z])+$/i", $str)) ? FALSE : TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Alpha-numeric
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function alpha_numeric($str)
	{
		return ( ! preg_match("/^([a-z0-9])+$/i", $str)) ? FALSE : TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Alpha-numeric with underscores and dashes
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function alpha_dash($str)
	{
		return ( ! preg_match("/^([-a-z0-9_-])+$/i", $str)) ? FALSE : TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Numeric
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function numeric($str)
	{
		return (bool)preg_match( '/^[\-+]?[0-9]*\.?[0-9]+$/', $str);

	}
	
	/**
	 * NotNull
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function notnull($str)
	{
		return ( ! strlen($str)) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

    /**
     * Is Numeric
     *
     * @access    public
     * @param    string
     * @return    bool
     */
    function is_numeric($str)
    {
        return ( ! is_numeric($str)) ? FALSE : TRUE;
    } 

	// --------------------------------------------------------------------
	
	/**
	 * Integer
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function integer($str)
	{
		return (bool)preg_match( '/^[\-+]?[0-9]+$/', $str);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Valid Base64
	 *
	 * Tests a string for characters outside of the Base64 alphabet
	 * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function valid_base64($str)
	{
		return (bool) ! preg_match('/[^a-zA-Z0-9\/\+=]/', $str);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Set Select
	 *
	 * Enables pull-down lists to be set to the value the user
	 * selected in the event of an error
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */	
	function set_select($field = '', $value = '')
	{
		if ($field == '' OR $value == '' OR  ! isset($this->_data[$field]))
		{
			return '';
		}
			
		if ($this->_data[$field] == $value)
		{
			return ' selected="selected"';
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set Radio
	 *
	 * Enables radio buttons to be set to the value the user
	 * selected in the event of an error
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */	
	function set_radio($field = '', $value = '')
	{
		if ($field == '' OR $value == '' OR  ! isset($this->_data[$field]))
		{
			return '';
		}
			
		if ($this->_data[$field] == $value)
		{
			return ' checked="checked"';
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set Checkbox
	 *
	 * Enables checkboxes to be set to the value the user
	 * selected in the event of an error
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */	
	function set_checkbox($field = '', $value = '')
	{
		if ($field == '' OR $value == '' OR  ! isset($this->_data[$field]))
		{
			return '';
		}
			
		if ($this->_data[$field] == $value)
		{
			return ' checked="checked"';
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Prep data for form
	 *
	 * This function allows HTML to be safely shown in a form.
	 * Special characters are converted.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function prep_for_form($data = '')
	{
		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				$data[$key] = $this->prep_for_form($val);
			}
			
			return $data;
		}
		
		if ($this->_safe_form_data == FALSE OR $data == '')
		{
			return $data;
		}

		return str_replace(array("'", '"', '<', '>'), array("&#39;", "&quot;", '&lt;', '&gt;'), stripslashes($data));
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Prep URL
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function prep_url($str = '')
	{
		if ($str == 'http://' OR $str == '')
		{
			$this->_data[$this->_current_field] = '';
			return;
		}
		
		if (substr($str, 0, 7) != 'http://' && substr($str, 0, 8) != 'https://')
		{
			$str = 'http://'.$str;
		}
		
		$this->_data[$this->_current_field] = $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Strip Image Tags
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function strip_image_tags($str)
	{
		$this->_data[$this->_current_field] = $this->CI->input->strip_image_tags($str);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * XSS Clean
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function xss_clean($str)
	{
		$this->_data[$this->_current_field] = $this->_xss_clean($str);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Convert PHP tags to entities
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function encode_php_tags($str)
	{
		$this->_data[$this->_current_field] = str_replace(array('<?php', '<?PHP', '<?', '?>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
	}
    
        /**
     * XSS Clean
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented.  This function does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts.  Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything passed
     * the filter.
     *
     * Note: This function should only be used to deal with data
     * upon submission.  It's not something that should
     * be used for general runtime processing.
     *
     * This function was based in part on some code and ideas I
     * got from Bitflux: http://blog.bitflux.ch/wiki/XSS_Prevention
     *
     * To help develop this script I used this great list of
     * vulnerabilities along with a few other hacks I've
     * harvested from examining vulnerabilities in other programs:
     * http://ha.ckers.org/xss.html
     *
     * @access    public
     * @param    string
     * @return    string
     */
    function _xss_clean($str)
    {
        /*
         * Is the string an array?
         *
         */
        if (is_array($str))
        {
            while (list($key) = each($str))
            {
                $str[$key] = $this->_xss_clean($str[$key]);
            }
    
            return $str;
        }

        /*
         * Remove Null Characters
         *
         * This prevents sandwiching null characters
         * between ascii characters, like Java\0script.
         *
         */
        $str = preg_replace('/\0+/', '', $str);
        $str = preg_replace('/(\\\\0)+/', '', $str);

        /*
         * Protect GET variables in URLs
         */
         
         // 901119URL5918AMP18930PROTECT8198
         
        $str = preg_replace('|\&([a-z\_0-9]+)\=([a-z\_0-9]+)|i', $this->xss_hash()."\\1=\\2", $str);

        /*
         * Validate standard character entities
         *
         * Add a semicolon if missing.  We do this to enable
         * the conversion of entities to ASCII later.
         *
         */
        $str = preg_replace('#(&\#?[0-9a-z]+)[\x00-\x20]*;?#i', "\\1;", $str);

        /*
         * Validate UTF16 two byte encoding (x00) 
         *
         * Just as above, adds a semicolon if missing.
         *
         */
        $str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);

        /*
         * Un-Protect GET variables in URLs
         */
         
        $str = str_replace($this->xss_hash(), '&', $str);

        /*
         * URL Decode
         *
         * Just in case stuff like this is submitted:
         *
         * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
         *
         * Note: Use rawurldecode() so it does not remove plus signs
         *
         */
        $str = rawurldecode($str);

        /*
         * Convert character entities to ASCII 
         *
         * This permits our tests below to work reliably.
         * We only convert entities that are within tags since
         * these are the ones that will pose security problems.
         *
         */

        $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_attribute_conversion'), $str);
         
        $str = preg_replace_callback("/<([\w]+)[^>]*>/si", array($this, '_html_entity_decode_callback'), $str);

        /*
         * Convert all tabs to spaces
         *
         * This prevents strings like this: ja    vascript
         * NOTE: we deal with spaces between characters later.
         * NOTE: preg_replace was found to be amazingly slow here on large blocks of data,
         * so we use str_replace.
         *
         */
         
        
         if (strpos($str, "\t") !== FALSE)
        {
            $str = str_replace("\t", ' ', $str);
        }        

        /*
         * Not Allowed Under Any Conditions
         */
        
        foreach ($this->never_allowed_str as $key => $val)
        {
            $str = str_replace($key, $val, $str);   
        }
    
        foreach ($this->never_allowed_regex as $key => $val)
        {
            $str = preg_replace("#".$key."#i", $val, $str);   
        }

        /*
         * Makes PHP tags safe
         *
         *  Note: XML tags are inadvertently replaced too:
         *
         *    <?xml
         *
         * But it doesn't seem to pose a problem.
         *
         */
        $str = str_replace(array('<?php', '<?PHP', '<?', '?'.'>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);

        /*
         * Compact any exploded words
         *
         * This corrects words like:  j a v a s c r i p t
         * These words are compacted back to their correct state.
         *
         */
        $words = array('javascript', 'expression', 'vbscript', 'script', 'applet', 'alert', 'document', 'write', 'cookie', 'window');
        foreach ($words as $word)
        {
            $temp = '';
            for ($i = 0; $i < strlen($word); $i++)
            {
                $temp .= substr($word, $i, 1)."\s*";
            }
    
            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $str = preg_replace('#('.substr($temp, 0, -3).')(\W)#ise', "preg_replace('/\s+/s', '', '\\1').'\\2'", $str);
        }

        /*
         * Remove disallowed Javascript in links or img tags
         */
        do
        {
            $original = $str;
    
            if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && stripos($str, '</a>') !== FALSE) OR 
                 preg_match("/<\/a>/i", $str))
            {
                $str = preg_replace_callback("#<a.*?</a>#si", array($this, '_js_link_removal'), $str);
            }
    
            if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && stripos($str, '<img') !== FALSE) OR 
                 preg_match("/img/i", $str))
            {
                $str = preg_replace_callback("#<img.*?".">#si", array($this, '_js_img_removal'), $str);
            }
    
            if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && (stripos($str, 'script') !== FALSE OR stripos($str, 'xss') !== FALSE)) OR
                 preg_match("/(script|xss)/i", $str))
            {
                $str = preg_replace("#</*(script|xss).*?\>#si", "", $str);
            }
        }
        while($original != $str);

        unset($original);

        /*
         * Remove JavaScript Event Handlers
         *
         * Note: This code is a little blunt.  It removes
         * the event handler and anything up to the closing >,
         * but it's unlikely to be a problem.
         *
         */
        $event_handlers = array('onblur','onchange','onclick','onfocus','onload','onmouseover','onmouseup','onmousedown','onselect','onsubmit','onunload','onkeypress','onkeydown','onkeyup','onresize', 'xmlns');
        $str = preg_replace("#<([^>]+)(".implode('|', $event_handlers).")([^>]*)>#iU", "&lt;\\1\\2\\3&gt;", $str);

        /*
         * Sanitize naughty HTML elements
         *
         * If a tag containing any of the words in the list
         * below is found, the tag gets converted to entities.
         *
         * So this: <blink>
         * Becomes: &lt;blink&gt;
         *
         */
        $naughty = 'alert|applet|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|layer|link|meta|object|plaintext|style|script|textarea|title|xml|xss';
        $str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array($this, '_sanitize_naughty_html'), $str);

        /*
         * Sanitize naughty scripting elements
         *
         * Similar to above, only instead of looking for
         * tags it looks for PHP and JavaScript commands
         * that are disallowed.  Rather than removing the
         * code, it simply converts the parenthesis to entities
         * rendering the code un-executable.
         *
         * For example:    eval('some code')
         * Becomes:        eval&#40;'some code'&#41;
         *
         */
        $str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);
                    
        /*
         * Final clean up
         *
         * This adds a bit of extra precaution in case
         * something got through the above filters
         *
         */
        foreach ($this->never_allowed_str as $key => $val)
        {
            $str = str_replace($key, $val, $str);   
        }
    
        foreach ($this->never_allowed_regex as $key => $val)
        {
            $str = preg_replace("#".$key."#i", $val, $str);   
        } 
                 
        return $str;
    }

               /**
     * Random Hash for protecting URLs
     *
     * @access    public
     * @return    string
     */
    function xss_hash()
    {
        if (!isset($this->xss_hash) || $this->xss_hash == '')
        {
            if (phpversion() >= 4.2)
                mt_srand();
            else
                mt_srand(hexdec(substr(md5(microtime()), -8)) & 0x7fffffff);

            $this->xss_hash = md5(time() + mt_rand(0, 1999999999));
        }

        return $this->xss_hash;
    }

    // --------------------------------------------------------------------
    
    /**
     * Sanitize Naughty HTML
     *
     * Callback function for xss_clean() to remove naughty HTML elements
     *
     * @access    private
     * @param    array
     * @return    string
     */
    function _sanitize_naughty_html($matches)
    {
        // encode opening brace
        $str = '&lt;'.$matches[1].$matches[2].$matches[3];
        
        // encode captured opening or closing brace to prevent recursive vectors
        if ($matches[4] == '>')
        {
            $str .= '&gt;';
        }
        elseif ($matches[4] == '<')
        {
            $str .= '&lt;';
        }

        return $str;
    }

    // --------------------------------------------------------------------
    
    /**
     * JS Link Removal
     *
     * Callback function for xss_clean() to sanitize links
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings
     *
     * @access    private
     * @param    array
     * @return    string
     */
    function _js_link_removal($match)
    {
        return preg_replace("#<a.+?href=.*?(alert\(|alert&\#40;|javascript\:|window\.|document\.|\.cookie|<script|<xss).*?\>.*?</a>#si", "", $match[0]);
    }

    /**
     * JS Image Removal
     *
     * Callback function for xss_clean() to sanitize image tags
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings
     *
     * @access    private
     * @param    array
     * @return    string
     */
    function _js_img_removal($match)
    {
        return preg_replace("#<img.+?src=.*?(alert\(|alert&\#40;|javascript\:|window\.|document\.|\.cookie|<script|<xss).*?\>#si", "", $match[0]);
    }

    // --------------------------------------------------------------------

    /**
     * Attribute Conversion
     *
     * Used as a callback for XSS Clean
     *
     * @access    public
     * @param    array
     * @return    string
     */
    function _attribute_conversion($match)
    {
        return str_replace('>', '&lt;', $match[0]);
    }

    // --------------------------------------------------------------------

    /**
     * HTML Entity Decode Callback
     *
     * Used as a callback for XSS Clean
     *
     * @access    public
     * @param    array
     * @return    string
     */
    function _html_entity_decode_callback($match)
    {
        return $this->_html_entity_decode($match[0]);
    }

    // --------------------------------------------------------------------

    /**
     * HTML Entities Decode
     *
     * This function is a replacement for html_entity_decode()
     *
     * In some versions of PHP the native function does not work
     * when UTF-8 is the specified character set, so this gives us
     * a work-around.  More info here:
     * http://bugs.php.net/bug.php?id=25670
     *
     * @access    private
     * @param    string
     * @param    string
     * @return    string
     */
    /* -------------------------------------------------
    /*  Replacement for html_entity_decode()
    /* -------------------------------------------------*/

    /*
    NOTE: html_entity_decode() has a bug in some PHP versions when UTF-8 is the
    character set, and the PHP developers said they were not back porting the
    fix to versions other than PHP 5.x.
    */
    function _html_entity_decode($str, $charset='UTF-8')
    {
        if (stristr($str, '&') === FALSE) return $str;

        // The reason we are not using html_entity_decode() by itself is because
        // while it is not technically correct to leave out the semicolon
        // at the end of an entity most browsers will still interpret the entity
        // correctly.  html_entity_decode() does not convert entities without
        // semicolons, so we are left with our own little solution here. Bummer.

        if (function_exists('html_entity_decode') && (strtolower($charset) != 'utf-8' OR version_compare(phpversion(), '5.0.0', '>=')))
        {
            $str = html_entity_decode($str, ENT_COMPAT, $charset);
            $str = preg_replace('~&#x([0-9a-f]{2,5})~ei', 'chr(hexdec("\\1"))', $str);
            return preg_replace('~&#([0-9]{2,4})~e', 'chr(\\1)', $str);
        }

        // Numeric Entities
        $str = preg_replace('~&#x([0-9a-f]{2,5});{0,1}~ei', 'chr(hexdec("\\1"))', $str);
        $str = preg_replace('~&#([0-9]{2,4});{0,1}~e', 'chr(\\1)', $str);

        // Literal Entities - Slightly slow so we do another check
        if (stristr($str, '&') === FALSE)
        {
            $str = strtr($str, array_flip(get_html_translation_table(HTML_ENTITIES)));
        }

        return $str;
    }
}
// END Validation Class
