<?php

namespace SampleAPI\Data;

use ArrayObject;
use JetBrains\PhpStorm\ArrayShape;

abstract class Model extends ArrayObject
{
    protected $id;
    protected string $table;
    protected string $jsonType;

    public function __construct()
    {
        parent::__construct([], self::ARRAY_AS_PROPS);
        $this->table = strtolower($this::class);
        $this->jsonType = $this::class;
    }

    public function __get($n)
    {
        return $this[$n];
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    #[ArrayShape([
        'id' => 'object',
        'type' => 'string',
        'attributes' => 'array',
        'relationships' => 'array',
        'meta' => 'array',
        'links' => 'array'
    ])]
    public function getJSONAPI(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getJsonType(),
            'attributes' => $this->getAttributes()
        ];
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getJsonType(): string
    {
        return $this->jsonType;
    }

    /**
     * Get an array of Model property keys and values.
     *
     * @return array
     */
    public abstract function getAttributes(): array;

    #[ArrayShape([
        'id' => 'object',
        'type' => 'string',
        'attributes' => 'array',
        'relationships' => 'array',
        'meta' => 'array',
        'links' => 'array'
    ])]
    public function getJSONAPIWithId($id): array
    {
        return [
            'id' => $id,
            'type' => $this->getJsonType(),
            'attributes' => $this->getAttributes()
        ];
    }
}