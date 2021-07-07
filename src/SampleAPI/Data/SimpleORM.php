<?php

namespace SampleAPI\Data;

use Exception;
use Throwable;
use Utopia\CLI\Console;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Authorization;

class SimpleORM implements ORM
{
    use InsertAllParallel;

    public function __construct(
        protected Database $db,
        protected string $schema
    )
    {
    }

    /**
     * @throws Exception
     */
    public function findById(
        string $class,
        $id,
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    )
    {
        $tableName = self::toTableName($class);

        try {
            $doc = $this->db->getDocument(
                $tableName,
                $id
            );
        } catch (Throwable $ex) {
            Console::error($ex);
        }

        $props = $doc->getAttributes();
        $props['id'] = $doc->getId();
        return new $class(...$props);
    }

    public function find(
        string $class,
        array $queries = [],
        int $count = 50,
        int $offset = 0,
        int $order = SortOrder::ASCENDING
    ): array
    {
        $tableName = self::toTableName($class);

        $docs = $this->db->find(
            $tableName,
            $queries,
            $count,
            $offset,
        );

        return array_map(function (Document $doc) use ($class) {
            $props = $doc->getAttributes();
            $props['id'] = $doc->getId();
            return new $class(...$props);
        }, $docs);
    }

    private static function toTableName(string $class): string
    {
        $parts = explode('\\', $class);
        return strtolower(end($parts));
    }

    /**
     * @throws Exception|Throwable
     */
    public function insert(Model $model)
    {
        $tableName = self::toTableName($model::class);

        try {
            $this->db->createCollection($tableName);

            // FIXME: Stop this from adding duplicates
            foreach ($model->getAttributes() as $attr => $val) {
                $type = is_numeric($val)
                    ? Database::VAR_INTEGER
                    : Database::VAR_STRING;

                $this->db->createAttribute(
                    $tableName,
                    $attr,
                    $type,
                    5000, // FIXME: This should come from the model
                    true // FIXME: This should come from the model
                );
            }
        } catch (Throwable $ex) {
            Console::error($ex);
        }

        try {
            $props = array_merge([
                '$read' => ['role:all'],
                '$write' => ['role:all'],
                '$collection' => $tableName,
            ], $model->getAttributes());

            $doc = $this->db->createDocument(
                $tableName,
                new Document($props)
            );

            $props = $doc->getAttributes();
            $props['id'] = $doc->getId();

            $clazz = $model::class;
            return new $clazz(...$props);

        } catch (Throwable $ex) {
            Console::error($ex);
            throw $ex;
        }
    }

    /**
     * @throws Exception
     */
    public function update(
        Model $model
    ): Document
    {
        $doc = $this->db->updateDocument(
            self::toTableName($model::class),
            $model->getId(),
            new Document($model->getAttributes())
        );
        $props = $doc->getAttributes();
        $props['id'] = $doc->getId();

        $clazz = $model::class;
        return new $clazz(...$props);
    }

    /**
     * @throws Authorization
     */
    public function delete(
        string $class,
        $id
    ): bool
    {
        return $this->db->deleteDocument(
            self::toTableName($class),
            $id
        );
    }
}