<?php

use SampleAPI\Data\SimpleORM;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Utopia\App;
use Utopia\Cache\Adapter\Filesystem;
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

$registry->set('db', function () use ($registry) {
    $dbScheme = App::getEnv('_APP_DB_SCHEMA', 'test');

    /** @var PDO $pdo */
    $pdo = $registry->get('dbPool')->get();
    $cache = new Cache(new Filesystem('.'));
    $database = new Database(new MariaDB($pdo), $cache);
    $database->setNamespace($dbScheme);
    $database->exists() || $database->create();

    return $database;
});

$registry->set('orm', function () {
    $schema = App::getEnv('_APP_DB_SCHEMA', 'test');

    return new SimpleORM($schema);
});