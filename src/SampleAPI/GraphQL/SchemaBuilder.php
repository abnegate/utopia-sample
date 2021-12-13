<?php

namespace SampleAPI\GraphQL;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use Utopia\CLI\Console;
use Utopia\Database\Database;
use Utopia\Registry\Registry;
use Utopia\Route;

class SchemaBuilder
{
    public function __construct(
        protected TypeResolver $typeResolver,
        protected DataResolver $dataResolver
    )
    {
    }

    /**
     * This function goes through all the REST endpoints in the API and builds a
     * GraphQL schema for all those routes whose response model is neither empty nor NONE
     *
     * @param array $routes
     * @param array $injections
     * @return Schema
     */
    public function buildModelSchema(
        array $routes, 
        array $injections
    ): Schema
    {
        Console::log("[INFO] Building GraphQL Schema...");
        $start = microtime(true);

        $queryFields = [];
        $mutationFields = [];

        foreach ($routes as $method => $routeCollection) {
            foreach ($routeCollection as $route) {
                $this->buildRouteFields(
                    $route,
                    $method,
                    $queryFields,
                    $mutationFields,
                    $injections
                );
            }
        }

        ksort( $queryFields);
        ksort($mutationFields);

        $queryType = new ObjectType([
            'name' => 'Query',
            'description' => 'The root of all your queries',
            'fields' => $queryFields
        ]);
        $mutationType = new ObjectType([
            'name' => 'Mutation',
            'description' => 'The root of all your mutations',
            'fields' => $mutationFields
        ]);
        $schema = new Schema([
            'query' => $queryType,
            'mutation' => $mutationType
        ]);

        $time_elapsed_secs = microtime(true) - $start;
        Console::log("[INFO] Time Taken To Build Schema : ${time_elapsed_secs}s");

        return $schema;
    }

    private function buildRouteFields(
        Route $route,
        string $method,
        array &$queryFields,
        array &$mutationFields,
        array $injections
    ) : void
    {
        $methodName = $route->getLabel('method', '');
        $responseModel = $route->getLabel('response.model', "");

        if ($responseModel === "") {
            return;
        }

        $type = $this->typeResolver->resolveObjectType($responseModel);

        // Get a description for this type
        $description = $route->getDesc();

        // Create the args required for this type
        $args = [];
        foreach ($route->getParams() as $key => $value) {
            $args[$key] = $this->buildRouteFieldArguments($value, $injections);
        }

        // Define a resolve function that defines how to fetch data for this type
        $resolve = function ($type, $args, $context, $info) {
            $this->dataResolver->resolveData(
                $type,
                $args,
                $context,
                $info
            );
        };

        // Create the field
        $field = [
            'type' => $type,
            'description' => $description,
            'args' => $args,
            'resolve' => $resolve
        ];

        if ($method == 'GET') {
            $queryFields[$methodName] = $field;
            return;
        }

        if ($method == 'POST'
            || $method == 'PUT'
            || $method == 'PATCH'
            || $method == 'DELETE'
        ) {
            $mutationFields[$methodName] = $field;
        }
    }
    private function buildRouteFieldArguments(
        mixed $value, 
        array $injections
    ) : array
    {
        return [
            'type' => $this->typeResolver->resolveInputType(
                $value['validator'],
                !$value['optional'],
                $injections
            ),
            'description' => $value['description'],
            'defaultValue' => $value['default']
        ];
    }

    public function buildDatabaseSchema(Registry $registry)
    {
        /** @var Database $db */

        $db = $registry->get('database');

        $limit = 25;
        $offset = 0;

        $queryFields = [];
        $mutationFields = [];

        while (true) {
            $page = $db->listCollections($limit, $offset);

            $pageSize = count($page);
            $offset += $pageSize;

            foreach($page as $collection) {
                $name = $collection['name'];
                $attributes = $collection['attributes'];

                foreach($attributes as $attribute) {
                    $type = $attribute['type'];
                    $name = $attribute['name'];
                    $description = $attribute['description'];
                    $default = $attribute['default'];
                    $optional = $attribute['optional'];

                    $field = [
                        'type' => $type,
                        'description' => $description,
                        'defaultValue' => $default
                    ];

                    $queryFields[$name] = $field;
                }
            }

            if ($pageSize < $limit) {
                break;
            }
        }
    }

    /**
     * Function to create an appropriate GraphQL Error Formatter
     * Based on whether we're on a development build or production
     * build of Appwrite.
     *
     * @param bool $isDevelopment
     * @param string $version
     * @return callable
     */
    public static function getErrorFormatter(bool $isDevelopment, string $version): callable
    {
        return function (Error $error) use ($isDevelopment, $version) {
            $formattedError = FormattedError::createFromException($error);
            /**  Previous error represents the actual error thrown by Appwrite server */
            $previousError = $error->getPrevious() ?? $error;
            $formattedError['code'] = $previousError->getCode();
            $formattedError['version'] = $version;
            if ($isDevelopment) {
                $formattedError['file'] = $previousError->getFile();
                $formattedError['line'] = $previousError->getLine();
            }
            return $formattedError;
        };
    }
}
