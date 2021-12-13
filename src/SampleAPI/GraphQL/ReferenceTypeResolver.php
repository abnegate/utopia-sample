<?php

namespace SampleAPI\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use SampleAPI\Data\Model;
use SampleAPI\GraphQL\Types\JsonType;
use Utopia\CLI\Console;

class ReferenceTypeResolver implements TypeResolver
{
    private static JsonType $jsonType;

    private array $typeMap;

    public function __construct()
    {
        $this->typeMap = [
            'boolean' => Type::boolean(),
            'string' => Type::string(),
            'integer' => Type::int(),
            'float' => Type::float(),
            'json' => self::json(),
        ];
    }

    private static function json() : JsonType
    {
        return self::$jsonType ?? (self::$jsonType = new JsonType());
    }

    public function resolveObjectType($model) : Type
    {
        if (isset($this->typeMap[$model])) {
            return $this->typeMap[$model];
        }

        /** @var Model $instance */
        $instance = new $model();
        $attributes = $instance->getAttributes();
        $fields = [];

        foreach ($attributes as $name => $attribute) {
            // Because $ is not allowed in GraphQL field names
            $escapedName = str_replace('$', '_', $name);

            if (!isset($this->typeMap[$attribute['type']])) {
                try {
                    $nestedModel = $attribute['type'];
                    $this->resolveObjectType($nestedModel);
                } catch (\Exception) {
                    Console::error("Could Not find model for : {$attribute['type']}");
                }
            }

            $type = $attribute['array']
                ? Type::listOf($this->typeMap[$attribute['type']])
                : $this->typeMap[$attribute['type']];

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

        return $this->typeMap[$model] = new ObjectType($objectType);
    }

    public function resolveInputType($validator, $required, $injections) : Type
    {
        $validator = (\is_callable($validator))
            ? call_user_func_array($validator, $injections)
            : $validator;

        $type = match ((!empty($validator)) ? \get_class($validator) : '') {
            'Appwrite\Auth\Validator\Password',
            'Appwrite\Database\Validator\UID',
            'Appwrite\Storage\Validator\File',
            'Utopia\Validator\Email',
            'Utopia\Validator\Host',
            'Utopia\Validator\Length',
            'Utopia\Validator\Text',
            'Utopia\Validator\URL',
            'Utopia\Validator\WhiteList' => Type::string(),
            'Utopia\Validator\Numeric' => Type::int(),
            'Utopia\Validator\Range',
            'Utopia\Validator\Boolean' => Type::boolean(),
            'Utopia\Validator\ArrayList' => Type::listOf(self::json()),
            default => self::json(),
        };

        if ($required) {
            $type = Type::nonNull($type);
        }

        return $type;
    }
}