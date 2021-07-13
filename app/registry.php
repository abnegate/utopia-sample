<?php

use SampleAPI\Data\SimpleORM;
use Utopia\App;
use Utopia\Cache\Adapter\None;
use Utopia\Cache\Cache;
use Utopia\Database\Adapter\MariaDB;
use Utopia\Database\Database;
use Utopia\Registry\Registry;

$registry = new Registry();
$registry->set('orm', function () use ($registry) {
    $dbHost = App::getEnv('_APP_DB_HOST', 'localhost');
    $dbPort = App::getEnv('_APP_DB_PORT', '3306');
    $dbUser = App::getEnv('_APP_DB_USER', 'root');
    $dbPass = App::getEnv('_APP_DB_PASS', '');
    $dbScheme = App::getEnv('_APP_DB_SCHEMA', 'sample');
    $dsn = "mysql:host=" . $dbHost . ";port=" . $dbPort . ";charset=utf8mb4";

    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        PDO::ATTR_TIMEOUT => 3, // Seconds
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $cache = new Cache(new None());
    $database = new Database(new MariaDB($pdo), $cache);
    $database->setNamespace($dbScheme);
    $database->exists() || $database->create();

    return new SimpleORM($database, $dbScheme);
});