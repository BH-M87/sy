<?php

$config = require 'db-project-config.php';

switch (YII_ENV) {
    case  "prod":
        return $config['prod'][YII_PROJECT];
        break;
    case  "release":
        return $config['test'][YII_PROJECT];
        break;
    case  "test":
        return $config['test'][YII_PROJECT];
        break;
    case "dev":
        return $config['test'][YII_PROJECT];
        break;
    default :
        return $config['test'][YII_PROJECT];
}