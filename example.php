<?php

require_once(__DIR__ . '/orbisius_dot_env.php');

$dot_env = Orbisius_Dot_Env::getInstance();

// make the class look for the file.
$data = $dot_env->read();

// or specify the .env file directly.
// $data = $dot_env->read('/path/to/.env');

// Updates env, $_ENV, $_SERVER if the value doesn't exist already
$dot_env->updateEnv($data);

// override any existing values
$dot_env->updateEnv($data, true);

// 
$dot_env->defineConsts($data);

// OR define the consts with this prefix
// 
// $dot_env->defineConsts($data, 'MY_APP_');
