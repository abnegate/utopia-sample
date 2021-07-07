<?php

use SampleAPI\Data\SimpleORM;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Utopia\App;
use Utopia\Cache\Adapter\None;
use Utopia\Cache\Cache;
use Utopia\Database\Adapter\MariaDB;
use Utopia\Database\Database;
use Utopia\Registry\Registry;

$registry = new Registry();

$registry->set('dbPool', function () {
    $dbHost = App::getEnv('_APP_DB_HOST', 'localhost');
    $dbPort = App::getEnv('_APP_DB_PORT', '3306');
    $dbUser = App::getEnv('_APP_DB_USER', 'root');
    $dbPass = App::getEnv('_APP_DB_PASS', '');
    $dbScheme = App::getEnv('_APP_DB_SCHEMA', 'test');

    return new PDOPool((new PDOConfig())
        ->withHost($dbHost)
        ->withPort($dbPort)
        ->withDbName($dbScheme)
        ->withUsername($dbUser)
        ->withPassword($dbPass)
    );
});

$registry->set('orm', function () use ($registry) {
    $dbScheme = App::getEnv('_APP_DB_SCHEMA', 'test');

    // To fix dependency cycle with dbPool
    $pdo = new PDO("mysql:host=localhost;port=3306;charset=utf8mb4", 'root', '', [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        PDO::ATTR_TIMEOUT => 3, // Seconds
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $database = new Database(
        new MariaDB($pdo),
        new Cache(new None())
    );
    $database->setNamespace($dbScheme);
    $database->exists() || $database->create();

    return new SimpleORM($database, $dbScheme);
});