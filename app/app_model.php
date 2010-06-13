<?php

# PHP-ActiveRecord
# http://github.com/kla/php-activerecord
require CORE_PATH.'vendor/plugins/php-activerecord/ActiveRecord.php';

# include the config
if ($database_config = Configure::read('database_config')) {
  if (file_exists($database_config)) {
    require $database_config;
  }
  else {
    Debugger::errorHandler(2, "The {$database_config} configuration not found.", __FILE__, __LINE__);
  }
}
