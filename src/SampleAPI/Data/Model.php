<?php

namespace SampleAPI\Data;

use ArrayObject;
use JetBrains\PhpStorm\ArrayShape;
use SampleAPI\Strings;

abstract class Model extends ArrayObject
{
    protected $id;
    protected string $table;

    public function __construct()
    {
        parent::__construct([], self::ARRAY_AS_PROPS);
        $this->table = Strings::classToTableName($this::class);
    }

    public function __get($n)
    {
        return $this[$n];
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
            'type' => $this->getTable(),
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
    public function getTable(): string
    {
        return $this->table;
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
            'type' => $this->getTable(),
            'attributes' => $this->getAttributes()
        ];
    }
}