<?php

namespace SampleAPI\Data;

use Exception;
use MongoDB\Driver\Exception\ExecutionTimeoutException;
use Throwable;
use Utopia\CLI\Console;
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
        $tableName = self::toTableName($class);

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
        $tableName = self::toTableName($class);

        try {
            $db->getCollection($tableName);
        } catch (Throwable $ex) {
            Console::error($ex);

            foreach ($model->getAttributes() as $attr => $val) {
                try {
                    $db->createAttribute(
                        $tableName,
                        $attr,
                        is_numeric($val) ? Database::VAR_INTEGER : Database::VAR_STRING,
                        0,
                        true
                    );
                }catch (Throwable $th) {
                    Console::error($th);
                }
            }

            try {
                $db->createCollection($tableName);
            } catch (Throwable $ex) {
                Console::error($ex);
            }
        }
        return $db->createDocument(
            $tableName,
            new Document([
                $model->getAttributes(),
                'read' => ['*'],
                'write' => ['*'],
            ])
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
            self::toTableName($class),
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
            self::toTableName($class),
            $id
        );
    }

    private static function toTableName(string $class): string
    {
        $parts = explode('\\', $class);
        return strtolower(end($parts));
    }
}