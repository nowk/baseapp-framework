<?php

# PHP-ActiveRecord
# http://github.com/kla/php-activerecord
require CORE_PATH.'vendor/plugins/php-activerecord/ActiveRecord.php';

# include the config
if ($php_activerecord_config = Configure::read('php_activerecord')) {
  if (file_exists($php_activerecord_config)) {
    require $php_activerecord_config;
  }
  else {
    Debugger::errorHandler(2, "The {$php_activerecord_config} configuration not found.", __FILE__, __LINE__);
  }
}
