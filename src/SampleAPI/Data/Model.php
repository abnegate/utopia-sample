<?php

namespace SampleAPI\Data;

use JetBrains\PhpStorm\ArrayShape;
use SampleAPI\Strings;

/**
 * Represents a database entity.
 *
 * Class Model
 * @package SampleAPI\Data
 */
abstract class Model
{
    protected $id;
    protected string $table;

    public function __construct()
    {
        $this->table = Strings::classToTableName($this::class);
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
        $attr = $this->getAttributes();

        return [
            'id' => $this->getId(),
            'type' => $this->getTable(),
            'attributes' => \array_walk( $attr,
                function (string &$key, $value) {
                    $key = $value['value'];
                }
            ),
        ];
    }

    /**
     * Get the unique ID for this model.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the table name for this model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get an array of the property keys and values for this model.
     *
     * @return array
     */
    public abstract function getAttributes(): array;


    /**
     * Get an array of the property keys and values for this model.
     *
     * @return array
     */
    public function getAttributesValues(): array
    {
        $attr = $this->getAttributes();

        \array_walk( $attr,
            function (string &$key, $value) {
                $key = $value['value'];
            }
        );

        return $attr;
    }

    // TODO: Move this to a 'Serializer' interface

    /**
     * Get a JSONAPI representation of this model.
     *
     * @param $id
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
    public function getJSONAPIWithId($id): array
    {
        $attr = $this->getAttributes();

        return [
            'id' => $id,
            'type' => $this->getTable(),
            'attributes' => $this->getAttributesValues(),
        ];
    }
}