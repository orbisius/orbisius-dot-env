<?php

$dot_env = Orbisius_Dot_Env::getInstance();

$data = $dot_env->read();
$dot_env->updateEnv($data);
$dot_env->defineConsts($data);
