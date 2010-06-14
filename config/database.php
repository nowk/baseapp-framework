<?php

# PHP ActiveRecord
ActiveRecord\Config::initialize(function($cfg)
  {
    $cfg->set_model_directory(APP_DIR.'models');
    $cfg->set_connections(array(
      'development' => 'mysql://username:password@localhost/table_name',
      'production'  => '...'
    ));

    # set the environment state
    # $cfg->set_default_connection('development');
    $cfg->set_default_connection(Configure::read('environment'));
  }
);

