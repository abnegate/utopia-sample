<?php

namespace SampleAPI\Data;

use ArrayObject;
use JetBrains\PhpStorm\ArrayShape;

abstract class Model extends ArrayObject
{
    protected object $id;
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
     * @return object
     */
    public function getId(): object
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
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

    /**
     * Get an array of JSONAPI Model property keys and values.
     *
     * @return array
     */
    #[ArrayShape([
        'id' => 'object',
        'type' => 'string',
        'attributes' => 'array',
        'relationships' => 'array',
        'meta' => 'array',
        'links' => 'array'
    ])]
    public abstract function getJSONAPI(): array;
}