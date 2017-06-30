<?php
umask(0002); // This will let the permissions be 0775

// or

umask(0000); // This will let the permissions be 0777
// ...

use Symfony\Component\Debug\Debug;


require_once __DIR__.'/../vendor/autoload.php';

Debug::enable();

$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../config/dev.php';
require __DIR__.'/../src/controllers.php';
$app->run();
