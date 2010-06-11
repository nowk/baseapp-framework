<?php  
/**
 * Mail class for sending E-Mails
 *
 * A tiny mailing class will use php mailer if it exists.
 * 
 * @version     $Id: validation.php 7 2009-03-04 22:18:40Z vikaspatial1983 $
 * @package     BaseApp Framework (v0.1)
 * @link        http://code.google.com/p/baseappframework/
 * @copyright   Copyright (C) 2009 NGCoders. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
*/

$phpmailer = BASEAPP_PATH.'libraries/phpmailer/class.phpmailer.php';

if (file_exists($phpmailer)) {
    include($phpmailer);
    
    class Mail extends phpmailer {
        
    }
} else {
    
    /* Use default php mail function in simple mailing class*/
    
    class Mail 
    {
        public $From = "";    
        public $FromName = "";
        
        public $Subject = "";
        public $Body = "";
        
        private $addresses = array();        
        
        /**
         * Add addresses to send email to
         *
         * @param string $email Email to which to send the email
         * @param string $name Name of the Email owner
         */
        public function AddAddress($email,$name = "")
        {
            $this->addresses[$email] = $name;
        }
        
        /**
         * Sends out the email
         *
         * @return boolean Returns true if file is sent else returns false
         */
        public function Send()
        {
            $headers = "";
            
            $to       = "";
            $toString = "";
            
            foreach ($this->addresses as $email=>$name)            
            {
                $toString .=(empty($toString))?'':', ';
                $to       .=(empty($to))?'':', ';
                
                $toString .= "$name <$email>";    
                $to       .= "$email";            
            }
            
            if (empty($this->FromName)) {
            	$this->FromName = $this->From;
            }
                        
            // Additional headers
            $headers .= "To: $toString \r\n";
            $headers .= 'From: $this->FromName <$this->From>' . "\r\n";

            // Mail it
            return mail($to, $this->Subject, $this->Body, $headers);
            
        }
        
        /**
         * Clears addresses stored int the class
         *
         */
        
        public function ClearAddresses()
        {
            $this->addresses = array();
        }
                
    }
       
}
