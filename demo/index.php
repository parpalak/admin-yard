<?php

use S2\AdminYard\DefaultAdminFactory;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

error_reporting(E_ALL);

require '../vendor/autoload.php';

Debug::enable();

$adminConfig = require 'admin_config.php';
$pdo         = new PDO('mysql:host=localhost;dbname=adminyard', 'root', '');
$adminPanel  = DefaultAdminFactory::createAdminPanel($adminConfig, $pdo, require '../translations/en.php', 'en');

$request  = Request::createFromGlobals();
$response = $adminPanel->handleRequest($request);
$response->send();
