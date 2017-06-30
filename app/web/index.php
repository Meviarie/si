<?php
umask(0002); // This will let the permissions be 0775

// or

umask(0000); // This will let the permissions be 0777


ini_set('error_reporting',E_ALL);
ini_set('display_errors',true);

require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';

$app = require_once dirname(dirname(__FILE__)).'/src/app.php';

require_once dirname(dirname(__FILE__)).'/src/controllers.php';

require_once __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../config/prod.php';
require __DIR__.'/../src/controllers.php';
$app->run();
