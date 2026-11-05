<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Kernel\Application;

(new Application(dirname(__DIR__)))->run();
