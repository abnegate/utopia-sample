<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

use SampleAPI\Data\ORM\ORM;
use SampleAPI\GraphQL\SchemaBuilder;
use SampleAPI\GraphQL\ReferenceDataResolver;
use SampleAPI\GraphQL\ReferenceTypeResolver;
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

    /** @var App $graphql */
    /** @var ORM $orm */

    $graphql = new App('Pacific/Auckland');
    $typeResolver = new ReferenceTypeResolver();
    $dataResolver = new ReferenceDataResolver($typeResolver);
    $schemaBuilder = new SchemaBuilder($typeResolver, $dataResolver);
    $orm = $registry->get('orm');

    App::setResource('graphql', function () use ($graphql) {
        return $graphql;
    });

    App::setResource('orm', function () use ($orm) {
        return $orm;
    });

    App::setResource('registry', function () use ($registry) {
        return $registry;
    });

    App::setResource('schemaBuilder', function () use ($schemaBuilder) {
        return $schemaBuilder;
    });

    App::setResource('baseSchema', function () use ($graphql, $schemaBuilder, $registry) {
        $schema = $registry->get('schema');

        if (isset($schema)) {
            return $schema;
        }

        $schema = $schemaBuilder->buildModelSchema(
            $graphql->getRoutes(),
            $graphql->getResource('injections')
        );

        $dbSchema = $schemaBuilder->buildDatabaseSchema();

        $registry->set('schema', static fn() => $schema);

        return $schema;
    });

    try {
        $graphql->run($request, $response);
    } catch (Throwable $ex) {
        $swooleResponse->end('500: Server Error');
    }
});

$http->start();
