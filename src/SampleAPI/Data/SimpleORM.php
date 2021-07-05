<?php

namespace SampleAPI\Data;

use Exception;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Authorization;
use Utopia\Database\Exception\Structure;
use Utopia\Database\Query;

class SimpleORM implements ORM
{
    use InsertAllParallel;

    public function __construct(
        protected string $schema
    )
    {
    }

    public function findById(
        Database $db,
        string $class,
        object $id,
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    ): array
    {
        return $this->find(
            $db,
            $class,
            [new Query('id', Query::TYPE_EQUAL, [$id])]
        );
    }

    public function find(
        Database $db,
        string $class,
        array $queries = [],
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    ): array
    {
        $tableName = $this->toTableName($class);

        return $db->find(
            $tableName,
            $queries,
            $count,
            $offset
        );
    }

    /**
     * @throws Authorization
     * @throws Structure
     * @throws Exception
     */
    public function insert(
        Database $db,
        string $class,
        Model $model): Document
    {
        $collection = $db->getCollection($class);
        if (null === $collection) {
            $db->createCollection($class);
        }

        return $db->createDocument(
            $this->toTableName($class),
            new Document($model->getAttributes())
        );
    }

    /**
     * @throws Exception
     */
    public function update(
        Database $db,
        string $class,
        Model $model
    ): Document
    {
        return $db->updateDocument(
            $this->toTableName($class),
            $model->getId(),
            new Document([
                $model->getAttributes()
            ])
        );
    }

    /**
     * @throws Authorization
     */
    public function delete(
        Database $db,
        string $class,
        object $id
    ): bool
    {
        return $db->deleteDocument(
            $this->toTableName($class),
            $id
        );
    }

    private static function toTableName(string $class): string
    {
        $parts = explode('\\', $class);
        return strtolower(end($parts));
    }
}