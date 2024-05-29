<?php

use S2\AdminYard\DefaultAdminFactory;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

error_reporting(E_ALL);

putenv('APP_DB_TYPE=mysql');

require '../vendor/autoload.php';

Debug::enable();

$adminConfig = require 'admin_config.php';

$pdo = match (getenv('APP_DB_TYPE')) {
    'pgsql' => new PDO('pgsql:host=localhost;dbname=adminyard', 'postgres', '12345'),
    'sqlite' => (static function () {
        $pdo = new PDO('sqlite:db.sqlite', '', '');
        $pdo->exec('PRAGMA foreign_keys = ON;');
        return $pdo;
    })(),
    default => new PDO('mysql:host=localhost;dbname=adminyard', 'root', ''),
};

$adminPanel = DefaultAdminFactory::createAdminPanel($adminConfig, $pdo, require '../translations/en.php', 'en');

$request = Request::createFromGlobals();
$request->setSession(new Session());
$response = $adminPanel->handleRequest($request);
$response->send();
