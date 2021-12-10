<?php

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use SampleAPI\GraphQL\SchemaBuilder;
use Utopia\App;

try {
    App::get('/v1/graphql')
        ->desc('GraphQL Endpoint.')
        ->groups(['graphql'])
        ->inject('request')
        ->inject('response')
        ->inject('schema')
        ->inject('utopia')
        ->inject('register')
        ->action(function ($request, $response, $schema, $utopia, $register) {
            /** @var Utopia\Swoole\Request $request */
            /** @var Utopia\Swoole\Response $response */
            /** @var GraphQL\Type\Schema $schema */
            /** @var Utopia\App $utopia */
            /** @var Utopia\Registry\Registry $register */

            $query = $request->getPayload('query', '');
            $variables = $request->getPayload('variables', null);
            $response->setContentType('application/json');

            //        $register->set('__app', function() use ($utopia) {
            //            return $utopia;
            //        });
            //
            //        $register->set('__response', function() use ($response) {
            //            return $response;
            //        });

            $isDevelopment = App::isDevelopment();
            $version = App::getEnv('_APP_VERSION', 'UNKNOWN');

            try {
                $debug = $isDevelopment
                    ? (DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE)
                    : DebugFlag::NONE;

                $rootValue = [];
                $result = GraphQL::executeQuery(
                    $schema,
                    $query,
                    $rootValue,
                    null,
                    $variables
                )->setErrorFormatter(SchemaBuilder::getErrorFormatter($isDevelopment, $version));
                $output = $result->toArray($debug);
            } catch (\Exception $error) {
                $output = [
                    'errors' => [
                        [
                            'message' => $error->getMessage() . 'xxx',
                            'code' => $error->getCode(),
                            'file' => $error->getFile(),
                            'line' => $error->getLine(),
                            'trace' => $error->getTrace(),
                        ]
                    ]
                ];
            }

            $response->json($output);
        }
        );
} catch (\Exception $e) {
    echo $e->getMessage();
}
