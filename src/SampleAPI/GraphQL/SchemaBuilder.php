<?php

namespace SampleAPI\GraphQL;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use SampleAPI\Data\Model;
use SampleAPI\GraphQL\Types\JsonType;
use Utopia\CLI\Console;
use Utopia\Response;

class SchemaBuilder
{

    protected static ?JsonType $jsonParser = null;

    protected static ?array $typeMapping = null;

    /**
     * Function to initialise the typeMapping array with the base cases of the recursion
     *
     * @return   void
     */
    public static function init()
    {
        self::$typeMapping = [
            'boolean' => Type::boolean(),
            'string' => Type::string(),
            'integer' => Type::int(),
            'float' => Type::float(),
            'json' => self::json(),
        ];
    }

    /**
     * Function to create a singleton for $jsonParser
     *
     * @return ?JsonType
     */
    public static function json(): ?JsonType
    {
        if (is_null(self::$jsonParser)) {
            self::$jsonParser = new JsonType();
        }
        return self::$jsonParser;
    }

    /**
     * If the map already contains the type, end the recursion and return.
     * Iterate through all the rules in the response model. Each rule is of the form
     *        [
     *            [KEY 1] => [
     *                'type' => A string from Appwrite/Utopia/Response
     *                'description' => A description of the type
     *                'default' => A default value for this type
     *                'example' => An example of this type
     *                'require' => a boolean representing whether this field is required
     *                'array' => a boolean representing whether this field is an array
     *            ],
     *            [KEY 2] => [
     *            ],
     *            [KEY 3] => [
     *            ] .....
     *        ]
     *   If there are any field names containing characters other than a-z, A-Z, 0-9, _ ,
     *   we need to remove all those characters. Currently Appwrite's Response model has only the
     *   $ sign which is prohibited by the GraphQL spec. So we're only replacing that. We need to replace this with a regex
     *   based approach.
     *
     * @param string $model
     * @param Response $response
     * @return Type
     */
    static function getTypeMapping(string $model, Response $response): Type
    {
        if (isset(self::$typeMapping[$model])) {
            return self::$typeMapping[$model];
        }

        /** @var Model $instance */
        $instance = new $model();
        $attributes = $instance->getAttributes();
        $fields = [];
        $type = null;
        foreach ($attributes as $name => $attribute) {
            $escapedName = str_replace('$', '_', $name);

            if (isset(self::$typeMapping[$attribute['type']])) {
                $type = self::$typeMapping[$attribute['type']];
            } else {
                try {
                    $nestedModel = $attribute['type'];
                    $type = self::getTypeMapping($nestedModel, $response);
                } catch (\Exception) {
                    Console::error("Could Not find model for : {$attribute['type']}");
                }
            }
            if ($attribute['array']) {
                $type = Type::listOf($type);
            }

            $fields[$escapedName] = [
                'type' => $type,
                'description' => $attribute['description'],
                'resolve' => function ($object, $args, $context, $info) use ($name) {
                    return $object[$name];
                }
            ];
        }
        $objectType = [
            'name' => $model,
            'fields' => $fields
        ];

        return self::$typeMapping[$model] = new ObjectType($objectType);
    }

    /**
     * Function to map a Utopia\Validator to a valid GraphQL Type
     *
     * @param $validator
     * @param bool $required
     * @param $utopia
     * @param $injections
     * @return Type
     */
    protected static function getArgType(
        $validator,
        bool $required,
        $utopia,
        $injections
    ): Type
    {
        $validator = (\is_callable($validator))
            ? call_user_func_array($validator, $utopia->getResources($injections))
            : $validator;

        $type = match ((!empty($validator)) ? \get_class($validator) : '') {
            'Appwrite\Database\Validator\UID',
            'Utopia\Validator\Host',
            'Utopia\Validator\Length',
            'Appwrite\Auth\Validator\Password',
            'Utopia\Validator\URL',
            'Utopia\Validator\Email',
            'Appwrite\Storage\Validator\File',
            'Utopia\Validator\Text',
            'Utopia\Validator\WhiteList' => Type::string(),
            'Utopia\Validator\Range',
            'Utopia\Validator\Numeric' => Type::int(),
            'Utopia\Validator\Boolean' => Type::boolean(),
            'Utopia\Validator\ArrayList' => Type::listOf(self::json()),
            default => self::json(),
        };

        if ($required) {
            $type = Type::nonNull($type);
        }

        return $type;
    }

    /**
     * This function goes through all the REST endpoints in the API and builds a
     * GraphQL schema for all those routes whose response model is neither empty nor NONE
     *
     * @param $utopia
     * @param $response
     * @param $register
     * @return Schema
     */
    public static function buildModelSchema($utopia, $response, $register): Schema
    {
        Console::log("[INFO] Building GraphQL Schema...");
        $start = microtime(true);

        self::init();
        $queryFields = [];
        $mutationFields = [];

        foreach ($utopia->getRoutes() as $method => $routes) {
            foreach ($routes as $route) {

                $methodName = $route->getLabel('method', '');
                $responseModel = $route->getLabel('response.model', "");

                if ($responseModel !== "") {
                    // Create a GraphQL type for the current response model
                    $type = self::getTypeMapping($responseModel, $response);

                    // Get a description for this type
                    $description = $route->getDesc();

                    // Create the args required for this type
                    $args = [];
                    foreach ($route->getParams() as $key => $value) {
                        $args[$key] = [
                            'type' => self::getArgType(
                                $value['validator'],
                                !$value['optional'],
                                $utopia,
                                $value['injections']
                            ),
                            'description' => $value['description'],
                            'defaultValue' => $value['default']
                        ];
                    }

                    // Define a resolve function that defines how to fetch data for this type
                    $resolve = function ($type, $args, $context, $info) use (&$register, $route) {
                        $utopia = $register->get('__app');

                        $utopia
                            ->setRoute($route)
                            ->execute($route, $args);

                        $response = $register->get('__response');

                        $result = $response->getPayload();

                        $responseModel = $response->getCurrentModel();

                        if ($responseModel == 'error'
                            || $responseModel == 'errorDev') {
                            throw new \Exception($result['message'], $result['code']);
                        }
                        return $result;
                    };

                    $field = [
                        'type' => $type,
                        'description' => $description,
                        'args' => $args,
                        'resolve' => $resolve
                    ];

                    if ($method == 'GET') {
                        $queryFields[$methodName] = $field;
                    } else if ($method == 'POST'
                        || $method == 'PUT'
                        || $method == 'PATCH'
                        || $method == 'DELETE'
                    ) {
                        $mutationFields[$methodName] = $field;
                    }
                }
            }
        }

        ksort($queryFields);
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
