<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

use SampleAPI\Data\ORM\SimpleORM;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server;
use Swoole\Process;
use Utopia\App;
use Utopia\CLI\Console;

include __DIR__ . '/registry.php';
include __DIR__ . '/database.php';
include __DIR__ . '/controllers/api/notes.php';

// For Swoole Coroutines to hook PDO ops
Co::set(['hook_flags' => SWOOLE_HOOK_TCP]);

App::setMode(App::getEnv('_APP_ENV', App::MODE_TYPE_DEVELOPMENT));

App::error(function (Throwable $error) {
    Console::error(json_encode($error));
});

$http = new Server('0.0.0.0', App::getEnv('_APP_PORT', 80));

$http->on('start', function (Server $http) {
    Console::log('Stop with Ctrl+C');
    Process::signal(2, function () use ($http) {
        $http->shutdown();
    });
});

$http->on('request', function (
    SwooleRequest $swooleRequest,
    SwooleResponse $swooleResponse
) use ($registry) {
    $request = new Utopia\Swoole\Request($swooleRequest);
    $response = new Utopia\Swoole\Response($swooleResponse);

    /** @var App $rest */
    /** @var SimpleORM $orm */

    $rest = new App('Pacific/Auckland');

    $orm = $registry->get('orm');

    App::setResource('rest', function () use ($rest) {
        return $rest;
    });

    App::setResource('orm', function () use ($orm) {
        return $orm;
    });

    App::setResource('registry', function () use ($registry) {
        return $registry;
    });

    try {
        $rest->run($request, $response);
    } catch (Throwable $ex) {
        $swooleResponse->end('500: Server Error');
    }
});

$http->start();
