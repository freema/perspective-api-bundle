<?php

declare(strict_types=1);

use Freema\PerspectiveApiBundle\Dev\DevKernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/../vendor/autoload.php';

// Load .env file if it exists
if (class_exists(Dotenv::class) && file_exists(__DIR__.'/.env')) {
    (new Dotenv())->load(__DIR__.'/.env');
}

$kernel = new DevKernel('dev', true);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
